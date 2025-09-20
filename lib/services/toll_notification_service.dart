import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';

class TollNotificationService {
  /// Handle transaction notification specifically for toll system
  static void handleTransactionNotification(RemoteMessage message) {
    Map<String, dynamic> data = message.data;
    
    String? transactionId = data['transaction_id'];
    String? amount = data['amount'];
    String? gate = data['gate'];
    String? timestamp = data['timestamp'];
    
    print('🚗 Toll Transaction Notification:');
    print('  ID: $transactionId');
    print('  Amount: Rp $amount');
    print('  Gate: $gate');
    print('  Time: $timestamp');
    
    // You can emit events or call callbacks here for UI updates
  }
  
  /// Handle payment notification for toll top-up or payment failures
  static void handlePaymentNotification(RemoteMessage message) {
    Map<String, dynamic> data = message.data;
    
    String? paymentType = data['payment_type']; // 'topup', 'deduction', 'failed'
    String? amount = data['amount'];
    String? balance = data['new_balance'];
    String? status = data['status'];
    
    print('💰 Toll Payment Notification:');
    print('  Type: $paymentType');
    print('  Amount: Rp $amount');
    print('  New Balance: Rp $balance');
    print('  Status: $status');
    
    // Handle different payment types
    switch (paymentType) {
      case 'topup':
        print('  ✅ Top-up successful');
        break;
      case 'deduction':
        print('  💸 Toll payment deducted');
        break;
      case 'failed':
        print('  ❌ Payment failed');
        break;
    }
  }
  
  /// Handle gate status notification (open, closed, maintenance)
  static void handleGateStatusNotification(RemoteMessage message) {
    Map<String, dynamic> data = message.data;
    
    String? gateId = data['gate_id'];
    String? status = data['status']; // 'open', 'closed', 'maintenance'
    String? location = data['location'];
    String? message_text = data['message'];
    
    print('🚧 Gate Status Notification:');
    print('  Gate: $gateId ($location)');
    print('  Status: $status');
    print('  Message: $message_text');
    
    // Handle different gate statuses
    switch (status) {
      case 'open':
        print('  ✅ Gate is open for traffic');
        break;
      case 'closed':
        print('  ⛔ Gate is closed');
        break;
      case 'maintenance':
        print('  🔧 Gate under maintenance');
        break;
    }
  }
  
  /// Handle low balance warning
  static void handleLowBalanceNotification(RemoteMessage message) {
    Map<String, dynamic> data = message.data;
    
    String? currentBalance = data['balance'];
    String? minimumBalance = data['minimum'];
    
    print('⚠️ Low Balance Warning:');
    print('  Current: Rp $currentBalance');
    print('  Minimum: Rp $minimumBalance');
    print('  Please top-up your EZToll balance');
  }
  
  /// Handle traffic update notification
  static void handleTrafficNotification(RemoteMessage message) {
    Map<String, dynamic> data = message.data;
    
    String? location = data['location'];
    String? trafficLevel = data['traffic_level']; // 'light', 'moderate', 'heavy'
    String? estimatedDelay = data['estimated_delay'];
    
    print('🚦 Traffic Update:');
    print('  Location: $location');
    print('  Traffic: $trafficLevel');
    print('  Delay: $estimatedDelay minutes');
  }
  
  /// Handle promotion notification
  static void handlePromoNotification(RemoteMessage message) {
    Map<String, dynamic> data = message.data;
    
    String? promoTitle = data['promo_title'];
    String? discount = data['discount'];
    String? validUntil = data['valid_until'];
    
    print('🎉 Toll Promotion:');
    print('  Title: $promoTitle');
    print('  Discount: $discount%');
    print('  Valid Until: $validUntil');
  }
  
  /// Show notification dialog with toll-specific styling
  static void showTollNotificationDialog(
    BuildContext context,
    String title,
    String body,
    String type,
  ) {
    IconData icon;
    Color color;
    
    switch (type) {
      case 'transaction':
        icon = Icons.receipt_long;
        color = Colors.green;
        break;
      case 'payment':
        icon = Icons.payment;
        color = Colors.blue;
        break;
      case 'gate_status':
        icon = Icons.traffic;
        color = Colors.orange;
        break;
      case 'low_balance':
        icon = Icons.warning;
        color = Colors.red;
        break;
      case 'promo':
        icon = Icons.local_offer;
        color = Colors.purple;
        break;
      default:
        icon = Icons.info;
        color = Colors.grey;
    }
    
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          title: Row(
            children: [
              Icon(icon, color: color, size: 28),
              SizedBox(width: 12),
              Expanded(
                child: Text(
                  title,
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: color,
                  ),
                ),
              ),
            ],
          ),
          content: Text(
            body,
            style: TextStyle(fontSize: 16),
          ),
          actions: [
            TextButton(
              onPressed: () {
                Navigator.of(context).pop();
              },
              child: Text(
                'OK',
                style: TextStyle(color: color),
              ),
            ),
          ],
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
        );
      },
    );
  }
  
  /// Route notification tap to appropriate page
  static void routeNotificationTap(BuildContext context, RemoteMessage message) {
    String? type = message.data['type'];
    
    switch (type) {
      case 'transaction':
        // Navigate to transaction history
        print('🔍 Navigating to transaction history');
        // Navigator.pushNamed(context, '/transaction-history');
        break;
      case 'payment':
        // Navigate to payment/topup page  
        print('🔍 Navigating to payment page');
        // Navigator.pushNamed(context, '/payment');
        break;
      case 'gate_status':
        // Navigate to gate status page
        print('🔍 Navigating to gate status');
        // Navigator.pushNamed(context, '/gate-status');
        break;
      case 'low_balance':
        // Navigate to topup page
        print('🔍 Navigating to topup page');
        // Navigator.pushNamed(context, '/topup');
        break;
      default:
        // Navigate to home
        print('🔍 Navigating to home');
        // Navigator.pushNamedAndRemoveUntil(context, '/home', (route) => false);
        break;
    }
  }
}
