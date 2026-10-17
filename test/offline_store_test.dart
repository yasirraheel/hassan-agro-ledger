import 'dart:io';

import 'package:flutter_test/flutter_test.dart';
import 'package:mobile_pos/offline/offline_store.dart';
import 'package:sqflite_common_ffi/sqflite_ffi.dart';

void main() {
  sqfliteFfiInit();
  test('outbox survives database reopen and stays isolated by account', () async {
    final folder = await Directory.systemTemp.createTemp('hassan-offline-test-');
    final path = '${folder.path}/test.db';
    Future<OfflineStore> open() async => OfflineStore(
      await databaseFactoryFfi.openDatabase(
        path,
        options: OpenDatabaseOptions(version: 1, onCreate: OfflineStore.createSchema),
      ),
    );
    var store = await open();
    final id = await store.enqueue('business-a:user-1', {
      'operation_id': 'test-1',
      'path': 'categories',
      'method': 'POST',
      'fields': '{"categoryName":"Seed"}',
      'files': '{}',
      'created_at': DateTime.now().toIso8601String(),
    });
    expect(id, lessThan(0));
    await store.db.close();
    store = await open();
    expect((await store.operations('business-a:user-1')).length, 1);
    expect(await store.operations('business-b:user-2'), isEmpty);
    final op = (await store.operations('business-a:user-1')).single;
    await store.accept('business-a:user-1', op, '{"data":{"id":99}}', 99);
    expect((await store.mappings('business-a:user-1'))[id], 99);
    expect((await store.operations('business-a:user-1')).single['state'], 'accepted');
    await store.replaceSnapshot(
      'business-a:user-1',
      {
        'categories': {
          'data': [
            {'id': 99, 'categoryName': 'Seed'},
          ],
        },
      },
      [op['seq'] as int],
      DateTime.now().toIso8601String(),
    );
    expect(await store.operations('business-a:user-1'), isEmpty);
    expect(await store.meta('business-a:user-1', 'ready'), 'true');
    expect((await store.snapshots('business-a:user-1'))['categories']['data'].single['id'], 99);
    await store.db.close();
    // The sole recursive target was created immediately above in the OS temp folder.
    await folder.delete(recursive: true);
  });
}
