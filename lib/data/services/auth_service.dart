import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert'; // Import for jsonDecode
import '../../core/services/api_service.dart';
import '../../core/utils/api_constants.dart';
import '../models/user_model.dart';
import '../../services/fcm_service.dart';
import 'package:flutter/foundation.dart'; // Import for debugPrint

class AuthService {
  static final AuthService _instance = AuthService._internal();
  factory AuthService() => _instance;
  AuthService._internal();

  final ApiService _apiService = ApiService();
  User? _currentUser;
  String? _token;

  // Getter untuk current user
  User? get currentUser => _currentUser;
  String? get token => _token;
  bool get isLoggedIn => _currentUser != null && _token != null;

  // Login dengan email dan password
  Future<User> loginWithEmailAndPassword(String email, String password) async {
    try {
      debugPrint('🔐 Starting login for: $email');
      
      // Single API call untuk login dan mendapatkan semua data yang diperlukan
      final response = await _apiService.post(
        '${ApiConstants.baseUrl}/auth/login',
        {
          'email': email,
          'password': password,
        },
      );

      debugPrint('📡 Login API Response: $response');
      debugPrint('🔍 Response success field: ${response['success']}');
      debugPrint('🔍 Response type: ${response['success'].runtimeType}');

      // Handle different possible API response formats
      bool isSuccess = false;
      
      if (response['success'] == true || 
          response['success'] == 'true' || 
          response['success'] == 1 ||
          (response.containsKey('data') || response.containsKey('user')) ||
          (response.containsKey('id') && response.containsKey('email'))) {
        isSuccess = true;
      }
      
      debugPrint('🎯 Final success status: $isSuccess');

      if (isSuccess) {
        // Try to find user data in different possible locations
        final userData = response['data'] ?? response['user'] ?? response;
        debugPrint('👤 User data received: $userData');
        
        _currentUser = User.fromJson(userData);
        debugPrint('✅ User object created: ${_currentUser?.email}');
        
        // Gunakan token dari API response jika ada, atau buat session token
        _token = response['token'] ?? 'session_${email}_${DateTime.now().millisecondsSinceEpoch}';
        debugPrint('🔑 Token set: $_token');
        
        // Simpan ke SharedPreferences
        await _saveAuthData();
        debugPrint('💾 Auth data saved');
        
        // FCM initialization dan registration dilakukan secara asinkron (non-blocking)
        // Ini tidak akan memperlambat login
        _initializeFCMAsync();
        
        debugPrint('🎉 Login successful for: $email');
        return _currentUser!;
      } else {
        debugPrint('❌ Login failed - API returned success: ${response['success']}');
        debugPrint('❌ API message: ${response['message']}');
        throw Exception(response['message'] ?? 'Email atau password salah');
      }
    } catch (e) {
      debugPrint('💥 Login exception: $e');
      throw Exception('Login gagal: $e');
    }
  }

  // FCM initialization dilakukan secara asinkron untuk tidak memblokir login
  void _initializeFCMAsync() async {
    try {
      await FCMService.initialize();
      
      // Jika ada token API, register FCM token
      if (_token != null && _token!.startsWith('session_') == false) {
        bool registered = await FCMService.registerToken(_token!);
        if (registered) {
          debugPrint('✅ FCM token registered successfully');
        } else {
          debugPrint('⚠️ FCM token registration failed');
        }
      }
    } catch (e) {
      debugPrint('❌ FCM initialization failed: $e');
      // Tidak throw error karena login sudah berhasil
    }
  }

  // Update user balance
  Future<User> updateUserBalance(int userId, double newBalance) async {
    try {
      final response = await _apiService.put(
        '${ApiConstants.users}/$userId',
        {'saldo': newBalance},
        token: _token,
      );

      if (response['success'] == true) {
        final userData = response['data'];
        _currentUser = User.fromJson(userData);
        await _saveAuthData();
        return _currentUser!;
      } else {
        throw Exception(response['message'] ?? 'Gagal update saldo');
      }
    } catch (e) {
      throw Exception('Update saldo gagal: $e');
    }
  }

  // Logout
  Future<void> logout() async {
    _currentUser = null;
    _token = null;
    
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_user');
    await prefs.remove('auth_token');
  }

  // Load auth data dari SharedPreferences
  Future<void> loadAuthData() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final userJson = prefs.getString('auth_user');
      final token = prefs.getString('auth_token');
      
      if (userJson != null && token != null && userJson.isNotEmpty && token.isNotEmpty) {
        _token = token;
        try {
          final Map<String, dynamic> userData = jsonDecode(userJson);
          _currentUser = User.fromJson(userData);
        } catch (e) {
          debugPrint('Error parsing user JSON from SharedPreferences: $e');
          _currentUser = null;
        }
      }
    } catch (e) {
      debugPrint('Error loading auth data: $e');
    }
  }

  // Save auth data ke SharedPreferences
  Future<void> _saveAuthData() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      
      if (_currentUser != null && _token != null) {
        // Simpan data pengguna dalam format JSON
        final userJson = _currentUser!.toJson();
        await prefs.setString('auth_user', userJson.toString());
        await prefs.setString('auth_token', _token!);
        
        // Simpan email terpisah untuk memudahkan auto-login berikutnya
        if (_currentUser!.email != null && _currentUser!.email!.isNotEmpty) {
          await prefs.setString('auth_email', _currentUser!.email!);
        }
      }
    } catch (e) {
      debugPrint('Error saving auth data: $e');
    }
  }
}
