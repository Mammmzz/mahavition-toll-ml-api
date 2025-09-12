import 'package:shared_preferences/shared_preferences.dart';
import '../../core/services/api_service.dart';
import '../../core/utils/api_constants.dart';
import '../models/user_model.dart';

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

  // Login dengan plat nomor (tanpa password karena sistem toll)
  Future<User> loginWithPlateNumber(String plateNumber) async {
    try {
      final response = await _apiService.post(
        ApiConstants.validatePlate,
        {'plat_nomor': plateNumber},
      );

      if (response['success'] == true && response['valid'] == true) {
        final userData = response['data']['user'];
        _currentUser = User.fromJson(userData);
        
        // Generate simple token dari plat nomor untuk session
        _token = 'plate_${plateNumber}_${DateTime.now().millisecondsSinceEpoch}';
        
        // Simpan ke SharedPreferences
        await _saveAuthData();
        
        return _currentUser!;
      } else {
        throw Exception(response['message'] ?? 'Plat nomor tidak terdaftar');
      }
    } catch (e) {
      throw Exception('Login gagal: $e');
    }
  }

  // Login dengan plat nomor dan kelompok kendaraan (double validation)
  Future<User> loginWithPlateAndVehicle(String plateNumber, String vehicleType) async {
    try {
      final response = await _apiService.post(
        ApiConstants.validatePlateVehicle,
        {
          'plat_nomor': plateNumber,
          'kelompok_kendaraan': vehicleType,
        },
      );

      if (response['success'] == true && response['valid'] == true) {
        final userData = response['data']['user'];
        _currentUser = User.fromJson(userData);
        
        // Generate simple token dari plat nomor untuk session
        _token = 'plate_${plateNumber}_${DateTime.now().millisecondsSinceEpoch}';
        
        // Simpan ke SharedPreferences
        await _saveAuthData();
        
        return _currentUser!;
      } else {
        throw Exception(response['message'] ?? 'Data tidak sesuai');
      }
    } catch (e) {
      throw Exception('Login gagal: $e');
    }
  }

  // Get user data by plate number
  Future<User?> getUserByPlateNumber(String plateNumber) async {
    try {
      final response = await _apiService.get('${ApiConstants.plates}?plat_nomor=$plateNumber');
      
      if (response['success'] == true && response['data'] != null) {
        return User.fromJson(response['data']);
      }
      return null;
    } catch (e) {
      print('Error getting user by plate: $e');
      return null;
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
      
      if (userJson != null && token != null) {
        final userData = Map<String, dynamic>.from(
          // Convert string to Map if needed
          userJson.contains('{') 
            ? {} // Parse JSON string if needed
            : {},
        );
        
        // Simple check - jika ada data user tersimpan
        if (userJson.isNotEmpty && token.isNotEmpty) {
          _token = token;
          // _currentUser akan di-load saat dibutuhkan
        }
      }
    } catch (e) {
      print('Error loading auth data: $e');
    }
  }

  // Save auth data ke SharedPreferences
  Future<void> _saveAuthData() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      
      if (_currentUser != null && _token != null) {
        await prefs.setString('auth_user', _currentUser!.toJson().toString());
        await prefs.setString('auth_token', _token!);
      }
    } catch (e) {
      print('Error saving auth data: $e');
    }
  }
}
