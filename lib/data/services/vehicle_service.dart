import '../../core/services/api_service.dart';
import '../../core/utils/api_constants.dart';
import '../models/user_model.dart';
import 'package:flutter/foundation.dart'; // Import for debugPrint

class VehicleService {
  static final VehicleService _instance = VehicleService._internal();
  factory VehicleService() => _instance;
  VehicleService._internal();

  final ApiService _apiService = ApiService();

  // Get all registered plates/vehicles
  Future<List<User>> getAllVehicles({String? token}) async {
    try {
      final response = await _apiService.get(
        ApiConstants.plates,
        token: token,
      );

      if (response['success'] == true && response['data'] != null) {
        final List<dynamic> vehicleList = response['data'];
        return vehicleList
            .map((json) => User.fromJson(json))
            .toList();
      }
      return [];
    } catch (e) {
      debugPrint('Error getting vehicles: $e');
      return [];
    }
  }

  // Validate plate number
  Future<Map<String, dynamic>> validatePlate(String plateNumber, {String? token}) async {
    try {
      final response = await _apiService.post(
        ApiConstants.validatePlate,
        {'plat_nomor': plateNumber},
        token: token,
      );

      return response;
    } catch (e) {
      throw Exception('Validasi plat gagal: $e');
    }
  }

  // Validate plate and vehicle type
  Future<Map<String, dynamic>> validatePlateAndVehicle({
    required String plateNumber,
    required String vehicleType,
    String? token,
  }) async {
    try {
      final response = await _apiService.post(
        ApiConstants.validatePlateVehicle,
        {
          'plat_nomor': plateNumber,
          'kelompok_kendaraan': vehicleType,
        },
        token: token,
      );

      return response;
    } catch (e) {
      throw Exception('Validasi plat dan kendaraan gagal: $e');
    }
  }

  // Get vehicle by plate number
  Future<User?> getVehicleByPlate(String plateNumber, {String? token}) async {
    try {
      final response = await _apiService.get(
        '${ApiConstants.plates}?plat_nomor=$plateNumber',
        token: token,
      );

      if (response['success'] == true && response['data'] != null) {
        return User.fromJson(response['data']);
      }
      return null;
    } catch (e) {
      debugPrint('Error getting vehicle by plate: $e');
      return null;
    }
  }

  // Add new vehicle/user
  Future<User> addVehicle({
    required String nama,
    required String email,
    required String plateNumber,
    required String vehicleType,
    double initialBalance = 0.0,
    String? token,
  }) async {
    try {
      final response = await _apiService.post(
        ApiConstants.users,
        {
          'nama': nama,
          'email': email,
          'plat_nomor': plateNumber,
          'kelompok_kendaraan': vehicleType,
          'saldo': initialBalance,
        },
        token: token,
      );

      if (response['success'] == true && response['data'] != null) {
        return User.fromJson(response['data']);
      } else {
        throw Exception(response['message'] ?? 'Gagal menambah kendaraan');
      }
    } catch (e) {
      throw Exception('Tambah kendaraan gagal: $e');
    }
  }

  // Update vehicle information
  Future<User> updateVehicle({
    required int userId,
    String? nama,
    String? email,
    String? plateNumber,
    String? vehicleType,
    double? saldo,
    String? token,
  }) async {
    try {
      final Map<String, dynamic> updateData = {};
      if (nama != null) updateData['nama'] = nama;
      if (email != null) updateData['email'] = email;
      if (plateNumber != null) updateData['plat_nomor'] = plateNumber;
      if (vehicleType != null) updateData['kelompok_kendaraan'] = vehicleType;
      if (saldo != null) updateData['saldo'] = saldo;

      final response = await _apiService.put(
        '${ApiConstants.users}/$userId',
        updateData,
        token: token,
      );

      if (response['success'] == true && response['data'] != null) {
        return User.fromJson(response['data']);
      } else {
        throw Exception(response['message'] ?? 'Gagal update kendaraan');
      }
    } catch (e) {
      throw Exception('Update kendaraan gagal: $e');
    }
  }

  // Delete vehicle
  Future<bool> deleteVehicle(int userId, {String? token}) async {
    try {
      final response = await _apiService.delete(
        '${ApiConstants.users}/$userId',
        token: token,
      );

      return response['success'] == true;
    } catch (e) {
      throw Exception('Hapus kendaraan gagal: $e');
    }
  }

  // Get vehicle types
  List<String> getVehicleTypes() {
    return ['Mobil', 'Bus', 'Truk'];
  }

  // Search vehicles by name or plate
  Future<List<User>> searchVehicles(String query, {String? token}) async {
    try {
      final response = await _apiService.get(
        '${ApiConstants.plates}?search=$query',
        token: token,
      );

      if (response['success'] == true && response['data'] != null) {
        final List<dynamic> vehicleList = response['data'];
        return vehicleList
            .map((json) => User.fromJson(json))
            .toList();
      }
      return [];
    } catch (e) {
      debugPrint('Error searching vehicles: $e');
      return [];
    }
  }

  // Check if plate is available
  Future<bool> isPlateAvailable(String plateNumber, {String? token}) async {
    try {
      final result = await validatePlate(plateNumber, token: token);
      return result['valid'] != true;
    } catch (e) {
      return true; // Assume available if error
    }
  }

  // Format plate number
  String formatPlateNumber(String plateNumber) {
    // Remove extra spaces and format consistently
    String formatted = plateNumber.trim().toUpperCase();
    
    // Add spaces if needed for Indonesian plate format
    // Example: "B1234ABC" -> "B 1234 ABC"
    if (formatted.length >= 8 && !formatted.contains(' ')) {
      // Basic format: Letter + Numbers + Letters
      final regExp = RegExp(r'([A-Z]+)(\d+)([A-Z]+)');
      final match = regExp.firstMatch(formatted);
      
      if (match != null) {
        final prefix = match.group(1);
        final numbers = match.group(2);
        final suffix = match.group(3);
        formatted = '$prefix $numbers $suffix';
      }
    }
    
    return formatted;
  }
}
