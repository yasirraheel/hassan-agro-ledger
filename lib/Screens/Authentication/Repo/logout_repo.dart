import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:http/http.dart' as http;
import 'package:restart_app/restart_app.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../../Const/api_config.dart';
import '../../../Repository/constant_functions.dart';
import '../../../currency.dart';
import '../../../offline/offline_service.dart';

class LogOutRepo {
  Future<void> signOut({bool restart = true}) async {
    if (!await OfflineService.instance.canSignOut()) {
      EasyLoading.showError('Sync your pending work before signing out.');
      return;
    }
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove("token");
    await prefs.remove("hasShownExpiredDialog");
    CurrencyMethods().removeCurrencyFromLocalDatabase();
    if (restart) {
      EasyLoading.showSuccess('Successfully Logged Out');
      Restart.restartApp();
    }
  }

  Future<void> signOutApi() async {
    if (!await OfflineService.instance.canSignOut()) {
      EasyLoading.showError('Sync your pending work before signing out.');
      return;
    }
    try {
      final uri = Uri.parse('${APIConfig.url}/sign-out');

      await http.get(
        uri,
        headers: {
          'Accept': 'application/json',
          'Authorization': await getAuthToken(),
        },
      );
      await signOut(restart: false);
    } catch (e) {
      await signOut(restart: false);
    }
  }
}
