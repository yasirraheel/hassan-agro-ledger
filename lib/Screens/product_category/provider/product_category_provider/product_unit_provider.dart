import 'package:mobile_pos/offline/offline_revision_provider.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../model/category_model.dart';
import '../../repo/product_category_repo.dart';

CategoryRepo categoryRepo = CategoryRepo();
final categoryProvider = FutureProvider<List<CategoryModel>>((ref) { ref.watch(offlineRevisionProvider); return categoryRepo.fetchAllCategory(); });
