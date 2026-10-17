import 'package:mobile_pos/offline/offline_revision_provider.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../model/product_variation_model.dart';
import '../repo/product_variation_repo.dart';

final repo = VariationRepo();
final variationListProvider = FutureProvider<VariationListModel>((ref) { ref.watch(offlineRevisionProvider); return repo.fetchAllVariations(); });
