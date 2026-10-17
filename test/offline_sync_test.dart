import 'dart:convert';
import 'dart:io';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:mobile_pos/Const/api_config.dart';
import 'package:mobile_pos/offline/offline_projection.dart';
import 'package:mobile_pos/offline/offline_service.dart';
import 'package:mobile_pos/offline/offline_store.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:sqflite_common_ffi/sqflite_ffi.dart';

import 'offline_projection_test.dart' show fixture;

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  sqfliteFfiInit();

  test('offline dependent creates remap IDs, survive lost upload response and sync exactly once', () async {
    SharedPreferences.setMockInitialValues({'token': 'test-session'});
    final database = await databaseFactoryFfi.openDatabase(
      inMemoryDatabasePath,
      options: OpenDatabaseOptions(version: 1, onCreate: OfflineStore.createSchema),
    );
    final store = OfflineStore(database);
    var reachable = true;
    var loseFirstResponse = true;
    var commits = 0;
    final receipts = <String, String>{};
    final server = jsonDecode(jsonEncode(fixture())) as Map<String, dynamic>;
    server['business'] = {
      'data': {'id': 1},
    };
    final resources = server.keys.toList();
    final client = MockClient((request) async {
      if (!reachable) throw const SocketException('No connection');
      final path = request.url.path.replaceFirst('${Uri.parse(APIConfig.url).path}/', '');
      if (path == 'sync/status') {
        return http.Response(
          jsonEncode({'protocol': 1, 'idempotency': true, 'scope': '1:1:all', 'resources': resources}),
          200,
        );
      }
      if (request.method == 'GET') return http.Response(jsonEncode(server[path]), 200);
      expect(request.headers['X-Offline-Scope'], '1:1:all');
      final key = request.headers['Idempotency-Key']!;
      if (receipts.containsKey(key)) return http.Response(receipts[key]!, 200);
      final fields = jsonDecode(request.body) as Map<String, dynamic>;
      if (path == 'parties') {
        final record = {...fields, 'id': 55, 'due': 0, 'wallet': 0};
        server['parties']['data'].add(record);
        receipts[key] = jsonEncode({'data': record});
      } else if (path == 'sales') {
        expect(fields['party_id'], 55);
        final p = OfflineProjection(server);
        final result = p.apply({
          'path': 'sales',
          'fields': jsonEncode(fields),
          'files': '{}',
          'method': 'POST',
          'local_id': 99,
          'created_at': DateTime.now().toIso8601String(),
        });
        server.addAll(p.data);
        receipts[key] = jsonEncode(result);
      } else {
        fail('Unexpected write $path');
      }
      commits++;
      if (loseFirstResponse) {
        loseFirstResponse = false;
        throw const SocketException('Response lost AFTER commit');
      }
      return http.Response(receipts[key]!, 200);
    });
    final service = OfflineService.forTesting(database: store, clientFactory: () => client);
    await service.sync();
    expect(service.ready, isTrue);
    reachable = false;
    final created = await service.write(
      Uri.parse('${APIConfig.url}/parties'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'name': 'Offline customer', 'type': 'Retailer'}),
    );
    final customerId = jsonDecode(created.body)['data']['id'];
    expect(customerId, lessThan(0));
    await service.sync();
    await service.write(
      Uri.parse('${APIConfig.url}/sales'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'party_id': customerId,
        'totalAmount': 120,
        'paidAmount': 120,
        'dueAmount': 0,
        'saleDate': DateTime.now().toIso8601String(),
        'isPaid': true,
        'products': jsonEncode([
          {'product_id': 1, 'stock_id': 11, 'quantities': 2, 'price': 60},
        ]),
        'payments': jsonEncode([
          {'type': '3', 'amount': 120},
        ]),
      }),
    );
    await service.sync();
    expect(service.pendingCount, 2);
    expect((await service.projection()).find('products', 1)!['productStock'], 8);
    reachable = true;
    await service.sync();
    expect(commits, 1, reason: service.lastError); // First operation committed but its response was lost.
    expect(service.pendingCount, 2);
    await service.sync();
    expect(commits, 2); // Exactly one customer and one sale, despite a retry.
    expect(service.pendingCount, 0);
    expect(await store.operations(service.scope!), isEmpty);
    expect((await service.projection()).find('products', 1)!['productStock'], 8);
    expect((await service.projection()).find('banks', 3)!['balance'], 620);
    service.dispose();
    await database.close();
  });

  test('an older server cannot enable an unsafe offline upload queue', () async {
    SharedPreferences.setMockInitialValues({'token': 'old-server-session'});
    final database = await databaseFactoryFfi.openDatabase(
      inMemoryDatabasePath,
      options: OpenDatabaseOptions(version: 1, onCreate: OfflineStore.createSchema),
    );
    final service = OfflineService.forTesting(
      database: OfflineStore(database),
      clientFactory: () => MockClient((_) async => http.Response('Not found', 404)),
    );
    await service.sync();
    expect(service.supported, isFalse);
    expect(service.ready, isFalse);
    expect(service.pendingCount, 0);
    service.dispose();
    await database.close();
  });

  test('a rejected change does not block later valid changes from syncing', () async {
    SharedPreferences.setMockInitialValues({'token': 'conflict-session'});
    final database = await databaseFactoryFfi.openDatabase(
      inMemoryDatabasePath,
      options: OpenDatabaseOptions(version: 1, onCreate: OfflineStore.createSchema),
    );
    final server = jsonDecode(jsonEncode(fixture())) as Map<String, dynamic>;
    server['business'] = {
      'data': {'id': 1},
    };
    final resources = server.keys.toList();
    var reachable = true;
    var acceptedIncomes = 0;
    final client = MockClient((request) async {
      if (!reachable) throw const SocketException('No connection');
      final path = request.url.path.replaceFirst('${Uri.parse(APIConfig.url).path}/', '');
      if (path == 'sync/status') {
        return http.Response(
          jsonEncode({'protocol': 1, 'idempotency': true, 'scope': '1:1:all', 'resources': resources}),
          200,
        );
      }
      if (request.method == 'GET') return http.Response(jsonEncode(server[path]), 200);
      if (path == 'parties') {
        return http.Response(jsonEncode({'message': 'Customer name is already used.'}), 422);
      }
      if (path == 'incomes') {
        acceptedIncomes++;
        return http.Response(
          jsonEncode({
            'data': {
              'id': 71,
              'amount': 25,
              'incomeDate': '2026-10-18',
              '_sync_version': 'income-v1',
            },
          }),
          201,
        );
      }
      fail('Unexpected write $path');
    });
    final service = OfflineService.forTesting(
      database: OfflineStore(database),
      clientFactory: () => client,
    );

    await service.sync();
    reachable = false;
    await service.write(
      Uri.parse('${APIConfig.url}/parties'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'name': 'Rejected customer', 'type': 'Retailer'}),
    );
    await service.write(
      Uri.parse('${APIConfig.url}/incomes'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'amount': 25, 'incomeDate': '2026-10-18', 'payment_type_id': 3}),
    );
    reachable = true;
    await service.sync(force: true);

    expect(acceptedIncomes, 1);
    expect(service.conflictCount, 1);
    expect(service.pendingCount, 1);
    expect(service.lastError, contains('Customer name is already used'));
    service.dispose();
    await database.close();
  });
}
