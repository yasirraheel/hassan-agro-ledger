import 'dart:convert';

part 'offline_reports.dart';
part 'offline_business.dart';
part 'offline_trades.dart';
part 'offline_banking.dart';
part 'offline_import.dart';
part 'offline_ledger.dart';

typedef JsonMap = Map<String, dynamic>;

class OfflineException implements Exception {
  final String message;
  const OfflineException(this.message);
  @override
  String toString() => message;
}

/// Deterministic projection over a server snapshot plus the ordered outbox.
/// It is rebuilt on launch; memory is never the only copy of an offline change.
class OfflineProjection {
  final Map<String, dynamic> data;
  final Map<int, int> ids;
  OfflineProjection(Map<String, dynamic> snapshot, [this.ids = const {}])
    : data = jsonDecode(jsonEncode(snapshot)) as Map<String, dynamic>;

  static const simpleResources = {
    'units',
    'brands',
    'categories',
    'product-models',
    'racks',
    'shelfs',
    'expense-categories',
    'income-categories',
    'warehouses',
    'departments',
    'designations',
    'shifts',
    'holidays',
    'leave-types',
    'variations',
    'users',
    'vats',
    'payment-types',
  };
  // Financial reducers must be implemented explicitly; an arbitrary cached POST
  // is never turned into a success response.
  static const writableResources = {
    ...simpleResources,
    'parties',
    'products',
    'sales',
    'purchase',
    'expenses',
    'incomes',
    'banks',
    'cashes',
    'bank-transactions',
    'stocks',
    'dues',
    'collect-invoice-due',
    'advance-collections',
    'sales-return',
    'purchases-return',
    'cheques',
    'cheque-reopen',
    'bulk-product-rows',
    ...singletonResources,
  };
  static const singletonResources = {
    'business',
    'profile',
    'product-settings',
    'invoice-settings',
    'currency-settings',
    'currency-selection',
    'lang',
  };

  static num number(dynamic value) => value is num ? value : num.tryParse('$value') ?? 0;
  static dynamic decoded(dynamic value) {
    if (value is String && (value.startsWith('[') || value.startsWith('{'))) {
      try {
        return jsonDecode(value);
      } catch (_) {
        /* Plain text remains text. */
      }
    }
    return value;
  }

  /// Negative IDs are remapped only in identifier fields, never amounts/prices.
  dynamic remap(dynamic value, [String key = '']) {
    if (value is Map) return value.map((k, v) => MapEntry('$k', remap(v, '$k')));
    if (value is List) return value.map((v) => remap(v, key)).toList();
    final parsed = decoded(value);
    if (parsed is Map || parsed is List) return jsonEncode(remap(parsed, key));
    final rootKey = key.split('[').first;
    final bracketKeys = RegExp(r'\[([^\]]+)\]').allMatches(key).map((m) => m[1]!).toList();
    final leafKey = bracketKeys.isEmpty ? key : bracketKeys.last;
    if (key == 'id' ||
        rootKey.endsWith('_id') ||
        rootKey.endsWith('_ids') ||
        leafKey.endsWith('_id') ||
        key == 'stockId' ||
        key == 'productId' ||
        key == 'from' ||
        key == 'to' ||
        leafKey == 'type' ||
        key == 'payment_type') {
      final id = int.tryParse('$value');
      if (id != null && ids.containsKey(id)) return value is String ? '${ids[id]}' : ids[id];
    }
    return value;
  }

  List<JsonMap> rows(String resource) {
    final envelope = data[resource];
    dynamic value = envelope is Map ? envelope['data'] : envelope;
    if (value is Map && value['data'] is List) value = value['data'];
    if (value is List) return value.whereType<Map>().map((e) => Map<String, dynamic>.from(e)).toList();
    return [];
  }

  JsonMap? find(String resource, dynamic id) {
    final mapped = ids[int.tryParse('$id')] ?? id;
    for (final row in rows(resource)) {
      if ('${row['id']}' == '$mapped') return row;
    }
    return null;
  }

  void setRows(String resource, List<JsonMap> values) {
    final envelope = data[resource];
    if (envelope is Map) {
      if (envelope['data'] is Map && envelope['data']['data'] is List) {
        envelope['data']['data'] = values;
        envelope['data']['total'] = values.length;
        envelope['data']['last_page'] = 1;
        envelope['data']['next_page_url'] = null;
      } else {
        envelope['data'] = values;
      }
    } else {
      data[resource] = {'message': 'Saved on this device', 'data': values};
    }
    if (resource == 'products' && data[resource] is Map) {
      data[resource]['total_stock_value'] = _stockValue();
    }
  }

  void upsert(String resource, JsonMap record) {
    final values = rows(resource);
    final index = values.indexWhere((r) => '${r['id']}' == '${record['id']}');
    if (index < 0) {
      values.insert(0, record);
    } else {
      values[index] = record;
    }
    setRows(resource, values);
  }

  void remove(String resource, dynamic id) =>
      setRows(resource, rows(resource).where((r) => '${r['id']}' != '$id').toList());

  JsonMap fields(Map<String, dynamic> raw) {
    final result = <String, dynamic>{};
    const textFields = {
      'phone',
      'account_number',
      'routing_number',
      'productCode',
      'invoiceNumber',
      'batch_no',
      'zip_code',
      'tax_no',
    };
    for (final entry in raw.entries) {
      if (entry.key == '_method' || entry.key.startsWith('__offline_')) continue;
      dynamic value = decoded(entry.value);
      if (value == 'true') value = true;
      if (value == 'false') value = false;
      if ((entry.key == 'has_serial' || entry.key == 'status') && value is bool) value = value ? 1 : 0;
      if (value is String &&
          !textFields.contains(entry.key) &&
          (entry.key.endsWith('_id') || _numericFields.contains(entry.key))) {
        value = num.tryParse(value) ?? value;
      }
      final payment = RegExp(r'^payments\[(\d+)\]\[(\w+)\]$').firstMatch(entry.key);
      if (payment != null) {
        final list = (result['payments'] ??= <dynamic>[]) as List;
        final index = int.parse(payment[1]!);
        while (list.length <= index) {
          list.add(<String, dynamic>{});
        }
        list[index][payment[2]] = payment[2] == 'amount' ? number(value) : value;
        continue;
      }
      if (entry.key.contains('[')) {
        final keys = [entry.key.split('[').first, ...RegExp(r'\[([^\]]*)\]').allMatches(entry.key).map((m) => m[1]!)];
        dynamic node = result;
        for (var index = 0; index < keys.length; index++) {
          final last = index == keys.length - 1;
          final key = keys[index];
          if (node is List) {
            final position = key.isEmpty ? node.length : int.parse(key);
            while (node.length <= position) {
              node.add(null);
            }
            if (last) {
              node[position] = value;
            } else {
              node[position] ??= int.tryParse(keys[index + 1]) != null || keys[index + 1].isEmpty
                  ? <dynamic>[]
                  : <String, dynamic>{};
              node = node[position];
            }
          } else {
            if (last) {
              node[key] = value;
            } else {
              node[key] ??= int.tryParse(keys[index + 1]) != null || keys[index + 1].isEmpty
                  ? <dynamic>[]
                  : <String, dynamic>{};
              node = node[key];
            }
          }
        }
      } else {
        result[entry.key] = value;
      }
    }
    return result;
  }

  static const _numericFields = {
    'totalAmount',
    'paidAmount',
    'dueAmount',
    'discountAmount',
    'discount_percent',
    'vat_amount',
    'vat_percent',
    'shipping_charge',
    'change_amount',
    'rounding_amount',
    'actual_total_amount',
    'opening_balance',
    'credit_limit',
    'amount',
    'balance',
    'productStock',
    'productPurchasePrice',
    'productSalePrice',
    'productWholeSalePrice',
    'productDealerPrice',
    'exclusive_price',
    'profit_percent',
    'alert_qty',
    'rate',
    'status',
    'has_serial',
  };

  JsonMap apply(Map<String, Object?> operation, {bool validate = false}) {
    final path = operation['path'] as String;
    final parts = path.split('/');
    final resource = parts.first;
    if (resource == 'bulk-product-rows') return applyProductImport(operation, validate: validate);
    if (resource == 'cheques' || resource == 'cheque-reopen') return applyCheque(operation, validate: validate);
    if (singletonResources.contains(resource)) return applyBusinessSettings(operation, validate: validate);
    if (resource == 'stocks') {
      data['stocks'] = {
        'data': [for (final product in rows('products')) ...((product['stocks'] as List?) ?? [])],
      };
    }
    if (!writableResources.contains(resource)) {
      throw const OfflineException('This action needs a connection until its offline rules are available.');
    }
    final raw = jsonDecode(operation['fields'] as String) as JsonMap;
    final values = fields(Map<String, dynamic>.from(remap(raw)));
    final method = '${raw['_method'] ?? operation['method']}'.toUpperCase();
    final stockIncrement = resource == 'stocks' && parts.length == 1;
    final originalId = stockIncrement
        ? int.tryParse('${values['stock_id']}')
        : parts.length > 1
        ? int.tryParse(parts[1])
        : operation['local_id'] as int;
    final id = ids[originalId] ?? originalId;
    final existing = parts.length > 1 || stockIncrement
        ? find(resource, id) ?? ({'cashes', 'bank-transactions'}.contains(resource) ? find('transactions', id) : null)
        : null;
    if (parts.length > 1 && existing == null) {
      throw const OfflineException('Download this record before changing it offline.');
    }
    if (!data.containsKey(resource) && {'collect-invoice-due', 'advance-collections'}.contains(resource)) {
      data[resource] = {'data': <JsonMap>[]};
    }
    if (!data.containsKey(resource)) {
      throw OfflineException('Download $resource before using it offline.');
    }
    final record = <String, dynamic>{
      ...?existing,
      ...values,
      'id': id,
      'created_at': existing?['created_at'] ?? operation['created_at'],
      'updated_at': operation['created_at'],
      '_pending_sync': true,
      '_offline_local_id': existing?['_offline_local_id'] ?? operation['local_id'],
    };
    record['business_id'] ??= data['business']?['data']?['id'];
    record['status'] ??= resource == 'vats' ? true : 1;
    final attachments = jsonDecode(operation['files'] as String? ?? '{}') as Map;
    for (final entry in attachments.entries) {
      record['${entry.key}'] = entry.value;
    }
    if (resource == 'sales-return' || resource == 'purchases-return') {
      applyReturn(record, purchase: resource == 'purchases-return', validate: validate);
    } else if (resource == 'vats') {
      applyTaxDefinition(record, deleting: method == 'DELETE');
    } else if (resource == 'stocks') {
      final product = find('products', record['product_id']);
      if (product == null) throw const OfflineException('Download the product before changing stock.');
      if (stockIncrement && existing == null)
        throw const OfflineException('Download this stock batch before adding quantities.');
      final serials = List<dynamic>.from(values['serial_numbers'] as List? ?? []);
      if (serials.isNotEmpty) {
        final all = (product['stocks'] as List? ?? [])
            .where((s) => stockIncrement || '${s['id']}' != '$id')
            .expand((s) => s['serial_numbers'] as List? ?? [])
            .toSet();
        if (validate && (serials.toSet().length != serials.length || serials.any(all.contains)))
          throw const OfflineException('These serial numbers already exist for this product.');
        record['serial_numbers'] = {...existing?['serial_numbers'] as List? ?? [], ...serials}.toList();
        record['productStock'] = stockIncrement
            ? number(existing?['productStock']) + serials.length
            : (record['serial_numbers'] as List).length;
      } else if (stockIncrement) {
        if (validate && number(values['productStock']) <= 0)
          throw const OfflineException('Enter a quantity greater than zero.');
        record['productStock'] = number(existing?['productStock']) + number(values['productStock']);
      }
      if (validate && number(record['productStock']) < 0) throw const OfflineException('Stock cannot be negative.');
      final stocks = (product['stocks'] as List? ?? []).where((s) => '${s['id']}' != '$id').toList();
      if (method != 'DELETE') stocks.add(record);
      product['stocks'] = stocks;
      product['productStock'] = stocks.fold<num>(0, (v, s) => v + number(s['productStock']));
      product['stocks_sum_product_stock'] = product['productStock'];
      upsert('products', product);
    } else if (resource == 'dues' || resource == 'collect-invoice-due') {
      if (validate && parts.length > 1) throw const OfflineException('Due receipts cannot be edited or deleted.');
      _due(record, guest: resource == 'collect-invoice-due', validate: validate);
    } else if (resource == 'advance-collections') {
      final party = find('parties', record['party_id']);
      if (party == null) throw const OfflineException('Select a downloaded customer or supplier.');
      final amount = _received(record);
      if (amount <= 0) throw const OfflineException('Enter an amount greater than zero.');
      party['wallet'] = number(party['wallet']) + amount;
      upsert('parties', party);
      _payments(record, party['type'] == 'Supplier' ? -1 : 1);
      record['wallet'] = party['wallet'];
      record['transactions'] = _transactionRows(record, party['type'] == 'Supplier' ? 'debit' : 'credit');
      _ledger(party['type'] == 'Supplier' ? 'advance_pay' : 'advance_collect', id, record);
    } else if (resource == 'sales' || resource == 'purchase') {
      if (validate &&
          existing != null &&
          ((existing['sale_returns'] as List? ?? []).isNotEmpty ||
              (existing['purchase_returns'] as List? ?? []).isNotEmpty)) {
        throw const OfflineException('An invoice with returns needs server validation before editing or deleting.');
      }
      _trade(resource, existing, method == 'DELETE' ? null : record, validate);
    } else if (resource == 'expenses' || resource == 'incomes') {
      final sign = resource == 'incomes' ? 1 : -1;
      if (existing != null) _payments(existing, -sign, reverseWallet: true);
      if (method != 'DELETE') _payments(record, sign);
      record['amount'] = _received(record);
      record['transactions'] = _transactionRows(record, sign == 1 ? 'credit' : 'debit');
    } else if (resource == 'cashes' || resource == 'bank-transactions') {
      _cashTransfer(resource, existing, method == 'DELETE' ? null : record, validate);
    } else if (resource == 'parties') {
      if (existing == null) {
        record['due'] = record['opening_balance_type'] == 'due' ? number(record['opening_balance']) : 0;
        record['wallet'] = record['opening_balance_type'] == 'advance' ? number(record['opening_balance']) : 0;
      } else if (values.containsKey('opening_balance')) {
        final previous = number(existing['opening_balance']);
        final next = number(record['opening_balance']);
        final oldType = existing['opening_balance_type'];
        final newType = record['opening_balance_type'];
        record['due'] = number(existing['due']) - (oldType == 'due' ? previous : 0) + (newType == 'due' ? next : 0);
        record['wallet'] =
            number(existing['wallet']) - (oldType == 'advance' ? previous : 0) + (newType == 'advance' ? next : 0);
      }
    } else if (resource == 'banks' || resource == 'payment-types') {
      if (resource == 'banks' &&
          validate &&
          existing != null &&
          number(existing['balance']) != number(existing['opening_balance'])) {
        throw const OfflineException(
          'A bank with transactions needs server validation before changing its opening balance.',
        );
      }
      record['balance'] = resource == 'payment-types' && existing != null
          ? existing['balance']
          : number(record['opening_balance']);
      record['show_in_invoice'] = record['show_in_invoice'] == true || record['show_in_invoice'] == '1';
    } else if (resource == 'products') {
      _product(record, existing, validate);
    } else if (resource == 'variations') {
      record['values'] = '${record['values']}'.split(',').map((v) => v.trim()).toList();
    }
    if (method == 'DELETE') {
      remove(resource, id);
      if (resource == 'banks') remove('payment-types', id);
      if (resource == 'payment-types') remove('banks', id);
    } else {
      if (operation['state'] == 'accepted' && operation['response'] is String) {
        final response = jsonDecode(operation['response'] as String);
        if (response is Map && response['data'] is Map && response['data']['_sync_version'] != null) {
          record['_sync_version'] = response['data']['_sync_version'];
        }
      }
      _relations(record);
      upsert(resource, record);
      if (resource == 'banks') upsert('payment-types', record);
      if (resource == 'payment-types') upsert('banks', record);
      if (resource == 'collect-invoice-due') upsert('dues', record);
    }
    if ({'sales', 'purchase', 'dues', 'collect-invoice-due', 'expenses', 'incomes'}.contains(resource)) {
      _ledger(resource, id, method == 'DELETE' ? null : record);
    }
    refreshComboStocks();
    return {'message': 'Saved on this device. Waiting to sync.', 'data': record, 'offline': true};
  }

  void _ledger(String resource, dynamic id, JsonMap? record) {
    final platform = switch (resource) {
      'sales' => 'sale',
      'expenses' => 'expense',
      'incomes' => 'income',
      'dues' || 'collect-invoice-due' => 'due_collect',
      _ => resource,
    };
    final transactions = rows('transactions')
        .where(
          (t) =>
              !('${t['reference_id']}' == '$id' &&
                  (t['platform'] == platform || platform == 'due_collect' && t['platform'] == 'due_pay')),
        )
        .toList();
    var index = 0;
    for (final raw in record?['transactions'] as List? ?? []) {
      final t = Map<String, dynamic>.from(raw);
      t['id'] = -7000000000000 + number(record!['_offline_local_id']).toInt() * 1000 - index++;
      t['id'] = ids[t['id']] ?? t['id'];
      t['reference_id'] = id;
      t['_offline_local_id'] = record['_offline_local_id'];
      t['platform'] = platform == 'due_collect' && record['purchase_id'] != null ? 'due_pay' : platform;
      t['invoice_no'] = record['invoiceNumber'];
      t['date'] ??= record['paymentDate'] ?? record['incomeDate'] ?? record['expenseDate'] ?? record['created_at'];
      t['created_at'] ??= record['created_at'];
      transactions.insert(0, t);
      if (t['transaction_type'] == 'cheque_payment') upsert('cheques', t);
    }
    if (data.containsKey('transactions')) setRows('transactions', transactions);
    recomputeCashBalance();
  }

  num _received(JsonMap record) {
    final payments = decoded(record['payments']);
    if (payments is! List) return 0;
    return payments.fold<num>(
      0,
      (sum, p) => sum + ('${p['type']}'.toLowerCase() == 'cheque' ? 0 : number(p['amount'])),
    );
  }

  void _due(JsonMap record, {required bool guest, required bool validate}) {
    final party = guest ? null : find('parties', record['party_id']);
    if (!guest && party == null) throw const OfflineException('Select a downloaded customer or supplier.');
    final resource = party?['type'] == 'Supplier' ? 'purchase' : 'sales';
    final reference = record['invoiceNumber'];
    final invoices = rows(resource)
        .where((r) => guest ? r['party_id'] == null || r['party_id'] == 0 : '${r['party_id']}' == '${party!['id']}')
        .toList();
    final matching = invoices.where((r) => r['invoiceNumber'] == reference).toList();
    final amount = _received(record);
    if (amount <= 0) throw const OfflineException('Enter an amount greater than zero. Cheques remain pending.');
    JsonMap? invoice;
    if (reference != null && '$reference'.isNotEmpty) {
      if (matching.length != 1)
        throw const OfflineException('Download the selected invoice before collecting its due.');
      invoice = matching.single;
      if (validate && number(invoice['dueAmount']) < amount)
        throw const OfflineException('Payment exceeds the invoice due.');
      record['totalDue'] = number(invoice['dueAmount']);
      invoice['dueAmount'] = number(invoice['dueAmount']) - amount;
      upsert(resource, invoice);
      record[resource == 'sales' ? 'sale_id' : 'purchase_id'] = invoice['id'];
      record[resource == 'sales' ? 'sale' : 'purchase'] = invoice;
      record['dueAmountAfterPay'] = invoice['dueAmount'];
    } else {
      final allDue = invoices.fold<num>(0, (sum, inv) => sum + number(inv['dueAmount']));
      if (guest || party == null || validate && allDue + amount > number(party['due'])) {
        throw const OfflineException('Select an invoice for this payment.');
      }
      record['totalDue'] = number(party['due']);
      record['dueAmountAfterPay'] = number(party['due']) - amount;
      if (party['opening_balance_type'] == 'due')
        party['opening_balance'] = (number(party['opening_balance']) - amount).clamp(0, double.infinity);
    }
    if (party != null) {
      if (validate && number(party['due']) < amount) throw const OfflineException('Payment exceeds the party due.');
      party['due'] = number(party['due']) - amount;
      upsert('parties', party);
    }
    record['payDueAmount'] = amount;
    _payments(record, resource == 'sales' ? 1 : -1);
    record['transactions'] = _transactionRows(record, resource == 'sales' ? 'credit' : 'debit');
    record['invoiceNumber'] = 'OFF-D-${number(record['_offline_local_id']).abs()}';
  }

  void _relations(JsonMap record) {
    const refs = {
      'party': 'parties',
      'category': 'categories',
      'brand': 'brands',
      'unit': 'units',
      'vat': 'vats',
      'branch': 'branches',
      'warehouse': 'warehouses',
      'payment_type': 'banks',
      'employee': 'employees',
      'department': 'departments',
    };
    for (final entry in refs.entries) {
      final id = record['${entry.key}_id'];
      if (id != null) record[entry.key] = find(entry.value, id);
    }
    final profile = data['profile'];
    if (record['user'] == null && profile is Map && profile['data'] is Map) {
      record['user'] = profile['data'];
      record['user_id'] ??= profile['data']['id'];
    }
  }

  void _product(JsonMap record, JsonMap? existing, bool validate) {
    if (record['product_type'] == 'combo') {
      final components = record['combo_products'] as List? ?? [];
      if (components.isEmpty) throw const OfflineException('Choose at least one component for the combo product.');
      for (final component in components) {
        final stock = _stock(component['stock_id']);
        if (stock == null || number(component['quantity']) <= 0)
          throw const OfflineException('Choose a downloaded stock batch and quantity for each combo component.');
        component['stock'] = {...stock, 'product': find('products', stock['product_id'])};
      }
      record['stocks'] = <JsonMap>[];
      record['productStock'] = components
          .map((c) => number(c['stock']['productStock']) ~/ number(c['quantity']))
          .reduce((a, b) => a < b ? a : b);
    }
    final stocks = record['stocks'];
    if (stocks is List) {
      final oldStocks = existing?['stocks'] as List? ?? [];
      for (var i = 0; i < stocks.length; i++) {
        final stock = stocks[i] as Map;
        stock['id'] ??= i < oldStocks.length
            ? oldStocks[i]['id']
            : -8000000000000 + number(record['_offline_local_id']).toInt() * 1000 - i;
        stock['id'] = ids[int.tryParse('${stock['id']}')] ?? stock['id'];
        stock['product_id'] = record['id'];
        for (final field in _numericFields) {
          if (stock.containsKey(field)) stock[field] = number(stock[field]);
        }
      }
      if (record['product_type'] != 'combo')
        record['productStock'] = stocks.fold<num>(0, (sum, s) => sum + number(s['productStock']));
      record['total_stock'] = record['productStock'];
      record['stocks_sum_product_stock'] = record['productStock'];
    }
  }

  void _trade(String resource, JsonMap? old, JsonMap? record, bool validate) {
    final sign = resource == 'sales' ? -1 : 1;
    if (old != null) _tradeEffect(resource, old, -sign, false);
    if (record != null) {
      final products = record['products'];
      if (products is! List || products.isEmpty) {
        throw const OfflineException('At least one product is required.');
      }
      var lineIndex = 0;
      record['details'] = products.map<JsonMap>((p) {
        final line = Map<String, dynamic>.from(p as Map);
        final product = find('products', line['product_id']);
        if (product == null)
          throw const OfflineException('Download the selected product before creating this invoice.');
        final linePosition = lineIndex++;
        line['id'] ??= -9000000000000 + number(record['_offline_local_id']).toInt() * 1000 - linePosition;
        line['id'] = ids[int.tryParse('${line['id']}')] ?? line['id'];
        if (resource == 'purchase') {
          final candidates = product['stocks'] as List? ?? [];
          final matches = candidates
              .where((s) => s['batch_no'] == line['batch_no'] && s['variant_name'] == line['variant_name'])
              .toList();
          final newStockId = -8000000000000 + number(record['_offline_local_id']).toInt() * 1000 - linePosition;
          line['stock_id'] = matches.isEmpty ? (ids[newStockId] ?? newStockId) : matches.first['id'];
          if (matches.isEmpty) {
            final stock = <String, dynamic>{...line, 'id': line['stock_id'], 'productStock': 0};
            product['stocks'] = [...candidates, stock];
            upsert('products', product);
          }
        }
        line['product'] = product;
        line['stock'] = _stock(line['stock_id']);
        line['productPurchasePrice'] = number(
          resource == 'purchase' ? line['productPurchasePrice'] : line['stock']?['productPurchasePrice'],
        );
        line['price'] = number(line['price']);
        line['quantities'] = number(line['quantities']);
        final serials = List<String>.from(line['serial_numbers'] as List? ?? []);
        if (serials.isNotEmpty) {
          final existingSerials = List<String>.from(line['stock']?['serial_numbers'] as List? ?? []);
          final selected = resource == 'purchase'
              ? serials.where((s) => !existingSerials.contains(s)).toSet().toList()
              : serials.toSet().toList();
          if (validate && (selected.isEmpty || selected.length != serials.length && resource == 'sales')) {
            throw const OfflineException('Choose valid, unique serial numbers for this product.');
          }
          line['serial_numbers'] = selected;
          line['quantities'] = selected.length;
        }
        line['discount'] = number(line['discount']);
        final party = find('parties', record['party_id']);
        final tax = taxComponents(product, party);
        final totalRate = tax.fold<num>(0, (v, t) => v + number(t['rate']));
        final actualUnit = resource == 'sales' ? line['price'] - line['discount'] : line['productPurchasePrice'];
        final quantity = number(line['quantities']);
        line['_vat_transactions'] = [
          for (final t in tax)
            {
              'vat_id': t['id'],
              'vat_rate': number(t['rate']),
              'vat_amount': actualUnit * quantity * number(t['rate']) / (100 + totalRate),
            },
        ];
        line['price_without_tax'] = num.parse((actualUnit * 100 / (100 + totalRate)).toStringAsFixed(3));
        final cost = product['product_type'] == 'combo'
            ? (product['combo_products'] as List? ?? []).fold<num>(0, (sum, c) => sum + number(c['purchase_price']))
            : number(line['stock']?['exclusive_price'] ?? line['productPurchasePrice']);
        line['productPurchasePrice'] = product['product_type'] == 'combo' ? cost : line['productPurchasePrice'];
        line['lossProfit'] =
            (number(line['price_without_tax']) - cost) * quantity - number(record['discountAmount']) / products.length;
        return line;
      }).toList();
      record['lossProfit'] = (record['details'] as List).fold<num>(0, (v, d) => v + number(d['lossProfit']));
      record['sale_returns'] ??= [];
      record['purchase_returns'] ??= [];
      record['meta'] = {'note': record['note'] ?? ''};
      _tradeEffect(resource, record, sign, validate);
      replaceTradeTaxes(resource, old, record);
      record['transactions'] = _transactionRows(record, resource == 'sales' ? 'credit' : 'debit');
    } else {
      replaceTradeTaxes(resource, old, null);
    }
  }

  JsonMap? _stock(dynamic id) {
    for (final product in rows('products')) {
      for (final stock in product['stocks'] as List? ?? []) {
        if ('${stock['id']}' == '$id') return Map<String, dynamic>.from(stock);
      }
    }
    return null;
  }

  void _tradeEffect(String resource, JsonMap record, int sign, bool validate) {
    final lines = (record['details'] ?? record['products']) as List? ?? [];
    for (final raw in lines) {
      final line = Map<String, dynamic>.from(raw as Map);
      final product = find('products', line['product_id']);
      if (product == null) throw const OfflineException('The product is missing from the offline download.');
      if (resource == 'sales' && product['product_type'] == 'combo') {
        for (final component in product['combo_products'] as List? ?? []) {
          changeStock(
            component['stock_id'],
            sign * number(line['quantities']) * number(component['quantity']),
            validate: validate,
          );
        }
        continue;
      }
      final stocks = product['stocks'] as List? ?? [];
      final index = stocks.indexWhere((s) => '${s['id']}' == '${line['stock_id']}');
      if (index < 0) throw const OfflineException('The stock batch is missing from the offline download.');
      final stock = Map<String, dynamic>.from(stocks[index]);
      final qty = number(line['quantities']);
      if (validate && (qty <= 0 || number(stock['productStock']) + sign * qty < 0)) {
        throw const OfflineException('There is not enough stock saved on this device.');
      }
      stock['productStock'] = number(stock['productStock']) + sign * qty;
      final serials = List<String>.from(line['serial_numbers'] as List? ?? []);
      if (serials.isNotEmpty) {
        final available = List<String>.from(stock['serial_numbers'] as List? ?? []);
        if (validate && sign < 0 && serials.any((s) => !available.contains(s)))
          throw const OfflineException('One or more serial numbers are no longer available on this device.');
        stock['serial_numbers'] = sign < 0
            ? available.where((s) => !serials.contains(s)).toList()
            : {...available, ...serials}.toList();
      }
      if (resource == 'purchase' && sign > 0) {
        for (final field in [
          'productPurchasePrice',
          'productSalePrice',
          'productWholeSalePrice',
          'productDealerPrice',
          'profit_percent',
          'expire_date',
          'mfg_date',
          'warehouse_id',
        ]) {
          if (line.containsKey(field)) stock[field] = line[field];
        }
        final rate = number(product['vat']?['rate']);
        stock['exclusive_price'] = number(stock['productPurchasePrice']) / (1 + rate / 100);
      }
      stocks[index] = stock;
      product['stocks'] = stocks;
      product['productStock'] = stocks.fold<num>(0, (sum, s) => sum + number(s['productStock']));
      product['total_stock'] = product['productStock'];
      product['stocks_sum_product_stock'] = product['productStock'];
      upsert('products', product);
    }
    final party = find('parties', record['party_id']);
    if (party != null) {
      final due = number(party['due']) + (resource == 'sales' ? -sign : sign) * number(record['dueAmount']);
      if (validate && number(party['credit_limit']) > 0 && due > number(party['credit_limit'])) {
        throw const OfflineException('This sale exceeds the customer credit limit.');
      }
      party['due'] = due;
      upsert('parties', party);
    }
    _payments(record, -sign, reverseWallet: resource == 'sales' ? sign > 0 : sign < 0);
  }

  List<JsonMap> _transactionRows(JsonMap record, String type) {
    final payments = decoded(record['payments']);
    if (payments is! List) return [];
    return payments.map<JsonMap>((raw) {
      final payment = Map<String, dynamic>.from(raw as Map);
      final bank = find('banks', payment['payment_type_id'] ?? payment['id'] ?? payment['type']);
      final method = '${payment['type'] ?? 'cash'}'.toLowerCase();
      return {
        ...payment,
        'id': record['id'],
        'amount': number(payment['amount']),
        'type': method == 'cheque' ? 'pending' : type,
        'meta': method == 'cheque' ? {'cheque_number': payment['cheque_number']} : null,
        'transaction_type': bank != null ? 'bank_payment' : '${method}_payment',
        'payment_type_id': bank?['id'],
        'payment_type': bank ?? {'name': payment['type'] ?? 'Cash'},
        'date': record['saleDate'] ?? record['purchaseDate'] ?? record['date'],
      };
    }).toList();
  }

  void _payments(JsonMap record, int sign, {bool reverseWallet = false}) {
    final payments =
        decoded(record['payments']) ??
        (record['transactions'] as List?)
            ?.map(
              (t) => {
                'type': t['payment_type_id'] ?? '${t['transaction_type'] ?? ''}'.replaceAll('_payment', ''),
                'amount': t['amount'],
              },
            )
            .toList();
    if (payments is! List) return;
    for (final raw in payments) {
      final p = raw as Map;
      if (number(p['amount']) < 0) throw const OfflineException('Payment amounts cannot be negative.');
      if ('${p['type']}'.toLowerCase() == 'cheque') continue;
      if ('${p['type']}'.toLowerCase() == 'wallet') {
        final party = find('parties', record['party_id']);
        if (party == null || (!reverseWallet && number(party['wallet']) < number(p['amount']))) {
          throw const OfflineException('There is not enough balance in this customer/supplier wallet.');
        }
        party['wallet'] = number(party['wallet']) + (reverseWallet ? 1 : -1) * number(p['amount']);
        upsert('parties', party);
        continue;
      }
      final bank = find('banks', p['payment_type_id'] ?? p['id'] ?? p['type']);
      if (bank == null && '${p['type']}'.toLowerCase() != 'cash') {
        throw const OfflineException('Download the selected payment account before using it offline.');
      }
      if (bank != null) {
        bank['balance'] = number(bank['balance']) + sign * number(p['amount']);
        upsert('banks', bank);
        upsert('payment-types', bank);
      }
    }
    final business = data['business'];
    if (business is Map && business['data'] is Map) {
      final amount = record['paidAmount'] != null ? number(record['paidAmount']) : _received(record);
      business['data']['remainingShopBalance'] = number(business['data']['remainingShopBalance']) + sign * amount;
    }
  }

  void _cashTransfer(String resource, JsonMap? old, JsonMap? record, bool validate) {
    void effect(JsonMap value, int sign) {
      final amount = number(value['amount']);
      if (validate && amount <= 0) throw const OfflineException('Enter an amount greater than zero.');
      final from = value['from'] ?? value['from_bank'];
      final to = value['to'] ?? value['to_bank'];
      final kind = value['transaction_type'];
      if (validate && kind == 'bank_to_bank' && (from == null || to == null || '$from' == '$to')) {
        throw const OfflineException('Choose two different bank accounts.');
      }
      final changes = <dynamic, int>{};
      if (kind == 'bank_to_bank' || kind == 'bank_to_cash') changes[from] = -1;
      if (kind == 'bank_to_bank' || kind == 'cash_to_bank') changes[to] = 1;
      if (kind == 'adjust_bank') changes[from] = value['type'] == 'credit' ? 1 : -1;
      for (final entry in changes.entries) {
        final bank = find('banks', entry.key);
        if (bank == null) throw const OfflineException('Download the selected bank account first.');
        if (validate && number(bank['balance']) + sign * entry.value * amount < 0) {
          throw const OfflineException('There is not enough balance in the source bank account.');
        }
        bank['balance'] = number(bank['balance']) + sign * entry.value * amount;
        upsert('banks', bank);
        upsert('payment-types', bank);
      }
    }

    if (old != null) effect(old, -1);
    if (record != null) effect(record, 1);
    if (old != null) remove('transactions', old['id']);
    if (record != null) {
      record['platform'] = resource == 'cashes' ? 'cash' : 'bank';
      record['from_bank'] = int.tryParse('${record['from']}');
      record['to_bank'] = int.tryParse('${record['to']}');
      if (!{'adjust_cash', 'adjust_bank'}.contains(record['transaction_type'])) record['type'] = 'transfer';
      upsert('transactions', record);
    }
    recomputeCashBalance();
  }

  dynamic read(String path, Map<String, String> query) {
    if (path == 'module-check' &&
        data['module-checks'] is Map &&
        (data['module-checks'] as Map).containsKey(query['module_name']))
      return {'status': data['module-checks'][query['module_name']]};
    if (path == 'cashes' || path == 'bank-transactions') {
      final transactions = rows('transactions');
      setRows(
        path,
        path == 'cashes'
            ? transactions
                  .where(
                    (r) => {
                      'cash_payment',
                      'bank_to_cash',
                      'cash_to_bank',
                      'adjust_cash',
                      'cheque_to_cash',
                    }.contains(r['transaction_type']),
                  )
                  .toList()
            : transactions,
      );
      recomputeCashBalance();
    }
    if (path.startsWith('party-ledger/')) return partyLedger(path.split('/').last, query);
    if (path.startsWith('money-receipt/')) {
      final transaction = find('transactions', path.split('/').last);
      if (transaction == null) throw const OfflineException('Download this receipt before opening it offline.');
      return {'data': enrichTransaction(transaction)};
    }
    if (path == 'invoices') {
      final party = find('parties', query['party_id']);
      if (party == null) throw const OfflineException('This customer or supplier is not downloaded.');
      final supplier = party['type'] == 'Supplier';
      return {
        'data': {
          ...party,
          supplier ? 'purchases_dues' : 'sales_dues': rows(
            supplier ? 'purchase' : 'sales',
          ).where((inv) => '${inv['party_id']}' == '${party['id']}' && number(inv['dueAmount']) > 0).toList(),
        },
      };
    }
    if (path == 'invoice-wise-dues') {
      final invoices = rows(
        'sales',
      ).where((r) => (r['party_id'] == null || r['party_id'] == 0) && number(r['dueAmount']) > 0).toList();
      return {'data': invoices, 'total_receivable': invoices.fold<num>(0, (v, r) => v + number(r['dueAmount']))};
    }
    if (path == 'summary' || path == 'dashboard' || path.startsWith('reports/')) {
      return report(path, query);
    }
    final parts = path.split('/');
    final resource = parts.first == 'get-paginated-products' ? 'products' : parts.first;
    if (resource == 'new-invoice' || path == 'product/generate-code') {
      throw const OfflineException('Invoice numbers are allocated by the offline service.');
    }
    if (parts.length == 2 && int.tryParse(parts[1]) != null) {
      if (resource == 'parties') {
        throw const OfflineException('Sending a due reminder needs an internet connection.');
      }
      final record = find(resource, int.parse(parts[1]));
      if (record == null) throw const OfflineException('This record has not been downloaded yet.');
      return {'message': 'Saved data', 'data': record};
    }
    if (!data.containsKey(resource)) throw OfflineException('$resource has not been downloaded for offline use yet.');
    if (query.isEmpty && resource != 'transactions') return data[resource];
    final exact = Uri(
      path: path,
      queryParameters: Map.fromEntries(query.entries.toList()..sort((a, b) => a.key.compareTo(b.key))),
    ).toString();
    if (data.containsKey(exact)) return data[exact];
    if (rows(resource).isEmpty &&
        data[resource] is Map &&
        data[resource]['data'] is! List &&
        !(data[resource]['data'] is Map && data[resource]['data']['data'] is List)) {
      throw const OfflineException('This report/filter has not been downloaded for offline use.');
    }
    var values = rows(resource);
    const supported = {
      'duration',
      'from_date',
      'to_date',
      'search',
      'search_query',
      'page',
      'per_page',
      'limit',
      'no_paginate',
      'branch_id',
      'warehouse_id',
      'party_id',
      'category_id',
      'brand_id',
      'country_id',
      'type',
      'returned-sales',
      'returned-purchases',
      'party_type',
      'price_sort',
      'platform',
      'start_date',
      'end_date',
      'bank_id',
    };
    if (query.keys.any((key) => !supported.contains(key))) {
      throw const OfflineException('This filter needs a connection until its data is downloaded.');
    }
    if (resource == 'transactions') values = values.map(enrichTransaction).toList();
    if (query['bank_id']?.isNotEmpty ?? false)
      values = values
          .where((r) => [r['payment_type_id'], r['from_bank'], r['to_bank']].any((id) => '$id' == query['bank_id']))
          .toList();
    for (final key in [
      'branch_id',
      'warehouse_id',
      'party_id',
      'category_id',
      'brand_id',
      'country_id',
      'type',
      'platform',
    ]) {
      if (resource == 'transactions' && key == 'party_id') {
        if (query[key]?.isNotEmpty ?? false)
          values = values
              .where(
                (r) =>
                    '${r['sale']?['party_id'] ?? r['purchase']?['party_id'] ?? r['due_collect']?['party_id']}' ==
                    query[key],
              )
              .toList();
        continue;
      }
      if (query[key]?.isNotEmpty ?? false) values = values.where((r) => '${r[key]}' == query[key]).toList();
    }
    final search = (query['search'] ?? query['search_query'] ?? '').toLowerCase();
    if (search.isNotEmpty) values = values.where((r) => jsonEncode(r).toLowerCase().contains(search)).toList();
    if (resource == 'products') {
      final priceColumn = switch (query['party_type']) {
        'Dealer' => 'productDealerPrice',
        'Wholesaler' => 'productWholeSalePrice',
        'Supplier' => 'productPurchasePrice',
        _ => 'productSalePrice',
      };
      if (query['party_type']?.isNotEmpty ?? false) {
        values = values.where((r) => (r['stocks'] as List? ?? []).any((s) => number(s[priceColumn]) > 0)).toList();
      }
      if (query['price_sort']?.isNotEmpty ?? false) {
        num price(JsonMap product) {
          final stocks = product['stocks'] as List? ?? [];
          return stocks.isEmpty ? 0 : number(stocks.last[priceColumn]);
        }

        values.sort(
          (a, b) => query['price_sort'] == 'low_to_high' ? price(a).compareTo(price(b)) : price(b).compareTo(price(a)),
        );
      }
    }
    final now = DateTime.now();
    DateTime? from = DateTime.tryParse(query['from_date'] ?? query['start_date'] ?? '');
    DateTime? to = DateTime.tryParse(query['to_date'] ?? query['end_date'] ?? '');
    switch (query['duration']) {
      case 'today':
        from = DateTime(now.year, now.month, now.day);
        to = from;
      case 'yesterday':
        from = DateTime(now.year, now.month, now.day - 1);
        to = from;
      case 'this_month':
      case 'current_month':
        from = DateTime(now.year, now.month);
        to = DateTime(now.year, now.month + 1, 0);
      case 'last_month':
        from = DateTime(now.year, now.month - 1);
        to = DateTime(now.year, now.month, 0);
      case 'this_year':
      case 'current_year':
        from = DateTime(now.year);
        to = DateTime(now.year, 12, 31);
      case 'last_seven_days':
        from = DateTime(now.year, now.month, now.day - 6);
        to = now;
      case 'last_thirty_days':
        from = DateTime(now.year, now.month, now.day - 29);
        to = now;
      case 'this_week':
        from = DateTime(now.year, now.month, now.day - now.weekday + 1);
        to = now;
      case null:
      case 'all':
      case 'custom_date':
        break;
      default:
        throw const OfflineException('This date filter is not available offline yet.');
    }
    if (from != null || to != null) {
      values = values.where((r) {
        final date = DateTime.tryParse(
          '${r['saleDate'] ?? r['purchaseDate'] ?? r['expenseDate'] ?? r['incomeDate'] ?? r['paymentDate'] ?? r['return_date'] ?? r['date'] ?? r['created_at']}',
        );
        return date != null &&
            (from == null || !date.isBefore(from)) &&
            (to == null || date.isBefore(DateTime(to.year, to.month, to.day + 1)));
      }).toList();
    }
    if (query['returned-sales'] == 'true') {
      values = values.where((r) => (r['sale_returns'] as List? ?? []).isNotEmpty).toList();
    }
    final body = jsonDecode(jsonEncode(data[resource])) as JsonMap;
    if (query['returned-purchases'] == 'true')
      values = values.where((r) => (r['purchase_returns'] as List? ?? []).isNotEmpty).toList();
    if (body['data'] is Map && body['data']['data'] is List || parts.first == 'get-paginated-products') {
      final limit = int.tryParse(query['per_page'] ?? query['limit'] ?? '') ?? 20;
      final page = int.tryParse(query['page'] ?? '') ?? 1;
      final pageSize = limit.clamp(1, 10000);
      final start = (page - 1).clamp(0, 1000000) * pageSize;
      body['data'] = {
        'data': values.skip(start).take(pageSize).toList(),
        'total': values.length,
        'current_page': page,
        'last_page': (values.length / pageSize).ceil().clamp(1, 1000000),
        'per_page': pageSize,
      };
    } else {
      body['data'] = values;
    }
    body['total_amount'] = values.fold<num>(0, (v, r) => v + number(r['totalAmount'] ?? r['amount']));
    if (resource == 'transactions') {
      body['money_in'] = values.where((r) => r['type'] == 'credit').fold<num>(0, (sum, r) => sum + number(r['amount']));
      body['money_out'] = values.where((r) => r['type'] == 'debit').fold<num>(0, (sum, r) => sum + number(r['amount']));
    }
    return body;
  }
}
