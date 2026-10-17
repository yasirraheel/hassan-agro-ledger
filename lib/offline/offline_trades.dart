part of 'offline_projection.dart';

extension OfflineTrades on OfflineProjection {
  void changeStock(dynamic id, num delta, {bool validate = false, List<String> serials = const []}) {
    for (final product in rows('products')) {
      final stocks = product['stocks'] as List? ?? [];
      final index = stocks.indexWhere((s) => '${s['id']}' == '$id');
      if (index < 0) continue;
      final stock = Map<String, dynamic>.from(stocks[index]);
      if (validate && OfflineProjection.number(stock['productStock']) + delta < 0) {
        throw const OfflineException('There is not enough stock available for this operation.');
      }
      final available = List<String>.from(stock['serial_numbers'] as List? ?? []);
      if (validate && delta < 0 && serials.any((s) => !available.contains(s)))
        throw const OfflineException('The selected serial numbers are not available.');
      if (serials.isNotEmpty)
        stock['serial_numbers'] = delta < 0
            ? available.where((s) => !serials.contains(s)).toList()
            : {...available, ...serials}.toList();
      stock['productStock'] = OfflineProjection.number(stock['productStock']) + delta;
      stocks[index] = stock;
      product['stocks'] = stocks;
      product['productStock'] = stocks.fold<num>(0, (sum, s) => sum + OfflineProjection.number(s['productStock']));
      product['stocks_sum_product_stock'] = product['productStock'];
      upsert('products', product);
      return;
    }
    throw const OfflineException('The required stock batch has not been downloaded.');
  }

  void replaceTradeTaxes(String resource, JsonMap? old, JsonMap? record) {
    final type = resource == 'sales' ? 'App\\Models\\SaleDetails' : 'App\\Models\\PurchaseDetails';
    final oldIds = (old?['details'] as List? ?? []).map((d) => '${d['id']}').toSet();
    final taxes = rows(
      'tax-transactions',
    ).where((t) => !(t['vatable_type'] == type && oldIds.contains('${t['vatable_id']}'))).toList();
    var sequence = 0;
    for (final detail in record?['details'] as List? ?? []) {
      for (final tax in detail['_vat_transactions'] as List? ?? []) {
        taxes.add({
          ...Map<String, dynamic>.from(tax),
          'id': -5000000000000 + OfflineProjection.number(record!['_offline_local_id']).toInt() * 1000 - sequence++,
          'vatable_type': type,
          'vatable_id': detail['id'],
        });
      }
    }
    setRows('tax-transactions', taxes);
  }

  void refreshComboStocks() {
    for (final product in rows('products').where((p) => p['product_type'] == 'combo')) {
      num? count;
      for (final component in product['combo_products'] as List? ?? []) {
        final stock = _stock(component['stock_id']);
        final qty = OfflineProjection.number(component['quantity']);
        if (stock == null || qty <= 0) {
          count = 0;
          continue;
        }
        component['stock'] = {...stock, 'product': find('products', stock['product_id'])};
        final available = OfflineProjection.number(stock['productStock']) ~/ qty;
        count = count == null || available < count ? available : count;
      }
      product['productStock'] = count ?? 0;
      upsert('products', product);
    }
  }

  void applyReturn(JsonMap record, {required bool purchase, required bool validate}) {
    final resource = purchase ? 'purchase' : 'sales';
    final tradeKey = purchase ? 'purchase' : 'sale';
    final detailKey = purchase ? 'purchase_detail_id' : 'sale_detail_id';
    final invoice = find(resource, record['${tradeKey}_id']);
    if (invoice == null) throw const OfflineException('Download the original invoice before returning products.');
    record['party_id'] = invoice['party_id'];
    final requests = record['products'] as List? ?? [];
    if (requests.isEmpty) throw const OfflineException('Select at least one product to return.');
    final details = invoice['details'] as List? ?? [];
    final gross = details.fold<num>(
      0,
      (sum, d) =>
          sum +
          OfflineProjection.number(d[purchase ? 'productPurchasePrice' : 'price']) *
              OfflineProjection.number(d['quantities']),
    );
    final quantity = details.fold<num>(0, (sum, d) => sum + OfflineProjection.number(d['quantities']));
    final discountFactor = gross > 0 ? OfflineProjection.number(invoice['discountAmount']) / gross : 0;
    final roundingUnit = quantity > 0 ? OfflineProjection.number(invoice['rounding_amount']) / quantity : 0;
    num total = 0, discount = 0, profitAdjustment = 0;
    final returned = <JsonMap>[];
    final taxes = rows('tax-transactions');
    var index = 0;
    for (final request in requests) {
      final detail = details.cast<Map>().where((d) => '${d['id']}' == '${request[detailKey]}').firstOrNull;
      if (detail == null) throw const OfflineException('The selected invoice line is not available.');
      final count = OfflineProjection.number(request['return_qty']);
      final oldQty = OfflineProjection.number(detail['quantities']);
      if (count <= 0 || validate && count > oldQty)
        throw const OfflineException('Return quantity exceeds the remaining invoice quantity.');
      final price = OfflineProjection.number(detail[purchase ? 'productPurchasePrice' : 'price']);
      final lineDiscount = price * discountFactor * count;
      final amount = (price - price * discountFactor + (purchase ? 0 : roundingUnit)) * count;
      total += amount;
      discount += lineDiscount;
      final profit = oldQty > 0 ? OfflineProjection.number(detail['lossProfit']) * count / oldQty : 0;
      profitAdjustment += profit;
      final product = find('products', detail['product_id']);
      final serials = List<String>.from(request['serial_numbers'] as List? ?? []);
      if (validate && product?['has_serial'] == 1 && serials.length != count)
        throw const OfflineException('Select one serial number for each returned item.');
      if (product?['product_type'] == 'combo' && !purchase) {
        for (final component in product?['combo_products'] as List? ?? []) {
          changeStock(
            component['stock_id'],
            OfflineProjection.number(component['quantity']) * count,
            validate: validate,
          );
        }
      } else {
        final soldSerials = List<String>.from(detail['serial_numbers'] as List? ?? []);
        if (validate && !purchase && serials.any((s) => !soldSerials.contains(s)))
          throw const OfflineException('Only serial numbers from this invoice can be returned.');
        changeStock(detail['stock_id'], purchase ? -count : count, validate: validate, serials: serials);
        if (serials.isNotEmpty) detail['serial_numbers'] = soldSerials.where((s) => !serials.contains(s)).toList();
      }
      final returnId = -6000000000000 + OfflineProjection.number(record['_offline_local_id']).toInt() * 1000 - index++;
      returned.add({
        'id': returnId,
        detailKey: detail['id'],
        'return_qty': count,
        'return_amount': amount,
        'serial_numbers': serials,
      });
      final originalType = purchase ? 'App\\Models\\PurchaseDetails' : 'App\\Models\\SaleDetails';
      final returnType = purchase ? 'App\\Models\\PurchaseReturnDetail' : 'App\\Models\\SaleReturnDetails';
      final previousReturns = rows(
        purchase ? 'purchases-return' : 'sales-return',
      ).expand((r) => r['details'] as List? ?? []).where((r) => '${r[detailKey]}' == '${detail['id']}');
      final originalQty =
          oldQty + previousReturns.fold<num>(0, (sum, r) => sum + OfflineProjection.number(r['return_qty']));
      for (final tax
          in taxes
              .where((t) => t['vatable_type'] == originalType && '${t['vatable_id']}' == '${detail['id']}')
              .toList()) {
        taxes.add({
          ...tax,
          'id': -4000000000000 + returnId - index,
          'vatable_id': returnId,
          'vatable_type': returnType,
          'vat_amount': originalQty > 0 ? OfflineProjection.number(tax['vat_amount']) * count / originalQty : 0,
        });
      }
      detail['quantities'] = oldQty - count;
      detail['lossProfit'] = OfflineProjection.number(detail['lossProfit']) - profit;
    }
    if (total <= 0) throw const OfflineException('The return amount must be greater than zero.');
    final party = find('parties', invoice['party_id']);
    final oldDue = OfflineProjection.number(invoice['dueAmount']);
    final oldPaid = OfflineProjection.number(invoice['paidAmount']);
    num positive(num value) => value < 0 ? 0 : value;
    if (purchase) {
      if (party != null) {
        final dueReduction = OfflineProjection.number(party['due']).clamp(0, total);
        party['due'] = OfflineProjection.number(party['due']) - dueReduction;
        party['wallet'] = positive(OfflineProjection.number(party['wallet']) - (total - dueReduction));
      }
      invoice['dueAmount'] = positive(oldDue - total);
      invoice['paidAmount'] = positive(oldPaid - total);
    } else {
      final dueReduction = oldDue.clamp(0, total);
      invoice['dueAmount'] = oldDue - dueReduction;
      final refundAfterDue = total - dueReduction;
      invoice['paidAmount'] = positive(oldPaid - refundAfterDue);
      final excess = positive(refundAfterDue - oldPaid);
      if (party != null) {
        party['due'] = positive(OfflineProjection.number(party['due']) - dueReduction);
        final furtherDue = OfflineProjection.number(party['due']).clamp(0, excess);
        party['due'] = OfflineProjection.number(party['due']) - furtherDue;
        party['wallet'] = OfflineProjection.number(party['wallet']) + excess - furtherDue;
      }
      invoice['lossProfit'] = OfflineProjection.number(invoice['lossProfit']) - profitAdjustment;
      invoice['actual_total_amount'] = positive(OfflineProjection.number(invoice['totalAmount']) - total);
    }
    invoice['totalAmount'] = positive(OfflineProjection.number(invoice['totalAmount']) - total);
    invoice['discountAmount'] = positive(OfflineProjection.number(invoice['discountAmount']) - discount);
    invoice['change_amount'] = 0;
    if (party != null) upsert('parties', party);
    record['details'] = returned;
    record['invoice_no'] = invoice['invoiceNumber'];
    record['return_date'] ??= record['created_at'];
    record['total_return_amount'] = total;
    record[tradeKey] = invoice;
    final payments = OfflineProjection.decoded(record['payments']) as List? ?? [];
    record['payments'] = [
      for (final p in payments) {...Map<String, dynamic>.from(p), 'amount': total},
    ];
    _payments(record, purchase ? 1 : -1);
    record['transactions'] = _transactionRows(record, purchase ? 'credit' : 'debit');
    _ledger(purchase ? 'purchase_return' : 'sale_return', record['id'], record);
    invoice[purchase ? 'purchase_returns' : 'sale_returns'] = [
      ...invoice[purchase ? 'purchase_returns' : 'sale_returns'] as List? ?? [],
      {'id': record['id'], 'details': returned, 'return_date': record['return_date']},
    ];
    upsert(resource, invoice);
    setRows('tax-transactions', taxes);
  }
}
