import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../../Const/api_config.dart';

class RemoteAppSettings {
  final String appName;
  final String poweredBy;
  final String companyWebsite;
  final String? splashLogoUrl;
  final String? appLogoUrl;
  final Color splashBgColor;
  final Color primaryColor;
  final String appVersion;
  final String supportEmail;
  final String supportPhone;

  RemoteAppSettings({
    this.appName = 'Business Manager',
    this.poweredBy = 'Business Manager',
    this.companyWebsite = 'https://hassanagro.com',
    this.splashLogoUrl,
    this.appLogoUrl,
    this.splashBgColor = const Color(0xffC52127),
    this.primaryColor = const Color(0xffC52127),
    this.appVersion = '1.0.0',
    this.supportEmail = '',
    this.supportPhone = '',
  });

  factory RemoteAppSettings.fromJson(Map<String, dynamic> json) {
    Color parseColor(String? hexString, Color defaultColor) {
      if (hexString == null || hexString.isEmpty) return defaultColor;
      try {
        final buffer = StringBuffer();
        if (hexString.length == 6 || hexString.length == 7) buffer.write('ff');
        buffer.write(hexString.replaceFirst('#', ''));
        return Color(int.parse(buffer.toString(), radix: 16));
      } catch (_) {
        return defaultColor;
      }
    }

    return RemoteAppSettings(
      appName: json['app_name'] ?? 'Business Manager',
      poweredBy: json['powered_by'] ?? 'Business Manager',
      companyWebsite: json['company_website'] ?? 'https://hassanagro.com',
      splashLogoUrl: json['splash_logo_url'],
      appLogoUrl: json['app_logo_url'],
      splashBgColor: parseColor(json['splash_bg_color'], const Color(0xffC52127)),
      primaryColor: parseColor(json['primary_color'], const Color(0xffC52127)),
      appVersion: json['app_version'] ?? '1.0.0',
      supportEmail: json['support_email'] ?? '',
      supportPhone: json['support_phone'] ?? '',
    );
  }
}

class AppSettingsRepository {
  static const String _cacheKey = 'cached_remote_app_settings';

  Future<RemoteAppSettings> getLocalSettings() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final cachedJson = prefs.getString(_cacheKey);
      if (cachedJson != null) {
        return RemoteAppSettings.fromJson(jsonDecode(cachedJson));
      }
    } catch (_) {}
    return RemoteAppSettings();
  }

  Future<RemoteAppSettings> fetchRemoteSettings() async {
    try {
      final uri = Uri.parse('${APIConfig.url}/app-settings');
      final response = await http.get(uri).timeout(const Duration(seconds: 4));
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        if (body['success'] == true && body['data'] != null) {
          final prefs = await SharedPreferences.getInstance();
          await prefs.setString(_cacheKey, jsonEncode(body['data']));
          return RemoteAppSettings.fromJson(body['data']);
        }
      }
    } catch (_) {}
    return getLocalSettings();
  }
}
