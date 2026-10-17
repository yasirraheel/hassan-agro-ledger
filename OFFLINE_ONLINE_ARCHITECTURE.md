# Hassan Agro mobile: online and offline architecture

This document is the handoff for future developers and AI agents working on the mobile app and its Laravel API.

## Current behavior

- The app keeps an account-scoped local SQLite database. Cached server snapshots are combined with an ordered local outbox to build the screen projection.
- Every supported create, edit, and delete is written locally first. A UUID idempotency key, operation payload, scope, and base version are stored in the durable outbox.
- When the device is online, the same operation is uploaded immediately through the existing refresh/sync path. When it is offline, the local record remains usable and is marked pending; connectivity and foreground retries upload it later.
- Accepted operations are retained until a refreshed server snapshot is committed. This prevents a successful local change from disappearing while a download is in progress.
- Server receipts are idempotent. Replaying the same UUID returns the original result instead of creating a duplicate sale, purchase, payment, or stock movement.
- Optimistic versions detect edits made on another device. Conflicts remain visible for review and can be explicitly rebased; the app never silently overwrites server data.
- Login credentials are kept in secure storage. Tokens, business/user/branch scope, snapshots, outbox records, and media cache are separated by account scope.
- The top status banner was removed. The existing refresh icon is the single manual sync control; tap it to synchronize and long-press it to open sync details.
- The splash screen reports connection/download progress. Cached data can open immediately, while a first authenticated download continues and can resume.
- Date filters are persisted per account and screen, including rolling ranges and custom dates, so reopening a report does not reset it to Today.

## Data and sync boundaries

The mobile manifest and backend plan gate define which modules are installed. Business, product, party, sale, purchase, payment, stock, banking, tax, reports, settings, and supported imports have offline projections. HRM/warehouse/branch add-ons are only offline-capable when their server module is installed and exposed by the manifest.

Bank payment records are ordinary payment-method records. The app does not process a bank gateway, OTP, email, or live verification offline. Such server-only actions remain pending until connectivity returns. Offline stock is necessarily based on the last synchronized snapshot plus this device's queued operations; another device's newer sale appears after synchronization.

## Backend protocol

The Laravel API exposes a plan-aware sync manifest, private indexed resources, UUID idempotency receipts, optimistic version checks, conflict review, and atomic mutation transactions. `mobile_sync_operations` is additive and records accepted, rejected, or conflicting requests without changing existing business tables. The server filters every mutation by the authenticated business and branch scope.

## Safe change checklist

1. Add or update the local projection and outbox operation before wiring a screen to it.
2. Keep identifiers and server aliases remappable; never remap money or quantities by numeric heuristics.
3. Verify the operation is idempotent and has an optimistic base version.
4. Update the manifest/resource reader and the server controller together.
5. Run the Flutter offline tests and the Laravel `MobileSyncOperationTest` before committing.
6. Do not add passwords, tokens, production snapshots, or generated APKs to Git.

See `pos-pro-admin/docs/mobile-offline-sync.md` for the backend protocol details.
