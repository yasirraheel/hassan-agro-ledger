# Mobile synchronization

Deploy this backend before distributing the updated Flutter app. Run only the additive migration:

```
php artisan migrate --path=database/migrations/2026_10_17_000001_create_mobile_sync_operations_table.php --force
php artisan optimize:clear
```

`GET /api/v1/sync/status` advertises protocol 1, installed read-only resources, business/user/branch scope, and whether the receipt table exists. Unauthenticated requests return 401. The app must not enable its outbox until idempotency is available.

Queued mutations send an immutable UUID in `Idempotency-Key`, the advertised scope in `X-Offline-Scope`, and the original record version when editing. The middleware commits the receipt and the controller mutation in the same database transaction. A lost response can be retried without creating another invoice, payment or stock movement. Reusing a UUID with different request data is rejected. Keep receipts for as long as an offline device may reconnect.

Record versions hash database fields and detect edits within the same timestamp second. A conflict keeps the phone's work and stops dependent uploads. The review endpoint supplies the current record without password fields; the user explicitly chooses whether to apply the saved edit to that version. Server validation runs again.

Successful product and trade responses include child IDs; newly generated payment transactions are also returned so later offline operations can refer to uploaded stock batches, invoice lines and cheques. Spreadsheet row imports use the existing importer inside the receipt transaction.

The download manifest includes installed controllers only. HRM, warehouse and branch add-ons must be installed and separately implemented/tested before advertising their offline mutation support. Authentication, password recovery, OTP, email/SMS delivery, account deletion and live external services require connectivity. A bank payment in this application records a received payment; no gateway transaction is performed.

Local verification:

```
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: CACHE_DRIVER=array php vendor/bin/phpunit tests/Feature/MobileSyncOperationTest.php
```

The test class also enforces its in-memory database configuration before bootstrapping; it never uses the production database.
