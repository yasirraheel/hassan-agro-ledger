import 'package:flutter/material.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;
import '../../../model/business_info_model.dart';

class GridItems {
  final String title, icon, route;

  GridItems({
    required this.title,
    required this.icon,
    required this.route,
  });
}

List<GridItems> getFreeIcons({
  required BuildContext context,
  bool? brunchPermission,
  bool? hrmPermission,
  bool? warehousePermission,
  AvailableModulesModel modules = const AvailableModulesModel(),
}) {
  List<GridItems> freeIcons = [];

  void addIf({
    required bool condition,
    required String title,
    required String icon,
    required String route,
  }) {
    if (condition) {
      freeIcons.add(
        GridItems(
          title: title,
          icon: icon,
          route: route,
        ),
      );
    }
  }

  addIf(
    condition: modules.sales,
    title: lang.S.of(context).sale,
    icon: 'assets/sales.svg',
    route: 'Sales',
  );
  addIf(
    condition: modules.sales,
    title: lang.S.of(context).posSale,
    icon: 'images/dash_pos.svg',
    route: 'Pos Sale',
  );
  addIf(
    condition: modules.parties,
    title: lang.S.of(context).parties,
    icon: 'assets/parties.svg',
    route: 'Parties',
  );
  addIf(
    condition: modules.purchases,
    title: lang.S.of(context).purchase,
    icon: 'assets/purchase.svg',
    route: 'Purchase',
  );
  addIf(
    condition: modules.products,
    title: lang.S.of(context).product,
    icon: 'assets/products.svg',
    route: 'Products',
  );
  addIf(
    condition: modules.dues,
    title: lang.S.of(context).dueList,
    icon: 'assets/duelist.svg',
    route: 'Due List',
  );
  addIf(
    condition: modules.stocks,
    title: lang.S.of(context).stockList,
    icon: 'assets/h_stock.svg',
    route: 'Stock',
  );
  addIf(
    condition: modules.reports,
    title: lang.S.of(context).reports,
    icon: 'assets/reports.svg',
    route: 'Reports',
  );
  addIf(
    condition: modules.sales,
    title: lang.S.of(context).saleList,
    icon: 'assets/salelist.svg',
    route: 'Sales List',
  );
  addIf(
    condition: modules.purchases,
    title: lang.S.of(context).purchaseList,
    icon: 'assets/purchaseLisst.svg',
    route: 'Purchase List',
  );
  addIf(
    condition: modules.lossProfits,
    title: lang.S.of(context).profitAndLoss,
    icon: 'assets/h_lossProfit.svg',
    route: 'Loss/Profit',
  );
  addIf(
    condition: modules.parties,
    title: lang.S.of(context).ledger,
    icon: 'assets/ledger.svg',
    route: 'ledger',
  );
  addIf(
    condition: modules.incomes,
    title: lang.S.of(context).income,
    icon: 'assets/h_income.svg',
    route: 'Income',
  );
  addIf(
    condition: modules.expenses,
    title: lang.S.of(context).expense,
    icon: 'assets/expense.svg',
    route: 'Expense',
  );
  addIf(
    condition: modules.vats,
    title: lang.S.of(context).vatAndTax,
    icon: 'assets/tax.svg',
    route: 'tax',
  );
  addIf(
    condition: modules.warehouses && (warehousePermission ?? false),
    title: lang.S.of(context).warehouse,
    icon: 'assets/warehouse.svg',
    route: 'warehouse',
  );
  addIf(
    condition: modules.purchases,
    title: lang.S.of(context).customPrint,
    icon: 'assets/printer.svg',
    route: 'customPrint',
  );
  addIf(
    condition: modules.branches && (brunchPermission == true),
    title: lang.S.of(context).branch,
    icon: 'assets/branch.svg',
    route: 'branch',
  );
  addIf(
    condition: modules.hrm && (hrmPermission ?? false),
    title: lang.S.of(context).hrms,
    icon: 'assets/hrm/hrm.svg',
    route: 'hrm',
  );

  return freeIcons;
}
