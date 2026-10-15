import 'dart:convert';

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:http/http.dart' as http;
import 'package:intl/intl.dart';
import 'package:mobile_pos/Const/api_config.dart';
import 'package:mobile_pos/model/dashboard_overview_model.dart';
import 'package:mobile_pos/model/todays_summary_model.dart';

import '../../http_client/customer_http_client_get.dart';
import '../../http_client/subscription_expire_provider.dart';
import '../../model/business_info_model.dart';

class BusinessRepository {
  CustomHttpClientGet clientGet = CustomHttpClientGet(client: http.Client());
  Future<BusinessInformationModel> fetchBusinessData() async {
    try {
      final uri = Uri.parse('${APIConfig.url}/business');
      final response = await clientGet.get(url: uri);
      if (response.statusCode == 200) {
        print('---------body-${response.body}------------------');
        final parsedData = jsonDecode(response.body);
        return BusinessInformationModel.fromJson(parsedData);
      } else {
        final _body = jsonDecode(response.body);
        throw Exception(_body?['message']?.toString() ?? 'HTTP ${response.statusCode}');
      }
    } catch (e) {
      rethrow;
    }
  }

  Future<void> fetchSubscriptionExpireDate({required WidgetRef ref}) async {
    try {
      final uri = Uri.parse('${APIConfig.url}/business');
      final response = await clientGet.get(url: uri);
      if (response.statusCode == 200) {
        final parsedData = jsonDecode(response.body);
        final BusinessInformationModel businessInformation = BusinessInformationModel.fromJson(parsedData);
        ref.read(subscriptionProvider.notifier).updateSubscription(businessInformation.data?.willExpire);
      } else {
        final _body = jsonDecode(response.body);
        throw Exception(_body?['message']?.toString() ?? 'HTTP ${response.statusCode}');
      }
    } catch (e) {
      rethrow;
    }
  }

  Future<BusinessInformationModel?> checkBusinessData() async {
    CustomHttpClientGet clientGet = CustomHttpClientGet(client: http.Client());
    final uri = Uri.parse('${APIConfig.url}/business');

    final response = await clientGet.get(url: uri);
    if (response.statusCode == 200) {
      final parsedData = jsonDecode(response.body);
      return BusinessInformationModel.fromJson(parsedData); // Extract the "data" object from the response
    } else {
      return null;
    }
  }

  Future<TodaysSummaryModel> fetchTodaySummaryData() async {
    try {
      CustomHttpClientGet clientGet = CustomHttpClientGet(client: http.Client());
      String date = DateFormat('yyyy-MM-dd').format(DateTime.now());
      final uri = Uri.parse('${APIConfig.url}/summary?date=$date');
      final response = await clientGet.get(url: uri);
      print('------------dashboard------${response.statusCode}--------------');
      if (response.statusCode == 200) {
        print(response.body);
        return TodaysSummaryModel.fromJson(jsonDecode(response.body));
      } else {
        final _body = jsonDecode(response.body);
        throw Exception(_body?['message']?.toString() ?? 'HTTP ${response.statusCode}');
      }
    } catch (e) {
      rethrow;
    }
  }

  Future<DashboardOverviewModel> dashboardData(String type) async {
    try {
      CustomHttpClientGet clientGet = CustomHttpClientGet(client: http.Client());

      Uri uri;

      if (type.startsWith('custom_date&')) {
        final uriParams = Uri.splitQueryString(type.replaceFirst('custom_date&', ''));
        final fromDate = uriParams['from_date'];
        final toDate = uriParams['to_date'];

        uri = Uri.parse('${APIConfig.url}/dashboard?duration=custom_date&from_date=$fromDate&to_date=$toDate');
      } else {
        uri = Uri.parse('${APIConfig.url}/dashboard?duration=$type');
      }

      final response = await clientGet.get(url: uri);

      if (response.statusCode == 200) {
        return DashboardOverviewModel.fromJson(jsonDecode(response.body));
      } else {
        final _body = jsonDecode(response.body);
        throw Exception(_body?['message']?.toString() ?? 'HTTP ${response.statusCode}');
      }
    } catch (e) {
      rethrow;
    }
  }
}
