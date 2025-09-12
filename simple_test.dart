import 'dart:convert';
import 'dart:io';

// Simple HTTP test without Flutter dependencies
void main() async {
  print('🚀 Testing API Connection...\n');
  
  try {
    // Test 1: Check server availability
    print('📋 Test 1: Checking server availability...');
    final client = HttpClient();
    final request = await client.getUrl(Uri.parse('http://127.0.0.1:8080/api/tarifs'));
    request.headers.set('Accept', 'application/json');
    request.headers.set('Content-Type', 'application/json');
    
    final response = await request.close();
    final responseBody = await response.transform(utf8.decoder).join();
    
    if (response.statusCode == 200) {
      print('✅ Server is running and accessible');
      final data = jsonDecode(responseBody);
      print('   Response: ${data['message']}');
      
      if (data['data'] != null) {
        print('   Tarifs available: ${data['data'].length}');
      }
    } else {
      print('❌ Server error: ${response.statusCode}');
      print('   Response: $responseBody');
    }
    
    client.close();
    
  } catch (e) {
    print('❌ Connection failed: $e');
  }
  
  print('\n🎉 Basic API Test Complete!');
}
