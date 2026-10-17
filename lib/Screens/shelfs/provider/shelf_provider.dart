import 'package:mobile_pos/offline/offline_revision_provider.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../model/shelf_list_model.dart';
import '../repo/shelf_repo.dart';

// Instantiate the repository
final repo = ShelfRepo();

final shelfListProvider = FutureProvider.autoDispose<ShelfListModel>((ref) {
  ref.watch(offlineRevisionProvider);
  return repo.fetchAllShelves();
});
