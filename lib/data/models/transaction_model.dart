import 'package:flutter/foundation.dart'; // Import for debugPrint

class Transaction {
  final int id;
  final int userId;
  final int tarifId;
  final String platNomor;
  final double jumlah;
  final String status;
  final String? description; // New field for transaction description
  final DateTime? createdAt;
  final DateTime? updatedAt;
  
  Transaction({
    required this.id,
    required this.userId,
    required this.tarifId,
    required this.platNomor,
    required this.jumlah,
    this.status = 'berhasil',
    this.description = 'Pembayaran Tol', // Default value
    this.createdAt,
    this.updatedAt,
  });
  
  factory Transaction.fromJson(Map<String, dynamic> json) {
    // Debug print untuk melihat nilai yang diterima
    debugPrint('Transaction data: ${json.toString()}');
    
    // Secara khusus mencari field saldo_pembayaran yang digunakan di database
    final jumlahValue = json['saldo_pembayaran'] ?? 
                       json['jumlah'] ?? 
                       json['amount'] ?? 
                       json['nominal'] ?? 
                       json['harga'] ?? 
                       json['nilai'] ??
                       0.0;
    
    debugPrint('Jumlah value found: $jumlahValue');
    
    // Parsing tanggal dengan berbagai kemungkinan format
    DateTime? createdDate;
    if (json['created_at'] != null) {
      try {
        // API mengirimkan string waktu lokal (WIB), tapi Dart menganggapnya UTC.
        // Kita parse lalu konversi ke lokal untuk menampilkannya dengan benar.
        createdDate = DateTime.parse(json['created_at'].toString()).toLocal();
        debugPrint('Parsed date: $createdDate');
      } catch (e) {
        debugPrint('Error parsing date: $e');
        createdDate = null;
      }
    }
    
    DateTime? updatedDate;
    if (json['updated_at'] != null) {
      try {
        updatedDate = DateTime.parse(json['updated_at'].toString()).toLocal();
      } catch (e) {
        updatedDate = null;
      }
    }
                       
    return Transaction(
      id: json['id'] ?? 0,
      userId: json['user_id'] ?? 0,
      tarifId: json['tarif_id'] ?? 0,
      platNomor: json['plat_nomor'] ?? '',
      jumlah: double.tryParse(jumlahValue.toString()) ?? 0.0,
      status: json['status'] ?? 'berhasil',
      description: json['description'] ?? 'Pembayaran Tol',
      createdAt: createdDate,
      updatedAt: updatedDate,
    );
  }
  
  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'user_id': userId,
      'tarif_id': tarifId,
      'plat_nomor': platNomor,
      'jumlah': jumlah,
      'status': status,
      'description': description,
      'created_at': createdAt?.toIso8601String(),
      'updated_at': updatedAt?.toIso8601String(),
    };
  }
  
  // Getter untuk kompatibilitas dengan code yang sudah ada
  String get plateNumber => platNomor;
  double get amount => jumlah;
  DateTime get date => createdAt ?? DateTime.now();
  String get tollGateName => 'Gerbang Tol E-Plat'; // Default name
  
  TransactionStatus get transactionStatus {
    switch(status.toLowerCase()) {
      case 'berhasil':
      case 'success':
        return TransactionStatus.success;
      case 'pending':
        return TransactionStatus.pending;
      case 'gagal':
      case 'failed':
        return TransactionStatus.failed;
      default:
        return TransactionStatus.success;
    }
  }
}

enum TransactionStatus {
  success,
  pending,
  failed,
}

extension TransactionStatusExtension on TransactionStatus {
  String get displayName {
    switch (this) {
      case TransactionStatus.success:
        return 'Berhasil';
      case TransactionStatus.pending:
        return 'Pending';
      case TransactionStatus.failed:
        return 'Gagal';
    }
  }
}
