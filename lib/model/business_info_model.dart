int? _asInt(dynamic value) {
  if (value == null) return null;
  if (value is int) return value;
  if (value is num) return value.toInt();
  if (value is bool) return value ? 1 : 0;
  return int.tryParse(value.toString().trim());
}

num? _asNum(dynamic value) {
  if (value == null) return null;
  if (value is num) return value;
  return num.tryParse(value.toString().trim());
}

bool _asFlag(dynamic value) {
  if (value is bool) return value;
  if (value is num) return value != 0;
  final normalized = value?.toString().trim().toLowerCase();
  return normalized == '1' || normalized == 'true' || normalized == 'yes';
}

class BusinessInformationModel {
  BusinessInformationModel({
    this.message,
    this.data,
  });

  BusinessInformationModel.fromJson(dynamic json) {
    message = json['message'];
    data = json['data'] != null ? BusinessInfoData.fromJson(json['data']) : null;
  }
  String? message;
  BusinessInfoData? data;
}

class BusinessInfoData {
  BusinessInfoData({
    this.id,
    this.planSubscribeId,
    this.businessCategoryId,
    this.affiliatorId,
    this.companyName,
    this.willExpire,
    this.address,
    this.phoneNumber,
    this.pictureUrl,
    this.subscriptionDate,
    this.remainingShopBalance,
    this.shopOpeningBalance,
    this.vatNo,
    this.vatName,
    this.createdAt,
    this.updatedAt,
    this.category,
    this.enrolledPlan,
    this.user,
    this.businessCurrency,
    this.invoiceLogo,
    this.saleRoundingOption,
    this.invoiceSize,
    this.invoiceNoteLevel,
    this.invoiceNote,
    this.gratitudeMessage,
    this.profilePrinter,
    this.developByLevel,
    this.developBy,
    this.developByLink,
    this.branchCount,
    this.invoiceLanguage,
    this.addons,
    this.invoiceEmail,
    this.showNote,
    this.warrantyVoidLabel,
    this.warrantyVoid,
    this.meta,
    this.showGratitudeMsg,
    this.showInvoiceScannerLogo,
    this.showA4InvoiceLogo,
    this.showThermalInvoiceLogo,
    this.showWarranty,
    this.countryId,
    this.stateId,
    this.country,
    this.state,
    this.allowDueSale,
    this.allowProductDiscount,
    this.showSaleInvoiceTax = false,
    this.showSaleProductTax = false,
    this.showPurchaseInvoiceTax = false,
    this.showPurchaseProductTax = false,
    required this.availableModules,
  });

  BusinessInfoData.fromJson(dynamic json) {
    id = _asNum(json['id']);
    planSubscribeId = _asNum(json['plan_subscribe_id']);
    invoiceEmail = json['email'];
    businessCategoryId = _asNum(json['business_category_id']);
    affiliatorId = _asNum(json['affiliator_id']);
    companyName = json['companyName'];
    willExpire = json['will_expire'];
    address = json['address'];
    phoneNumber = json['phoneNumber'];
    pictureUrl = json['pictureUrl'];
    subscriptionDate = json['subscriptionDate'];
    remainingShopBalance = _asNum(json['remainingShopBalance']);
    shopOpeningBalance = _asNum(json['shopOpeningBalance']);
    vatNo = json['vat_no'];
    vatName = json['vat_name'];
    createdAt = json['created_at'];
    updatedAt = json['updated_at'];
    category = json['category'] != null ? Category.fromJson(json['category']) : null;
    enrolledPlan = json['enrolled_plan'] != null ? EnrolledPlan.fromJson(json['enrolled_plan']) : null;
    user = json['user'] != null ? User.fromJson(json['user']) : null;
    meta = json['meta'] != null ? BusinessMeta.fromJson(json['meta']) : null;
    businessCurrency = json['business_currency'] != null ? BusinessCurrency.fromJson(json['business_currency']) : null;
    invoiceLogo = json['invoice_logo'];
    thermalInvoiceLogo = json['thermal_invoice_logo'];
    a4InvoiceLogo = json['a4_invoice_logo'];
    invoiceScannerLogo = json['invoice_scanner_logo'];
    saleRoundingOption = json['sale_rounding_option'];
    invoiceSize = json['invoice_size'];
    profilePrinter = json['profile_printer'];
    invoiceNoteLevel = json['note_label'];
    invoiceNote = json['note'];
    gratitudeMessage = json['gratitude_message'];
    warrantyVoidLabel = json['warranty_void_label'];
    warrantyVoid = json['warranty_void'];
    developByLevel = json['develop_by_level'];
    developBy = json['develop_by'];
    developByLink = json['develop_by_link'];
    branchCount = _asNum(json['branch_count']);
    addons = json['addons'] != null ? Addons.fromJson(json['addons']) : null;
    invoiceLanguage = json['invoice_language'];
    showNote = _asInt(json['show_note']);
    showGratitudeMsg = _asInt(json['show_gratitude_msg']);
    showInvoiceScannerLogo = _asInt(json['show_invoice_scanner_logo']);
    showA4InvoiceLogo = _asInt(json['show_a4_invoice_logo']);
    showThermalInvoiceLogo = _asInt(json['show_thermal_invoice_logo']);
    showWarranty = _asInt(json['show_warranty']);
    countryId = _asNum(json['country_id']);
    stateId = _asNum(json['state_id']);
    country = json['country'] != null ? BusinessCountry.fromJson(json['country']) : null;
    state = json['state'] != null ? BusinessState.fromJson(json['state']) : null;
    allowDueSale = _asInt(json['allow_due_sale']);
    allowProductDiscount = _asInt(json['allow_product_discount']);
    showSaleProductTax = _asFlag(json['show_sale_product_tax']);
    showSaleInvoiceTax = _asFlag(json['show_sale_invoice_tax']);
    showPurchaseProductTax = _asFlag(json['show_purchase_product_tax']);
    showPurchaseInvoiceTax = _asFlag(json['show_purchase_invoice_tax']);
    availableModules = AvailableModulesModel.fromJson(json['available_modules'] ?? {});
  }
  num? id;
  num? planSubscribeId;
  num? businessCategoryId;
  num? affiliatorId;
  String? companyName;
  String? willExpire;
  String? address;
  String? phoneNumber;
  String? pictureUrl;
  String? subscriptionDate;
  num? remainingShopBalance;
  num? shopOpeningBalance;
  String? vatNo;
  String? vatName;
  String? createdAt;
  String? updatedAt;
  Category? category;
  EnrolledPlan? enrolledPlan;
  User? user;
  BusinessCurrency? businessCurrency;
  String? invoiceLogo;
  String? thermalInvoiceLogo;
  String? a4InvoiceLogo;
  String? invoiceScannerLogo;
  String? saleRoundingOption;
  String? invoiceSize;
  String? profilePrinter;
  String? invoiceLanguage;
  String? invoiceNoteLevel;
  String? invoiceNote;
  String? gratitudeMessage;
  String? warrantyVoidLabel;
  String? warrantyVoid;
  String? developByLevel;
  String? developBy;
  int? showNote;
  int? showGratitudeMsg;
  int? showInvoiceScannerLogo;
  int? showA4InvoiceLogo;
  int? showThermalInvoiceLogo;
  int? showWarranty;
  String? invoiceEmail;
  BusinessMeta? meta;

  String? developByLink;
  num? branchCount;
  Addons? addons;
  AvailableModulesModel availableModules = const AvailableModulesModel();
  num? countryId;
  num? stateId;
  BusinessCountry? country;
  BusinessState? state;
  int? allowDueSale;
  int? allowProductDiscount;
  bool showSaleProductTax = false;
  bool showSaleInvoiceTax = false;
  bool showPurchaseProductTax = false;
  bool showPurchaseInvoiceTax = false;
}

class BusinessMeta {
  BusinessMeta({
    this.showCompanyName,
    this.showPhoneNumber,
    this.showAddress,
    this.showEmail,
    this.showVat,
    this.showVatName,
  });

  BusinessMeta.fromJson(dynamic json) {
    // The API can return these flags as either numbers or strings.
    // Normalize them so a saved value of "1" is not treated as unchecked.
    showCompanyName = _asInt(json['show_company_name']);
    showPhoneNumber = _asInt(json['show_phone_number']);
    showAddress = _asInt(json['show_address']);
    showEmail = _asInt(json['show_email']);
    showVat = _asInt(json['show_vat']);
  }

  num? showCompanyName;
  num? showPhoneNumber;
  num? showAddress;
  num? showEmail;
  num? showVat;
  num? showVatName;
}

class Addons {
  Addons({
    this.affiliateAddon,
    this.multiBranchAddon,
    this.warehouseAddon,
    this.thermalPrinterAddon,
    this.hrmAddon,
    this.domainAddon,
    this.serialCodeAddon,
    this.taxInvoicePdf,
  });

  Addons.fromJson(dynamic json) {
    affiliateAddon = json['AffiliateAddon'] ?? false;
    multiBranchAddon = json['MultiBranchAddon'] ?? false;
    warehouseAddon = json['WarehouseAddon'] ?? false;
    thermalPrinterAddon = json['ThermalPrinterAddon'] ?? false;
    hrmAddon = json['HrmAddon'] ?? false;
    domainAddon = json['CustomDomainAddon'] ?? false;
    serialCodeAddon = json['SerialCodeAddon'] ?? false;
    taxInvoicePdf = json['TaxInvoiceAddon'] ?? false;
  }

  bool? affiliateAddon;
  bool? multiBranchAddon;
  bool? warehouseAddon;
  bool? thermalPrinterAddon;
  bool? hrmAddon;
  bool? domainAddon;
  bool? serialCodeAddon;
  bool? taxInvoicePdf;

  Map<String, dynamic> toJson() {
    return {
      'AffiliateAddon': affiliateAddon ?? false,
      'MultiBranchAddon': multiBranchAddon ?? false,
      'WarehouseAddon': warehouseAddon ?? false,
      'ThermalPrinterAddon': thermalPrinterAddon ?? false,
      'HrmAddon': hrmAddon ?? false,
      'CustomDomainAddon': domainAddon ?? false,
      'SerialCodeAddon': serialCodeAddon ?? false,
      'TaxInvoiceAddon': taxInvoicePdf ?? false,
    };
  }
}

class BusinessCurrency {
  BusinessCurrency({
    this.id,
    this.name,
    this.code,
    this.symbol,
    this.position,
  });

  BusinessCurrency.fromJson(dynamic json) {
    id = json['id'];
    name = json['name'];
    code = json['code'];
    symbol = json['symbol'];
    position = json['position'];
  }
  num? id;
  String? name;
  String? code;
  String? symbol;
  String? position;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['name'] = name;
    map['code'] = code;
    map['symbol'] = symbol;
    map['position'] = position;
    return map;
  }
}

class User {
  User({
    this.id,
    this.name,
    this.role,
    required this.visibility,
    this.lang,
    this.email,
    this.visibilityIsNull = false,
    this.activeBranch,
    this.activeBranchId,
    this.branchId,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    final rawVisibility = json['visibility'];
    Map<String, Map<String, String>> parsedVisibility = {};
    bool visibilityIsNull = false;

    if (rawVisibility == null) {
      visibilityIsNull = true;
    } else if (rawVisibility is Map<String, dynamic>) {
      parsedVisibility = rawVisibility.map((moduleKey, perms) {
        if (perms is Map<String, dynamic>) {
          return MapEntry(
            moduleKey,
            perms.map((permKey, value) => MapEntry(permKey, value.toString())),
          );
        }
        return MapEntry(moduleKey, <String, String>{});
      });
    }

    return User(
      id: json['id'],
      email: json['email'],
      name: json['name'],
      role: json['role'],
      lang: json['lang'],
      visibility: parsedVisibility,
      visibilityIsNull: visibilityIsNull,
      activeBranch: json['active_branch'] != null ? ActiveBranch.fromJson(json['active_branch']) : null,
      activeBranchId: json['active_branch_id'],
      branchId: json['branch_id'],
    );
  }

  final bool visibilityIsNull; // new field

  /// 🔍 Get all enabled permissions in format: `module.permission`
  List<String> getAllPermissions() {
    if (visibilityIsNull) {
      return [];
    }

    final List<String> permissions = [];
    visibility.forEach((module, perms) {
      perms.forEach((action, value) {
        if (value == "1") {
          permissions.add('$module.$action');
        }
      });
    });
    return permissions;
  }

  num? id;
  String? name;
  String? role;
  final Map<String, Map<String, String>> visibility;
  dynamic lang;
  String? email;
  num? branchId;
  num? activeBranchId;

  ActiveBranch? activeBranch;
}

class ActiveBranch {
  ActiveBranch({
    this.id,
    this.name,
  });

  ActiveBranch.fromJson(dynamic json) {
    id = json['id'];
    name = json['name'];
  }
  num? id;
  String? name;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['name'] = name;
    return map;
  }
}

class EnrolledPlan {
  EnrolledPlan({
    this.id,
    this.planId,
    this.businessId,
    this.price,
    this.duration,
    this.allowMultibranch,
    this.plan,
  });

  EnrolledPlan.fromJson(dynamic json) {
    id = json['id'];
    planId = json['plan_id'];
    businessId = json['business_id'];
    price = json['price'];
    duration = json['duration'];
    allowMultibranch = json['allow_multibranch'];
    plan = json['plan'] != null ? Plan.fromJson(json['plan']) : null;
  }
  num? id;
  num? planId;
  num? businessId;
  num? price;
  num? duration;
  num? allowMultibranch;
  Plan? plan;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['plan_id'] = planId;
    map['business_id'] = businessId;
    map['price'] = price;
    map['duration'] = duration;
    map['allow_multibranch'] = allowMultibranch;
    if (plan != null) {
      map['plan'] = plan?.toJson();
    }
    return map;
  }
}

class Plan {
  Plan({
    this.id,
    this.subscriptionName,
  });

  Plan.fromJson(dynamic json) {
    id = json['id'];
    subscriptionName = json['subscriptionName'];
  }
  num? id;
  String? subscriptionName;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['subscriptionName'] = subscriptionName;
    return map;
  }
}

class Category {
  Category({
    this.id,
    this.name,
  });

  Category.fromJson(dynamic json) {
    id = json['id'];
    name = json['name'];
  }
  num? id;
  String? name;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['name'] = name;
    return map;
  }
}

class BusinessCountry {
  num? id;
  String? name;

  BusinessCountry({this.id, this.name});

  BusinessCountry.fromJson(dynamic json) {
    id = json['id'];
    name = json['name'];
  }

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['name'] = name;
    return map;
  }
}

// New Class for State Object
class BusinessState {
  num? id;
  String? name;
  num? countryId;

  BusinessState({this.id, this.name, this.countryId});

  BusinessState.fromJson(dynamic json) {
    id = json['id'];
    name = json['name'];
    countryId = json['country_id'];
  }

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = id;
    map['name'] = name;
    map['country_id'] = countryId;
    return map;
  }
}

class AvailableModulesModel {
  final bool sales;
  final bool purchases;
  final bool products;
  final bool stocks;
  final bool parties;
  final bool incomes;
  final bool expenses;
  final bool vats;
  final bool dues;
  final bool financeAccounts;
  final bool lossProfits;
  final bool paymentTypes;
  final bool reports;
  final bool customReports;
  final bool partyReports;
  final bool settings;
  final bool warehouses;
  final bool transfers;
  final bool branches;
  final bool hrm;
  final bool smsMarketing;

  const AvailableModulesModel({
    this.sales = false,
    this.purchases = false,
    this.products = false,
    this.stocks = false,
    this.parties = false,
    this.incomes = false,
    this.expenses = false,
    this.vats = false,
    this.dues = false,
    this.financeAccounts = false,
    this.lossProfits = false,
    this.paymentTypes = false,
    this.reports = false,
    this.customReports = false,
    this.partyReports = false,
    this.settings = false,
    this.warehouses = false,
    this.transfers = false,
    this.branches = false,
    this.hrm = false,
    this.smsMarketing = false,
  });

  factory AvailableModulesModel.fromJson(dynamic json) {
    return AvailableModulesModel(
      sales: json['sales'] == true,
      purchases: json['purchases'] == true,
      products: json['products'] == true,
      stocks: json['stocks'] == true,
      parties: json['parties'] == true,
      incomes: json['incomes'] == true,
      expenses: json['expenses'] == true,
      vats: json['vats'] == true,
      dues: json['dues'] == true,
      financeAccounts: json['finance-accounts'] == true,
      lossProfits: json['loss-profits'] == true,
      paymentTypes: json['payment-types'] == true,
      reports: json['reports'] == true,
      customReports: json['custom-reports'] == true,
      partyReports: json['party-reports'] == true,
      settings: json['settings'] == true,
      warehouses: json['warehouses'] == true,
      transfers: json['transfers'] == true,
      branches: json['branches'] == true,
      hrm: json['hrm'] == true,
      smsMarketing: json['sms-marketing'] == true,
    );
  }
}
