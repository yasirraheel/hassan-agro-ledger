import 'dart:convert';
import '../../../offline/offline_service.dart';

import 'package:http/http.dart' as http;

import '../../../Const/api_config.dart';
import '../../../http_client/customer_http_client_get.dart';
import '../Model/currency_model.dart';

class CurrencyRepo {
  Future<List<CurrencyModel>> fetchAllCurrency() async {
    try {
      CustomHttpClientGet clientGet = CustomHttpClientGet(client: http.Client());
      final uri = Uri.parse('${APIConfig.url}/currencies');

      final response = await clientGet.get(url: uri);

      if (response.statusCode == 200) {
        final parsedData = jsonDecode(response.body) as Map<String, dynamic>;

        final partyList = parsedData['data'] as List<dynamic>;

        return partyList
            .where((category) => category['status'] == true)
            .map((category) => CurrencyModel.fromJson(category))
            .toList();
      } else {
        final _body = jsonDecode(response.body);
        throw Exception(_body?['message']?.toString() ?? 'HTTP ${response.statusCode}');
      }
    } catch (e) {
      rethrow;
    }
  }

  // Future<List<CurrencyModel>> fetchAllCurrency() async {
  //   final uri = Uri.parse('${APIConfig.url}/currencies');
  //
  //   final response = await http.get(uri, headers: {
  //     'Accept': 'application/json',
  //     'Authorization': await getAuthToken(),
  //   });
  //
  //   if (response.statusCode == 200) {
  //     final parsedData = jsonDecode(response.body) as Map<String, dynamic>;
  //
  //     final partyList = parsedData['data'] as List<dynamic>;
  //     return partyList.map((category) => CurrencyModel.fromJson(category)).toList();
  //     // Parse into Party objects
  //   } else {
  //     throw Exception('Failed to fetch Currency');
  //   }
  // }

  Future<bool> setDefaultCurrency({required num id}) async {
    CustomHttpClientGet clientGet = CustomHttpClientGet(client: http.Client());
    final uri = Uri.parse('${APIConfig.url}/currency-selection');

    final response = await OfflineService.instance.write(uri, body: {'currency_id': id.toString()});

    if (response.statusCode == 200) {
      return true;
    } else {
      return false;
    }
  }
}
