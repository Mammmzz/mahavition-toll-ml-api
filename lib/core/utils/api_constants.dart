import 'dart:io';

class ApiConstants {
  // Base URL
  // Gunakan '10.0.2.2' untuk emulator Android
  // Gunakan IP lokal komputer Anda (misal: '192.168.0.132') untuk perangkat fisik
  static const String baseUrl = 'http://192.168.0.132:8080/api';
  
  // Endpoint paths
  static const String users = 'users';
  static const String plates = 'plates';
  static const String validatePlate = 'validate-plate';
  static const String validatePlateVehicle = 'validate-plate-vehicle';
  static const String login = 'login';
  static const String register = 'register';
  static const String transactions = 'transactions';
  static const String processTransaction = 'transactions/plate';
  static const String tarifs = 'tarifs';

  // Connection settings
  static const Duration connectTimeout = Duration(seconds: 10);
  
  // Helper method to build full URLs
  static String getFullUrl(String endpoint) {
    if (endpoint.startsWith('http')) {
      return endpoint; // Already a full URL
    }
    return '$baseUrl/$endpoint';
  }
}

class ApiHeaders {
  // Common JSON headers
  static Map<String, String> get jsonHeaders => {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  };
  
  // Headers dengan token lokal (tidak digunakan untuk autentikasi API)
  static Map<String, String> authHeaders(String token) {
    // Di sini kita hanya menggunakan header standar tanpa Authorization
    // karena API Laravel tidak menggunakan token
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
  }
}
