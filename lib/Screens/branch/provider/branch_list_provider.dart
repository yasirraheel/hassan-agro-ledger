import 'package:mobile_pos/offline/offline_revision_provider.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile_pos/Screens/branch/model/branch_list_model.dart';
import 'package:mobile_pos/Screens/branch/repo/branch_repo.dart';

final branchListProvider = FutureProvider.autoDispose<BranchListModel>((ref) { ref.watch(offlineRevisionProvider); return BranchRepo().fetchBranchList(); });
