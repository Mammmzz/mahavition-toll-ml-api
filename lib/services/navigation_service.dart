import 'package:flutter/material.dart';

class NavigationService {
  static final GlobalKey<NavigatorState> navigatorKey = GlobalKey<NavigatorState>();
  static BuildContext? get context => navigatorKey.currentContext;
  
  /// Check if the navigator is ready to be used
  static bool get isReady => navigatorKey.currentContext != null;
  
  /// Get the current context with a safety check
  static BuildContext? get safeContext => isReady ? navigatorKey.currentContext : null;
  
  /// Show a snackbar using the navigator context
  static void showSnackBar(String message, {Duration? duration}) {
    if (isReady) {
      ScaffoldMessenger.of(navigatorKey.currentContext!).showSnackBar(
        SnackBar(
          content: Text(message),
          duration: duration ?? const Duration(seconds: 4),
        ),
      );
    } else {
      debugPrint('⚠️ Cannot show snackbar: No navigator context available');
    }
  }
}
