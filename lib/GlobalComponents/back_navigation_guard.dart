import 'package:flutter/material.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;

/// Handles Android back consistently for screens that can also be opened as
/// the first route. Pushed screens pop normally; only a real app exit asks for
/// confirmation.
Future<bool> handleBackNavigation(BuildContext context) async {
  final navigator = Navigator.of(context);
  if (navigator.canPop()) {
    navigator.pop();
    return false;
  }

  return await showDialog<bool>(
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
      ) ??
      false;
}
