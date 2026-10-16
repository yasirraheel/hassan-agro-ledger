import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:internet_connection_checker_plus/internet_connection_checker_plus.dart';
import 'package:mobile_pos/Screens/SplashScreen/on_board.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;
import 'package:nb_utils/nb_utils.dart';
import 'package:nb_utils/nb_utils.dart' as SystemNavigator;
import 'package:permission_handler/permission_handler.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'package:cached_network_image/cached_network_image.dart';
import '../../Repository/API/app_settings_repo.dart';
import '../../Repository/API/business_info_repo.dart';
import '../../core/constant_variables/local_data_saving_keys.dart';
import '../../currency.dart';
import '../Authentication/Repo/licnese_repo.dart';
import '../Authentication/Sign In/sign_in_screen.dart';
import '../Home/home.dart';
import '../language/language_provider.dart';

class SplashScreen extends ConsumerStatefulWidget {
  const SplashScreen({super.key});

  @override
  SplashScreenState createState() => SplashScreenState();
}

class SplashScreenState extends ConsumerState<SplashScreen> {
  void getPermission() async {
    Map<Permission, PermissionStatus> statuses = await [
      Permission.bluetoothScan,
      Permission.bluetoothConnect,
    ].request();
  }

  int retryCount = 0;
  RemoteAppSettings _appSettings = RemoteAppSettings();

  Future<void> _loadAppSettings() async {
    final local = await AppSettingsRepository().getLocalSettings();
    if (mounted) setState(() => _appSettings = local);
    final remote = await AppSettingsRepository().fetchRemoteSettings();
    if (mounted) setState(() => _appSettings = remote);
  }

  Future<void> checkUserValidity() async {
    nextPage();
  }

  @override
  void initState() {
    super.initState();
    _loadAppSettings();
    getPermission();
    CurrencyMethods().getCurrencyFromLocalDatabase();
    checkUserValidity();
    setLanguage();
  }

  Future<void> setLanguage() async {
    final prefs = await SharedPreferences.getInstance();
    final savedLanguageCode = prefs.getString('lang') ?? 'en'; // Default to English code
    setState(() {
      selectedLanguage = savedLanguageCode;
    });
    context.read<LanguageChangeProvider>().changeLocale(savedLanguageCode);
  }

  Future<void> nextPage() async {
    final prefs = await SharedPreferences.getInstance();
    await Future.delayed(const Duration(seconds: 2));

    final token = prefs.getString(LocalDataBaseSavingKey.tokenKey);
    final skipOnBoard = prefs.getBool(LocalDataBaseSavingKey.skipOnBodingKey) ?? false;

    if (token == null) {
      CurrencyMethods().removeCurrencyFromLocalDatabase();
      return _goTo(skipOnBoard ? const SignIn() : const OnBoard());
    }

    final data = await BusinessRepository().checkBusinessData();
    _goTo(data == null ? (skipOnBoard ? const SignIn() : const OnBoard()) : const Home());
  }

  void _goTo(Widget page) {
    Navigator.of(context).pushAndRemoveUntil(MaterialPageRoute(builder: (_) => page), (_) => false);
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final effectivePoweredBy = _appSettings.poweredBy.isNotEmpty ? _appSettings.poweredBy : companyName;

    return SafeArea(
      child: Scaffold(
        backgroundColor: _appSettings.splashBgColor,
        body: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            const Spacer(),
            Container(
              height: 220,
              width: 220,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(28),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.15),
                    blurRadius: 16,
                    offset: const Offset(0, 8),
                  ),
                ],
              ),
              child: ClipRRect(
                borderRadius: BorderRadius.circular(28),
                child: _appSettings.splashLogoUrl != null && _appSettings.splashLogoUrl!.isNotEmpty
                    ? CachedNetworkImage(
                        imageUrl: _appSettings.splashLogoUrl!,
                        fit: BoxFit.contain,
                        placeholder: (context, url) => Image.asset(splashLogo, fit: BoxFit.contain),
                        errorWidget: (context, url, error) => Image.asset(splashLogo, fit: BoxFit.contain),
                      )
                    : Image.asset(splashLogo, fit: BoxFit.contain),
              ),
            ),
            const Spacer(),
            Center(
              child: Text(
                '${lang.S.of(context).poweredBy} $effectivePoweredBy',
                style: theme.textTheme.titleLarge?.copyWith(
                  color: Colors.white,
                  fontWeight: FontWeight.w500,
                  fontSize: 18,
                ),
              ),
            ),
            const SizedBox(height: 16),
          ],
        ),
      ),
    );
  }
}
