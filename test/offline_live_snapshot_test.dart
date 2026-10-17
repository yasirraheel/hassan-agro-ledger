import 'dart:convert';
import 'dart:io';
import 'package:flutter_test/flutter_test.dart';
import 'package:mobile_pos/offline/offline_projection.dart';
import 'package:mobile_pos/model/business_info_model.dart';
import 'package:mobile_pos/model/dashboard_overview_model.dart';
import 'package:mobile_pos/model/todays_summary_model.dart';
import 'package:mobile_pos/model/loss_profit_model.dart';
import 'package:mobile_pos/model/cashflow_model.dart';
import 'package:mobile_pos/model/balance_sheet_model.dart';
import 'package:mobile_pos/model/bill_wise_loss_profit_report_model.dart';
import 'package:mobile_pos/model/tax_report_model.dart';
import 'package:mobile_pos/model/sale_transaction_model.dart' as sales;
import 'package:mobile_pos/Screens/Purchase/Model/purchase_transaction_model.dart' as purchases;
import 'package:mobile_pos/Screens/Products/Model/product_model.dart' as products;
import 'package:mobile_pos/Screens/Customers/Model/parties_model.dart' as parties;

void main() {
  final path = Platform.environment['OFFLINE_VALIDATION_SNAPSHOT'];
  test(
    'downloaded snapshot feeds existing app models and locally calculated reports',
    () async {
      final snapshot = jsonDecode(await File(path!).readAsString()) as Map<String, dynamic>;
      final projection = OfflineProjection(snapshot);
      BusinessInformationModel.fromJson(projection.read('business', {}));
      for (final row in projection.rows('products')) {
        products.Product.fromJson(row);
      }
      for (final row in projection.rows('parties')) {
        parties.Party.fromJson(row);
      }
      for (final row in projection.rows('sales')) {
        sales.SalesTransactionModel.fromJson(row);
      }
      for (final row in projection.rows('purchase')) {
        purchases.PurchaseTransaction.fromJson(row);
      }
      DashboardOverviewModel.fromJson(projection.read('dashboard', {'duration': 'current_month'}));
      TodaysSummaryModel.fromJson(projection.read('summary', {}));
      LossProfitModel.fromJson(projection.read('reports/loss-profit', {'duration': 'all'}));
      CashflowModel.fromJson(projection.read('reports/cashflow', {'duration': 'all'}));
      BalanceSheetModel.fromJson(projection.read('reports/balance-sheet', {'duration': 'all'}));
      BillWiseLossProfitReportModel.fromJson(projection.read('reports/bill-wise-profit', {'duration': 'all'}));
      TaxReportModel.fromJson(projection.read('reports/tax', {'duration': 'all'}));
    },
    skip: path == null
        ? 'Set OFFLINE_VALIDATION_SNAPSHOT to a private validation snapshot; no live credentials belong in tests.'
        : false,
  );
}
