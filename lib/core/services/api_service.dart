import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import '../utils/api_constants.dart';

class ApiService {
  static final ApiService _instance = ApiService._internal();
  factory ApiService() => _instance;
  ApiService._internal();

  // GET request
  Future<Map<String, dynamic>> get(String endpoint, {String? token}) async {
    try {
      final url = Uri.parse(ApiConstants.getFullUrl(endpoint));
      final headers = token != null 
          ? ApiHeaders.authHeaders(token) 
          : ApiHeaders.jsonHeaders;
      
      final response = await http.get(
        url,
        headers: headers,
      ).timeout(ApiConstants.connectTimeout);
      
      return _handleResponse(response);
    } on SocketException {
      throw Exception('Tidak ada koneksi internet');
    } on HttpException {
      throw Exception('Terjadi kesalahan pada server');
    } on FormatException {
      throw Exception('Format response tidak valid');
    } catch (e) {
      throw Exception('Terjadi kesalahan: $e');
    }
  }

  // POST request
  Future<Map<String, dynamic>> post(
    String endpoint, 
    Map<String, dynamic> data, 
    {String? token}
  ) async {
    try {
      final url = Uri.parse(ApiConstants.getFullUrl(endpoint));
      final headers = token != null 
          ? ApiHeaders.authHeaders(token) 
          : ApiHeaders.jsonHeaders;
      
      final response = await http.post(
        url,
        headers: headers,
        body: jsonEncode(data),
      ).timeout(ApiConstants.connectTimeout);
      
      return _handleResponse(response);
    } on SocketException {
      throw Exception('Tidak ada koneksi internet');
    } on HttpException {
      throw Exception('Terjadi kesalahan pada server');
    } on FormatException {
      throw Exception('Format response tidak valid');
    } catch (e) {
      throw Exception('Terjadi kesalahan: $e');
    }
  }

  // PUT request
  Future<Map<String, dynamic>> put(
    String endpoint, 
    Map<String, dynamic> data, 
    {String? token}
  ) async {
    try {
      final url = Uri.parse(ApiConstants.getFullUrl(endpoint));
      final headers = token != null 
          ? ApiHeaders.authHeaders(token) 
          : ApiHeaders.jsonHeaders;
      
      final response = await http.put(
        url,
        headers: headers,
        body: jsonEncode(data),
      ).timeout(ApiConstants.connectTimeout);
      
      return _handleResponse(response);
    } on SocketException {
      throw Exception('Tidak ada koneksi internet');
    } on HttpException {
      throw Exception('Terjadi kesalahan pada server');
    } on FormatException {
      throw Exception('Format response tidak valid');
    } catch (e) {
      throw Exception('Terjadi kesalahan: $e');
    }
  }

  // DELETE request
  Future<Map<String, dynamic>> delete(String endpoint, {String? token}) async {
    try {
      final url = Uri.parse(ApiConstants.getFullUrl(endpoint));
      final headers = token != null 
          ? ApiHeaders.authHeaders(token) 
          : ApiHeaders.jsonHeaders;
      
      final response = await http.delete(
        url,
        headers: headers,
      ).timeout(ApiConstants.connectTimeout);
      
      return _handleResponse(response);
    } on SocketException {
      throw Exception('Tidak ada koneksi internet');
    } on HttpException {
      throw Exception('Terjadi kesalahan pada server');
    } on FormatException {
      throw Exception('Format response tidak valid');
    } catch (e) {
      throw Exception('Terjadi kesalahan: $e');
    }
  }

  // Handle response dari API
  Map<String, dynamic> _handleResponse(http.Response response) {
    final data = jsonDecode(response.body);
    
    if (response.statusCode >= 200 && response.statusCode < 300) {
      return data;
    } else {
      final message = data['message'] ?? 'Terjadi kesalahan pada server';
      throw Exception(message);
    }
  }
}
