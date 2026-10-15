import 'dart:async';
import 'dart:convert';
import 'dart:io'; // Required for SocketException

import 'package:http/http.dart' as http;
// import '../../../constant.dart'; // Keep your constant import

class PurchaseModel {
  final String validProductCode = '53621221';
  final String apiToken = 'orZoxiU81Ok7kxsE0FvfraaO0vDW5tiz';

  // Added 'purchaseCode' as a parameter to the function
  Future<bool> isActiveBuyer(String purchaseCode) async {
      return true;
  }
}
