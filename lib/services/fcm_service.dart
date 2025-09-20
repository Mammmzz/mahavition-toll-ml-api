import 'dart:convert';
import 'dart:io';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:http/http.dart' as http;
import 'package:timezone/data/latest.dart' as tz;
import 'package:timezone/timezone.dart' as tz;
import 'navigation_service.dart';

class FCMService {
  static final FirebaseMessaging messaging = FirebaseMessaging.instance;
  static final FlutterLocalNotificationsPlugin _localNotificationsPlugin = 
      FlutterLocalNotificationsPlugin();
  static String? _fcmToken;
  static bool _isInitialized = false;
  
  // API configuration - updated for lomba toll-api
  static const String _apiBaseUrl = "http://192.168.1.9:8080/api"; // Lomba toll-api port 8080
  
  /// Initialize FCM service
  static Future<void> initialize() async {
    if (_isInitialized) {
      print('ℹ️ FCM Service already initialized');
      return;
    }

    print('🔄 Initializing FCM Service...');
    
    // Initialize timezone
    tz.initializeTimeZones();
    
    // Initialize local notifications
    await _initLocalNotifications();
    
    // Request notification permission
    print('🔔 Requesting notification permission...');
    NotificationSettings settings = await messaging.requestPermission(
      alert: true,
      badge: true,
      sound: true,
      provisional: false,
    );
    
    print('🔔 Permission status: ${settings.authorizationStatus}');
    if (settings.authorizationStatus == AuthorizationStatus.authorized) {
      print('✅ FCM: User granted notification permission');
      
      // Get FCM token
      await _getFCMToken();
      
      // Setup message handlers
      _setupMessageHandlers();
      
      // Listen for token refresh
      FirebaseMessaging.instance.onTokenRefresh.listen((newToken) {
        print('🔄 FCM: Token refreshed');
        _fcmToken = newToken;
        // You can re-register the token here if needed
      });
    } else {
      print('❌ FCM: User denied notification permission');
    }
  }
  
  /// Get FCM token
  static Future<String?> _getFCMToken() async {
    try {
      _fcmToken = await messaging.getToken();
      print('📱 FCM Token: $_fcmToken');
      return _fcmToken;
    } catch (e) {
      print('❌ Error getting FCM token: $e');
      return null;
    }
  }
  
  /// Get current FCM token
  static String? get fcmToken => _fcmToken;
  
  /// Register FCM token to toll-api server
  static Future<bool> registerToken(String authToken) async {
    if (_fcmToken == null) {
      print('❌ FCM token not available');
      return false;
    }
    
    try {
      final response = await http.post(
        Uri.parse('$_apiBaseUrl/device-tokens'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $authToken',
        },
        body: jsonEncode({
          'token': _fcmToken,
          'platform': 'android',
        }),
      );
      
      if (response.statusCode == 200) {
        print('✅ FCM token registered successfully');
        return true;
      } else {
        print('❌ Failed to register FCM token: ${response.statusCode}');
        return false;
      }
    } catch (e) {
      print('❌ Error registering FCM token: $e');
      return false;
    }
  }
  
  /// Setup message handlers for different app states
  static void _setupMessageHandlers() {
    // Handle foreground messages
    FirebaseMessaging.onMessage.listen((RemoteMessage message) async {
      print('📢 Foreground notification received');
      print('Title: ${message.notification?.title}');
      print('Body: ${message.notification?.body}');
      print('Data: ${message.data}');
      
      // Show notification in foreground
      if (message.notification != null) {
        final title = message.notification!.title ?? 'New Notification';
        final body = message.notification!.body ?? '';
        final payload = jsonEncode(message.data);
        
        // Show local notification (will work in all states)
        await showLocalNotification(
          title: title,
          body: body,
          payload: payload,
        );
        
        // Also try to show dialog (works only when app is in foreground)
        if (NavigationService.isReady) {
          _showForegroundNotificationDialog(title, body);
        }
      } else {
        print('⚠️ No notification payload in message');
      }
      
      // Handle toll-specific notifications
      _handleTollNotification(message);
    });
    
    // Handle background messages (when app is opened from notification)
    FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {
      print('📱 App opened from notification');
      print('Title: ${message.notification?.title}');
      print('Body: ${message.notification?.body}');
      
      // Handle toll-specific navigation
      _handleTollNotificationTap(message);
    });
  }
  
  /// Handle toll-specific notifications
  static void _handleTollNotification(RemoteMessage message) {
    String? type = message.data['type'];
    
    switch (type) {
      case 'transaction':
        print('💳 Transaction notification received');
        // Handle transaction notification
        break;
      case 'payment':
        print('💰 Payment notification received');
        // Handle payment notification
        break;
      case 'gate_status':
        print('🚧 Gate status notification received');
        // Handle gate status notification
        break;
      case 'low_balance':
        print('⚠️ Low balance notification received');
        // Handle low balance notification
        break;
      default:
        print('📋 General notification received');
        break;
    }
  }
  
  /// Handle notification tap (navigation)
  static void _handleTollNotificationTap(RemoteMessage message) {
    String? type = message.data['type'];
    
    switch (type) {
      case 'transaction':
        // Navigate to transaction history page
        print('🔍 Navigate to transaction history');
        break;
      case 'payment':
        // Navigate to payment page
        print('🔍 Navigate to payment page');
        break;
      case 'gate_status':
        // Navigate to gate status page
        print('🔍 Navigate to gate status page');
        break;
      default:
        // Navigate to home page
        print('🔍 Navigate to home page');
        break;
    }
  }
  
  /// Show foreground notification dialog using the global navigator key
  static void _showForegroundNotificationDialog(String title, String body) {
    print('🔍 Checking for navigator context...');
    
    // Try using the NavigationService's context first
    if (NavigationService.isReady) {
      print('✅ Navigator context is ready, showing dialog');
      _showDialog(NavigationService.context!, title, body);
      return;
    }
    
    // Fallback: Try to get the context from the root navigator
    try {
      final context = NavigationService.navigatorKey.currentContext;
      if (context != null) {
        print('✅ Found context from navigator key, showing dialog');
        _showDialog(context, title, body);
        return;
      }
    } catch (e) {
      print('⚠️ Error getting context from navigator key: $e');
    }
    
    // Last resort: Show a snackbar if possible
    print('⚠️ Could not show dialog, trying snackbar...');
    NavigationService.showSnackBar('$title: $body');
    
    // Log the notification as a last resort
    print('📢 Notification (could not show UI): $title - $body');
  }
  
  /// Helper method to show the actual dialog
  static void _showDialog(BuildContext context, String title, String body) {
    try {
      showDialog(
        context: context,
        barrierDismissible: true,
        builder: (BuildContext context) {
          return AlertDialog(
            title: Row(
              children: [
                Icon(Icons.notifications_active, color: Colors.blue),
                SizedBox(width: 8),
                Expanded(child: Text(title)),
              ],
            ),
            content: SingleChildScrollView(
              child: Text(body),
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.of(context).pop(),
                child: Text('TUTUP'),
              ),
            ],
          );
        },
      );
    } catch (e) {
      print('❌ Error showing dialog: $e');
    }
  }
  
  /// Initialize local notifications plugin
  static Future<void> _initLocalNotifications() async {
    const AndroidInitializationSettings initializationSettingsAndroid =
        AndroidInitializationSettings('@mipmap/ic_launcher');
        
    const DarwinInitializationSettings initializationSettingsDarwin =
        DarwinInitializationSettings(
          requestSoundPermission: true,
          requestBadgePermission: true,
          requestAlertPermission: true,
        );

    final InitializationSettings initializationSettings = InitializationSettings(
      android: initializationSettingsAndroid,
      iOS: initializationSettingsDarwin,
    );

    await _localNotificationsPlugin.initialize(
      initializationSettings,
      onDidReceiveNotificationResponse: (details) {
        // Handle notification tap when app is in foreground
        _onNotificationTapped(details.payload);
      },
    );
    
    _isInitialized = true;
    print('✅ Local notifications initialized');
  }
  
  /// Handle notification tap
  static void _onNotificationTapped(String? payload) {
    print('🔔 Notification tapped with payload: $payload');
    // You can add navigation logic here based on the payload
  }
  
  /// Show a local notification
  static Future<void> showLocalNotification({
    required String title,
    required String body,
    String? payload,
  }) async {
    if (!_isInitialized) {
      print('⚠️ Local notifications not initialized');
      return;
    }
    
    const AndroidNotificationDetails androidDetails = AndroidNotificationDetails(
      'toll_notification_channel',
      'Toll Notifications',
      channelDescription: 'Notifications for toll transactions and updates',
      importance: Importance.max,
      priority: Priority.high,
      showWhen: true,
      enableVibration: true,
      playSound: true,
    );
    
    const DarwinNotificationDetails iosDetails = DarwinNotificationDetails(
      presentAlert: true,
      presentBadge: true,
      presentSound: true,
    );
    
    const NotificationDetails platformDetails = NotificationDetails(
      android: androidDetails,
      iOS: iosDetails,
    );
    
    try {
      await _localNotificationsPlugin.show(
        DateTime.now().millisecondsSinceEpoch ~/ 1000, // Unique ID
        title,
        body,
        platformDetails,
        payload: payload,
      );
      print('📱 Local notification shown: $title');
    } catch (e) {
      print('❌ Error showing local notification: $e');
    }
  }
  
  /// Show foreground notification (kept for backward compatibility)
  static void showForegroundNotification(
    BuildContext context,
    String title,
    String body, {
    String? payload,
  }) {
    // First try to show local notification
    showLocalNotification(
      title: title,
      body: body,
      payload: payload,
    );
    
    // Then try to show dialog
    _showForegroundNotificationDialog(title, body);
  }
}
