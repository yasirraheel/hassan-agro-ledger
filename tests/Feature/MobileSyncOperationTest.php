<?php

namespace Tests\Feature;

use App\Http\Middleware\MobileSyncOperation;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileSyncOperationTest extends TestCase
{
    protected function setUp(): void
    {
        // Never run this database fixture against the configured business database.
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        $_ENV['DB_CONNECTION'] = $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = $_SERVER['DB_DATABASE'] = ':memory:';
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('businesses', fn (Blueprint $table) => $table->id());
        Schema::create('users', fn (Blueprint $table) => $table->id());
        Schema::create('sync_test_ledger', function (Blueprint $table) {
            $table->id();
            $table->integer('amount');
        });
        Schema::create('parties', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('business_id'); $table->timestamps();
            $table->string('name')->nullable();
        });
        Schema::create('sales', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('business_id'); $table->timestamps();
        });
        $migration = require database_path('migrations/2026_10_17_000001_create_mobile_sync_operations_table.php');
        $migration->up();
        DB::table('businesses')->insert(['id' => 1]);
        DB::table('users')->insert(['id' => 1]);
    }

    private function request(string $key = '142f6751-93bb-4a5d-a45f-44c346834987', int $amount = 100): Request
    {
        $request = Request::create('/api/v1/sales', 'POST', ['amount' => $amount]);
        $request->headers->set('Idempotency-Key', $key);
        $request->headers->set('X-Offline-Scope', '1:1:all');
        $user = new User();
        $user->forceFill(['id' => 1, 'business_id' => 1]);
        $request->setUserResolver(fn () => $user);
        return $request;
    }

    public function test_retry_after_lost_response_does_not_duplicate_a_bank_or_sale_record(): void
    {
        $next = function () {
            DB::table('sync_test_ledger')->insert(['amount' => 100]);
            return response()->json(['data' => ['id' => 42]], 201);
        };
        $middleware = new MobileSyncOperation();
        $first = $middleware->handle($this->request(), $next);
        $retry = $middleware->handle($this->request(), $next);
        $this->assertSame(201, $retry->getStatusCode());
        $this->assertSame($first->getContent(), $retry->getContent());
        $this->assertSame('true', $retry->headers->get('X-Sync-Replayed'));
        $this->assertSame(1, DB::table('sync_test_ledger')->count());
        $this->assertSame(1, DB::table('mobile_sync_operations')->count());
    }

    public function test_reusing_an_operation_key_with_different_data_is_a_conflict(): void
    {
        $middleware = new MobileSyncOperation();
        $middleware->handle($this->request(), fn () => response()->json(['data' => ['id' => 1]]));
        $response = $middleware->handle($this->request(amount: 200), fn () => $this->fail('Must not run the controller twice.'));
        $this->assertSame(409, $response->getStatusCode());
    }

    public function test_error_response_rolls_back_legacy_early_return_and_has_no_receipt(): void
    {
        $response = (new MobileSyncOperation())->handle($this->request(), function () {
            DB::beginTransaction();
            DB::table('sync_test_ledger')->insert(['amount' => 100]);
            return response()->json(['message' => 'Insufficient stock'], 400);
        });
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(0, DB::transactionLevel());
        $this->assertSame(0, DB::table('sync_test_ledger')->count());
        $this->assertSame(0, DB::table('mobile_sync_operations')->count());
    }

    public function test_business_or_branch_mismatch_cannot_replay_work(): void
    {
        $request = $this->request();
        $request->headers->set('X-Offline-Scope', '2:1:all');
        $response = (new MobileSyncOperation())->handle($request, fn () => $this->fail('Wrong business must not reach the controller.'));
        $this->assertSame(409, $response->getStatusCode());
    }

    public function test_server_edit_conflict_is_detected_before_mutating(): void
    {
        DB::table('parties')->insert(['id' => 4, 'business_id' => 1, 'created_at' => '2026-10-17 10:00:00', 'updated_at' => '2026-10-17 12:00:00']);
        $request = $this->request();
        $request->server->set('REQUEST_URI', '/api/v1/parties/4');
        $request = Request::create('/api/v1/parties/4', 'PUT', ['name' => 'Changed']);
        $original = $this->request();
        $request->headers->replace($original->headers->all());
        $request->setUserResolver($original->getUserResolver());
        $request->headers->set('X-Offline-Base-Updated-At', '2026-10-17 10:00:00');
        $response = (new MobileSyncOperation())->handle($request, fn () => $this->fail('Stale edit must not reach the controller.'));
        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame(0, DB::table('mobile_sync_operations')->count());
    }

    public function test_row_hash_catches_a_conflict_even_when_timestamp_did_not_change(): void
    {
        DB::table('parties')->insert(['id' => 4, 'business_id' => 1, 'name' => 'Before', 'created_at' => '2026-10-17 10:00:00', 'updated_at' => '2026-10-17 10:00:00']);
        $original = $this->request();
        $read = Request::create('/api/v1/parties', 'GET');
        $read->setUserResolver($original->getUserResolver());
        $middleware = new MobileSyncOperation();
        $response = $middleware->handle($read, fn () => response()->json(['data' => [['id' => 4, 'name' => 'Before']]]));
        $version = json_decode($response->getContent(), true)['data'][0]['_sync_version'];
        $this->assertNotEmpty($version);
        DB::table('parties')->where('id', 4)->update(['name' => 'Concurrent edit']);
        $edit = Request::create('/api/v1/parties/4', 'PUT', ['name' => 'Offline edit']);
        $edit->headers->replace($original->headers->all());
        $edit->setUserResolver($original->getUserResolver());
        $edit->headers->set('X-Offline-Base-Version', $version);
        $result = $middleware->handle($edit, fn () => $this->fail('A same-second conflict must not overwrite server data.'));
        $this->assertSame(409, $result->getStatusCode());
    }

    public function test_unchanged_downloads_return_304_with_a_private_etag(): void
    {
        $original = $this->request();
        $read = Request::create('/api/v1/parties', 'GET');
        $read->setUserResolver($original->getUserResolver());
        $middleware = new MobileSyncOperation();
        $next = fn () => response()->json(['data' => []]);
        $first = $middleware->handle($read, $next);
        $read->headers->set('If-None-Match', $first->headers->get('ETag'));
        $second = $middleware->handle($read, $next);
        $this->assertSame(304, $second->getStatusCode());
        $this->assertSame('', $second->getContent());
        $this->assertStringContainsString('private', $second->headers->get('Cache-Control'));
    }
}
