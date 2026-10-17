import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:mobile_pos/model/sale_transaction_model.dart';
import 'package:mobile_pos/model/tax_report_model.dart';
import 'package:mobile_pos/offline/offline_projection.dart';

Map<String, dynamic> fixture() => {
  'products': {
    'data': [
      {
        'id': 1,
        'productName': 'Seed',
        'product_type': 'single',
        'has_serial': 0,
        'productStock': 10,
        'stocks': [
          {
            'id': 11,
            'product_id': 1,
            'batch_no': 'A',
            'productStock': 10,
            'productPurchasePrice': 40,
            'productSalePrice': 60,
          },
        ],
      },
    ],
  },
  'parties': {
    'data': [
      {'id': 2, 'name': 'Customer', 'due': 5, 'credit_limit': 100, 'wallet': 50},
    ],
  },
  'banks': {
    'data': [
      {'id': 3, 'name': 'Bank account', 'balance': 500, 'opening_balance': 500},
    ],
  },
  'payment-types': {'data': []},
  'sales': {'data': []},
  'purchase': {'data': []},
  'expenses': {'data': []},
  'incomes': {'data': []},
  'cashes': {'data': []},
  'bank-transactions': {'data': []},
  'categories': {'data': []},
  'transactions': {'data': []},
  'cheques': {'data': []},
  'vats': {'data': []},
  'sales-return': {'data': []},
  'purchases-return': {'data': []},
  'dues': {'data': []},
  'tax-transactions': {'data': []},
};

Map<String, Object?> operation(String path, Map<String, dynamic> fields, {int id = -1, String method = 'POST'}) => {
  'path': path,
  'fields': jsonEncode(fields),
  'method': method,
  'local_id': id,
  'created_at': '2026-10-17T10:00:00',
  'files': '{}',
};

Map<String, dynamic> saleFields({num qty = 2, num due = 20}) => {
  'party_id': '2',
  'saleDate': '2026-10-17',
  'invoiceNumber': 'OFF-TEST',
  'totalAmount': '${qty * 60}',
  'dueAmount': '$due',
  'paidAmount': '${qty * 60 - due}',
  'discountAmount': '0',
  'vat_amount': '0',
  'vat_percent': '0',
  'isPaid': 'false',
  'products': jsonEncode([
    {'product_id': 1, 'stock_id': 11, 'quantities': qty, 'price': 60, 'discount': 0},
  ]),
  'payments': jsonEncode([
    {'type': '3', 'amount': qty * 60 - due},
  ]),
};

void main() {
  test('bank payment sale updates stock, customer due and bank balance offline', () {
    final p = OfflineProjection(fixture());
    final result = p.apply(operation('sales', saleFields()), validate: true);
    expect(p.find('products', 1)!['stocks'][0]['productStock'], 8);
    expect(p.find('parties', 2)!['due'], 25);
    expect(p.find('banks', 3)!['balance'], 600);
    final sale = SalesTransactionModel.fromJson(result['data']);
    expect(sale.id, -1);
    expect(sale.salesDetails!.length, 1);
    expect(sale.transactions!.first.paymentType!.name, 'Bank account');
    expect(sale.invoiceNumber, 'OFF-TEST');
  });

  test('replaying the persisted outbox reconstructs identical balances after restart', () {
    final op = operation('sales', saleFields());
    final first = OfflineProjection(fixture())..apply(op);
    final restarted = OfflineProjection(fixture())..apply(jsonDecode(jsonEncode(op)).cast<String, Object?>());
    expect(restarted.data, first.data);
  });

  test('overselling and credit-limit violations are rejected before enqueue', () {
    expect(
      () => OfflineProjection(fixture()).apply(operation('sales', saleFields(qty: 11)), validate: true),
      throwsA(isA<OfflineException>()),
    );
    expect(
      () => OfflineProjection(fixture()).apply(operation('sales', saleFields(qty: 2, due: 120)), validate: true),
      throwsA(isA<OfflineException>()),
    );
  });

  test('editing and deleting a queued sale reverse the old financial effects', () {
    final p = OfflineProjection(fixture())..apply(operation('sales', saleFields()));
    p.apply(operation('sales/-1', {...saleFields(qty: 3), '_method': 'put'}, id: -2), validate: true);
    expect(p.find('products', 1)!['stocks'][0]['productStock'], 7);
    expect(p.find('banks', 3)!['balance'], 660);
    p.apply(operation('sales/-1', {}, method: 'DELETE', id: -3), validate: true);
    expect(p.find('products', 1)!['stocks'][0]['productStock'], 10);
    expect(p.find('banks', 3)!['balance'], 500);
    expect(p.find('parties', 2)!['due'], 5);
  });

  test('ID remapping changes foreign keys and payment account IDs, never money', () {
    final p = OfflineProjection(fixture(), {-1: 99, -2: 100});
    final mapped =
        p.remap({
              'party_id': '-1',
              'amount': -1,
              'discountAmount': '-2',
              'payments': '[{"type":"-2","amount":-2}]',
              'products': '[{"product_id":-1,"price":-2}]',
            })
            as Map;
    expect(mapped['party_id'], '99');
    expect(mapped['amount'], -1);
    expect(mapped['discountAmount'], '-2');
    expect(jsonDecode(mapped['payments'])[0], {'type': '100', 'amount': -2});
    expect(jsonDecode(mapped['products'])[0], {'product_id': 99, 'price': -2});
  });

  test('bank transfer uses actual from/to fields and preserves combined balance', () {
    final snapshot = fixture();
    snapshot['banks']['data'].add({'id': 4, 'name': 'Second bank', 'balance': 100});
    final p = OfflineProjection(snapshot);
    p.apply(
      operation('bank-transactions', {'from': '3', 'to': '4', 'amount': '75', 'transaction_type': 'bank_to_bank'}),
      validate: true,
    );
    expect(p.find('banks', 3)!['balance'], 425);
    expect(p.find('banks', 4)!['balance'], 175);
  });

  test('empty lists remain valid and unknown filters cannot return misleading results', () {
    final p = OfflineProjection(fixture());
    expect(p.read('categories', {'search': 'abc'})['data'], isEmpty);
    expect(() => p.read('sales', {'unknown_filter': '1'}), throwsA(isA<OfflineException>()));
  });

  test('a purchase can add a new batch without an existing stock ID', () {
    final p = OfflineProjection(fixture());
    p.apply(
      operation('purchase', {
        'party_id': 2,
        'dueAmount': 0,
        'totalAmount': 120,
        'paidAmount': 120,
        'products': [
          {'product_id': 1, 'batch_no': 'NEW', 'quantities': 3, 'productPurchasePrice': 40, 'productSalePrice': 60},
        ],
        'payments': [
          {'type': '3', 'amount': 120},
        ],
      }),
      validate: true,
    );
    expect(p.find('products', 1)!['stocks'].last['productStock'], 3);
    expect(p.find('products', 1)!['productStock'], 13);
    expect(p.find('banks', 3)!['balance'], 380);
  });

  test('unsafe complex actions are explicit failures rather than false successes', () {
    final p = OfflineProjection(fixture());
    expect(() => p.apply(operation('payrolls', {'amount': 50}), validate: true), throwsA(isA<OfflineException>()));
  });

  test('sale return restores stock and reduces the invoice due before its paid amount', () {
    final p = OfflineProjection(fixture())..apply(operation('sales', saleFields()));
    final sale = p.find('sales', -1)!;
    p.apply(
      operation('sales-return', {
        'sale_id': -1,
        'products': [
          {'sale_detail_id': sale['details'][0]['id'], 'return_qty': 1},
        ],
        'payments': [
          {'type': '3', 'amount': 0},
        ],
      }, id: -2),
      validate: true,
    );
    expect(p.find('products', 1)!['productStock'], 9);
    expect(p.find('sales', -1)!['totalAmount'], 60);
    expect(p.find('sales', -1)!['dueAmount'], 0);
    expect(p.find('sales', -1)!['paidAmount'], 60);
    expect(p.find('parties', 2)!['due'], 5);
    expect(p.find('banks', 3)!['balance'], 540);
  });

  test('combo sales reduce component quantities and returns restore them', () {
    final snapshot = fixture();
    snapshot['products']['data'].add({
      'id': 5,
      'productName': 'Combo',
      'product_type': 'combo',
      'stocks': [],
      'combo_products': [
        {'stock_id': 11, 'quantity': 2, 'purchase_price': 40},
      ],
    });
    final p = OfflineProjection(snapshot);
    p.apply(
      operation('sales', {
        ...saleFields(due: 0),
        'products': [
          {'product_id': 5, 'quantities': 2, 'price': 150},
        ],
        'payments': [
          {'type': 'cash', 'amount': 300},
        ],
      }),
      validate: true,
    );
    expect(p.find('products', 1)!['productStock'], 6);
    expect(p.find('products', 5)!['productStock'], 3);
    final detail = p.find('sales', -1)!['details'][0];
    p.apply(
      operation('sales-return', {
        'sale_id': -1,
        'products': [
          {'sale_detail_id': detail['id'], 'return_qty': 1},
        ],
        'payments': [],
      }, id: -2),
      validate: true,
    );
    expect(p.find('products', 1)!['productStock'], 8);
    expect(p.find('products', 5)!['productStock'], 4);
  });

  test('serial numbers cannot be sold twice and are restored by a return', () {
    final snapshot = fixture();
    final product = snapshot['products']['data'][0];
    product['has_serial'] = 1;
    product['stocks'][0]['serial_numbers'] = ['A', 'B', 'C'];
    product['stocks'][0]['productStock'] = 3;
    final p = OfflineProjection(snapshot);
    final fields = {
      ...saleFields(qty: 1, due: 0),
      'products': [
        {
          'product_id': 1,
          'stock_id': 11,
          'price': 60,
          'quantities': 1,
          'serial_numbers': ['A'],
        },
      ],
    };
    p.apply(operation('sales', fields), validate: true);
    expect(p.find('products', 1)!['stocks'][0]['serial_numbers'], ['B', 'C']);
    expect(
      () => OfflineProjection(p.data).apply(operation('sales', fields, id: -2), validate: true),
      throwsA(isA<OfflineException>()),
    );
    final detail = p.find('sales', -1)!['details'][0];
    p.apply(
      operation('sales-return', {
        'sale_id': -1,
        'payments': [],
        'products': [
          {
            'sale_detail_id': detail['id'],
            'return_qty': 1,
            'serial_numbers': ['A'],
          },
        ],
      }, id: -3),
      validate: true,
    );
    expect(p.find('products', 1)!['stocks'][0]['serial_numbers'], contains('A'));
  });

  test('fractional VAT uses the server inclusive-tax calculation and feeds tax reports', () {
    final snapshot = fixture();
    snapshot['vats']['data'].add({'id': 9, 'name': 'VAT', 'rate': 5.5, 'status': true, 'manage_state': 0});
    snapshot['products']['data'][0]['vat_id'] = 9;
    snapshot['products']['data'][0]['stocks'][0]['exclusive_price'] = 40;
    final p = OfflineProjection(snapshot);
    p.apply(
      operation('sales', {
        ...saleFields(qty: 1, due: 0),
        'products': [
          {'product_id': 1, 'stock_id': 11, 'price': 105.5, 'quantities': 1},
        ],
        'payments': [
          {'type': '3', 'amount': 105.5},
        ],
      }),
      validate: true,
    );
    expect(p.find('sales', -1)!['lossProfit'], closeTo(60, 0.001));
    final report = TaxReportModel.fromJson(p.read('reports/tax', {'duration': 'all'}));
    expect(report.vats.single.rate, 5.5);
    expect(p.rows('tax-transactions').single['vat_amount'], closeTo(5.5, 0.001));
  });

  test('due collections update party, invoice and selected bank locally', () {
    final p = OfflineProjection(fixture())..apply(operation('sales', saleFields()));
    p.apply(
      operation('dues', {
        'party_id': 2,
        'invoiceNumber': 'OFF-TEST',
        'paymentDate': '2026-10-17',
        'payments': [
          {'type': '3', 'amount': 10},
        ],
        'payDueAmount': 10,
      }, id: -2),
      validate: true,
    );
    expect(p.find('sales', -1)!['dueAmount'], 10);
    expect(p.find('parties', 2)!['due'], 15);
    expect(p.find('banks', 3)!['balance'], 610);
  });

  test('a pending cheque can be deposited and reopened without losing the original receipt', () {
    final snapshot = fixture();
    snapshot['parties']['data'][0]['credit_limit'] = 1000;
    final p = OfflineProjection(snapshot);
    p.apply(
      operation('sales', {
        ...saleFields(due: 120),
        'payments': [
          {'type': 'cheque', 'amount': 120, 'cheque_number': 'C1'},
        ],
      }),
      validate: true,
    );
    final cheque = p.rows('cheques').single;
    expect(cheque['type'], 'pending');
    p.apply(operation('cheques', {'transaction_id': cheque['id'], 'payment_type': '3'}, id: -2), validate: true);
    expect(p.find('banks', 3)!['balance'], 620);
    expect(p.find('sales', -1)!['dueAmount'], 0);
    p.apply(operation('cheque-reopen/${cheque['id']}', {}, id: -3), validate: true);
    expect(p.find('banks', 3)!['balance'], 500);
    expect(p.find('sales', -1)!['dueAmount'], 120);
    expect(p.rows('cheques').single['type'], 'pending');
  });

  test('Laravel bracket fields preserve group tax IDs and role visibility offline', () {
    final p = OfflineProjection(fixture(), {-1: 20});
    final parsed = p.fields(Map<String, dynamic>.from(p.remap({'vat_ids[0]': '-1', 'visibility[sales][create]': '1'})));
    expect(parsed['vat_ids'], ['20']);
    expect(parsed['visibility'], {
      'sales': {'create': '1'},
    });
  });

  test('stock replenishment increments an existing batch and rejects duplicate serials', () {
    final p = OfflineProjection(fixture());
    p.apply(operation('stocks', {'stock_id': 11, 'product_id': 1, 'productStock': 3}), validate: true);
    expect(p.find('products', 1)!['stocks'].single['productStock'], 13);
    p.apply(
      operation('stocks', {
        'stock_id': 11,
        'product_id': 1,
        'productStock': 1,
        'serial_numbers': ['ABC'],
      }, id: -2),
      validate: true,
    );
    expect(
      () => p.apply(
        operation('stocks', {
          'stock_id': 11,
          'product_id': 1,
          'productStock': 1,
          'serial_numbers': ['ABC'],
        }, id: -3),
        validate: true,
      ),
      throwsA(isA<OfflineException>()),
    );
  });

  test('editing a party opening balance preserves later due and advance movements', () {
    final data = fixture();
    data['parties']['data'][0].addAll({'opening_balance': 5, 'opening_balance_type': 'due'});
    final p = OfflineProjection(data);
    p.apply(operation('parties/2', {'opening_balance': 10, 'opening_balance_type': 'due'}), validate: true);
    expect(p.find('parties', 2)!['due'], 10);
    expect(p.find('parties', 2)!['wallet'], 50);
    p.apply(operation('parties/2', {'opening_balance': 20, 'opening_balance_type': 'advance'}, id: -2), validate: true);
    expect(p.find('parties', 2)!['due'], 0);
    expect(p.find('parties', 2)!['wallet'], 70);
  });

  test('bulk imported products can be sold offline with master and stock relations', () {
    final p = OfflineProjection(fixture());
    final header = List<dynamic>.filled(21, 'Header');
    final row = [
      'Fertilizer',
      'Inputs',
      'Bag',
      'Agro',
      5,
      'P-002',
      40,
      60,
      60,
      60,
      'VAT',
      0,
      'exclusive',
      1,
      null,
      null,
      'B2',
      'M1',
      null,
      'single',
      null,
    ];
    p.apply(
      operation('bulk-product-rows', {
        'rows': [header, row, row],
      }),
      validate: true,
    );
    expect(p.rows('products').length, 2);
    final product = p.rows('products').firstWhere((r) => r['productCode'] == 'P-002');
    expect(product['category']['categoryName'], 'Inputs');
    final fields = saleFields(due: 0);
    fields['products'] = [
      {
        'product_id': product['id'],
        'stock_id': product['stocks'].single['id'],
        'quantities': 2,
        'price': 60,
        'productPurchasePrice': 40,
      },
    ];
    p.apply(operation('sales', fields, id: -2), validate: true);
    expect(p.find('products', product['id'])!['stocks'].single['productStock'], 3);
    expect(p.find('sales', -2)!['details'].single['product']['productName'], 'Fertilizer');
  });

  test('offline transaction filtering, receipts and party ledger include new sales', () {
    final p = OfflineProjection(fixture());
    p.apply(operation('sales', saleFields()), validate: true);
    final result = p.read('transactions', {'party_id': '2', 'platform': 'sale'});
    expect(result['data'].length, 1);
    expect(result['money_in'], 100);
    final id = result['data'].single['id'];
    expect(p.read('money-receipt/$id', {})['data']['sale']['party']['name'], 'Customer');
    final ledger = p.read('party-ledger/2', {})['data'] as List;
    expect(ledger.last['platform'], 'Sales');
    expect(ledger.last['balance'], 20);
  });
}
