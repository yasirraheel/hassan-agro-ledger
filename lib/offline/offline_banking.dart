part of 'offline_projection.dart';

extension OfflineBanking on OfflineProjection {
  void recomputeCashBalance() {
    if (!data.containsKey('transactions')) return;
    num credits = 0, debits = 0;
    for (final transaction in rows('transactions')) {
      final amount = OfflineProjection.number(transaction['amount']);
      if (transaction['type'] == 'credit' || transaction['transaction_type'] == 'bank_to_cash') credits += amount;
      if (transaction['type'] == 'debit' || transaction['transaction_type'] == 'cash_to_bank') debits += amount;
    }
    if (data['cashes'] is Map) data['cashes']['total_balance'] = credits - debits;
    if (data['bank-transactions'] is Map)
      data['bank-transactions']['total_balance'] = rows(
        'banks',
      ).fold<num>(0, (v, b) => v + OfflineProjection.number(b['balance']));
  }

  JsonMap applyCheque(Map<String, Object?> operation, {bool validate = false}) {
    final path = operation['path'] as String;
    final reopen = path.startsWith('cheque-reopen/');
    final values = fields(Map<String, dynamic>.from(remap(jsonDecode(operation['fields'] as String))));
    final requestedId = reopen ? path.split('/').last : values['transaction_id'];
    final original = find('cheques', requestedId) ?? find('transactions', requestedId);
    if (original == null || original['transaction_type'] != 'cheque_payment')
      throw const OfflineException('Download the original cheque before changing it.');
    if (operation['method'] == 'DELETE')
      throw const OfflineException('Cheque deletion is not supported by the server.');
    if (validate && ((!reopen && original['type'] != 'pending') || (reopen && original['type'] == 'pending'))) {
      throw OfflineException(reopen ? 'This cheque is already pending.' : 'This cheque is already deposited.');
    }
    final amount = OfflineProjection.number(original['amount']);
    final deposits = rows('transactions')
        .where((t) => t['platform'] == 'cheque' && '${t['meta']?['source_transaction_id']}' == '${original['id']}')
        .toList();
    if (reopen && deposits.isEmpty)
      throw const OfflineException('Download the cheque deposit transaction before reopening it.');
    final deposit = deposits.isEmpty ? null : deposits.first;
    final paymentType = reopen ? (deposit?['payment_type_id']) : values['payment_type'];
    final bank = paymentType == null || '$paymentType'.toLowerCase() == 'cash' ? null : find('banks', paymentType);
    if (paymentType != null && '$paymentType'.toLowerCase() != 'cash' && bank == null)
      throw const OfflineException('Download the selected bank account first.');
    if (bank != null) {
      bank['balance'] = OfflineProjection.number(bank['balance']) + (reopen ? -amount : amount);
      upsert('banks', bank);
      upsert('payment-types', bank);
    }
    final invoice = original['platform'] == 'sale' ? find('sales', original['reference_id']) : null;
    if (invoice != null) {
      final oldPaid = OfflineProjection.number(invoice['paidAmount']);
      final oldDue = OfflineProjection.number(invoice['dueAmount']);
      invoice['paidAmount'] = (oldPaid + (reopen ? -amount : amount)).clamp(0, double.infinity);
      invoice['dueAmount'] = (oldDue + (reopen ? amount : -amount)).clamp(0, double.infinity);
      invoice['change_amount'] = reopen
          ? (OfflineProjection.number(invoice['change_amount']) - (amount - oldDue).clamp(0, double.infinity)).clamp(
              0,
              double.infinity,
            )
          : OfflineProjection.number(invoice['change_amount']) + (amount - oldDue).clamp(0, double.infinity);
      invoice['isPaid'] = OfflineProjection.number(invoice['dueAmount']) == 0;
      upsert('sales', invoice);
      final party = find('parties', invoice['party_id']);
      if (party != null) {
        party['due'] = (OfflineProjection.number(party['due']) + (reopen ? amount : -amount)).clamp(0, double.infinity);
        upsert('parties', party);
      }
    } else if (original['platform'] == 'income') {
      final income = find('incomes', original['reference_id']);
      if (income != null) {
        income['amount'] = OfflineProjection.number(income['amount']) + (reopen ? -amount : amount);
        upsert('incomes', income);
      }
    }
    final transaction = <String, dynamic>{
      'id': -7000000000000 + (operation['local_id'] as int) * 1000,
      '_offline_local_id': operation['local_id'],
      'type': reopen ? 'debit' : 'credit',
      'platform': 'cheque',
      'amount': amount,
      'date': values['date'] ?? operation['created_at'],
      'created_at': operation['created_at'],
      'payment_type_id': bank?['id'],
      'payment_type': bank,
      'transaction_type': reopen
          ? bank == null
                ? 'cash_to_cheque'
                : 'bank_to_cheque'
          : bank == null
          ? 'cheque_to_cash'
          : 'cheque_to_bank',
      'meta': reopen ? {'reverted_transaction_id': deposit?['id']} : {'source_transaction_id': original['id']},
    };
    transaction['id'] = ids[transaction['id']] ?? transaction['id'];
    upsert('transactions', transaction);
    original['type'] = reopen ? 'pending' : 'deposit';
    upsert('cheques', original);
    upsert('transactions', original);
    recomputeCashBalance();
    return {'message': 'Cheque change saved on this device. Waiting to sync.', 'data': original, 'offline': true};
  }
}
