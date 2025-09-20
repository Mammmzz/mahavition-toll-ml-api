class User {
  final int id;
  final String nama;
  final String email;
  final String platNomor;
  final String kelompokKendaraan;
  final double saldo;
  final DateTime? createdAt;
  final DateTime? updatedAt;
  
  User({
    required this.id,
    required this.nama,
    required this.email,
    required this.platNomor,
    required this.kelompokKendaraan,
    this.saldo = 0.0,
    this.createdAt,
    this.updatedAt,
  });
  
  factory User.fromJson(Map<String, dynamic> json) {
    // Mencari nama pengguna dari berbagai kemungkinan field
    String nama = json['nama_lengkap'] ?? 
                  json['name'] ?? 
                  json['nama'] ?? 
                  json['username'] ?? 
                  '';
                  
    return User(
      id: json['id'] ?? 0,
      nama: nama,
      email: json['email'] ?? '',
      platNomor: json['plat_nomor'] ?? '',
      kelompokKendaraan: json['kelompok_kendaraan'] ?? 'Mobil',
      saldo: double.tryParse(json['saldo'].toString()) ?? 0.0,
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
      'nama': nama,
      'email': email,
      'plat_nomor': platNomor,
      'kelompok_kendaraan': kelompokKendaraan,
      'saldo': saldo,
      'created_at': createdAt?.toIso8601String(),
      'updated_at': updatedAt?.toIso8601String(),
    };
  }
  
  // Getter untuk kompatibilitas dengan code yang sudah ada
  String get name => nama;
  String get plateNumber => platNomor;
  String get vehicleType => kelompokKendaraan;
  double get balance => saldo;
  
  // Copy with method untuk update data
  User copyWith({
    int? id,
    String? nama,
    String? email,
    String? platNomor,
    String? kelompokKendaraan,
    double? saldo,
    DateTime? createdAt,
    DateTime? updatedAt,
  }) {
    return User(
      id: id ?? this.id,
      nama: nama ?? this.nama,
      email: email ?? this.email,
      platNomor: platNomor ?? this.platNomor,
      kelompokKendaraan: kelompokKendaraan ?? this.kelompokKendaraan,
      saldo: saldo ?? this.saldo,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }
}
