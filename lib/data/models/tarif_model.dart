class Tarif {
  final int id;
  final String kelompokKendaraan;
  final double harga;
  final DateTime? createdAt;
  final DateTime? updatedAt;
  
  Tarif({
    required this.id,
    required this.kelompokKendaraan,
    required this.harga,
    this.createdAt,
    this.updatedAt,
  });
  
  factory Tarif.fromJson(Map<String, dynamic> json) {
    return Tarif(
      id: json['id'] ?? 0,
      kelompokKendaraan: json['kelompok_kendaraan'] ?? '',
      harga: double.tryParse(json['harga'].toString()) ?? 0.0,
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
      'kelompok_kendaraan': kelompokKendaraan,
      'harga': harga,
      'created_at': createdAt?.toIso8601String(),
      'updated_at': updatedAt?.toIso8601String(),
    };
  }
  
  // Getter untuk kompatibilitas
  String get vehicleType => kelompokKendaraan;
  double get price => harga;
}
