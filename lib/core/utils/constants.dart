import 'package:flutter/material.dart';

class AppColors {
  static const Color primaryColor = Color(0xFF2979FF);
  static const Color secondaryColor = Color(0xFF2E3B62);
  static const Color accentColor = Color(0xFF56CCF2);
  static const Color backgroundColor = Color(0xFFFFFFFF);
  static const Color textColor = Color(0xFF222B45);
  static const Color textLightColor = Color(0xFF8F9BB3);
  static const Color errorColor = Color(0xFFFF3D71);
  static const Color successColor = Color(0xFF00E096);
  static const Color whiteColor = Color(0xFFFFFFFF);
  static const Color cardColor = Color(0xFFFFFFFF);
  static const Color shadowColor = Color(0x1A000000);
  static const Color borderColor = Color(0xFFEEF2FA);
}

class AppStrings {
  static const String appName = "EZToll";
  static const String welcome = "Selamat Datang";
  static const String loginTitle = "Masuk ke Akun Anda";
  static const String loginSubtitle = "Silakan masukkan kredensial Anda untuk mengakses layanan E-Tol Plat";
  static const String email = "Email";
  static const String password = "Kata Sandi";
  static const String forgotPassword = "Lupa Kata Sandi?";
  static const String login = "Masuk";
  static const String dontHaveAccount = "Belum punya akun? ";
  static const String register = "Daftar";
  static const String emailHint = "Masukkan email Anda";
  static const String passwordHint = "Masukkan kata sandi Anda";
  static const String or = "Atau masuk dengan";
  
  // User Dashboard Strings
  static const String dashboard = "Dasbor";
  static const String myVehicles = "Kendaraan Saya";
  static const String transactions = "Transaksi";
  static const String profile = "Profil";
  static const String addVehicle = "Tambah Kendaraan";
  static const String balance = "Saldo";
  static const String topUp = "Isi Saldo";
  static const String lastTransaction = "Transaksi Terakhir";
  static const String viewAll = "Lihat Semua";
}

class AppSizes {
  static const double paddingXS = 4.0;
  static const double paddingS = 8.0;
  static const double paddingM = 16.0;
  static const double paddingL = 24.0;
  static const double paddingXL = 32.0;
  static const double paddingXXL = 48.0;
  
  static const double borderRadiusS = 4.0;
  static const double borderRadiusM = 8.0;
  static const double borderRadiusL = 16.0;
  static const double borderRadiusXL = 24.0;
  
  static const double buttonHeight = 56.0;
  static const double inputHeight = 56.0;
  
  static const double iconSizeS = 16.0;
  static const double iconSizeM = 24.0;
  static const double iconSizeL = 32.0;
}

class AppTextStyles {
  static const TextStyle heading1 = TextStyle(
    fontSize: 28.0,
    fontWeight: FontWeight.bold,
    color: AppColors.textColor,
  );
  
  static const TextStyle heading2 = TextStyle(
    fontSize: 24.0,
    fontWeight: FontWeight.bold,
    color: AppColors.textColor,
  );
  
  static const TextStyle heading3 = TextStyle(
    fontSize: 20.0,
    fontWeight: FontWeight.bold,
    color: AppColors.textColor,
  );
  
  static const TextStyle body = TextStyle(
    fontSize: 16.0,
    color: AppColors.textColor,
  );
  
  static const TextStyle bodySmall = TextStyle(
    fontSize: 14.0,
    color: AppColors.textLightColor,
  );
  
  static const TextStyle button = TextStyle(
    fontSize: 16.0,
    fontWeight: FontWeight.bold,
    color: AppColors.whiteColor,
  );
  
  static const TextStyle link = TextStyle(
    fontSize: 16.0,
    fontWeight: FontWeight.w500,
    color: AppColors.primaryColor,
    decoration: TextDecoration.underline,
  );
}
