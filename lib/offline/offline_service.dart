import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'dart:math';

import 'package:crypto/crypto.dart';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;
import 'package:path_provider/path_provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../Const/api_config.dart';
import 'offline_projection.dart';
import 'offline_store.dart';
import 'offline_media.dart';
import 'offline_spreadsheet.dart';

enum ServerConnection { checking, connected, unavailable, signInRequired }

class OfflineService extends ChangeNotifier with WidgetsBindingObserver {
  OfflineService._();

  /// Uses the same sync code with a real temporary database and a simulated server.
  OfflineService.forTesting({required OfflineStore database, required http.Client Function() clientFactory}) {
    store = database;
    _initialized = true;
    _clientFactory = clientFactory;
    _cacheMedia = false;
  }
  static final instance = OfflineService._();
  late OfflineStore store;
  http.Client Function() _clientFactory = http.Client.new;
  bool _cacheMedia = true;
  final _secure = const FlutterSecureStorage();
  final _testSecrets = <String, String>{};
  final _revisionEvents = StreamController<int>.broadcast();
  Stream<int> get revisions => _revisionEvents.stream;
  int _revision = 0;
  StreamSubscription<List<ConnectivityResult>>? _connectivity;
  void _changedData() => _revisionEvents.add(++_revision);
  bool _initialized = false;
  bool _disposed = false;
  Future<void>? _syncing;
  Future<void> _writes = Future.value();
  Timer? _timer;
  String? _token;
  String? scope;
  bool ready = false;
  bool supported = false;
  bool downloading = false;
  double? progress;
  int pendingCount = 0;
  int conflictCount = 0;
  DateTime? lastSync;
  String status = 'Checking connection…';
  String? lastError;
  ServerConnection connection = ServerConnection.checking;
  List<String> _resources = [];

  bool get syncing => _syncing != null;
  bool get online => connection == ServerConnection.connected;
  bool get hasSession => _token != null && _token!.isNotEmpty;

  String _hash(String value) => sha256.convert(utf8.encode(value)).toString();
  String get _sessionKey => 'offline_scope_${_hash('${APIConfig.url}|$_token')}';

  Future<void> initialize() async {
    if (_initialized) return;
    store = await OfflineStore.open();
    _initialized = true;
    WidgetsBinding.instance.addObserver(this);
    _connectivity = Connectivity().onConnectivityChanged.listen((connections) {
      if (connections.contains(ConnectivityResult.none)) {
        connection = ServerConnection.unavailable;
        status = ready ? 'Offline — working with saved data' : 'No internet. Connect to prepare offline data.';
        notifyListeners();
      } else {
        unawaited(sync());
      }
    });
    _timer = Timer.periodic(const Duration(seconds: 45), (_) => unawaited(sync()));
    await restoreSession();
  }

  Future<void> restoreSession() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token');
    if (token == _token && scope != null) return;
    // A different login cannot inherit the previous user's memory or snapshots.
    _token = token;
    scope = token == null ? null : prefs.getString(_sessionKey);
    ready = false;
    supported = false;
    pendingCount = conflictCount = 0;
    lastSync = null;
    _resources = [];
    if (_cacheMedia) OfflineMedia.clearActive();
    if (scope != null) {
      if (_cacheMedia) await OfflineMedia.load(scope!);
      ready = await store.meta(scope!, 'ready') == 'true';
      supported = await store.meta(scope!, 'protocol') == '1';
      _resources = List<String>.from(jsonDecode(await store.meta(scope!, 'resources') ?? '[]'));
      lastSync = DateTime.tryParse(await store.meta(scope!, 'last_sync') ?? '');
      await _counts();
    }
    notifyListeners();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) unawaited(sync(force: true));
  }

  Map<String, String> get _headers => {
    'Accept': 'application/json',
    'Authorization': 'Bearer ${_token ?? ''}',
  };

  String _relative(Uri uri) {
    final base = Uri.parse(APIConfig.url);
    if (uri.origin != base.origin || !uri.path.startsWith('${base.path}/')) {
      throw const OfflineException('Only this business server can use offline storage.');
    }
    return uri.path.substring(base.path.length + 1);
  }

  String _cacheKey(String path, Map<String, String> query) {
    if (query.isEmpty) return path;
    return Uri(
      path: path,
      queryParameters: Map.fromEntries(query.entries.toList()..sort((a, b) => a.key.compareTo(b.key))),
    ).toString();
  }

  Future<bool> checkConnection() async {
    if (!_initialized) await initialize();
    await restoreSession();
    final token = _token;
    if (!hasSession) return false;
    final client = _clientFactory();
    try {
      final response = await client
          .get(Uri.parse('${APIConfig.url}/sync/status'), headers: _headers)
          .timeout(const Duration(seconds: 5));
      if (token != _token) return false;
      if (response.statusCode == 401) {
        connection = ServerConnection.signInRequired;
        status = 'Sign in again to sync. Saved work is kept on this device.';
        return false;
      }
      connection = ServerConnection.connected;
      if (response.statusCode != 200) {
        // Older servers remain usable online. Never queue without duplicate protection.
        supported = false;
        lastError = 'Server offline support is not installed (${response.statusCode}).';
        status = ready
            ? 'Saved data available; server sync needs an update.'
            : 'Connected. Offline setup needs a server update.';
        return false;
      }
      final manifest = jsonDecode(response.body) as JsonMap;
      supported = manifest['protocol'] == 1 && manifest['idempotency'] == true;
      final resolved = '${_hash(APIConfig.url).substring(0, 16)}:${manifest['scope']}';
      if (scope != null && resolved != scope) {
        // A server-side branch change cannot replay old-branch work in the new branch.
        if ((await store.operations(scope!)).isNotEmpty) {
          connection = ServerConnection.signInRequired;
          status = 'Switch back to the original business/branch to sync pending work.';
          return false;
        }
      }
      if (scope != resolved) {
        scope = resolved;
        if (_cacheMedia) await OfflineMedia.load(scope!);
        ready = await store.meta(scope!, 'ready') == 'true';
        lastSync = DateTime.tryParse(await store.meta(scope!, 'last_sync') ?? '');
      }
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(_sessionKey, scope!);
      _resources = List<String>.from(manifest['resources'] as List);
      await store.setMeta(scope!, 'protocol', supported ? '1' : '0');
      await store.setMeta(scope!, 'server_scope', '${manifest['scope']}');
      await store.setMeta(scope!, 'resources', jsonEncode(_resources));
      await store.setMeta(scope!, 'module_checks', jsonEncode(manifest['modules'] ?? {}));
      status = supported ? 'Connected to server' : 'Server sync migration is required.';
      lastError = supported ? null : status;
      return supported;
    } on TimeoutException {
      connection = ServerConnection.unavailable;
      status = ready ? 'Offline — working with saved data' : 'Server did not respond. Check your internet and retry.';
      return false;
    } on Object {
      connection = ServerConnection.unavailable;
      status = ready ? 'Offline — working with saved data' : 'No connection. Connect to download your business data.';
      return false;
    } finally {
      client.close();
      notifyListeners();
    }
  }

  Future<void> _counts() async {
    if (scope == null) return;
    final operations = await store.operations(scope!);
    pendingCount = operations.where((op) => op['state'] != 'accepted').length;
    conflictCount = operations.where((op) => op['state'] == 'conflict').length;
    notifyListeners();
  }

  Future<OfflineProjection> projection() async {
    final current = scope;
    if (current == null) throw const OfflineException('Sign in online once to prepare this account for offline use.');
    final projection = OfflineProjection(await store.snapshots(current), await store.mappings(current));
    for (final op in await store.operations(current)) {
      projection.apply(op);
    }
    return projection;
  }

  Future<http.Response> get(Uri url, {Map<String, String>? headers, http.Client? client}) async {
    if (!_initialized) await initialize();
    await restoreSession();
    final path = _relative(url);
    final sendsReminder = RegExp(r'^parties/-?\d+$').hasMatch(path);
    if (sendsReminder && !online) {
      throw const OfflineException('Connect to the internet to send a due reminder.');
    }
    if (path == 'new-invoice' || path == 'product/generate-code') {
      // A random device-specific reference is also submitted with the mutation.
      // It cannot collide with another phone's sequential invoice preview.
      final code = 'OFF-${DateTime.now().millisecondsSinceEpoch}-${_uuid().substring(0, 8).toUpperCase()}';
      return _response(path == 'new-invoice' ? code : {'data': code});
    }
    final current = scope;
    if (!sendsReminder && current != null && (ready || (await store.operations(current)).isNotEmpty)) {
      try {
        final result = (await projection()).read(path, url.queryParameters);
        return _response(result);
      } on OfflineException {
        if (!online || (await store.operations(current)).isNotEmpty) rethrow;
      }
    }
    final ownClient = client == null;
    final transport = client ?? _clientFactory();
    try {
      final response = await transport.get(url, headers: headers ?? _headers).timeout(const Duration(seconds: 12));
      if (!sendsReminder &&
          response.statusCode == 200 &&
          current != null &&
          !{'sign-out', 'refresh-token', 'update-expire-date'}.contains(path)) {
        try {
          jsonDecode(response.body);
          await store.cache(current, _cacheKey(path, url.queryParameters), response.body);
        } on FormatException {
          /* HTML responses are not valid snapshots. */
        }
      }
      return response;
    } on Object {
      if (!sendsReminder && current != null) {
        final cached = (await store.snapshots(current))[_cacheKey(path, url.queryParameters)];
        if (cached != null) return _response(cached);
      }
      rethrow;
    } finally {
      if (ownClient) transport.close();
    }
  }

  http.Response _response(dynamic body) =>
      http.Response(jsonEncode(body), 200, headers: {'content-type': 'application/json; charset=utf-8'});

  String _uuid() {
    final random = Random.secure();
    final bytes = List<int>.generate(16, (_) => random.nextInt(256));
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    final h = bytes.map((b) => b.toRadixString(16).padLeft(2, '0')).join();
    return '${h.substring(0, 8)}-${h.substring(8, 12)}-${h.substring(12, 16)}-${h.substring(16, 20)}-${h.substring(20)}';
  }

  Future<http.Response> write(
    Uri url, {
    String method = 'POST',
    Object? body,
    Map<String, String>? headers,
    Map<String, File> files = const {},
    bool multipart = false,
  }) {
    final completer = Completer<http.Response>();
    _writes = _writes.then((_) async {
      try {
        completer.complete(
          await _write(url, method: method, body: body, headers: headers, files: files, multipart: multipart),
        );
      } catch (error, stack) {
        completer.completeError(error, stack);
      }
    });
    return completer.future.then((response) async {
      dynamic result;
      try { result = jsonDecode(response.body); } catch (_) { return response; }
      if (result is! Map || result['_offline_operation'] == null) return response;
      final current = scope!;
      if (connection != ServerConnection.unavailable) {
        await sync(force:true,uploadOnly:true);
        var saved = await store.db.query('outbox',where:'scope=? AND operation_id=?',whereArgs:[current,result['_offline_operation']]);
        // A sync already running before this save may have taken its upload list earlier.
        if (saved.isNotEmpty && saved.single['state'] == 'pending' && online) {
          await sync(force:true,uploadOnly:true);
          saved = await store.db.query('outbox',where:'scope=? AND operation_id=?',whereArgs:[current,result['_offline_operation']]);
        }
        if (scope != current) throw const OfflineException('The signed-in account changed while saving. Your original work is kept.');
        if (saved.isNotEmpty && saved.single['state'] == 'accepted') {
          final body = saved.single['response'] as String;
          unawaited(Future<void>(() => sync()));
          return http.Response(body,200,headers:{'content-type':'application/json; charset=utf-8'});
        }
        if (saved.isNotEmpty && saved.single['state'] == 'conflict') {
          return http.Response(jsonEncode({'message':saved.single['error'],'saved_on_device':true,'needs_review':true}),409,headers:{'content-type':'application/json; charset=utf-8'});
        }
      }
      unawaited(Future<void>(() => sync()));
      return response;
    });
  }

  Future<http.Response> _write(
    Uri url, {
    required String method,
    Object? body,
    Map<String, String>? headers,
    required Map<String, File> files,
    required bool multipart,
  }) async {
    if (!_initialized) await initialize();
    await restoreSession();
    final path = _relative(url);
    final resource = path.split('/').first;
    Map<String, dynamic> fields = body == null
        ? {}
        : body is String
        ? Map<String, dynamic>.from(jsonDecode(body) as Map)
        : Map<String, dynamic>.from(body as Map);
    if (resource == 'bulk-uploads' && ready && supported && files['file'] != null) {
      final rows = await readProductSpreadsheet(files['file']!);
      return _write(
        url.replace(path: url.path.replaceFirst('bulk-uploads', 'bulk-product-rows')),
        method: 'POST',
        body: jsonEncode({'rows': rows}),
        headers: {..._headers, 'Content-Type': 'application/json'},
        files: const {},
        multipart: false,
      );
    }
    final canQueue = ready && supported && scope != null && OfflineProjection.writableResources.contains(resource);
    if (!canQueue) {
      if (connection == ServerConnection.unavailable) {
        throw const OfflineException('This action needs an internet connection. No change has been saved.');
      }
      return _send(url, method: method, body: body, headers: headers, files: files, multipart: multipart);
    }
    final current = scope!;
    final projected = await projection();
    final op = <String, Object?>{
      'operation_id': _uuid(),
      'path': path,
      'method': method,
      'fields': jsonEncode(fields),
      'files': '{}',
      'local_id': -2147483647,
      'created_at': DateTime.now().toIso8601String(),
    };
    if (partsForInvoice(path) && !fields.containsKey('invoiceNumber')) {
      fields['invoiceNumber'] = 'OFF-${(op['operation_id'] as String).substring(0, 8).toUpperCase()}';
      fields['offline_reference'] = fields['invoiceNumber'];
      op['fields'] = jsonEncode(fields);
    }
    if (path == 'sales-return' || path == 'purchases-return') {
      fields['return_date'] = op['created_at'];
      op['fields'] = jsonEncode(fields);
    }
    final parts = path.split('/');
    if (parts.length == 2) op['base_updated_at'] = projected.find(resource, parts[1])?['updated_at']?.toString();
    if (parts.length == 2) op['base_version'] = projected.find(resource, parts[1])?['_sync_version']?.toString();
    // Validate the complete local mutation BEFORE writing an outbox item or copying files.
    try {
      projected.apply(op, validate: true);
    } on OfflineException {
      if (!online || (await store.operations(current)).isNotEmpty) rethrow;
      // Complex/unsupported variants remain a normal validated online operation.
      return _send(url, method: method, body: body, headers: headers, files: files, multipart: multipart);
    }
    final copied = <String, String>{};
    if (files.isNotEmpty) {
      final directory = await getApplicationSupportDirectory();
      final folder = Directory('${directory.path}/offline/${_hash(current)}/${op['operation_id']}');
      await folder.create(recursive: true);
      for (final entry in files.entries) {
        final name = entry.value.uri.pathSegments.last;
        copied[entry.key] = (await entry.value.copy('${folder.path}/${copied.length}-$name')).path;
      }
    }
    op['files'] = jsonEncode(copied);
    final sensitive = <String, dynamic>{};
    for (final key in ['password', 'password_confirmation', 'current_password']) {
      if (fields.containsKey(key)) sensitive[key] = fields.remove(key);
    }
    if (sensitive.isNotEmpty) {
      final key = 'offline:$current:${op['operation_id']}';
      if (_cacheMedia) {
        await _secure.write(key: key, value: jsonEncode(sensitive));
      } else {
        _testSecrets[key] = jsonEncode(sensitive);
      }
      fields['__offline_secure_fields'] = true;
    }
    // Multipart vs JSON is preserved for replay. No authorization token is stored in the outbox.
    fields = {
      ...fields,
      '__offline_multipart': multipart,
      '__offline_json': (headers?['Content-Type'] ?? '').contains('application/json') || body is String,
    };
    op['fields'] = jsonEncode(fields);
    final id = await store.enqueue(current, Map.of(op)..remove('local_id'));
    op['local_id'] = id;
    await _counts();
    _changedData();
    final local = (await projection()).applyResponse(op);
    local['_offline_operation'] = op['operation_id'];
    return _response(local);
  }

  bool partsForInvoice(String path) => path == 'sales' || path == 'purchase';

  Future<http.Response> _send(
    Uri url, {
    required String method,
    Object? body,
    Map<String, String>? headers,
    Map<String, File> files = const {},
    required bool multipart,
  }) async {
    final client = _clientFactory();
    try {
      if (multipart || files.isNotEmpty) {
        final request = http.MultipartRequest(method, url)..headers.addAll(headers ?? _headers);
        request.headers.remove('Content-Type');
        final values = body is Map ? body : <String, dynamic>{};
        values.forEach(
          (key, value) => request.fields['$key'] = value is Map || value is List ? jsonEncode(value) : '$value',
        );
        for (final entry in files.entries) {
          request.files.add(await http.MultipartFile.fromPath(entry.key, entry.value.path));
        }
        return await http.Response.fromStream(await client.send(request)).timeout(const Duration(seconds: 30));
      }
      final request = http.Request(method, url)..headers.addAll(headers ?? _headers);
      if (body is String) {
        request.body = body;
      } else if (body is Map) {
        if ((request.headers['Content-Type'] ?? '').contains('application/json')) {
          request.body = jsonEncode(body);
        } else {
          request.bodyFields = body.map((k, v) => MapEntry('$k', '$v'));
        }
      }
      return await http.Response.fromStream(await client.send(request)).timeout(const Duration(seconds: 30));
    } finally {
      client.close();
    }
  }

  Future<void> sync({bool force = false, bool uploadOnly = false}) {
    if (_disposed) return Future.value();
    if (_syncing != null) return _syncing!;
    final future = _runSync(force: force,uploadOnly:uploadOnly);
    _syncing = future;
    return future.whenComplete(() {
      _syncing = null;
      notifyListeners();
    });
  }

  Future<void> _runSync({required bool force, required bool uploadOnly}) async {
    try {
      final wasOnline = online;
      if (!await checkConnection()) return;
      final current = scope!;
      final token = _token;
      String? conflictMessage;
      if (!force &&
          wasOnline &&
          ready &&
          lastSync != null &&
          DateTime.now().difference(lastSync!).inMinutes < 5 &&
          (await store.operations(current)).isEmpty) {
        status = 'Data synchronized';
        return;
      }
      for (final queued in await store.operations(current)) {
        final refreshed = await store.db.query(
          'outbox',
          where: 'scope=? AND seq=?',
          whereArgs: [current, queued['seq']],
        );
        if (refreshed.isEmpty) continue;
        final op = refreshed.single;
        if (scope != current || token != _token) return;
        if (op['state'] == 'accepted') continue;
        if (op['state'] == 'conflict') {
          // A conflict belongs to this operation only. Keep it pending for review,
          // but do not block unrelated operations that can still be uploaded.
          conflictMessage ??= op['error']?.toString();
          continue;
        }
        final mapping = await store.mappings(current);
        final projection = OfflineProjection({}, mapping);
        var path = op['path'] as String;
        final parts = path.split('/');
        if (parts.length == 2) {
          final id = int.tryParse(parts[1]);
          if (mapping.containsKey(id)) {
            path = '${parts[0]}/${mapping[id]}';
          } else if (id != null && id < 0) {
            throw const OfflineException('A related record must synchronize first.');
          }
        }
        final fields = Map<String, dynamic>.from(projection.remap(jsonDecode(op['fields'] as String)));
        if (fields.remove('__offline_secure_fields') == true) {
          final key = 'offline:$current:${op['operation_id']}';
          final saved = _cacheMedia ? await _secure.read(key: key) : _testSecrets[key];
          if (saved == null)
            throw const OfflineException('This saved account change needs its secure credentials before it can sync.');
          fields.addAll(Map<String, dynamic>.from(jsonDecode(saved)));
        }
        if (fields['invoiceNumber'] != null) {
          fields['invoiceNumber'] =
              await store.meta(current, 'reference:${fields['invoiceNumber']}') ?? fields['invoiceNumber'];
        }
        final multipart = fields.remove('__offline_multipart') == true;
        final jsonBody = fields.remove('__offline_json') == true;
        final filePaths = Map<String, String>.from(jsonDecode(op['files'] as String));
        final serverScope = await store.meta(current, 'server_scope');
        final response = await _send(
          Uri.parse('${APIConfig.url}/$path'),
          method: op['method'] as String,
          multipart: multipart,
          body: jsonBody && !multipart ? jsonEncode(fields) : fields,
          files: filePaths.map((k, v) => MapEntry(k, File(v))),
          headers: {
            ..._headers,
            'Idempotency-Key': op['operation_id'] as String,
            'X-Offline-Scope': serverScope!,
            if (op['base_updated_at'] != null) 'X-Offline-Base-Updated-At': op['base_updated_at'] as String,
            if (op['base_version'] != null) 'X-Offline-Base-Version': op['base_version'] as String,
            if (jsonBody && !multipart) 'Content-Type': 'application/json',
          },
        );
        if (response.statusCode >= 200 && response.statusCode < 300) {
          final body = jsonDecode(response.body);
          final value = body is Map ? body['data'] : null;
          final id = value is Map ? int.tryParse('${value['id']}') : null;
          final aliases = value is Map
              ? await _childAliases(current, op, Map<String, dynamic>.from(value))
              : <int, int>{};
          final originalFields = jsonDecode(op['fields'] as String) as Map;
          final oldReference = originalFields['invoiceNumber'];
          final newReference = value is Map ? value['invoiceNumber'] : null;
          await store.accept(
            current,
            op,
            response.body,
            id,
            aliases: aliases,
            references: {
              if (partsForInvoice(op['path'] as String) && oldReference is String && newReference is String)
                oldReference: newReference,
            },
          );
          final secureKey = 'offline:$current:${op['operation_id']}';
          if (_cacheMedia) {
            await _secure.delete(key: secureKey);
          } else {
            _testSecrets.remove(secureKey);
          }
        } else {
          if (response.statusCode == 401) {
            connection = ServerConnection.signInRequired;
            status = 'Sign in again to upload your saved work.';
            return;
          }
          if (response.statusCode >= 500 || response.statusCode == 429) {
            throw const OfflineException('Server is temporarily unavailable. Saved work will retry automatically.');
          }
          String message = 'Server rejected this change (${response.statusCode}).';
          try {
            final body = jsonDecode(response.body);
            if (body is Map) {
              message = '${body['message'] ?? message}';
              final errors = body['errors'];
              if (errors is Map && errors.isNotEmpty) {
                final details = errors.entries
                    .map((entry) => '${entry.key}: ${entry.value is List ? (entry.value as List).join(', ') : entry.value}')
                    .join('\n');
                message = '$message\n$details';
              }
            }
          } catch (_) {}
          await store.db.update(
            'outbox',
            {'state': 'conflict', 'error': message},
            where: 'scope=? AND seq=?',
            whereArgs: [current, op['seq']],
          );
          conflictMessage ??= message;
          // Continue with the remaining outbox records. One invalid or stale
          // request must not prevent successful sales, purchases, or settings
          // saved after it from reaching the server.
          continue;
        }
        await _counts();
      }
      if (uploadOnly && ready) {
        lastError = conflictMessage;
        status = conflictMessage == null
            ? 'Saved on device and server'
            : 'Saved on device and server. A saved change needs review.';
        return;
      }
      await _download(current, token);
      if (conflictMessage != null) {
        lastError = conflictMessage;
        status = 'Data synchronized. A saved change needs review.';
      }
    } catch (error) {
      lastError = error.toString();
      status = ready
          ? 'Saved on device. Sync will retry when the server is available.'
          : 'Setup paused. Retry to finish downloading your data.';
      connection = ServerConnection.unavailable;
    } finally {
      downloading = false;
      progress = null;
      await _counts();
      notifyListeners();
    }
  }

  Future<Map<int, int>> _childAliases(String current, Map<String, Object?> op, JsonMap remote) async {
    final resource = (op['path'] as String).split('/').first;
    final local = OfflineProjection(await store.snapshots(current), await store.mappings(current));
    for (final previous in await store.operations(current)) {
      local.apply(previous);
      if (previous['seq'] == op['seq']) break;
    }
    final aliases = <int, int>{};
    void link(dynamic localId, dynamic serverId) {
      final from = int.tryParse('$localId');
      final to = int.tryParse('$serverId');
      if (from != null && from < 0 && to != null && to > 0) aliases[from] = to;
    }

    if (resource == 'bulk-product-rows') {
      const names = {
        'products': 'productCode',
        'categories': 'categoryName',
        'units': 'unitName',
        'brands': 'brandName',
        'vats': 'name',
        'product-models': 'name',
      };
      final entities = remote['_sync_entities'] as Map? ?? {};
      for (final entry in names.entries) {
        final remoteRows = entities[entry.key] as List? ?? [];
        for (final row in local.rows(entry.key).where((r) => OfflineProjection.number(r['id']) < 0)) {
          final match = remoteRows.where((r) => '${r[entry.value]}' == '${row[entry.value]}').firstOrNull;
          if (match == null) continue;
          link(row['id'], match['id']);
          if (entry.key == 'products') {
            for (final stock in row['stocks'] as List? ?? []) {
              final target = (match['stocks'] as List? ?? [])
                  .where(
                    (s) =>
                        '${s['batch_no'] ?? ''}' == '${stock['batch_no'] ?? ''}' &&
                        '${s['variant_name'] ?? ''}' == '${stock['variant_name'] ?? ''}',
                  )
                  .firstOrNull;
              if (target != null) link(stock['id'], target['id']);
            }
          }
        }
      }
      return aliases;
    }

    final remoteTransactions = List<dynamic>.from(remote['_sync_transactions'] as List? ?? []);
    final localTransactions = local.rows('transactions').where((t) => t['_offline_local_id'] == op['local_id']).toList()
      ..sort((a, b) => OfflineProjection.number(b['id']).compareTo(OfflineProjection.number(a['id'])));
    for (final transaction in localTransactions) {
      final index = remoteTransactions.indexWhere(
        (t) =>
            t['transaction_type'] == transaction['transaction_type'] &&
            '${t['payment_type_id']}' == '${transaction['payment_type_id']}' &&
            OfflineProjection.number(t['amount']) == OfflineProjection.number(transaction['amount']),
      );
      if (index >= 0) {
        link(transaction['id'], remoteTransactions[index]['id']);
        remoteTransactions.removeAt(index);
      }
    }
    if (!{'products', 'sales', 'purchase'}.contains(resource)) return aliases;
    final parts = (op['path'] as String).split('/');
    final record = local.find(resource, parts.length == 1 ? op['local_id'] : parts.last);
    if (record == null) return aliases;

    final field = resource == 'products' ? 'stocks' : 'details';
    final localRows = record[field] as List? ?? [];
    final remoteRows = remote[field] as List? ?? [];
    for (var i = 0; i < localRows.length; i++) {
      final item = localRows[i] as Map;
      final matches = remoteRows
          .where(
            (r) => resource == 'products'
                ? '${r['batch_no'] ?? ''}' == '${item['batch_no'] ?? ''}' &&
                      '${r['variant_name'] ?? ''}' == '${item['variant_name'] ?? ''}'
                : '${r['product_id']}' == '${item['product_id']}',
          )
          .toList();
      final match = matches.length == 1
          ? matches.single
          : (localRows.length == remoteRows.length ? remoteRows[i] : null);
      if (match == null) continue;
      link(item['id'], match['id']);
      if (resource == 'purchase') link(item['stock_id'], match['stock_id'] ?? match['stock']?['id']);
    }
    return aliases;
  }

  Future<void> _download(String current, String? token) async {
    downloading = true;
    progress = 0;
    final stage = <String, dynamic>{};
    stage['module-checks'] = jsonDecode(await store.meta(current, 'module_checks') ?? '{}');
    final metadata = <String, String>{};
    final existing = await store.snapshots(current);
    final manifestKey = _hash(jsonEncode(_resources));
    final started = DateTime.tryParse(await store.meta(current, 'download_started') ?? '');
    final resume =
        !ready &&
        started != null &&
        DateTime.now().difference(started).inMinutes < 30 &&
        await store.meta(current, 'download_manifest') == manifestKey;
    if (!ready && !resume) {
      await store.setMeta(current, 'download_manifest', manifestKey);
      await store.setMeta(current, 'download_started', DateTime.now().toIso8601String());
    }
    final accepted = (await store.operations(
      current,
    )).where((op) => op['state'] == 'accepted').map((op) => op['seq'] as int).toList();
    final client = _clientFactory();
    try {
      for (var index = 0; index < _resources.length; index++) {
        if (scope != current || token != _token) return;
        final resource = _resources[index];
        status = 'Downloading ${resource.replaceAll('-', ' ')}…';
        progress = index / _resources.length;
        notifyListeners();
        if (resume && existing.containsKey('__download__/$resource')) {
          stage[resource] = existing['__download__/$resource'];
          continue;
        }
        final result = await _downloadResource(
          client,
          resource,
          cached: ready ? existing[resource] : null,
          etag: ready ? await store.meta(current, 'etag:$resource') : null,
          metadata: metadata,
        );
        if (result != null) {
          stage[resource] = result;
          if (!ready) await store.cache(current, '__download__/$resource', jsonEncode(result));
        }
      }
      if (!stage.containsKey('business') || !stage.containsKey('products') || !stage.containsKey('parties')) {
        throw const OfflineException('Business, products and customers must finish downloading before offline use.');
      }
      // Do not swap a snapshot while a foreground write is being enqueued.
      if (_cacheMedia) {
        status = 'Saving business images for offline use…';
        progress = 0.98;
        notifyListeners();
        await OfflineMedia.download(stage, client);
      }
      await _writes;
      await store.replaceSnapshot(current, stage, accepted, DateTime.now().toIso8601String(), metadata: metadata);
      ready = true;
      lastSync = DateTime.now();
      lastError = null;
      status = 'Data synchronized';
      _changedData();
    } finally {
      client.close();
    }
  }

  Future<dynamic> _downloadResource(
    http.Client client,
    String resource, {
    dynamic cached,
    String? etag,
    required Map<String, String> metadata,
  }) async {
    var page = 1;
    dynamic envelope;
    final all = <dynamic>[];
    while (true) {
      final uri = Uri.parse(
        '${APIConfig.url}/$resource',
      ).replace(queryParameters: {'page': '$page', 'per_page': '100', 'no_paginate': 'true'});
      final response = await client
          .get(
            uri,
            headers: {
              ..._headers,
              if (page == 1 && cached != null && etag?.isNotEmpty == true) 'If-None-Match': etag!,
            },
          )
          .timeout(const Duration(seconds: 25));
      if (response.statusCode == 304 && cached != null && page == 1) {
        metadata['etag:$resource'] = etag ?? '';
        return cached;
      }
      // Modules unavailable to this user are omitted, never borrowed from another account.
      if (response.statusCode == 403 || response.statusCode == 404) return null;
      if (response.statusCode != 200) throw OfflineException('Could not download $resource (${response.statusCode}).');
      final body = jsonDecode(response.body);
      envelope ??= body;
      final data = body is Map ? body['data'] : null;
      if (data is! Map || data['data'] is! List || data['last_page'] == null) {
        metadata['etag:$resource'] = response.headers['etag'] ?? '';
        return body;
      }
      all.addAll(data['data'] as List);
      final lastPage = int.tryParse('${data['last_page']}') ?? page;
      // A page-one ETag must never hide changes on later pages.
      metadata['etag:$resource'] = lastPage == 1 ? response.headers['etag'] ?? '' : '';
      if (page >= lastPage) {
        envelope['data']['data'] = all;
        envelope['data']['current_page'] = 1;
        envelope['data']['last_page'] = 1;
        envelope['data']['per_page'] = all.length;
        envelope['data']['next_page_url'] = null;
        return envelope;
      }
      page++;
      if (page > 10000) throw OfflineException('Download pagination did not finish for $resource.');
    }
  }

  Future<List<Map<String, Object?>>> pendingOperations() async => scope == null ? [] : store.operations(scope!);

  Future<JsonMap> reviewConflict(Map<String, Object?> operation) async {
    if (!online) throw const OfflineException('Connect to review the latest server record. Your saved work is kept.');
    final path = operation['path'] as String;
    final parts = path.split('/');
    // Creates and collection-level actions do not have a server record ID to
    // compare. Return the saved server validation error instead of sending the
    // user to a review endpoint that can never resolve such an operation.
    if (parts.length != 2 || int.tryParse(parts.last) == null) {
      return {
        'data': <String, dynamic>{},
        'version': null,
        'reviewable': false,
        'message': operation['error']?.toString() ?? 'The server rejected this saved request.',
      };
    }
    final mapped = await store.mappings(scope!);
    final id = mapped[int.parse(parts.last)] ?? int.parse(parts.last);
    final client = _clientFactory();
    try {
      final response = await client
          .get(Uri.parse('${APIConfig.url}/sync/review/${parts.first}/$id'), headers: _headers)
          .timeout(const Duration(seconds: 15));
      if (response.statusCode != 200)
        throw const OfflineException('The current record could not be loaded. No saved change was removed.');
      return Map<String, dynamic>.from(jsonDecode(response.body));
    } finally {
      client.close();
    }
  }

  Future<void> applyReviewedConflict(Map<String, Object?> operation, JsonMap review) async {
    final current = scope;
    if (current == null || syncing || review['version'] is! String)
      throw const OfflineException('Wait for synchronization to finish and review this change again.');
    await store.db.transaction((txn) async {
      final rows = await txn.query(
        'outbox',
        where: 'scope=? AND seq=? AND state=?',
        whereArgs: [current, operation['seq'], 'conflict'],
      );
      if (rows.isEmpty || rows.single['operation_id'] != operation['operation_id'])
        throw const OfflineException('This saved change has already been updated.');
      await txn.insert('metadata', {
        'scope': current,
        'key': 'review:${operation['operation_id']}:${DateTime.now().microsecondsSinceEpoch}',
        'value': jsonEncode(review),
      });
      await txn.update(
        'outbox',
        {
          'state': 'pending',
          'error': null,
          'base_version': review['version'],
          'base_updated_at': review['data']?['updated_at'],
        },
        where: 'scope=? AND seq=?',
        whereArgs: [current, operation['seq']],
      );
    });
    await _counts();
    notifyListeners();
    await sync(force: true);
  }

  Future<void> retryConflicts() async {
    if (scope == null) return;
    // Immutable requests retain their operation IDs for safe retries.
    await store.db.update(
      'outbox',
      {'state': 'pending', 'error': null},
      where: 'scope=? AND state=?',
      whereArgs: [scope, 'conflict'],
    );
    await sync(force: true);
  }

  Future<bool> canSignOut() async => scope == null || (await store.operations(scope!)).isEmpty;

  @override
  void dispose() {
    _disposed = true;
    _timer?.cancel();
    _connectivity?.cancel();
    _revisionEvents.close();
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }
}

extension on OfflineProjection {
  JsonMap applyResponse(Map<String, Object?> operation) {
    final path = operation['path'] as String;
    final resource = path.split('/').first;
    if (resource == 'bulk-product-rows')
      return {'message': 'Products saved on this device. Waiting to sync.', 'data': {}, 'offline': true};
    if (resource == 'cheques' || resource == 'cheque-reopen') {
      final values = Map<String, dynamic>.from(remap(jsonDecode(operation['fields'] as String)));
      return {
        'message': 'Cheque change saved on this device. Waiting to sync.',
        'data': find('cheques', resource == 'cheques' ? values['transaction_id'] : path.split('/').last),
        'offline': true,
      };
    }
    if (OfflineProjection.singletonResources.contains(resource)) {
      final result = resource == 'currency-selection'
          ? (data['business']?['data']?['business_currency'])
          : data[resource];
      return {
        'message': 'Saved on this device. Waiting to sync.',
        'data': result is Map ? result['data'] ?? result : result,
        'offline': true,
      };
    }
    final id = path.contains('/') ? path.split('/').last : operation['local_id'];
    return {'message': 'Saved on this device. Waiting to sync.', 'data': find(resource, id), 'offline': true};
  }
}
