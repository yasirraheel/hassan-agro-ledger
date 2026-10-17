import 'dart:convert';

import 'package:sqflite/sqflite.dart';

/// Server snapshots and the durable outbox share a single SQLite transaction boundary.
/// A scope identifies a server, business, user and branch, never just a device.
class OfflineStore {
  final Database db;
  OfflineStore(this.db);

  static Future<OfflineStore> open() async {
    final root = await getDatabasesPath();
    return OfflineStore(
      await openDatabase(
        '$root/hassan_offline_v1.db',
        version: 1,
        onConfigure: (db) async {
          await db.execute('PRAGMA foreign_keys = ON');
          await db.rawQuery('PRAGMA journal_mode = WAL');
          await db.execute('PRAGMA synchronous = FULL');
        },
        onCreate: createSchema,
      ),
    );
  }

  static Future<void> createSchema(Database db, int version) async {
    await db.execute(
      'CREATE TABLE metadata (scope TEXT NOT NULL, key TEXT NOT NULL, value TEXT NOT NULL, PRIMARY KEY(scope,key))',
    );
    await db.execute(
      'CREATE TABLE snapshots (scope TEXT NOT NULL, path TEXT NOT NULL, body TEXT NOT NULL, PRIMARY KEY(scope,path))',
    );
    await db.execute('''CREATE TABLE outbox (
      seq INTEGER PRIMARY KEY AUTOINCREMENT, scope TEXT NOT NULL,
      operation_id TEXT NOT NULL UNIQUE, local_id INTEGER NOT NULL,
      path TEXT NOT NULL, method TEXT NOT NULL, fields TEXT NOT NULL,
      files TEXT NOT NULL, state TEXT NOT NULL DEFAULT 'pending',
      error TEXT, response TEXT, created_at TEXT NOT NULL,
      base_updated_at TEXT, base_version TEXT
    )''');
    await db.execute('CREATE INDEX outbox_scope_seq ON outbox(scope,seq)');
    await db.execute(
      'CREATE TABLE id_map (scope TEXT NOT NULL, local_id INTEGER NOT NULL, server_id INTEGER NOT NULL, PRIMARY KEY(scope,local_id))',
    );
  }

  Future<String?> meta(String scope, String key) async {
    final rows = await db.query('metadata', where: 'scope=? AND key=?', whereArgs: [scope, key]);
    return rows.isEmpty ? null : rows.first['value'] as String;
  }

  Future<void> setMeta(String scope, String key, String value) =>
      db.insert('metadata', {'scope': scope, 'key': key, 'value': value}, conflictAlgorithm: ConflictAlgorithm.replace);

  Future<Map<String, dynamic>> snapshots(String scope) async {
    final rows = await db.query('snapshots', where: 'scope=?', whereArgs: [scope]);
    return {for (final row in rows) row['path'] as String: jsonDecode(row['body'] as String)};
  }

  Future<void> cache(String scope, String path, String body) => db.insert('snapshots', {
    'scope': scope,
    'path': path,
    'body': body,
  }, conflictAlgorithm: ConflictAlgorithm.replace);

  Future<List<Map<String, Object?>>> operations(String scope) =>
      db.query('outbox', where: 'scope=?', whereArgs: [scope], orderBy: 'seq');

  Future<Map<int, int>> mappings(String scope) async {
    final rows = await db.query('id_map', where: 'scope=?', whereArgs: [scope]);
    return {for (final row in rows) row['local_id'] as int: row['server_id'] as int};
  }

  Future<int> enqueue(String scope, Map<String, Object?> operation) => db.transaction((txn) async {
    final seq = await txn.insert('outbox', {'scope': scope, ...operation, 'local_id': 0});
    await txn.update('outbox', {'local_id': -seq}, where: 'seq=?', whereArgs: [seq]);
    return -seq;
  });

  Future<void> accept(
    String scope,
    Map<String, Object?> operation,
    String response,
    int? serverId, {
    Map<int, int> aliases = const {},
    Map<String, String> references = const {},
  }) => db.transaction((txn) async {
    await txn.update(
      'outbox',
      {'state': 'accepted', 'response': response, 'error': null},
      where: 'seq=? AND scope=?',
      whereArgs: [operation['seq'], scope],
    );
    if (serverId != null && operation['path'].toString().split('/').length == 1) {
      await txn.insert('id_map', {
        'scope': scope,
        'local_id': operation['local_id'],
        'server_id': serverId,
      }, conflictAlgorithm: ConflictAlgorithm.replace);
    }
    for (final entry in aliases.entries) {
      await txn.insert('id_map', {
        'scope': scope,
        'local_id': entry.key,
        'server_id': entry.value,
      }, conflictAlgorithm: ConflictAlgorithm.replace);
    }
    for (final entry in references.entries) {
      await txn.insert('metadata', {
        'scope': scope,
        'key': 'reference:${entry.key}',
        'value': entry.value,
      }, conflictAlgorithm: ConflictAlgorithm.replace);
    }
    final decoded = jsonDecode(response);
    final record = decoded is Map ? decoded['data'] : null;
    if (record is Map && record['_sync_version'] is String) {
      final resource = operation['path'].toString().split('/').first;
      final paths = {
        operation['path'],
        '$resource/${operation['local_id']}',
        if (serverId != null) '$resource/$serverId',
      };
      for (final path in paths) {
        await txn.update(
          'outbox',
          {'base_version': record['_sync_version'], 'base_updated_at': record['updated_at']},
          where: 'scope=? AND seq>? AND path=? AND state=?',
          whereArgs: [scope, operation['seq'], path, 'pending'],
        );
      }
    }
  });

  /// Only replace a snapshot after every resource was downloaded successfully.
  /// Accepted operations stay projected until this swap, preventing stale reads
  /// and duplicate application of their stock/balance effects.
  Future<void> replaceSnapshot(
    String scope,
    Map<String, dynamic> data,
    List<int> acceptedSequences,
    String completedAt, {
    Map<String, String> metadata = const {},
  }) => db.transaction((txn) async {
    await txn.delete('snapshots', where: 'scope=?', whereArgs: [scope]);
    for (final entry in data.entries) {
      await txn.insert('snapshots', {'scope': scope, 'path': entry.key, 'body': jsonEncode(entry.value)});
    }
    for (final seq in acceptedSequences) {
      await txn.delete('outbox', where: 'scope=? AND seq=? AND state=?', whereArgs: [scope, seq, 'accepted']);
    }
    for (final entry in {'ready': 'true', 'last_sync': completedAt, ...metadata}.entries) {
      await txn.insert('metadata', {
        'scope': scope,
        'key': entry.key,
        'value': entry.value,
      }, conflictAlgorithm: ConflictAlgorithm.replace);
    }
  });
}
