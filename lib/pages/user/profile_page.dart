import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:fluttertoast/fluttertoast.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../core/utils/constants.dart';
import '../../data/models/user_model.dart';
import '../../data/services/auth_service.dart';
import '../auth/splash_screen.dart';

class ProfilePage extends StatefulWidget {
  const ProfilePage({super.key});

  @override
  State<ProfilePage> createState() => _ProfilePageState();
}

class _ProfilePageState extends State<ProfilePage> {
  User? _currentUser;
  final AuthService _authService = AuthService();
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadUserData();
  }

  Future<void> _loadUserData() async {
    setState(() {
      _isLoading = true;
    });
    try {
      final user = _authService.currentUser;
      if (user != null) {
        // Refresh user data from API if needed, or just use cached
        // For now, we'll just use the cached user.
        setState(() {
          _currentUser = user;
        });
      }
    } catch (e) {
      Fluttertoast.showToast(
        msg: "Gagal memuat data pengguna: $e",
        backgroundColor: AppColors.errorColor,
      );
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<void> _handleLogout() async {
    bool? confirmLogout = await showDialog<bool>(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
          ),
          title: Text(
            "Konfirmasi Logout",
            style: GoogleFonts.poppins(
              fontWeight: FontWeight.bold,
              color: AppColors.textColor,
            ),
          ),
          content: Text(
            "Apakah Anda yakin ingin keluar dari akun?",
            style: GoogleFonts.poppins(
              color: AppColors.textLightColor,
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(false),
              child: Text(
                "Batal",
                style: GoogleFonts.poppins(
                  color: AppColors.primaryColor,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
            ElevatedButton(
              onPressed: () => Navigator.of(context).pop(true),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.errorColor,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(10),
                ),
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
              ),
              child: Text(
                "Logout",
                style: GoogleFonts.poppins(
                  color: Colors.white,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ],
        );
      },
    );

    if (confirmLogout == true) {
      await _authService.logout();
      if (mounted) {
        Navigator.of(context).pushAndRemoveUntil(
          MaterialPageRoute(builder: (context) => const SplashScreen()),
          (Route<dynamic> route) => false,
        );
      }
      Fluttertoast.showToast(
        msg: "Berhasil logout!",
        backgroundColor: AppColors.successColor,
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return _isLoading
        ? Center(
            child: CircularProgressIndicator(
              color: AppColors.primaryColor,
            ),
          )
        : SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  "Profil Pengguna",
                  style: GoogleFonts.poppins(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: AppColors.textColor,
                  ),
                ),
                const SizedBox(height: 24),
                _buildProfileInfoCard(),
                const SizedBox(height: 24),
                Text(
                  "Pengaturan Akun",
                  style: GoogleFonts.poppins(
                    fontSize: 18,
                    fontWeight: FontWeight.w600,
                    color: AppColors.textColor,
                  ),
                ),
                const SizedBox(height: 16),
                _buildSettingItem(
                  icon: Icons.edit,
                  title: "Edit Profil",
                  onTap: () {
                    Fluttertoast.showToast(
                        msg: "Fitur edit profil akan segera hadir!",
                        backgroundColor: AppColors.accentColor);
                  },
                ),
                _buildSettingItem(
                  icon: Icons.lock,
                  title: "Ubah Password",
                  onTap: () {
                    Fluttertoast.showToast(
                        msg: "Fitur ubah password akan segera hadir!",
                        backgroundColor: AppColors.accentColor);
                  },
                ),
                _buildSettingItem(
                  icon: Icons.logout,
                  title: "Logout",
                  isDestructive: true,
                  onTap: _handleLogout,
                ),
              ],
            ),
          );
  }

  Widget _buildProfileInfoCard() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withAlpha(8),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          const CircleAvatar(
            radius: 40,
            backgroundColor: AppColors.primaryColor,
            child: Icon(
              Icons.person,
              size: 40,
              color: Colors.white,
            ),
          ),
          const SizedBox(height: 16),
          Text(
            _currentUser?.name ?? "Nama Pengguna",
            style: GoogleFonts.poppins(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: AppColors.textColor,
            ),
          ),
          Text(
            _currentUser?.email ?? "email@example.com",
            style: GoogleFonts.poppins(
              fontSize: 14,
              color: AppColors.textLightColor,
            ),
          ),
          const SizedBox(height: 20),
          _buildInfoRow(
              "Plat Nomor", _currentUser?.platNomor ?? "Tidak Ada"),
          _buildInfoRow(
              "Kelompok Kendaraan", _currentUser?.kelompokKendaraan ?? "Tidak Ada"),
          _buildInfoRow(
              "Saldo", "Rp${_currentUser?.saldo.toStringAsFixed(0).replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (Match m) => '${m[1]}.') ?? '0'}"),
        ],
      ),
    );
  }

  Widget _buildInfoRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: GoogleFonts.poppins(
              fontSize: 15,
              fontWeight: FontWeight.w500,
              color: AppColors.textColor,
            ),
          ),
          Text(
            value,
            style: GoogleFonts.poppins(
              fontSize: 15,
              color: AppColors.textLightColor,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSettingItem({
    required IconData icon,
    required String title,
    required VoidCallback onTap,
    bool isDestructive = false,
  }) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
      ),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Icon(
                icon,
                color: isDestructive ? AppColors.errorColor : AppColors.primaryColor,
                size: 24,
              ),
              const SizedBox(width: 16),
              Text(
                title,
                style: GoogleFonts.poppins(
                  fontSize: 16,
                  fontWeight: FontWeight.w500,
                  color: isDestructive ? AppColors.errorColor : AppColors.textColor,
                ),
              ),
              const Spacer(),
              Icon(
                Icons.arrow_forward_ios,
                size: 18,
                color: isDestructive ? AppColors.errorColor : AppColors.textLightColor,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
