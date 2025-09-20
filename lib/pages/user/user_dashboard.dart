import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:fluttertoast/fluttertoast.dart';
import '../../core/utils/constants.dart';
import '../../widgets/common/app_bottom_navigation.dart';
import '../../widgets/user/balance_card.dart';
import '../../widgets/user/transaction_item.dart';
import '../../data/models/transaction_model.dart';
import '../../data/models/user_model.dart';
import '../../data/services/auth_service.dart';
import '../../data/services/transaction_service.dart';
import 'all_transactions_page.dart';
import 'profile_page.dart';

class UserDashboard extends StatefulWidget {
  const UserDashboard({super.key});

  @override
  State<UserDashboard> createState() => _UserDashboardState();
}

class _UserDashboardState extends State<UserDashboard> {
  int _currentIndex = 0;
  final AuthService _authService = AuthService();
  final TransactionService _transactionService = TransactionService();
  
  User? _currentUser;
  List<Transaction> _recentTransactions = [];
  bool _isLoading = true;
  bool _isLoadingTransactions = true;
  
  @override
  void initState() {
    super.initState();
    _loadUserData();
    _loadTransactions();
  }

  Future<void> _loadUserData() async {
    try {
      setState(() {
        _isLoading = true;
      });
      
      _currentUser = _authService.currentUser;
      
      setState(() {
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _isLoading = false;
      });
      print('Error loading user data: $e');
    }
  }

  Future<void> _loadTransactions() async {
    try {
      setState(() {
        _isLoadingTransactions = true;
      });
      
      if (_currentUser != null) {
        final transactions = await _transactionService.getTransactionsByPlate(
          _currentUser!.platNomor,
          token: _authService.token,
        );
        
        setState(() {
          _recentTransactions = transactions.take(3).toList();
          _isLoadingTransactions = false;
        });
      } else {
        setState(() {
          _isLoadingTransactions = false;
        });
      }
    } catch (e) {
      setState(() {
        _isLoadingTransactions = false;
      });
      print('Error loading transactions: $e');
    }
  }

  void _handleNavTap(int index) {
    setState(() {
      _currentIndex = index;
    });
  }
  
  void _handleTopUp() {
    Fluttertoast.showToast(
      msg: "Fitur isi saldo akan segera hadir!",
      backgroundColor: AppColors.accentColor,
    );
  }
  
  void _handleAddVehicle() {
    Fluttertoast.showToast(
      msg: "Fitur tambah kendaraan akan segera hadir!",
      backgroundColor: AppColors.accentColor,
    );
  }
  
  void _handleTransactionTap(Transaction transaction) {
    Fluttertoast.showToast(
      msg: "Detail transaksi ${transaction.id} akan segera hadir!",
      backgroundColor: AppColors.accentColor,
    );
  }
  
  void _handleViewAllTransactions() {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => const AllTransactionsPage(),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFFAFAFA),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        title: Text(
          AppStrings.appName,
          style: GoogleFonts.poppins(
            fontSize: 20,
            fontWeight: FontWeight.bold,
            color: AppColors.textColor,
          ),
        ),
        actions: [
          IconButton(
            icon: const Icon(
              Icons.notifications_outlined,
              color: AppColors.textColor,
            ),
            onPressed: () {
              Fluttertoast.showToast(
                msg: "Notifikasi akan segera hadir!",
                backgroundColor: AppColors.accentColor,
              );
            },
          ),
        ],
      ),
      body: IndexedStack(
        index: _currentIndex,
        children: [
          _buildHomeTab(),
          _buildEmptyTab('Kendaraan'),
          _buildEmptyTab('Transaksi'),
          const ProfilePage(),
        ],
      ),
      bottomNavigationBar: AppBottomNavigation(
        currentIndex: _currentIndex,
        onTap: _handleNavTap,
      ),
    );
  }
  
  Widget _buildHomeTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      physics: const BouncingScrollPhysics(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Greeting
          _isLoading
              ? _buildShimmerText(width: 200, height: 24)
              : Text(
                  'Halo, ${_currentUser?.name ?? 'Pengguna'}',
                  style: GoogleFonts.poppins(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: AppColors.textColor,
                  ),
                ),
          const SizedBox(height: 4),
          _isLoading
              ? _buildShimmerText(width: 300, height: 14)
              : Text(
                  'Plat: ${_currentUser?.platNomor ?? '-'} | ${_currentUser?.kelompokKendaraan ?? '-'}',
                  style: GoogleFonts.poppins(
                    fontSize: 14,
                    color: AppColors.textLightColor,
                  ),
                ),
          
          const SizedBox(height: 24),
          
          // Balance Card
          _isLoading
              ? _buildShimmerCard()
              : BalanceCard(
                  balance: _currentUser?.saldo ?? 0.0,
                  onTopUp: _handleTopUp,
                ),
          
          const SizedBox(height: 24),
          
          // Quick Actions
          Text(
            'Menu Utama',
            style: GoogleFonts.poppins(
              fontSize: 18,
              fontWeight: FontWeight.w600,
              color: AppColors.textColor,
            ),
          ),
          
          const SizedBox(height: 16),
          
          Row(
            children: [
              _buildQuickActionItem(
                icon: Icons.directions_car,
                label: 'Tambah Kendaraan',
                onTap: _handleAddVehicle,
              ),
              const SizedBox(width: 16),
              _buildQuickActionItem(
                icon: Icons.history,
                label: 'Riwayat',
                onTap: _handleViewAllTransactions,
              ),
              const SizedBox(width: 16),
              _buildQuickActionItem(
                icon: Icons.place_outlined,
                label: 'Lokasi',
                onTap: () {
                  Fluttertoast.showToast(
                    msg: "Fitur lokasi akan segera hadir!",
                    backgroundColor: AppColors.accentColor,
                  );
                },
              ),
            ],
          ),
          
          const SizedBox(height: 24),
          
          // Recent Transactions
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Transaksi Terakhir',
                style: GoogleFonts.poppins(
                  fontSize: 18,
                  fontWeight: FontWeight.w600,
                  color: AppColors.textColor,
                ),
              ),
              TextButton(
                onPressed: _handleViewAllTransactions,
                child: Text(
                  'Lihat Semua',
                  style: GoogleFonts.poppins(
                    fontSize: 14,
                    fontWeight: FontWeight.w500,
                    color: AppColors.primaryColor,
                  ),
                ),
              ),
            ],
          ),
          
          const SizedBox(height: 8),
          
          // Transactions List
          _isLoadingTransactions
              ? _buildShimmerTransactions()
              : _recentTransactions.isEmpty
                  ? _buildEmptyTransactions()
                  : ListView.separated(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      itemCount: _recentTransactions.length,
                      separatorBuilder: (context, index) => const SizedBox(height: 12),
                      itemBuilder: (context, index) {
                        return TransactionItem(
                          transaction: _recentTransactions[index],
                          onTap: () => _handleTransactionTap(_recentTransactions[index]),
                        );
                      },
                    ),
            
          const SizedBox(height: 24),
        ],
      ),
    );
  }
  
  Widget _buildQuickActionItem({
    required IconData icon,
    required String label,
    required VoidCallback onTap,
  }) {
    return Expanded(
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 16),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(12),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withAlpha(8),
                blurRadius: 10,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: AppColors.primaryColor.withAlpha(20),
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  icon,
                  color: AppColors.primaryColor,
                  size: 24,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                label,
                style: GoogleFonts.poppins(
                  fontSize: 13,
                  fontWeight: FontWeight.w500,
                  color: AppColors.textColor,
                ),
                textAlign: TextAlign.center,
              ),
            ],
          ),
        ),
      ),
    );
  }
  
  Widget _buildEmptyTransactions() {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 40),
      alignment: Alignment.center,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            Icons.receipt_long,
            size: 70,
            color: Colors.grey.shade300,
          ),
          const SizedBox(height: 16),
          Text(
            'Belum ada transaksi',
            style: GoogleFonts.poppins(
              fontSize: 16,
              fontWeight: FontWeight.w500,
              color: AppColors.textLightColor,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Transaksi Anda akan muncul di sini',
            style: GoogleFonts.poppins(
              fontSize: 14,
              color: Colors.grey,
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }
  
  Widget _buildEmptyTab(String title) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            Icons.construction,
            size: 70,
            color: Colors.grey.shade300,
          ),
          const SizedBox(height: 16),
          Text(
            'Halaman $title',
            style: GoogleFonts.poppins(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: AppColors.textColor,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Fitur ini akan segera hadir',
            style: GoogleFonts.poppins(
              fontSize: 16,
              color: AppColors.textLightColor,
            ),
          ),
        ],
      ),
    );
  }
  
  Widget _buildShimmerText({required double width, required double height}) {
    return Container(
      width: width,
      height: height,
      decoration: BoxDecoration(
        color: Colors.grey.shade300,
        borderRadius: BorderRadius.circular(4),
      ),
    );
  }
  
  Widget _buildShimmerCard() {
    return Container(
      width: double.infinity,
      height: 120,
      decoration: BoxDecoration(
        color: Colors.grey.shade300,
        borderRadius: BorderRadius.circular(16),
      ),
    );
  }
  
  Widget _buildShimmerTransactions() {
    return Column(
      children: List.generate(3, (index) => Container(
        margin: const EdgeInsets.only(bottom: 12),
        child: Container(
          width: double.infinity,
          height: 80,
          decoration: BoxDecoration(
            color: Colors.grey.shade300,
            borderRadius: BorderRadius.circular(12),
          ),
        ),
      )),
    );
  }
}
