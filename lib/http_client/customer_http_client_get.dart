import 'dart:convert';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:http/http.dart' as http;

import '../Repository/constant_functions.dart';
import '../offline/offline_service.dart';

class CustomHttpClientGet {
  final http.Client client;

  CustomHttpClientGet({
    required this.client,
  });

  Future<http.Response> get({
    required Uri url,
    Map<String, String>? headers,
    bool? addContentTypeInHeader,
  }) async {
    final http.Response response = await OfflineService.instance.get(
      url,
      client: client,
      headers:
          headers ??
          {
            'Accept': 'application/json',
            'Authorization': await getAuthToken(),
            if (addContentTypeInHeader ?? false) 'Content-Type': 'application/json',
          },
    );

    if (response.statusCode == 401 || response.statusCode == 403) {
      EasyLoading.showError(
        response.statusCode == 401
            ? 'Sign in again to connect. Saved work is kept on this device.'
            : 'You do not have permission for this action.',
      );
      // Return a safe JSON response to prevent jsonDecode format exception
      return http.Response(jsonEncode({'error': 'Unauthorized or Session Expired'}), response.statusCode);
    }

    return response;
  }
}
