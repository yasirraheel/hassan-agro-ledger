// ignore_for_file: file_names

import 'dart:convert';
import 'dart:developer';

import 'package:http/http.dart' as http;

import '../../../Const/api_config.dart';
import '../../../http_client/customer_http_client_get.dart';
import '../Model/subscription_plan_model.dart';

class SubscriptionPlanRepo {
  final http.Client _client = http.Client();

  /// Fetch all subscription plans (New Model)
  Future<List<SubscriptionPlanModel>> fetchAllPlans() async {
    final uri = Uri.parse('${APIConfig.url}/plans');
    final clientGet = CustomHttpClientGet(client: _client);

    try {
      final response = await clientGet.get(url: uri);

      if (response.statusCode == 200) {
        final parsedData = jsonDecode(response.body) as Map<String, dynamic>;
        final planList = parsedData['data'] as List<dynamic>;

        return planList.map((plan) => SubscriptionPlanModel.fromJson(plan)).toList();
      } else {
        log('Error: Failed to fetch plans. Status Code: ${response.statusCode}');
        return [];
      }
    } catch (e) {
      log('Exception in fetchAllPlans: $e');
      throw Exception('Failed to fetch Plans: $e');
    }
  }
}
