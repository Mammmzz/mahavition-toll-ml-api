#!/bin/bash

# Test Transaction Notification Flow
# Simulates toll gate app sending transaction notification

echo "=== Testing Complete Transaction Notification Flow ==="
echo "This simulates the toll gate app sending FCM notifications"

API_URL="http://192.168.0.132:8080/api"

# Get authentication token
echo "1. Getting authentication token..."
AUTH_RESPONSE=$(curl -s -X POST "$API_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email": "john@example.com", "password": "password123"}')

if echo "$AUTH_RESPONSE" | jq -e '.token' > /dev/null 2>&1; then
    TOKEN=$(echo "$AUTH_RESPONSE" | jq -r '.token')
    echo "✅ Authentication successful"
else
    echo "❌ Authentication failed"
    exit 1
fi

# Check current FCM tokens
echo ""
echo "2. Checking registered FCM tokens..."
cd /home/archmam/AndroidStudioProjects/lomba/toll-api
REAL_TOKENS=$(php artisan tinker --execute="echo App\Models\DeviceToken::count();" 2>/dev/null)
echo "Real FCM tokens registered: $REAL_TOKENS"

# Test transaction notification (simulating toll gate app)
echo ""
echo "3. Sending transaction notification (simulating toll gate app)..."
TRANSACTION_RESPONSE=$(curl -s -X POST "$API_URL/send-test" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "title": "Transaksi Berhasil!", 
    "body": "Kendaraan telah berhasil melalui gerbang tol, dengan Tarif: Rp 15,000"
  }')

echo "Transaction notification response:"
echo "$TRANSACTION_RESPONSE" | jq .

# Check if successful
if echo "$TRANSACTION_RESPONSE" | jq -e '.success' > /dev/null 2>&1; then
    MESSAGE_ID=$(echo "$TRANSACTION_RESPONSE" | jq -r '.results[0].name // "none"')
    if [ "$MESSAGE_ID" != "none" ] && [ "$MESSAGE_ID" != "null" ]; then
        echo ""
        echo "🎉 SUCCESS! Transaction notification sent successfully!"
        echo "Firebase Message ID: $MESSAGE_ID"
        echo ""
        echo "Check your Android device - you should receive the notification!"
    else
        echo ""
        echo "⚠️ Notification sent but no message ID received"
        echo "This might indicate an issue with the FCM token"
    fi
else
    echo ""
    echo "❌ Transaction notification failed"
fi

echo ""
echo "=== Transaction Flow Test Complete ==="
echo "If successful, your Flutter app should show the toll transaction notification"
