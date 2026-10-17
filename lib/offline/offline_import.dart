part of 'offline_projection.dart';

extension OfflineImport on OfflineProjection {
  JsonMap applyProductImport(Map<String, Object?> operation, {required bool validate}) {
    final source = jsonDecode(operation['fields'] as String)['rows'] as List;
    if (source.length < 2) throw const OfflineException('The spreadsheet has no product rows.');
    if (source.length > 10001) throw const OfflineException('Import up to 10,000 products at a time.');
    var next = -4000000000000 + OfflineProjection.number(operation['local_id']).toInt() * 100000;
    int allocate() {
      final value = next--;
      return ids[value] ?? value;
    }

    int master(String resource, String field, dynamic value, [JsonMap extra = const {}]) {
      final name = '${value ?? ''}'.trim();
      final existing = rows(resource).where((r) => '${r[field]}' == name).firstOrNull;
      if (existing != null) return OfflineProjection.number(existing['id']).toInt();
      final id = allocate();
      upsert(resource, {'id': id, field: name, ...extra, 'status': 1, '_pending_sync': true});
      return id;
    }

    String? date(dynamic value) {
      if (value == null || '$value'.isEmpty) return null;
      if (value is num)
        return DateTime(1899, 12, 30).add(Duration(days: value.floor())).toIso8601String().substring(0, 10);
      return DateTime.tryParse('$value')?.toIso8601String().substring(0, 10);
    }

    var imported = 0;
    for (final raw in source.skip(1)) {
      final row = List<dynamic>.from(raw as List);
      while (row.length < 21) {
        row.add(null);
      }
      final name = '${row[0] ?? ''}'.trim(), code = '${row[5] ?? ''}'.trim();
      if (name.isEmpty || code.isEmpty || '${row[1] ?? ''}'.trim().isEmpty) continue;
      if (rows('products').any((r) => '${r['productCode']}' == code)) continue;
      final cost = OfflineProjection.number(row[6]), price = OfflineProjection.number(row[7]);
      final rate = OfflineProjection.number(row[11]);
      final vatType = row[12] ?? 'exclusive';
      final category = master('categories', 'categoryName', row[1]);
      final unit = master('units', 'unitName', row[2]);
      final brand = master('brands', 'brandName', row[3]);
      final vat = master('vats', 'name', row[10], {'rate': rate, 'status': true});
      final model = master('product-models', 'name', row[17]);
      final variation = <String, dynamic>{};
      for (final item in '${row[20] ?? ''}'.split('|')) {
        final pair = item.split(':');
        if (pair.length == 2) variation[pair[0].trim()] = pair[1].trim();
      }
      final variationIds = <int>[];
      for (final entry in variation.entries) {
        for (final v in rows('variations')) {
          final values = v['values'];
          if (v['name'] == entry.key && values is List && values.contains(entry.value))
            variationIds.add(OfflineProjection.number(v['id']).toInt());
        }
      }
      final product = <String, dynamic>{
        'id': allocate(),
        'productName': name,
        'productCode': code,
        'category_id': category,
        'unit_id': unit,
        'brand_id': brand,
        'model_id': model,
        'vat_id': vat,
        'vat_type': vatType,
        'vat_amount': cost * rate / 100,
        'alert_qty': OfflineProjection.number(row[13]),
        'manufacturer': row[14],
        'expire_date': date(row[15]),
        'product_type': row[19] ?? 'single',
        'variation_ids': variationIds,
        'has_serial': 0,
        'status': 1,
        'created_at': operation['created_at'],
        'updated_at': operation['created_at'],
        '_pending_sync': true,
        '_offline_local_id': operation['local_id'],
        'stocks': [
          {
            'id': allocate(),
            'batch_no': row[16],
            'expire_date': date(row[15]),
            'manufacturing_date': row[18],
            'productStock': OfflineProjection.number(row[4]),
            'productPurchasePrice': cost,
            'productSalePrice': price,
            'productDealerPrice': row[8] == null ? price : OfflineProjection.number(row[8]),
            'productWholeSalePrice': row[9] == null ? price : OfflineProjection.number(row[9]),
            'exclusive_price': vatType == 'inclusive'
                ? double.parse((cost / (1 + rate / 100)).toStringAsFixed(2))
                : cost,
            'profit_percent': cost == 0 ? 0 : double.parse(((price - cost) / cost * 100).toStringAsFixed(3)),
            'variant_name': variation.values.join(' - '),
            'variation_data': variation,
          },
        ],
      };
      _product(product, null, validate);
      _relations(product);
      upsert('products', product);
      imported++;
    }
    return {
      'message': '$imported products saved on this device. Waiting to sync.',
      'data': {'imported': imported},
      'offline': true,
    };
  }
}
