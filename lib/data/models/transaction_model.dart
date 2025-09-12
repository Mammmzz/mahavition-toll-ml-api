class Transaction {
  final int id;
  final int userId;
  final int tarifId;
  final String platNomor;
  final double jumlah;
  final String status;
  final DateTime? createdAt;
  final DateTime? updatedAt;
  
  Transaction({
    required this.id,
    required this.userId,
    required this.tarifId,
    required this.platNomor,
    required this.jumlah,
    this.status = 'berhasil',
    this.createdAt,
    this.updatedAt,
  });
  
  factory Transaction.fromJson(Map<String, dynamic> json) {
    return Transaction(
      id: json['id'] ?? 0,
      userId: json['user_id'] ?? 0,
      tarifId: json['tarif_id'] ?? 0,
      platNomor: json['plat_nomor'] ?? '',
      jumlah: double.tryParse(json['jumlah'].toString()) ?? 0.0,
      status: json['status'] ?? 'berhasil',
      createdAt: json['created_at'] != null 
          ? DateTime.tryParse(json['created_at']) 
          : null,
      updatedAt: json['updated_at'] != null 
          ? DateTime.tryParse(json['updated_at']) 
          : null,
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
  
  static TransactionStatus _getStatusFromString(String status) {
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
  
  static String _getStringFromStatus(TransactionStatus status) {
    switch(status) {
      case TransactionStatus.success:
        return 'berhasil';
      case TransactionStatus.pending:
        return 'pending';
      case TransactionStatus.failed:
        return 'gagal';
    }
  }
}

enum TransactionStatus {
  success,
  pending,
  failed,
}
