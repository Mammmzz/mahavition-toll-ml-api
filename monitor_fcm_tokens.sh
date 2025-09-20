#!/bin/bash

# FCM Token Monitoring Script
# Monitors real-time FCM token registration from Flutter app

echo "=== FCM Token Registration Monitor ==="
echo "Monitoring real FCM token registration from Flutter app..."

API_URL="http://192.168.1.9:8080/api"

echo ""
echo "Initial Database State:"
cd /home/archmam/AndroidStudioProjects/lomba/toll-api
php artisan tinker --execute="
echo 'Total device tokens: ' . App\Models\DeviceToken::count() . PHP_EOL;
echo 'Recent tokens:' . PHP_EOL;
App\Models\DeviceToken::latest()->take(5)->get()->each(function(\$token) {
    echo '- Token: ' . substr(\$token->token, 0, 30) . '... (Platform: ' . \$token->platform . ', User: ' . \$token->user_id . ', Created: ' . \$token->created_at . ')' . PHP_EOL;
});
" 2>/dev/null

echo ""
echo "=== INSTRUCTIONS FOR TESTING ==="
echo "1. Open the lomba Flutter app on your Android device"
echo "2. Login with: john@example.com / password123"
echo "3. Watch for FCM token generation and registration"
echo "4. Press Ctrl+C to stop monitoring when done"
echo ""
echo "Monitoring for new FCM tokens (press Ctrl+C to stop)..."

# Monitor for new tokens every 5 seconds
INITIAL_COUNT=$(php artisan tinker --execute="echo App\Models\DeviceToken::count();" 2>/dev/null)
echo "Starting token count: $INITIAL_COUNT"

while true; do
    sleep 5
    CURRENT_COUNT=$(php artisan tinker --execute="echo App\Models\DeviceToken::count();" 2>/dev/null)
    
    if [ "$CURRENT_COUNT" -gt "$INITIAL_COUNT" ]; then
        echo ""
        echo "🎉 NEW FCM TOKEN DETECTED!"
        echo "Token count changed from $INITIAL_COUNT to $CURRENT_COUNT"
        
        # Show the latest token
        php artisan tinker --execute="
        \$latest = App\Models\DeviceToken::latest()->first();
        echo 'Latest FCM Token:' . PHP_EOL;
        echo '- Token: ' . substr(\$latest->token, 0, 50) . '...' . PHP_EOL;
        echo '- Platform: ' . \$latest->platform . PHP_EOL;
        echo '- User ID: ' . \$latest->user_id . PHP_EOL;
        echo '- Created: ' . \$latest->created_at . PHP_EOL;
        echo '- Is Real Token: ' . (strpos(\$latest->token, 'test-') === false ? 'YES' : 'NO') . PHP_EOL;
        " 2>/dev/null
        
        # Test sending notification to the new token
        echo ""
        echo "Testing notification to new token..."
        
        # Get auth token first
        AUTH_RESPONSE=$(curl -s -X POST "$API_URL/auth/login" \
          -H "Content-Type: application/json" \
          -d '{"email": "john@example.com", "password": "password123"}')
        
        if echo "$AUTH_RESPONSE" | jq -e '.token' > /dev/null 2>&1; then
            TOKEN=$(echo "$AUTH_RESPONSE" | jq -r '.token')
            
            # Send test notification
            NOTIFICATION_RESPONSE=$(curl -s -X POST "$API_URL/send-test" \
              -H "Content-Type: application/json" \
              -H "Authorization: Bearer $TOKEN" \
              -d '{"title": "FCM Auto-Registration Success!", "body": "Your device successfully registered for notifications"}')
            
            echo "Notification sent response:"
            echo "$NOTIFICATION_RESPONSE" | jq .
        fi
        
        INITIAL_COUNT=$CURRENT_COUNT
    else
        echo -n "."
    fi
done
