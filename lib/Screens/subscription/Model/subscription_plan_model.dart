class SubscriptionPlanModel {
  final int? id;
  final String? subscriptionName;
  final int? duration;
  final double? offerPrice;
  final double? subscriptionPrice;
  final int? status;
  final Map<String, dynamic>? features;

  num get currentPrice {
    return offerPrice ?? subscriptionPrice ?? 0;
  }

  bool get hasOffer {
    return offerPrice != null && offerPrice! >= 0;
  }

  const SubscriptionPlanModel({
    this.id,
    this.subscriptionName,
    this.duration,
    this.offerPrice,
    this.subscriptionPrice,
    this.status,
    this.features,
  });

  factory SubscriptionPlanModel.fromJson(Map<String, dynamic> json) {
    final features = <String, dynamic>{};
    final availableModules = json['available_modules'] is Map
        ? Map<String, dynamic>.from(json['available_modules'])
        : <String, dynamic>{};
    for (final entry in availableModules.entries) {
      features['module_${entry.key}'] = [
        '${entry.key[0].toUpperCase()}${entry.key.substring(1)}',
        if (entry.value == true) '1',
      ];
    }
    String? formatLimitValue(dynamic value) {
      if (value == null) return null;
      if (value == -1) return 'Unlimited';
      if (value is num && value > 0) return value.toString();
      return null;
    }

    final limitFields = {
      'user_limit': 'User Limit',
      'product_limit': 'Product Limit',
      'customer_limit': 'Customer Limit',
      'supplier_limit': 'Supplier Limit',
      'addon_domain_limit': 'Addon Domain Limit',
      'subdomain_limit': 'Subdomain Limit',
    };
    for (final entry in limitFields.entries) {
      final value = formatLimitValue(json[entry.key]);
      if (value != null) {
        features['limit_${entry.key}'] = ['${entry.value}: $value', '1'];
      }
    }

    if (availableModules['warehouses'] == true) {
      final value = formatLimitValue(json['warehouse_limit']);
      if (value != null) {
        features['limit_warehouse_limit'] = ['Warehouse Limit: $value', '1'];
      }
    }

    if (availableModules['branches'] == true) {
      final allowMultibranch = json['allow_multibranch'];
      if (allowMultibranch == 1) {
        features['limit_branch_limit'] = ['Branch Limit: Multi-branch Allowed', '1'];
      } else {
        final value = formatLimitValue(json['branch_limit']);
        if (value != null) {
          features['limit_branch_limit'] = ['Branch Limit: $value', '1'];
        }
      }
    }

    final rawFeatures = json['features'] is Map ? Map<String, dynamic>.from(json['features']) : <String, dynamic>{};
    features.addAll(rawFeatures);

    return SubscriptionPlanModel(
      id: json['id'],
      subscriptionName: json['subscriptionName'],
      duration: json['duration'],
      offerPrice: json['offerPrice']?.toDouble(),
      subscriptionPrice: json['subscriptionPrice'].toDouble(),
      status: json['status'],
      features: features,
    );
  }

  @override
  bool operator ==(Object other) {
    return other is SubscriptionPlanModel && other.id == id;
  }

  @override
  int get hashCode => id.hashCode;
}
