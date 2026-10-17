part of 'offline_projection.dart';

extension OfflineReports on OfflineProjection {
  List<JsonMap> _filtered(String resource, Map<String, String> query) {
    if (!data.containsKey(resource)) throw OfflineException('Download $resource to calculate this report offline.');
    final result = read(resource, query);
    final values = result['data'];
    return (values is Map ? values['data'] as List : values as List).map((r) => Map<String, dynamic>.from(r)).toList();
  }

  num _sum(List<JsonMap> values, String field) => values.fold<num>(0, (v, r) => v + OfflineProjection.number(r[field]));

  dynamic report(String path, Map<String, String> query) {
    final filters = Map<String, String>.from(query);
    if (path.startsWith('reports/')) filters['duration'] ??= 'today';
    if (path == 'reports/subscription') return {'data': _filtered('subscribes', filters)};
    if (path.contains('product-sale-history') || path.contains('product-purchase-history')) {
      return _productHistory(path, filters);
    }
    if (path == 'summary') {
      final date = query['date'] ?? DateTime.now().toIso8601String().substring(0, 10);
      filters
        ..remove('date')
        ..addAll({'duration': 'custom_date', 'from_date': date, 'to_date': date});
    }
    final sales = _filtered('sales', filters);
    final purchases = _filtered('purchase', filters);
    final incomes = _filtered('incomes', filters);
    final expenses = _filtered('expenses', filters);
    final profit = _sum(sales.where((r) => OfflineProjection.number(r['lossProfit']) >= 0).toList(), 'lossProfit');
    final loss = _sum(sales.where((r) => OfflineProjection.number(r['lossProfit']) < 0).toList(), 'lossProfit');
    if (path == 'summary') {
      return {
        'message': 'Calculated from saved data',
        'data': {
          'sales': _sum(sales, 'totalAmount'),
          'purchase': _sum(purchases, 'totalAmount'),
          'income': _sum(incomes, 'amount') + profit,
          'expense': _sum(expenses, 'amount'),
        },
      };
    }
    if (path == 'dashboard') {
      return {
        'message': 'Calculated from saved data',
        'data': {
          'total_items': rows('products').length,
          'total_categories': rows('categories').length,
          'total_income': _sum(incomes, 'amount') + profit,
          'total_expense': _sum(expenses, 'amount'),
          'total_due': _sum(sales, 'dueAmount'),
          'total_profit': profit,
          'total_loss': loss,
          'stock_value': _stockValue(),
          'sales': _chart(sales, 'saleDate', filters),
          'purchases': _chart(purchases, 'purchaseDate', filters),
        },
      };
    }
    if (path == 'reports/balance-sheet') {
      final products = rows('products').map((p) => {...p, 'source': 'product'}).toList();
      final banks = rows('banks').map((b) => {...b, 'source': 'bank'}).toList();
      return {
        'total_asset': _stockValue() + _sum(banks, 'balance'),
        'asset_datas': [...products, ...banks],
      };
    }
    if (path == 'reports/bill-wise-profit')
      return {
        'data': sales,
        'total_amount': _sum(sales, 'totalAmount'),
        'total_bill_profit': profit,
        'total_bill_loss': loss,
      };
    if (path == 'reports/loss-profit') {
      final incomeRows = <JsonMap>[];
      final expenseRows = <JsonMap>[];
      final groups = <String, JsonMap>{};
      void group(
        String type,
        List<JsonMap> values,
        String dateField,
        String amountField,
        String targetField,
        List<JsonMap> output,
      ) {
        for (final value in values) {
          final rawDate = '${value[dateField] ?? value['created_at'] ?? ''}';
          final date = rawDate.length >= 10 ? rawDate.substring(0, 10) : rawDate;
          final suffix = type == 'Sale'
              ? OfflineProjection.number(value['lossProfit']) < 0
                    ? ':negative'
                    : ':positive'
              : '';
          final key = '$type:$date$suffix';
          final row = groups.putIfAbsent(key, () {
            final r = <String, dynamic>{
              'type': type,
              'date': date,
              targetField: 0,
              if (type == 'Sale') 'profit_type': suffix.substring(1),
              if (type == 'Sale') 'total_sales': 0,
            };
            output.add(r);
            return r;
          });
          row[targetField] = OfflineProjection.number(row[targetField]) + OfflineProjection.number(value[amountField]);
          if (type == 'Sale')
            row['total_sales'] =
                OfflineProjection.number(row['total_sales']) +
                OfflineProjection.number(value['actual_total_amount'] ?? value['totalAmount']);
        }
      }

      group('Sale', sales, 'saleDate', 'lossProfit', 'total_incomes', incomeRows);
      group('Income', incomes, 'incomeDate', 'amount', 'total_incomes', incomeRows);
      group('Expense', expenses, 'expenseDate', 'amount', 'total_expenses', expenseRows);
      if (data.containsKey('payrolls'))
        group('Payroll', _filtered('payrolls', filters), 'date', 'amount', 'total_expenses', expenseRows);
      incomeRows.sort((a, b) => '${a['date']}'.compareTo('${b['date']}'));
      expenseRows.sort((a, b) => '${a['date']}'.compareTo('${b['date']}'));
      final grossIncome = _sum(incomeRows, 'total_incomes');
      final totalExpenses = _sum(expenseRows, 'total_expenses');
      final allProfit = _sum(rows('incomes'), 'amount') + _sum(rows('sales'), 'lossProfit');
      final allExpenses = _sum(rows('expenses'), 'amount') + _sum(rows('payrolls'), 'amount');
      return {
        'mergedIncomeSaleData': incomeRows,
        'mergedExpenseData': expenseRows,
        'grossSaleProfit': _sum(incomeRows, 'total_sales'),
        'grossIncomeProfit': grossIncome,
        'totalExpenses': totalExpenses,
        'netProfit': grossIncome - totalExpenses,
        'cardGrossProfit': allProfit,
        'totalCardExpenses': allExpenses,
        'cardNetProfit': allProfit - allExpenses,
      };
    }
    if (path == 'reports/cashflow') {
      final transactions = _filtered(
        'transactions',
        filters,
      ).where((t) => t['type'] == 'credit' || t['type'] == 'debit').toList();
      final all = rows('transactions');
      final cashIn = _sum(all.where((t) => t['type'] == 'credit').toList(), 'amount');
      final cashOut = _sum(all.where((t) => t['type'] == 'debit').toList(), 'amount');
      return {
        'cash_in': cashIn,
        'cash_out': cashOut,
        'running_cash': cashIn - cashOut,
        'initial_running_cash': 0,
        'data': transactions,
      };
    }
    if (path == 'reports/tax') return _taxReport(sales, purchases);
    throw OfflineException('Unknown report: $path');
  }

  JsonMap _productHistory(String path, Map<String, String> query) {
    final saleMode = path.contains('product-sale-history');
    final parts = path.split('/');
    final productId = parts.length > 2 ? parts.last : null;
    final selected = _filtered(saleMode ? 'sales' : 'purchase', query);
    final selectedIds = selected.map((r) => '${r['id']}').toSet();
    final result = <JsonMap>[];
    for (final product in rows('products')) {
      if (productId != null && '${product['id']}' != '$productId') continue;
      final saleDetails = <JsonMap>[], purchaseDetails = <JsonMap>[];
      for (final entry in {'sales': saleDetails, 'purchase': purchaseDetails}.entries) {
        for (final invoice in rows(entry.key)) {
          final invoiceKey = entry.key == 'sales' ? 'sale' : 'purchase';
          for (final detail in invoice['details'] as List? ?? []) {
            if ('${detail['product_id']}' != '${product['id']}') continue;
            if (productId != null &&
                entry.key == (saleMode ? 'sales' : 'purchase') &&
                !selectedIds.contains('${invoice['id']}'))
              continue;
            entry.value.add({
              ...Map<String, dynamic>.from(detail),
              '${invoiceKey}_id': invoice['id'],
              invoiceKey: {
                'id': invoice['id'],
                'invoiceNumber': invoice['invoiceNumber'],
                'saleDate': invoice['saleDate'],
                'purchaseDate': invoice['purchaseDate'],
              },
            });
          }
        }
      }
      final relevant = saleMode ? saleDetails : purchaseDetails;
      if (productId == null && !relevant.any((d) => selectedIds.contains('${d[saleMode ? 'sale_id' : 'purchase_id']}')))
        continue;
      result.add({...product, 'sale_details': saleDetails, 'purchase_details': purchaseDetails});
    }
    if (productId != null) {
      if (result.isEmpty) throw const OfflineException('Download this product before viewing its history.');
      final product = result.single;
      final details = List<JsonMap>.from(product[saleMode ? 'sale_details' : 'purchase_details']);
      return {
        'data': product,
        'total_quantities': _sum(details, 'quantities'),
        'total_sale_price': _sum(details, 'price'),
        'total_purchase_price': _sum(details, 'productPurchasePrice'),
      };
    }
    return {
      'data': result,
      'total_purchase_qty': result.fold<num>(
        0,
        (v, p) => v + _sum(List<JsonMap>.from(p['purchase_details']), 'quantities'),
      ),
      'total_sale_qty': result.fold<num>(0, (v, p) => v + _sum(List<JsonMap>.from(p['sale_details']), 'quantities')),
    };
  }

  JsonMap _taxReport(List<JsonMap> sales, List<JsonMap> purchases) {
    final result = <String, dynamic>{
      'sales': sales,
      'purchases': purchases,
      'vats': rows('vats').where((v) => v['status'] == true || v['status'] == 1).toList(),
    };
    for (final purchase in [false, true]) {
      final root = purchase ? 'purchase' : 'sales';
      final invoiceRows = rows(root);
      final returns = rows(purchase ? 'purchases-return' : 'sales-return');
      for (final returned in [false, true]) {
        final type = purchase
            ? returned
                  ? 'App\\Models\\PurchaseReturnDetail'
                  : 'App\\Models\\PurchaseDetails'
            : returned
            ? 'App\\Models\\SaleReturnDetails'
            : 'App\\Models\\SaleDetails';
        final detailInvoice = <String, String>{};
        for (final invoice in returned ? returns : invoiceRows) {
          for (final detail in invoice['details'] as List? ?? []) {
            detailInvoice['${detail['id']}'] =
                '${returned ? invoice[purchase ? 'purchase_id' : 'sale_id'] : invoice['id']}';
          }
        }
        final totals = <String, num>{}, rowMap = <String, Map<String, num>>{};
        for (final tax in rows('tax-transactions').where((t) => t['vatable_type'] == type)) {
          final invoiceId = detailInvoice['${tax['vatable_id']}'];
          if (invoiceId == null) continue;
          final vat = '${tax['vat_id']}';
          final amount = OfflineProjection.number(tax['vat_amount']);
          totals[vat] = (totals[vat] ?? 0) + amount;
          final row = rowMap.putIfAbsent(invoiceId, () => <String, num>{});
          row[vat] = (row[vat] ?? 0) + amount;
        }
        final totalKey = purchase
            ? returned
                  ? 'purchaseReturnVatTotals'
                  : 'purchasesVatTotals'
            : returned
            ? 'salesReturnVatTotals'
            : 'salesVatTotals';
        final rowKey = purchase
            ? returned
                  ? 'purchaseReturnVatRowMap'
                  : 'purchaseVatRowMap'
            : returned
            ? 'saleReturnVatRowMap'
            : 'saleVatRowMap';
        result[totalKey] = totals;
        result[rowKey] = rowMap;
      }
    }
    return result;
  }

  num _stockValue() => rows('products').fold<num>(0, (total, p) {
    if (p['product_type'] == 'combo') {
      return total +
          (p['combo_products'] as List? ?? []).fold<num>(0, (value, c) {
            final qty = OfflineProjection.number(c['quantity']);
            return value +
                (qty > 0
                    ? OfflineProjection.number(c['stock']?['productStock']) /
                          qty *
                          OfflineProjection.number(c['purchase_price'])
                    : 0);
          });
    }
    return total +
        (p['stocks'] as List? ?? []).fold<num>(
          0,
          (value, s) =>
              value + OfflineProjection.number(s['productStock']) * OfflineProjection.number(s['productPurchasePrice']),
        );
  });

  List<JsonMap> _chart(List<JsonMap> values, String dateField, Map<String, String> query) {
    final now = DateTime.now();
    final duration = query['duration'];
    final hourly = duration == 'today' || duration == 'yesterday';
    final monthly = duration == 'current_year' || duration == 'this_year';
    final rolling = duration == 'last_seven_days' || duration == 'last_thirty_days';
    final rollingDays = duration == 'last_seven_days' ? 7 : 30;
    final totals = <String, num>{};
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    for (final value in values) {
      final date = DateTime.tryParse('${value[dateField] ?? value['created_at']}');
      if (date == null) continue;
      final key = hourly
          ? '${date.hour}'.padLeft(2, '0')
          : monthly
          ? months[date.month - 1]
          : rolling
          ? date.toIso8601String().substring(0, 10)
          : '${date.day}'.padLeft(2, '0');
      totals[key] = (totals[key] ?? 0) + OfflineProjection.number(value['totalAmount']);
    }
    if (hourly)
      return [
        for (var hour = 0; hour < 24; hour++)
          {'date': '$hour'.padLeft(2, '0'), 'amount': totals['$hour'.padLeft(2, '0')] ?? 0},
      ];
    if (monthly)
      return [
        for (final month in months) {'date': month, 'amount': totals[month] ?? 0},
      ];
    if (rolling)
      return [
        for (var index = rollingDays - 1; index >= 0; index--)
          {
            'date': DateTime(now.year, now.month, now.day - index).day.toString().padLeft(2, '0'),
            'amount': totals[DateTime(now.year, now.month, now.day - index).toIso8601String().substring(0, 10)] ?? 0,
          },
      ];
    final count = duration == 'last_seven_days'
        ? 7
        : duration == 'last_thirty_days'
        ? 30
        : DateTime(now.year, now.month + 1, 0).day;
    return [
      for (var day = 1; day <= count; day++)
        {'date': '$day'.padLeft(2, '0'), 'amount': totals['$day'.padLeft(2, '0')] ?? 0},
    ];
  }
}
