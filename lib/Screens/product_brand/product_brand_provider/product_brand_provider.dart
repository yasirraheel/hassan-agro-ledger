import 'package:mobile_pos/offline/offline_revision_provider.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile_pos/Screens/product_brand/model/brands_model.dart';

import '../brand repo/brand_repo.dart';

BrandsRepo brandsRepo = BrandsRepo();
final brandsProvider = FutureProvider<List<Brand>>((ref) { ref.watch(offlineRevisionProvider); return brandsRepo.fetchAllBrands(); });
