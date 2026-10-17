import 'package:mobile_pos/offline/offline_revision_provider.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../model/bank_account_list_model.dart';
import '../repo/bank_account_repo.dart';

final repo = BankRepo();
final bankListProvider = FutureProvider.autoDispose<BankListModel>((ref) { ref.watch(offlineRevisionProvider); return repo.fetchAllBanks(); });
