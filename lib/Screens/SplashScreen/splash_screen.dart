import 'dart:async';

import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:provider/provider.dart' as provider;
import 'package:shared_preferences/shared_preferences.dart';

import '../../Repository/API/app_settings_repo.dart';
import '../../Repository/API/business_info_repo.dart';
import '../../constant.dart';
import '../../core/constant_variables/local_data_saving_keys.dart';
import '../../currency.dart';
import '../../offline/offline_service.dart';
import '../Authentication/Sign In/sign_in_screen.dart';
import '../Home/home.dart';
import '../language/language_provider.dart';
import 'on_board.dart';

class SplashScreen extends ConsumerStatefulWidget {
  const SplashScreen({super.key});
  @override
  SplashScreenState createState() => SplashScreenState();
}

class SplashScreenState extends ConsumerState<SplashScreen> with SingleTickerProviderStateMixin {
  RemoteAppSettings _settings = RemoteAppSettings();
  final _service = OfflineService.instance;
  late final AnimationController _pulse;
  bool _busy = true;
  bool _canContinueOnline = false;
  bool _signInRequired = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _pulse = AnimationController(vsync: this, duration: const Duration(milliseconds: 1600))..repeat(reverse: true);
    unawaited(_loadBrand());
    unawaited(_start());
  }

  @override
  void dispose() {
    _pulse.dispose();
    super.dispose();
  }

  Future<void> _loadBrand() async {
    final local = await AppSettingsRepository().getLocalSettings();
    if (mounted) setState(() => _settings = local);
    final remote = await AppSettingsRepository().fetchRemoteSettings();
    if (mounted) setState(() => _settings = remote);
  }

  Future<void> _start() async {
    setState(() {
      _busy = true;
      _error = null;
      _canContinueOnline = false;
    });
    try {
      final prefs = await SharedPreferences.getInstance();
      final language = prefs.getString('lang') ?? 'en';
      if (!mounted) return;
      provider.Provider.of<LanguageChangeProvider>(context, listen: false).changeLocale(language);
      await CurrencyMethods().getCurrencyFromLocalDatabase();
      await _service.initialize();
      await _service.restoreSession();
      if (prefs.getString(LocalDataBaseSavingKey.tokenKey) == null) {
        _goTo((prefs.getBool(LocalDataBaseSavingKey.skipOnBodingKey) ?? false) ? const SignIn() : const OnBoard());
        return;
      }
      if (_service.ready) {
        // Returning users open their database immediately. Reconnection is in the background.
        _goTo(const Home());
        unawaited(_service.sync());
        return;
      }
      await _service.sync();
      if (_service.ready) {
        _goTo(const Home());
        return;
      }
      _signInRequired = _service.connection == ServerConnection.signInRequired;
      if (_service.online && !_signInRequired) {
        try {
          _canContinueOnline = await BusinessRepository().checkBusinessData() != null;
        } catch (_) {
          _canContinueOnline = false;
        }
      }
      _error = _service.lastError ?? _service.status;
    } catch (error) {
      _error = 'Unable to prepare saved data. Please retry. $error';
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  void _goTo(Widget page) {
    if (!mounted) return;
    Navigator.of(context).pushAndRemoveUntil(MaterialPageRoute(builder: (_) => page), (_) => false);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _settings.splashBgColor,
      body: SafeArea(
        child: LayoutBuilder(
          builder: (context, constraints) => SingleChildScrollView(
            child: ConstrainedBox(
              constraints: BoxConstraints(minHeight: constraints.maxHeight),
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 24),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const SizedBox(height: 32),
                    AnimatedBuilder(
                      animation: _pulse,
                      builder: (context, child) => Container(
                        padding: const EdgeInsets.all(18),
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          border: Border.all(
                            color: Colors.white.withValues(alpha: 0.12 + _pulse.value * 0.12),
                            width: 1.5,
                          ),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.white.withValues(alpha: 0.05 + _pulse.value * 0.05),
                              blurRadius: 38,
                              spreadRadius: 10,
                            ),
                          ],
                        ),
                        child: child,
                      ),
                      child: Container(
                        width: 156,
                        height: 156,
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(32),
                          boxShadow: const [BoxShadow(color: Color(0x26000000), blurRadius: 28, offset: Offset(0, 12))],
                        ),
                        child: ClipRRect(
                          borderRadius: BorderRadius.circular(22),
                          child: _settings.splashLogoUrl?.isNotEmpty == true
                              ? CachedNetworkImage(
                                  imageUrl: _settings.splashLogoUrl!,
                                  fit: BoxFit.contain,
                                  placeholder: (_, _) => Image.asset(splashLogo),
                                  errorWidget: (_, _, _) => Image.asset(splashLogo),
                                )
                              : Image.asset(splashLogo, fit: BoxFit.contain),
                        ),
                      ),
                    ),
                    const SizedBox(height: 38),
                    ListenableBuilder(
                      listenable: _service,
                      builder: (context, _) => Container(
                        constraints: const BoxConstraints(maxWidth: 420),
                        padding: const EdgeInsets.all(22),
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(24),
                          border: Border.all(color: Colors.white.withValues(alpha: 0.2)),
                        ),
                        child: Column(
                          children: [
                            if (_busy)
                              SizedBox(
                                height: 30,
                                width: 30,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2.5,
                                  color: Colors.white,
                                  backgroundColor: Colors.white24,
                                  value: _service.downloading ? _service.progress : null,
                                ),
                              )
                            else
                              Icon(
                                _signInRequired ? Icons.lock_outline_rounded : Icons.cloud_off_rounded,
                                color: Colors.white,
                                size: 30,
                              ),
                            const SizedBox(height: 18),
                            Text(
                              _busy
                                  ? (_service.downloading ? 'Preparing your business' : 'Connecting securely')
                                  : 'Connection needs attention',
                              textAlign: TextAlign.center,
                              style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.w600),
                            ),
                            const SizedBox(height: 10),
                            Semantics(
                              liveRegion: true,
                              child: Text(
                                _error ?? _service.status,
                                textAlign: TextAlign.center,
                                style: const TextStyle(color: Colors.white, fontSize: 14, height: 1.5),
                              ),
                            ),
                            if (_service.downloading) ...[
                              const SizedBox(height: 18),
                              ClipRRect(
                                borderRadius: BorderRadius.circular(8),
                                child: LinearProgressIndicator(
                                  minHeight: 5,
                                  value: _service.progress,
                                  color: Colors.white,
                                  backgroundColor: Colors.white24,
                                ),
                              ),
                              const SizedBox(height: 10),
                              Text(
                                '${((_service.progress ?? 0) * 100).round()}% · Keep the app open for the first download',
                                textAlign: TextAlign.center,
                                style: const TextStyle(color: Colors.white, fontSize: 12),
                              ),
                            ],
                            if (!_busy) ...[
                              const SizedBox(height: 18),
                              FilledButton(
                                style: FilledButton.styleFrom(
                                  backgroundColor: Colors.white,
                                  foregroundColor: _settings.splashBgColor,
                                ),
                                onPressed: _start,
                                child: const Text('Try again'),
                              ),
                              if (_canContinueOnline)
                                TextButton(
                                  onPressed: () => _goTo(const Home()),
                                  child: const Text('Continue online', style: TextStyle(color: Colors.white)),
                                ),
                              if (_signInRequired)
                                TextButton(
                                  onPressed: () => _goTo(const SignIn()),
                                  child: const Text('Sign in to reconnect', style: TextStyle(color: Colors.white)),
                                ),
                            ],
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 36),
                    Text(
                      'Powered by ${_settings.poweredBy.isNotEmpty ? _settings.poweredBy : companyName}',
                      textAlign: TextAlign.center,
                      style: const TextStyle(color: Colors.white, fontSize: 13),
                    ),
                    const SizedBox(height: 20),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
