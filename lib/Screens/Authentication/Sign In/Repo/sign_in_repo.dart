import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:http/http.dart' as http;

import '../../../../Const/api_config.dart';
import '../../../../Repository/constant_functions.dart';
import '../../../../currency.dart';
import '../../../SplashScreen/splash_screen.dart';
import '../../Sign Up/verify_email.dart';
import '../../profile_setup_screen.dart';

class LogInRepo {
  String _messageForResponse(http.Response response) {
    final body = response.body.toLowerCase();
    final contentType = (response.headers['content-type'] ?? '').toLowerCase();

    // The hosting CDN can return an HTML browser-verification page for the
    // API.  That page is not a Laravel response and jsonDecode would turn it
    // into the misleading "check your connection" message.
    if (response.statusCode == 403 &&
        (contentType.contains('text/html') ||
            body.contains('checking your browser') ||
            body.contains('hcdn-cgi') ||
            body.contains('just a moment'))) {
      return 'The login server is blocking mobile API requests with a browser check. Please ask the administrator to exempt /api/v1 from the hosting security challenge.';
    }

    try {
      final decoded = jsonDecode(response.body);
      if (decoded is Map && decoded['message'] is String) {
        return decoded['message'] as String;
      }
    } catch (_) {
      // Keep a useful status message for non-JSON server responses.
    }

    return 'Login failed (HTTP ${response.statusCode}). Please try again.';
  }

  Future<bool> logIn({
    required String email,
    required String password,
    required BuildContext context,
  }) async {
    final url = Uri.parse('${APIConfig.url}/sign-in');

    final body = {
      'email': email.trim(),
      'password': password,
    };
    final headers = {
      'Accept': 'application/json',
    };

    try {
      final response = await http.post(url, headers: headers, body: body).timeout(const Duration(seconds: 20));

      EasyLoading.dismiss();
      if (!context.mounted) return false;

      Map<String, dynamic>? responseData;
      try {
        final decoded = jsonDecode(response.body);
        if (decoded is Map<String, dynamic>) {
          responseData = decoded;
        }
      } catch (_) {
        // The response may be an HTML error page from the hosting layer.
      }

      if (response.statusCode == 200) {
        if (responseData == null || responseData['data'] is! Map) {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(_messageForResponse(response))));
          return false;
        }

        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(responseData['message']?.toString() ?? 'Login successful.')));

        final data = responseData['data'] as Map;
        final bool isSetupDone = data['is_setup'] == true;
        final token = data['token'];
        if (token is! String || token.isEmpty) {
          ScaffoldMessenger.of(
            context,
          ).showSnackBar(const SnackBar(content: Text('Login server returned an invalid session token.')));
          return false;
        }
        await saveUserData(token: token);
        try {
          await CurrencyMethods().saveCurrencyDataInLocalDatabase(
            selectedCurrencySymbol: data['currency']['symbol'],
            selectedCurrencyName: data['currency']['name'],
          );
        } catch (error) {
          print(error);
        }
        if (!isSetupDone) {
          Navigator.pushAndRemoveUntil(
            context,
            MaterialPageRoute(builder: (context) => const ProfileSetup()),
            (_) => false,
          );
        } else {
          Navigator.pushAndRemoveUntil(
            context,
            MaterialPageRoute(builder: (context) => const SplashScreen()),
            (_) => false,
          );
        }

        return true;
      } else if (response.statusCode == 201) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(responseData?['message']?.toString() ?? 'Please verify your email.')));
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => VerifyEmail(
              email: email,
              isFormForgotPass: false,
            ),
          ),
        );

        return true;
      } else {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(_messageForResponse(response))));
      }
    } catch (error) {
      EasyLoading.dismiss();
      if (context.mounted)
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Could not reach the login server. Check your connection and try again.')),
        );
    }

    return false;
  }
}
