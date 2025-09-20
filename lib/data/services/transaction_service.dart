import '../../core/services/api_service.dart';
import '../../core/utils/api_constants.dart';
import '../models/transaction_model.dart';
import '../models/tarif_model.dart';
import 'package:flutter/foundation.dart'; // Import for debugPrint

class TransactionService {
  static final TransactionService _instance = TransactionService._internal();
  factory TransactionService() => _instance;
  TransactionService._internal();

  final ApiService _apiService = ApiService();

  // Get all transactions
  Future<List<Transaction>> getAllTransactions({String? token}) async {
    try {
      final response = await _apiService.get(
        ApiConstants.transactions,
        token: token,
      );

      if (response['success'] == true && response['data'] != null) {
        final List<dynamic> transactionList = response['data'];
        return transactionList
            .map((json) => Transaction.fromJson(json))
            .toList();
      }
      return [];
    } catch (e) {
      debugPrint('Error getting transactions: $e');
      return [];
    }
  }

  // Get transactions by user/plate number
  Future<List<Transaction>> getTransactionsByPlate(String plateNumber, {String? token}) async {
    try {
      final response = await _apiService.get(
        '${ApiConstants.transactions}?plat_nomor=$plateNumber',
        token: token,
      );

      debugPrint('API Response for transactions: $response');

      if (response['success'] == true && response['data'] != null) {
        final List<dynamic> transactionList = response['data'];
        
        // Filter hanya transaksi dengan plat nomor yang sama dengan pengguna yang login
        final filteredList = transactionList.where((json) => 
          json['plat_nomor'] == plateNumber).toList();
        
        debugPrint('Filtered transactions for plate $plateNumber: ${filteredList.length} of ${transactionList.length}');
        
        // Urutkan transaksi dari yang terbaru
        final transactions = filteredList
            .map((json) => Transaction.fromJson(json))
            .toList();
        
        // Urutkan berdasarkan tanggal terbaru
        transactions.sort((a, b) => 
          (b.createdAt ?? DateTime.now()).compareTo(a.createdAt ?? DateTime.now()));
        
        return transactions;
      }
      return [];
    } catch (e) {
      debugPrint('Error getting transactions by plate: $e');
      return [];
    }
  }

  // Process a transaction (from toll gate)
  Future<Map<String, dynamic>> processTransaction({
    required String plateNumber,
    required String vehicleType,
    String? token,
  }) async {
    try {
      final response = await _apiService.post(
        ApiConstants.processTransaction,
        {
          'plat_nomor': plateNumber,
          'kelompok_kendaraan': vehicleType,
        },
        token: token,
      );

      return response;
    } catch (e) {
      throw Exception('Proses transaksi gagal: $e');
    }
  }

  // Get all tarifs
  Future<List<Tarif>> getAllTarifs({String? token}) async {
    try {
      final response = await _apiService.get(
        ApiConstants.tarifs,
        token: token,
      );

      if (response['success'] == true && response['data'] != null) {
        final List<dynamic> tarifList = response['data'];
        return tarifList
            .map((json) => Tarif.fromJson(json))
            .toList();
      }
      return [];
    } catch (e) {
      debugPrint('Error getting tarifs: $e');
      return [];
    }
  }

  // Get tarif by vehicle type
  Future<Tarif?> getTarifByVehicleType(String vehicleType, {String? token}) async {
    try {
      final response = await _apiService.get(
        '${ApiConstants.tarifs}?kelompok_kendaraan=$vehicleType',
        token: token,
      );

      if (response['success'] == true && response['data'] != null) {
        final List<dynamic> tarifList = response['data'];
        if (tarifList.isNotEmpty) {
          return Tarif.fromJson(tarifList.first);
        }
      }
      return null;
    } catch (e) {
      debugPrint('Error getting tarif by vehicle type: $e');
      return null;
    }
  }

  // Create manual transaction (for testing)
  Future<Transaction> createTransaction({
    required int userId,
    required int tarifId,
    required String plateNumber,
    required double amount,
    String status = 'berhasil',
    String? token,
  }) async {
    try {
      final response = await _apiService.post(
        ApiConstants.transactions,
        {
          'user_id': userId,
          'tarif_id': tarifId,
          'plat_nomor': plateNumber,
          'jumlah': amount,
          'status': status,
        },
        token: token,
      );

      if (response['success'] == true && response['data'] != null) {
        return Transaction.fromJson(response['data']);
      } else {
        throw Exception(response['message'] ?? 'Gagal membuat transaksi');
      }
    } catch (e) {
      throw Exception('Buat transaksi gagal: $e');
    }
  }

  // Get transaction statistics
  Future<Map<String, dynamic>> getTransactionStats({String? plateNumber, String? token}) async {
    try {
      String endpoint = '${ApiConstants.transactions}/stats';
      if (plateNumber != null) {
        endpoint += '?plat_nomor=$plateNumber';
      }
      
      final response = await _apiService.get(endpoint, token: token);

      if (response['success'] == true && response['data'] != null) {
        return response['data'];
      }
      return {
        'total_transactions': 0,
        'total_amount': 0.0,
        'success_transactions': 0,
        'failed_transactions': 0,
      };
    } catch (e) {
      debugPrint('Error getting transaction stats: $e');
      return {
        'total_transactions': 0,
        'total_amount': 0.0,
        'success_transactions': 0,
        'failed_transactions': 0,
      };
    }
  }

  // Validate plate and process if valid
  Future<Map<String, dynamic>> validateAndProcess({
    required String plateNumber,
    required String vehicleType,
    String? token,
  }) async {
    try {
      // First validate plate and vehicle
      final validateResponse = await _apiService.post(
        ApiConstants.validatePlateVehicle,
        {
          'plat_nomor': plateNumber,
          'kelompok_kendaraan': vehicleType,
        },
        token: token,
      );

      if (validateResponse['success'] == true && validateResponse['valid'] == true) {
        // If validation successful, process transaction
        final processResponse = await processTransaction(
          plateNumber: plateNumber,
          vehicleType: vehicleType,
          token: token,
        );
        
        return processResponse;
      } else {
        return validateResponse;
      }
    } catch (e) {
      throw Exception('Validasi dan proses gagal: $e');
    }
  }
}
