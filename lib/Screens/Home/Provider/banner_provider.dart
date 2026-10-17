import 'package:mobile_pos/offline/offline_revision_provider.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../Model/banner_model.dart';
import '../Repo/banner_repo.dart';

BannerRepo imageRepo = BannerRepo();
final bannerProvider = FutureProvider<List<Banner>>((ref) { ref.watch(offlineRevisionProvider); return imageRepo.fetchAllIBanners(); });
