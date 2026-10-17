import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'offline_service.dart';

/// Rebuild data providers only when records change, never on spinner/status updates.
final offlineRevisionProvider = StreamProvider<int>((ref) => OfflineService.instance.revisions);
