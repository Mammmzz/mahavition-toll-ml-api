import 'package:flutter/material.dart';
import 'lib/data/services/auth_service.dart';
import 'lib/data/services/vehicle_service.dart';
import 'lib/data/services/transaction_service.dart';

void main() async {
  // Test basic connectivity
  print('🚀 Testing Mobile App API Integration...\n');
  
  try {
    final vehicleService = VehicleService();
    final authService = AuthService();
    final transactionService = TransactionService();
    
    // Test 1: Validate existing plate
    print('📋 Test 1: Validating existing plate number...');
    try {
      final result = await vehicleService.validatePlate('B 5432 KRI');
      print('✅ Plate validation successful: ${result['valid']}');
      print('   Message: ${result['message']}');
    } catch (e) {
      print('❌ Plate validation failed: $e');
    }
    
    print('\n');
    
    // Test 2: Login with plate and vehicle type
    print('📋 Test 2: Login with plate number and vehicle type...');
    try {
      final user = await authService.loginWithPlateAndVehicle('B 5432 KRI', 'Mobil');
      print('✅ Login successful!');
      print('   User: ${user.nama}');
      print('   Plate: ${user.platNomor}');
      print('   Vehicle Type: ${user.kelompokKendaraan}');
      print('   Balance: Rp ${user.saldo.toStringAsFixed(0)}');
    } catch (e) {
      print('❌ Login failed: $e');
    }
    
    print('\n');
    
    // Test 3: Get transactions
    print('📋 Test 3: Getting transactions...');
    try {
      final transactions = await transactionService.getTransactionsByPlate(
        'B 5432 KRI',
        token: authService.token,
      );
      print('✅ Transactions loaded: ${transactions.length} items');
      if (transactions.isNotEmpty) {
        final latest = transactions.first;
        print('   Latest: ${latest.platNomor} - Rp ${latest.jumlah}');
      }
    } catch (e) {
      print('❌ Load transactions failed: $e');
    }
    
    print('\n');
    
    // Test 4: Get all tarifs
    print('📋 Test 4: Getting tarifs...');
    try {
      final tarifs = await transactionService.getAllTarifs();
      print('✅ Tarifs loaded: ${tarifs.length} items');
      for (final tarif in tarifs) {
        print('   ${tarif.kelompokKendaraan}: Rp ${tarif.harga.toStringAsFixed(0)}');
      }
    } catch (e) {
      print('❌ Load tarifs failed: $e');
    }
    
    print('\n🎉 API Integration Test Complete!');
    
  } catch (e) {
    print('❌ General error: $e');
  }
}
