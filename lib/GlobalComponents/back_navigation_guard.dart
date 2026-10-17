import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;

/// Handles Android back consistently, including predictive back gestures.
/// Pushed screens pop normally; only a real app exit asks for confirmation.
class AppBackGuard extends StatelessWidget {
  const AppBackGuard({required this.child, super.key});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    final canPop = Navigator.of(context).canPop();
    return PopScope<void>(
      canPop: canPop,
      onPopInvokedWithResult: (didPop, result) {
        if (didPop || canPop || !context.mounted) return;
        showDialog<bool>(
          context: context,
          builder: (dialogContext) => AlertDialog(
            title: Text(lang.S.of(dialogContext).areYouSure),
            content: Text(lang.S.of(dialogContext).doYouWantToExitTheApp),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(dialogContext, false),
                child: Text(lang.S.of(dialogContext).no),
              ),
              TextButton(
                onPressed: () => Navigator.pop(dialogContext, true),
                child: Text(lang.S.of(dialogContext).yes),
              ),
            ],
          ),
        ).then((shouldExit) {
          if (shouldExit == true) SystemNavigator.pop();
        });
      },
      child: child,
    );
  }
}
