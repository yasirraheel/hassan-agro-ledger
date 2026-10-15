import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile_pos/Provider/profile_provider.dart';
import 'package:mobile_pos/Screens/subscription/payment_getway_screen.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;
import 'package:shimmer/shimmer.dart';

import '../../GlobalComponents/go_to_subscription-package_page_popup_widget.dart';
import '../../constant.dart';
import '../../core/helpers/helpers.export.dart';
import '../../model/business_info_model.dart' as b_info;
import '../Currency/Model/currency_model.dart';
import '../Currency/Provider/currency_provider.dart';
import '../Home/home.dart';
import '../../service/check_user_role_permission_provider.dart';
import 'Model/subscription_plan_model.dart';
import 'Provider/subacription_plan_provider.dart';
import 'Repo/subscriptionPlanRepo.dart';

class PurchasePremiumPlanScreen extends ConsumerStatefulWidget {
  const PurchasePremiumPlanScreen({
    super.key,
    required this.isCameBack,
    this.isExpired,
    this.enrolledPlan,
    this.willExpire,
  });
  final bool isCameBack;
  final bool? isExpired;
  final b_info.EnrolledPlan? enrolledPlan;
  final String? willExpire;

  @override
  ConsumerState<PurchasePremiumPlanScreen> createState() => _SubscriptionPlanScreenState();
}

class _SubscriptionPlanScreenState extends ConsumerState<PurchasePremiumPlanScreen> {
  SubscriptionPlanModel? selectedPlan;
  bool isPlanExpiringIn7Days = false;
  bool _isRefreshing = false;

  late Future<List<SubscriptionPlanModel>> _plansFuture;
  final SubscriptionPlanRepo subscriptionRepo = SubscriptionPlanRepo();

  @override
  void initState() {
    super.initState();
    _checkExpiryDate();
    _plansFuture = subscriptionRepo.fetchAllPlans();
  }

  void _checkExpiryDate() {
    if (widget.willExpire != null && DateTime.tryParse(widget.willExpire ?? '') != null) {
      DateTime expiryDate = DateTime.parse(widget.willExpire!);
      isPlanExpiringIn7Days = expiryDate.isBefore(DateTime.now().add(const Duration(days: 6)));
    }
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (widget.isExpired == true) {
        getUpgradeDialog();
      }
    });
  }

  void getUpgradeDialog() {
    showDialog(
      context: context,
      builder: (BuildContext dialogContext) {
        return goToPackagePagePopup(
          context: dialogContext,
          enrolledPlan: widget.enrolledPlan,
        );
      },
    );
  }

  Future<void> refreshData() async {
    if (_isRefreshing) return;
    setState(() {
      _isRefreshing = true;
      _plansFuture = subscriptionRepo.fetchAllPlans();
      selectedPlan = null;
    });

    ref.refresh(businessInfoProvider);
    ref.refresh(subscriptionPlanProvider);
    ref.refresh(getExpireDateProvider(ref));

    await Future.delayed(const Duration(seconds: 1));
    if (mounted) {
      setState(() {
        _isRefreshing = false;
      });
    }
  }

  CurrencyModel? getDefoultCurrency({required List<CurrencyModel> currencies}) {
    for (var element in currencies) {
      if (element.isDefault ?? false) {
        return element;
      }
    }
    return null;
  }

  int calculateDiscountPercent(double originalPrice, double offerPrice) {
    if (originalPrice == 0) return 0;
    return ((1 - (offerPrice / originalPrice)) * 100).round();
  }

  @override
  Widget build(BuildContext context) {
    final businessInfo = ref.watch(businessInfoProvider);
    final currencyData = ref.watch(currencyProvider);
    final permissionService = PermissionService(ref);
    final currencySymbol = getDefoultCurrency(currencies: currencyData.value ?? [])?.symbol ?? '';

    return SafeArea(
      child: Scaffold(
        backgroundColor: kWhite,
        bottomNavigationBar: _buildBottomButton(businessInfo, permissionService, currencySymbol),
        appBar: AppBar(
          automaticallyImplyLeading: false,
          title: Text(
            lang.S.of(context).purchasePremium,
            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w500, color: kTitleColor),
          ),
          actions: [
            CloseButton(
              color: widget.isExpired != true ? Colors.grey : Colors.black,
              onPressed: () {
                if (widget.isExpired != true) {
                  if (widget.isCameBack) {
                    Navigator.pop(context);
                  } else {
                    Navigator.pushAndRemoveUntil(
                      context,
                      MaterialPageRoute(builder: (context) => const Home()),
                      (Route<dynamic> route) => false,
                    );
                  }
                } else {
                  Navigator.pushAndRemoveUntil(
                    context,
                    MaterialPageRoute(builder: (context) => const Home()),
                    (Route<dynamic> route) => false,
                  );
                }
              },
            ),
          ],
        ),
        body: RefreshIndicator(
          onRefresh: () => refreshData(),
          child: FutureBuilder<List<SubscriptionPlanModel>>(
            future: _plansFuture,
            builder: (context, snapshot) {
              if (snapshot.hasError) {
                return Center(child: Text('Error: ${snapshot.error}'));
              }

              if (snapshot.connectionState == ConnectionState.waiting) {
                return _buildShimmerLoading();
              }

              if (!snapshot.hasData || snapshot.data!.isEmpty) {
                return Center(child: Text(lang.S.of(context).noDataFound));
              }

              final plans = snapshot.data!;

              if (selectedPlan == null) {
                final currentPlanId = widget.enrolledPlan?.planId;
                try {
                  selectedPlan = plans.firstWhere(
                    (plan) => plan.id == currentPlanId,
                    orElse: () => plans.first,
                  );
                } catch (e) {
                  selectedPlan = plans.first;
                }
              }

              // Plans come first: choosing a plan is the primary task on this
              // screen, so it should never be pushed below the fold by a long
              // feature list. Features are shown afterwards inside a bounded,
              // scrollable panel so they stay visible without limiting how
              // many a plan can have.
              return CustomScrollView(
                slivers: [
                  SliverPadding(
                    padding: const EdgeInsets.all(16),
                    sliver: SliverToBoxAdapter(
                      child: Text(
                        'Choose your plan',
                        style: const TextStyle(
                          fontSize: 15,
                          fontWeight: FontWeight.w600,
                          color: kTitleColor,
                        ),
                      ),
                    ),
                  ),
                  _PlanListBuilder(
                    currencySymbol: currencySymbol,
                    value: selectedPlan,
                    plans: plans,
                    onChanged: (value) => setState(() => selectedPlan = value),
                  ),
                  SliverPadding(
                    padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
                    sliver: SliverToBoxAdapter(
                      child: _buildFeaturesPanel(),
                    ),
                  ),
                ],
              );
            },
          ),
        ),
      ),
    );
  }

  // --- Widgets Components ---
  Widget _buildFeaturesPanel() {
    final features =
        selectedPlan?.features?.entries
            .where((f) => ((f.value as List?)?.length ?? 0) >= 2)
            .map((entry) => _buildFeatureItem(entry.key, entry.value))
            .toList() ??
        [];

    if (features.isEmpty) return const SizedBox.shrink();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          "Module (${features.length})",
          style: const TextStyle(
            fontSize: 17,
            fontWeight: FontWeight.w600,
            color: kGreyTextColor,
          ),
        ),
        const SizedBox(height: 12),
        LayoutBuilder(
          builder: (context, constraints) {
            final itemWidth = (constraints.maxWidth - 16) / 2;
            return Wrap(
              spacing: 16,
              runSpacing: 12,
              children: features.map((feature) => SizedBox(width: itemWidth, child: feature)).toList(),
            );
          },
        ),
      ],
    );
  }

  Widget _buildFeatureItem(String featureKey, dynamic featureValue) {
    final featureText = featureValue is List ? featureValue[0].toString() : featureKey;

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Icon(Icons.check, color: Color(0xff32CD32), size: 16),
        const SizedBox.square(dimension: 8),
        Expanded(
          child: Text(
            featureText,
            style: TextStyle(
              color: Colors.grey[800],
              fontSize: 15,
              fontWeight: FontWeight.w500,
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildBottomButton(
    AsyncValue<b_info.BusinessInformationModel> businessInfo,
    PermissionService permissionService,
    String currencySymbol,
  ) {
    if (selectedPlan == null) return const SizedBox.shrink();

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      decoration: BoxDecoration(
        color: kWhite,
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withOpacity(0.175),
            spreadRadius: 1,
            blurRadius: 10,
            offset: const Offset(0, -3),
          ),
        ],
      ),
      child: Row(
        children: [
          Expanded(
            child: Text(
              '$currencySymbol${(selectedPlan?.currentPrice ?? 0).formatDecimal()}',
              style: const TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.w600,
                color: kTitleColor,
              ),
            ),
          ),
          const SizedBox(width: 12),
          SizedBox(
            height: 50,
            width: 170,
            child: ElevatedButton(
              onPressed: () => _handlePayment(businessInfo, permissionService),
              style: ElevatedButton.styleFrom(
                backgroundColor: kMainColor,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
              child: Text(
                selectedPlan?.id == widget.enrolledPlan?.planId
                    ? lang.S.of(context).extendPlan
                    : lang.S.of(context).buyNow,
                style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.w600,
                  fontSize: 16,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  // --- UPDATED PAYMENT HANDLING LOGIC ---
  // --- UPDATED PAYMENT HANDLING LOGIC (Fixed SnackBar Delay) ---
  Future<void> _handlePayment(
    AsyncValue<b_info.BusinessInformationModel> businessInfo,
    PermissionService permissionService,
  ) async {
    // 1. Check Permissions
    if (!permissionService.hasPermission(Permit.subscriptionsRead.value)) {
      EasyLoading.showError(lang.S.of(context).youDoNotHavePermissionToCreatePurchase);
      return;
    }

    final plan = selectedPlan!;
    final currentPlanId = widget.enrolledPlan?.planId;
    final bool isFreePlan = (plan.subscriptionPrice ?? 0) <= 0 && (plan.offerPrice ?? 0) <= 0;

    DateTime? currentExpiryDate;
    if (widget.willExpire != null) {
      currentExpiryDate = DateTime.tryParse(widget.willExpire!);
    }
    final now = DateTime.now();

    // 2. LOGIC CHECK: Free Plan Restriction
    if (isFreePlan && widget.enrolledPlan != null) {
      // Fix: Hide previous snackbar immediately
      ScaffoldMessenger.of(context).hideCurrentSnackBar();
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text("Sorry, you cannot subscribe to a free plan again."),
          backgroundColor: Colors.red,
          duration: Duration(seconds: 2), // Duration কমিয়ে দেওয়া হলো
        ),
      );
      return;
    }

    // 3. LOGIC CHECK: Validity & Downgrade Prevention
    if (currentExpiryDate != null && currentExpiryDate.isAfter(now)) {
      // 3.1: Same Plan Extension Limit
      if (currentPlanId == plan.id) {
        final sevenDaysFromNow = now.add(const Duration(days: 7));
        if (currentExpiryDate.isAfter(sevenDaysFromNow)) {
          // Fix: Hide previous snackbar immediately
          ScaffoldMessenger.of(context).hideCurrentSnackBar();
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Plan is not expiring soon enough to extend'),
              backgroundColor: Colors.orange,
              duration: const Duration(seconds: 2),
            ),
          );
          return;
        }
      }

      // 3.2: Check if Current Validity exceeds New Plan Duration
      final int newPlanDuration = plan.duration ?? 0;
      final DateTime theoreticalNewExpiry = now.add(Duration(days: newPlanDuration));

      if (currentExpiryDate.isAfter(theoreticalNewExpiry) || currentExpiryDate.isAtSameMomentAs(theoreticalNewExpiry)) {
        // Fix: Hide previous snackbar immediately
        ScaffoldMessenger.of(context).hideCurrentSnackBar();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text("You cannot subscribe to this plan as your current plan validity is longer."),
            backgroundColor: Colors.red,
            duration: Duration(seconds: 2),
          ),
        );
        return;
      }
    }

    // 4. Proceed to Payment
    // Check if context is mounted before push
    if (!mounted) return;

    final bool success = await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => PaymentScreen(
          planId: plan.id.toString(),
          businessId: businessInfo.value?.data?.id.toString() ?? '',
        ),
      ),
    );

    if (success == true) {
      ref.refresh(businessInfoProvider);
      ref.refresh(getExpireDateProvider(ref));
      EasyLoading.showSuccess(lang.S.of(context).successfullyPaid);

      if (!mounted) return;
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (context) => const Home()),
      );
    } else {
      EasyLoading.showError('Payment Unsuccessfull');
    }
  }

  Widget _buildShimmerLoading() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Shimmer.fromColors(
        baseColor: Colors.grey[300]!,
        highlightColor: Colors.grey[100]!,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            for (int i = 0; i < 5; i++)
              Container(
                height: 60,
                width: double.infinity,
                margin: const EdgeInsets.only(bottom: 10),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            const SizedBox(height: 20),
            Container(height: 16, width: 140, color: Colors.white),
            const SizedBox(height: 12),
            Container(
              height: 140,
              width: double.infinity,
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _PlanListBuilder extends StatelessWidget {
  const _PlanListBuilder({
    // ignore: unused_element_parameter
    super.key,
    this.value,
    this.plans = const [],
    this.onChanged,
    this.currencySymbol = "\$",
  });
  final SubscriptionPlanModel? value;
  final List<SubscriptionPlanModel> plans;
  final ValueChanged<SubscriptionPlanModel>? onChanged;
  final String currencySymbol;

  @override
  Widget build(BuildContext context) {
    final _theme = Theme.of(context);

    return SliverPadding(
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 0),
      sliver: SliverList.separated(
        itemCount: plans.length,
        itemBuilder: (_, index) {
          final _plan = plans[index];
          final _isSelected = _plan == value;
          final _discountPercent = _plan.hasOffer
              ? calculateDiscountPercent(_plan.subscriptionPrice ?? 0, _plan.offerPrice!)
              : null;

          return Material(
            type: MaterialType.transparency,
            child: InkWell(
              onTap: onChanged != null ? () => onChanged!(_plan) : null,
              borderRadius: BorderRadius.circular(18),
              child: Container(
                constraints: const BoxConstraints(minHeight: 64),
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                decoration: BoxDecoration(
                  color: _isSelected ? _theme.colorScheme.primary.withValues(alpha: 0.15) : null,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: _isSelected ? _theme.colorScheme.primary : kGreyTextColor,
                    width: _isSelected ? 2 : 1,
                  ),
                ),
                child: Row(
                  children: [
                    Container(
                      width: 22,
                      height: 22,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        color: _isSelected ? _theme.colorScheme.primary : Colors.transparent,
                        border: Border.all(
                          color: _isSelected ? _theme.colorScheme.primary : Colors.grey.shade500,
                          width: 2,
                        ),
                      ),
                      child: _isSelected ? const Icon(Icons.check, size: 13, color: Colors.white) : null,
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        _plan.subscriptionName ?? "N/A",
                        style: _theme.textTheme.bodyLarge?.copyWith(
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text.rich(
                          TextSpan(
                            text: '$currencySymbol${_plan.currentPrice.formatDecimal()}',
                            style: _theme.textTheme.bodyLarge?.copyWith(
                              fontWeight: FontWeight.w600,
                            ),
                            children: [
                              TextSpan(
                                text: '/mo',
                                style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w400,
                                  color: kGreyTextColor,
                                ),
                              ),
                            ],
                          ),
                        ),
                        if (_plan.hasOffer) ...[
                          const SizedBox(height: 2),
                          Text.rich(
                            TextSpan(
                              children: [
                                TextSpan(
                                  text: '$currencySymbol${(_plan.subscriptionPrice ?? 0).formatDecimal()}',
                                  style: TextStyle(
                                    fontSize: 14,
                                    color: Colors.grey[800],
                                    decoration: TextDecoration.lineThrough,
                                    decorationColor: Colors.grey[800],
                                  ),
                                ),
                                TextSpan(
                                  text: ' ${lang.S.of(context).save} $_discountPercent%',
                                  style: TextStyle(
                                    fontSize: 12,
                                    fontWeight: FontWeight.w500,
                                    color: _theme.colorScheme.primary,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ],
                    ),
                  ],
                ),
              ),
            ),
          );
        },
        separatorBuilder: (_, _) => const SizedBox.square(dimension: 12),
      ),
    );
  }

  int calculateDiscountPercent(double originalPrice, double offerPrice) {
    if (originalPrice == 0) return 0;
    return ((1 - (offerPrice / originalPrice)) * 100).round();
  }
}
