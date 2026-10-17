part of 'offline_projection.dart';

extension OfflineBusiness on OfflineProjection {
  JsonMap applyBusinessSettings(Map<String, Object?> operation, {bool validate = false}) {
    final path = operation['path'] as String;
    final resource = path.split('/').first;
    final values = fields(Map<String, dynamic>.from(remap(jsonDecode(operation['fields'] as String))));
    final business = data['business'];
    if (business is! Map || business['data'] is! Map) {
      throw const OfflineException('Complete the initial business download before changing settings offline.');
    }
    final businessData = business['data'] as Map;
    final attachments = jsonDecode(operation['files'] as String? ?? '{}') as Map;
    values.addAll(attachments.map((k, v) => MapEntry('$k', v)));
    JsonMap result;
    switch (resource) {
      case 'lang':
        businessData['user']?['lang'] = values['lang'];
        if (data['profile']?['data'] is Map) data['profile']['data']['lang'] = values['lang'];
        result = {'lang': values['lang']};
      case 'business':
        if (path.split('/').length > 1 && path.split('/')[1] != '${businessData['id']}') {
          throw const OfflineException('This business is not available in the current account.');
        }
        result = {...Map<String, dynamic>.from(businessData), ...values};
        data['business'] = {'message': 'Saved on this device', 'data': result};
      case 'profile':
        final profile = data['profile'] is Map ? data['profile']['data'] : businessData['user'];
        result = {...?profile as Map<String, dynamic>?, ...values};
        result.remove('password');
        data['profile'] = {'message': 'Saved on this device', 'data': result};
        businessData['user'] = {...?businessData['user'] as Map<String, dynamic>?, ...result};
      case 'product-settings':
        final old = data['product-settings']?['data'] as Map? ?? {};
        result = {
          ...Map<String, dynamic>.from(old),
          'business_id': businessData['id'],
          'modules': {
            ...?old['modules'] as Map<String, dynamic>?,
            ...values.map((key, value) => MapEntry(key, '$value')),
          },
        };
        data['product-settings'] = {'data': result};
      case 'invoice-settings':
        result = {'invoice_size': values['invoice_size']};
        data['invoice-settings'] = result;
        businessData['invoice_size'] = values['invoice_size'];
      case 'currency-settings':
        result = {'currency_data': values['currency']};
        data['currency-settings'] = result;
      case 'currency-selection':
        final currency = find('currencies', values['currency_id']);
        if (currency == null)
          throw const OfflineException('Download the currency list before changing currency offline.');
        businessData['business_currency'] = currency;
        result = currency;
      default:
        throw const OfflineException('Unknown settings action.');
    }
    result['_pending_sync'] = true;
    return {'message': 'Saved on this device. Waiting to sync.', 'data': result, 'offline': true};
  }

  void applyTaxDefinition(JsonMap vat, {bool deleting = false}) {
    List<JsonMap> selected(dynamic ids) {
      final list = OfflineProjection.decoded(ids);
      if (list is! List) return [];
      return list.map((id) => find('vats', id)).whereType<JsonMap>().toList();
    }

    final grouped = selected(vat['vat_ids']);
    final inner = selected(vat['inner_vat_ids']);
    final outer = selected(vat['outer_vat_ids']);
    vat['manage_state'] = OfflineProjection.number(vat['manage_state']).toInt();
    if (vat['manage_state'] == 1) {
      vat['inner_state_vats'] = [
        for (final child in inner) {'child_vat_id': child['id'], 'child_vat': child, 'type': 'inner'},
      ];
      vat['outer_state_vats'] = [
        for (final child in outer) {'child_vat_id': child['id'], 'child_vat': child, 'type': 'outer'},
      ];
      final totalInner = inner.fold<num>(0, (sum, row) => sum + OfflineProjection.number(row['rate']));
      vat['rate'] = totalInner != 0
          ? totalInner
          : outer.fold<num>(0, (sum, row) => sum + OfflineProjection.number(row['rate']));
      vat['sub_vat'] = null;
    } else if (grouped.isNotEmpty) {
      vat['sub_vat'] = [
        for (final row in grouped) {'id': row['id'], 'name': row['name'], 'rate': row['rate']},
      ];
      vat['rate'] = grouped.fold<num>(0, (sum, row) => sum + OfflineProjection.number(row['rate']));
    }
    vat['status'] = vat['status'] == true || vat['status'] == 1 || vat['status'] == '1';
    for (final group in rows('vats')) {
      if (group['sub_vat'] is List) {
        group['sub_vat'] = (group['sub_vat'] as List)
            .where((v) => !deleting || '${v['id']}' != '${vat['id']}')
            .map(
              (v) => '${v['id']}' == '${vat['id']}' ? {'id': vat['id'], 'name': vat['name'], 'rate': vat['rate']} : v,
            )
            .toList();
        group['rate'] = (group['sub_vat'] as List).fold<num>(
          0,
          (sum, row) => sum + OfflineProjection.number(row['rate']),
        );
        upsert('vats', group);
      }
    }
  }

  List<JsonMap> taxComponents(JsonMap product, JsonMap? party) {
    final vat = find('vats', product['vat_id']);
    if (vat == null) return [];
    if (OfflineProjection.number(vat['manage_state']) == 1) {
      final business = data['business']?['data'];
      final state = business?['user']?['active_branch']?['state_id'] ?? business?['state_id'];
      final type = party == null || '$state' == '${party['state_id']}' ? 'inner' : 'outer';
      return (vat['${type}_state_vats'] as List? ?? [])
          .map((row) => find('vats', row['child_vat_id']))
          .whereType<JsonMap>()
          .toList();
    }
    if (vat['sub_vat'] is List) return (vat['sub_vat'] as List).map((v) => Map<String, dynamic>.from(v)).toList();
    return [vat];
  }
}
