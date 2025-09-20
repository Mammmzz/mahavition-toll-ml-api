#!/bin/bash

# Complete FCM Flow Test Script
# Tests the full FCM integration from toll gate app to Flutter app

echo "=== Complete FCM Flow Test ==="
echo "Testing complete toll system FCM integration..."

API_URL="http://192.168.1.9:8080/api"

echo ""
echo "1. Testing API Health..."
curl -s "$API_URL/health" | jq -r '.message // "Failed"'

echo ""
echo "2. Authenticating as test user..."
AUTH_RESPONSE=$(curl -s -X POST "$API_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email": "john@example.com", "password": "password123"}')

if echo "$AUTH_RESPONSE" | jq -e '.token' > /dev/null 2>&1; then
    TOKEN=$(echo "$AUTH_RESPONSE" | jq -r '.token')
    USER_ID=$(echo "$AUTH_RESPONSE" | jq -r '.user.id')
    echo "✓ Authentication successful - User ID: $USER_ID"
else
    echo "✗ Authentication failed"
    echo "$AUTH_RESPONSE"
    exit 1
fi

echo ""
echo "3. Checking current device tokens..."
cd /home/archmam/AndroidStudioProjects/lomba/toll-api
TOKENS_COUNT=$(php artisan tinker --execute="echo App\Models\DeviceToken::count();" 2>/dev/null)
echo "Current FCM tokens in database: $TOKENS_COUNT"

echo ""
echo "4. Testing transaction notification (simulating toll gate)..."
TRANSACTION_RESPONSE=$(curl -s -X POST "$API_URL/notify-transaction" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "plate_number": "B1234XYZ", 
    "vehicle_type": "Car", 
    "toll_amount": 15000,
    "gate_name": "Test Gate A"
  }')

echo "Transaction notification response:"
echo "$TRANSACTION_RESPONSE" | jq .

echo ""
echo "5. Testing direct FCM send..."
DIRECT_FCM_RESPONSE=$(curl -s -X POST "$API_URL/send-test" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"title": "End-to-End Test", "body": "Testing complete FCM flow from backend to Flutter app"}')

echo "Direct FCM response:"
echo "$DIRECT_FCM_RESPONSE" | jq .

echo ""
echo "6. Final database state..."
php artisan tinker --execute="
echo 'Total users: ' . App\Models\User::count() . PHP_EOL;
echo 'Total device tokens: ' . App\Models\DeviceToken::count() . PHP_EOL;
echo 'Recent tokens:' . PHP_EOL;
App\Models\DeviceToken::latest()->take(3)->get()->each(function(\$token) {
    echo '- ' . substr(\$token->token, 0, 20) . '... (Platform: ' . \$token->platform . ', User: ' . \$token->user_id . ')' . PHP_EOL;
});
" 2>/dev/null

echo ""
echo "=== Test Instructions ==="
echo "1. Open the lomba Flutter app on your device"
echo "2. Login with: john@example.com / password123"
echo "3. Check console logs to verify FCM token generation and registration"
echo "4. Check if you receive the test notifications"
echo ""
echo "Expected behavior:"
echo "- App should auto-generate FCM token on login"
echo "- Token should be registered with backend API"
echo "- Backend should send notifications to registered tokens"
echo "- App should receive and display notifications"
