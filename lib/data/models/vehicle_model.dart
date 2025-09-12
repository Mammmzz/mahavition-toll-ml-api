class Vehicle {
  final String id;
  final String plateNumber;
  final String brand;
  final String model;
  final String type;
  final String color;
  final int year;
  final bool isVerified;
  
  Vehicle({
    required this.id,
    required this.plateNumber,
    required this.brand,
    required this.model,
    required this.type,
    required this.color,
    required this.year,
    this.isVerified = false,
  });
  
  factory Vehicle.fromJson(Map<String, dynamic> json) {
    return Vehicle(
      id: json['id'] ?? '',
      plateNumber: json['plate_number'] ?? '',
      brand: json['brand'] ?? '',
      model: json['model'] ?? '',
      type: json['type'] ?? '',
      color: json['color'] ?? '',
      year: json['year'] ?? 0,
      isVerified: json['is_verified'] ?? false,
    );
  }
  
  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'plate_number': plateNumber,
      'brand': brand,
      'model': model,
      'type': type,
      'color': color,
      'year': year,
      'is_verified': isVerified,
    };
  }
}
