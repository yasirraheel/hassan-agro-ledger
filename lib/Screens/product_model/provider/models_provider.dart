import 'package:mobile_pos/offline/offline_revision_provider.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../model/product_models_model.dart';
import '../repo/product_models_repo.dart';

ProductModelsRepo repo = ProductModelsRepo();

// fetch models list
final fetchModelListProvider = FutureProvider<ProductModelsModel>((ref) {
  ref.watch(offlineRevisionProvider);
  return repo.fetchModelsList();
});
