part of 'offline_projection.dart';

extension OfflineLedger on OfflineProjection {
  JsonMap enrichTransaction(JsonMap value) {
    final record = Map<String, dynamic>.from(value);
    final resource = switch (record['platform']) {
      'sale' => 'sales',
      'purchase' => 'purchase',
      'due_collect' || 'due_pay' => 'dues',
      _ => null,
    };
    if (resource != null) {
      final invoice = find(resource, record['reference_id']);
      if (invoice != null) {
        final relation = resource == 'sales'
            ? 'sale'
            : resource == 'dues'
            ? 'due_collect'
            : 'purchase';
        record[relation] = {...invoice, 'party': find('parties', invoice['party_id'])};
        record['total_amount'] = invoice['totalAmount'] ?? invoice['totalDue'] ?? 0;
      }
    }
    record['payment_type'] = find('payment-types', record['payment_type_id']);
    return record;
  }

  JsonMap partyLedger(String id, Map<String, String> query) {
    final party = find('parties', id);
    if (party == null) throw const OfflineException('Download this customer or supplier before opening the ledger.');
    final customer = party['type'] != 'Supplier';
    final opening =
        OfflineProjection.number(party['opening_balance']) *
        (party['opening_balance_type'] == 'advance' ? -1 : 1) *
        (customer ? 1 : -1);
    final events = <JsonMap>[
      {
        'id': null,
        'date': party['created_at'],
        'created_at': party['created_at'],
        'invoice_no': null,
        'platform': 'Opening',
        'debit_amount': opening > 0 ? opening : 0,
        'credit_amount': opening < 0 ? -opening : 0,
      },
    ];
    final resource = customer ? 'sales' : 'purchase';
    for (final invoice in rows(resource).where((r) => '${r['party_id']}' == '${party['id']}')) {
      final payments = rows('transactions')
          .where(
            (t) =>
                t['platform'] == (customer ? 'sale' : 'purchase') &&
                '${t['reference_id']}' == '${invoice['id']}' &&
                t['transaction_type'] != 'wallet_payment',
          )
          .fold<num>(0, (sum, t) => sum + OfflineProjection.number(t['amount']));
      events.add({
        'id': invoice['id'],
        'date': invoice[customer ? 'saleDate' : 'purchaseDate'],
        'created_at': invoice['created_at'],
        'invoice_no': invoice['invoiceNumber'],
        'platform': customer ? 'Sales' : 'Purchase',
        'debit_amount': customer ? invoice['actual_total_amount'] ?? invoice['totalAmount'] : payments,
        'credit_amount': customer ? payments : invoice['totalAmount'],
      });
    }
    for (final due in rows('dues').where((r) => '${r['party_id']}' == '${party['id']}')) {
      final opening = due['sale_id'] == null && due['purchase_id'] == null;
      final amount = OfflineProjection.number(due['payDueAmount']);
      events.add({
        'id': due['id'],
        'date': due['paymentDate'],
        'created_at': due['created_at'],
        'invoice_no': due['invoiceNumber'],
        'platform': opening ? (customer ? 'Opening Collection' : 'Opening Payment') : 'Payment',
        'debit_amount': !customer && !opening ? amount : 0,
        'credit_amount': customer && !opening ? amount : 0,
        'amount': opening ? amount : 0,
      });
    }
    events.sort((a, b) => '${a['created_at'] ?? ''}'.compareTo('${b['created_at'] ?? ''}'));
    final temporary = OfflineProjection({
      'ledger-events': {'data': events},
    });
    final filtered = List<JsonMap>.from(temporary.read('ledger-events', query)['data']);
    num balance = 0;
    return {
      'data': [
        for (final event in filtered)
          {
            ...event,
            'amount': event['amount'] ?? 0,
            'balance': balance +=
                OfflineProjection.number(event['debit_amount']) - OfflineProjection.number(event['credit_amount']),
          },
      ],
    };
  }
}
