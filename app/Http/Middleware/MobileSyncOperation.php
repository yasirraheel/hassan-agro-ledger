<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Api\MobileSyncController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/** A receipt and the business mutation commit in the SAME database transaction. */
class MobileSyncOperation
{
    public const TABLES = [
        'sales' => 'sales', 'purchase' => 'purchases', 'products' => 'products', 'stocks' => 'stocks',
        'parties' => 'parties', 'units' => 'units', 'brands' => 'brands',
        'categories' => 'categories', 'product-models' => 'product_models',
        'racks' => 'racks', 'shelfs' => 'shelves', 'banks' => 'payment_types',
        'expenses' => 'expenses', 'incomes' => 'incomes',
        'expense-categories' => 'expense_categories', 'income-categories' => 'income_categories',
        'cashes' => 'transactions', 'bank-transactions' => 'transactions',
        'vats' => 'vats', 'payment-types' => 'payment_types', 'users' => 'users', 'transfers' => 'transfers',
    ];
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('Idempotency-Key');
        if (!$key || $request->isMethodSafe()) {
            $response = $this->annotateVersions($request, $next($request));
            if ($request->isMethod('GET') && $response->getStatusCode() === 200 && !$request->is('api/v1/sync/*')) {
                $etag = '"'.hash('sha256', $response->getContent()).'"';
                $response->headers->set('ETag', $etag);
                $response->headers->set('Cache-Control', 'private, no-cache');
                if ($request->header('If-None-Match') === $etag) {
                    $response->setStatusCode(304);
                    $response->setContent('');
                }
            }
            return $response;
        }
        if (!Str::isUuid($key)) {
            return response()->json(['message' => 'Invalid operation ID.'], 422);
        }
        $user = $request->user();
        $scope = MobileSyncController::scope($user);
        if ($request->header('X-Offline-Scope') !== $scope) {
            return response()->json(['message' => 'Business, user or branch changed. Sign in to the original account to sync.', 'sync_conflict' => true], 409);
        }
        if (!Schema::hasTable('mobile_sync_operations')) {
            return response()->json(['message' => 'Offline synchronization migration has not been installed.'], 503);
        }

        $input = $request->except(array_keys($request->allFiles()));
        $canonical = function ($value) use (&$canonical) {
            if (is_array($value)) {
                if (!array_is_list($value)) ksort($value);
                return array_map($canonical, $value);
            }
            return $value;
        };
        $files = [];
        foreach ($request->allFiles() as $field => $file) {
            if (is_array($file)) {
                return response()->json(['message' => 'Nested attachments are not supported by offline sync.'], 422);
            }
            $files[$field] = hash_file('sha256', $file->getRealPath());
        }
        ksort($files);
        $fingerprint = hash('sha256', json_encode([$request->method(), $request->path(), $canonical($input), $files], JSON_UNESCAPED_UNICODE));

        // A per-user row lock serializes retries, including two simultaneous copies of an operation.
        $level = DB::transactionLevel();
        DB::beginTransaction();
        try {
            DB::table('businesses')->where('id', $user->business_id)->lockForUpdate()->first();
            DB::table('users')->where('id', $user->id)->lockForUpdate()->first();
            $receipt = DB::table('mobile_sync_operations')->where('user_id', $user->id)->where('operation_id', $key)->first();
            if ($receipt) {
                DB::rollBack();
                if ($receipt->fingerprint !== $fingerprint || $receipt->scope !== $scope) {
                    return response()->json(['message' => 'Operation ID already belongs to different data.', 'sync_conflict' => true], 409);
                }
                return response($receipt->response_body, $receipt->status_code)
                    ->header('Content-Type', 'application/json')->header('X-Sync-Replayed', 'true');
            }

            $baseUpdatedAt = $request->header('X-Offline-Base-Updated-At');
            $baseVersion = $request->header('X-Offline-Base-Version');
            $parts = explode('/', trim($request->path(), '/'));
            $resource = $parts[2] ?? null;
            $recordId = $parts[3] ?? null;
            $table = self::TABLES[$resource] ?? null;
            if (($baseVersion || $baseUpdatedAt) && $table && ctype_digit((string) $recordId)) {
                $record = DB::table($table)->where('id', $recordId)
                    ->where('business_id', $user->business_id)->lockForUpdate()->first();
                $changed = !$record || ($baseVersion
                    ? !hash_equals($this->version($record), $baseVersion)
                    : \Carbon\Carbon::parse($record->updated_at)->getTimestamp() !== \Carbon\Carbon::parse($baseUpdatedAt)->getTimestamp());
                if ($changed) {
                    DB::rollBack();
                    return response()->json(['message' => 'This record changed on the server. Review it before applying the offline edit.', 'sync_conflict' => true, 'current_version' => $record ? $this->version($record) : null], 409);
                }
            }

            // Existing controllers still enforce their validation and permission rules.
            $transactionCursor = Schema::hasTable('transactions') ? (int) DB::table('transactions')->max('id') : null;
            $response = $next($request);
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                if ($transactionCursor !== null) {
                    $body = json_decode($response->getContent(), true);
                    if (is_array($body) && isset($body['data']) && is_array($body['data'])) {
                        $body['data']['_sync_transactions'] = \App\Models\Transaction::with('paymentType:id,name,meta')
                            ->where('business_id', $user->business_id)->where('user_id', $user->id)
                            ->where('id', '>', $transactionCursor)->orderBy('id')->get()->toArray();
                        $response->setContent(json_encode($body, JSON_UNESCAPED_UNICODE));
                    }
                }
                $response = $this->annotateVersions($request, $response);
                // Some legacy controllers leave nested transactions open on early returns.
                while (DB::transactionLevel() > $level + 1) DB::commit();
                if (DB::transactionLevel() !== $level + 1) {
                    throw new \RuntimeException('Controller closed the synchronization transaction.');
                }
                DB::table('mobile_sync_operations')->insert([
                    'user_id' => $user->id, 'business_id' => $user->business_id,
                    'scope' => $scope, 'operation_id' => $key, 'fingerprint' => $fingerprint,
                    'status_code' => $response->getStatusCode(), 'response_body' => $response->getContent(),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                DB::commit();
            } else {
                while (DB::transactionLevel() > $level) DB::rollBack();
            }
            return $response;
        } catch (\Throwable $e) {
            while (DB::transactionLevel() > $level) DB::rollBack();
            throw $e;
        }
    }

    public function recordVersion(object $record): string
    {
        return $this->version($record);
    }

    private function version(object $record): string
    {
        $values = (array) $record;
        ksort($values);
        return hash('sha256', json_encode($values, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION));
    }

    private function annotateVersions(Request $request, Response $response): Response
    {
        if ($response->getStatusCode() !== 200 && $response->getStatusCode() !== 201) return $response;
        $resource = explode('/', trim($request->path(), '/'))[2] ?? '';
        $table = self::TABLES[$resource] ?? null;
        if (!$table) return $response;
        $body = json_decode($response->getContent(), true);
        if (!is_array($body) || !isset($body['data']) || !is_array($body['data'])) return $response;
        $payload = &$body['data'];
        if (isset($payload['data']) && is_array($payload['data'])) $payload = &$payload['data'];
        $single = isset($payload['id']);
        $rows = $single ? [$payload] : $payload;
        $ids = array_filter(array_map(fn ($row) => is_array($row) ? ($row['id'] ?? null) : null, $rows));
        if (!$ids) return $response;
        $records = DB::table($table)->where('business_id', $request->user()->business_id)->whereIn('id', $ids)->get()->keyBy('id');
        foreach ($rows as &$row) {
            if (isset($row['id'], $records[$row['id']])) $row['_sync_version'] = $this->version($records[$row['id']]);
        }
        unset($row);
        $payload = $single ? $rows[0] : $rows;
        $response->setContent(json_encode($body, JSON_UNESCAPED_UNICODE));
        $response->headers->remove('Content-Length');
        return $response;
    }
}
