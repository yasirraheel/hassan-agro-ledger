import 'dart:convert';
import 'dart:io';
import 'dart:typed_data';

import 'package:cached_network_image/cached_network_image.dart';
import 'package:crypto/crypto.dart';
import 'package:flutter/painting.dart';
import 'package:http/http.dart' as http;
import 'package:path_provider/path_provider.dart';

import '../Const/api_config.dart';

/// Permanent business images live in app support storage, separate from an
/// evictable network-image cache. URLs in business records remain unchanged.
class OfflineMedia {
  static Directory? _directory;
  static Map<String, dynamic> _index = {};

  static Future<void> load(String scope) async {
    final root = await getApplicationSupportDirectory();
    _directory = Directory('${root.path}/offline_media/${sha256.convert(utf8.encode(scope))}');
    await _directory!.create(recursive: true);
    final file = File('${_directory!.path}/index.json');
    try {
      _index = jsonDecode(await file.readAsString()) as Map<String, dynamic>;
    } catch (_) {
      _index = {};
    }
  }

  static void clearActive() {
    _directory = null;
    _index = {};
  }

  static String normalize(String value) {
    if (value.startsWith('http://') ||
        value.startsWith('https://') ||
        value.startsWith('/data/') ||
        value.startsWith('/var/'))
      return value;
    return Uri.parse(APIConfig.domain).resolve(value).toString();
  }

  static File? file(String value) {
    if (value.startsWith('/') || RegExp(r'^[A-Za-z]:[\\/]').hasMatch(value)) {
      final local = File(value);
      if (local.existsSync()) return local;
    }
    final entry = _index[normalize(value)];
    if (entry is! Map) return null;
    final local = File('${entry['path']}');
    return local.existsSync() ? local : null;
  }

  static Future<Uint8List> bytes(String url) async {
    final saved = file(url);
    if (saved != null) return saved.readAsBytes();
    final response = await http.get(Uri.parse(normalize(url))).timeout(const Duration(seconds: 10));
    if (response.statusCode != 200) throw const HttpException('Image is unavailable.');
    return response.bodyBytes;
  }

  static Future<void> download(Map<String, dynamic> snapshot, http.Client client) async {
    if (_directory == null) return;
    final urls = <String, String>{};
    void collect(dynamic value, String version) {
      if (value is Map) {
        final updated = '${value['updated_at'] ?? version}';
        for (final entry in value.entries) {
          if (entry.value is String && RegExp(r'image|picture|logo', caseSensitive: false).hasMatch('${entry.key}')) {
            final raw = entry.value as String;
            if (raw.isNotEmpty && !raw.startsWith('/data/') && !raw.startsWith('images/')) {
              final normalized = normalize(raw);
              final uri = Uri.tryParse(normalized);
              if (uri != null && (uri.scheme == 'https' || uri.scheme == 'http')) urls[normalized] = updated;
            }
          } else {
            collect(entry.value, updated);
          }
        }
      } else if (value is List) {
        for (final item in value) {
          collect(item, version);
        }
      }
    }

    collect(snapshot, '');
    for (final entry in urls.entries) {
      final previous = _index[entry.key];
      if (previous is Map && previous['version'] == entry.value && file(entry.key) != null) continue;
      try {
        final response = await client.get(Uri.parse(entry.key)).timeout(const Duration(seconds: 10));
        if (response.statusCode != 200 ||
            response.bodyBytes.length > 10 * 1024 * 1024 ||
            !(response.headers['content-type'] ?? '').startsWith('image/'))
          continue;
        final name = sha256.convert(utf8.encode('${entry.key}|${entry.value}')).toString();
        final temporary = File('${_directory!.path}/$name.part');
        await temporary.writeAsBytes(response.bodyBytes, flush: true);
        final target = File('${_directory!.path}/$name.image');
        if (await target.exists()) await target.delete();
        await temporary.rename(target.path);
        _index[entry.key] = {'path': target.path, 'version': entry.value};
      } catch (_) {
        /* Missing artwork never discards downloaded business records. */
      }
    }
    final temporary = File('${_directory!.path}/index.part');
    await temporary.writeAsString(jsonEncode(_index), flush: true);
    final target = File('${_directory!.path}/index.json');
    if (await target.exists()) await target.delete();
    await temporary.rename(target.path);
  }
}

ImageProvider offlineImageProvider(String url) {
  final local = OfflineMedia.file(url);
  return local != null ? FileImage(local) : CachedNetworkImageProvider(OfflineMedia.normalize(url));
}
