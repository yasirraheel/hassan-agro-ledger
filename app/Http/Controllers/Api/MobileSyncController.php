<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class MobileSyncController extends Controller
{
    // Only read-only indexes. Never prefetch sign-out, refresh-token or update-expire-date.
    public const RESOURCES = [
        'business', 'profile', 'products', 'parties', 'units', 'brands', 'categories', 'tax-transactions',
        'product-models', 'racks', 'shelfs', 'variations', 'vats', 'payment-types',
        'banks', 'bank-transactions', 'cashes', 'cheques', 'purchase', 'sales',
        'sales-return', 'purchases-return', 'dues', 'expenses', 'expense-categories',
        'incomes', 'income-categories', 'transactions', 'transfers', 'advance-collections',
        'business-categories', 'currency-settings', 'currencies', 'product-settings',
        'invoice-settings', 'users', 'branches', 'warehouses', 'employees', 'departments',
        'designations', 'shifts', 'holidays', 'attendances', 'leave-types', 'leaves',
        'payrolls', 'plans', 'subscribes', 'countries', 'states',
        'banners', 'lang', 'term-conditions', 'privacy-policy',
    ];

    public static function scope($user): string
    {
        return implode(':', [$user->business_id, $user->id, $user->active_branch_id ?? $user->branch_id ?? 'all']);
    }

    public function status(Request $request)
    {
        $user = $request->user();
        abort_unless($user->business_id, 403, 'Complete business setup before downloading offline data.');
        $paths = [];
        foreach (Route::getRoutes() as $route) {
            if (in_array('GET', $route->methods(), true)) {
                $action = explode('@', $route->getActionName());
                if (count($action) === 2 && class_exists($action[0]) && method_exists($action[0], $action[1])) {
                    $paths[$route->uri()] = true;
                }
            }
        }
        $resources = array_values(array_filter(self::RESOURCES, fn ($path) => isset($paths['api/v1/'.$path])));

        return response()->json([
            'protocol' => 1,
            'idempotency' => Schema::hasTable('mobile_sync_operations'),
            'scope' => self::scope($user),
            'business_id' => (int) $user->business_id,
            'user_id' => (int) $user->id,
            'branch_id' => $user->active_branch_id ?? $user->branch_id,
            'server_time' => now()->toIso8601String(),
            'resources' => $resources,
            'modules' => ['SocialLoginAddon' => moduleCheck('SocialLoginAddon'), 'ThermalPrinterAddon' => moduleCheck('ThermalPrinterAddon')],
        ])->header('Cache-Control', 'no-store');
    }

    public function taxes(Request $request)
    {
        return response()->json(['data' => \App\Models\VatTransaction::with('vat:id,name,rate')
            ->where('business_id', $request->user()->business_id)->get()]);
    }

    public function review(Request $request, string $resource, int $id)
    {
        $table = \App\Http\Middleware\MobileSyncOperation::TABLES[$resource] ?? null;
        abort_unless($table && Schema::hasTable($table), 404);
        $record = \Illuminate\Support\Facades\DB::table($table)->where('business_id', $request->user()->business_id)->where('id', $id)->first();
        abort_unless($record, 404);
        $version = (new \App\Http\Middleware\MobileSyncOperation)->recordVersion($record);
        $data = (array) $record;
        foreach (['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'] as $secret) unset($data[$secret]);
        return response()->json(['data' => $data, 'version' => $version])->header('Cache-Control', 'no-store');
    }
}
