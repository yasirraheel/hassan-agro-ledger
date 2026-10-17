import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../offline/offline_revision_provider.dart';
import 'package:mobile_pos/model/business_info_model.dart';
import 'package:mobile_pos/model/dashboard_overview_model.dart';

import '../Repository/API/business_info_repo.dart';
import '../model/todays_summary_model.dart';

final BusinessRepository businessRepository = BusinessRepository();
final businessInfoProvider = FutureProvider<BusinessInformationModel>((ref) async {
  ref.watch(offlineRevisionProvider);
  return await BusinessRepository().fetchBusinessData();
});

final availableModulesProvider = Provider<AvailableModulesModel>((ref) {
  final businessInfo = ref.watch(businessInfoProvider).valueOrNull;
  return businessInfo?.data?.availableModules ?? const AvailableModulesModel();
});

final getExpireDateProvider = FutureProvider.family<void, WidgetRef>(
  (ref, widgetRef) => businessRepository.fetchSubscriptionExpireDate(ref: widgetRef),
);
final summaryInfoProvider = FutureProvider<TodaysSummaryModel>((ref) {
  ref.watch(offlineRevisionProvider);
  return businessRepository.fetchTodaySummaryData();
});
final dashboardInfoProvider = FutureProvider.family<DashboardOverviewModel, String>((ref, type) {
  ref.watch(offlineRevisionProvider);
  return businessRepository.dashboardData(type);
});
