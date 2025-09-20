import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:fluttertoast/fluttertoast.dart';
import 'package:intl/intl.dart'; // Import for DateFormat
import '../../core/utils/constants.dart';
import '../../data/models/transaction_model.dart';
import '../../data/services/auth_service.dart';
import '../../data/services/transaction_service.dart';
import 'package:shimmer/shimmer.dart';

class AllTransactionsPage extends StatefulWidget {
  const AllTransactionsPage({super.key});

  @override
  State<AllTransactionsPage> createState() => _AllTransactionsPageState();
}

class _AllTransactionsPageState extends State<AllTransactionsPage> {
  final AuthService _authService = AuthService();
  final TransactionService _transactionService = TransactionService();

  List<Transaction> _allTransactions = [];
  List<Transaction> _filteredTransactions = [];
  bool _isLoading = true;
  String? _currentUserPlateNumber;

  // Filter options
  TransactionStatus? _selectedStatusFilter;
  DateTime? _startDate;
  DateTime? _endDate;

  @override
  void initState() {
    super.initState();
    _loadUserDataAndTransactions();
  }

  Future<void> _loadUserDataAndTransactions() async {
    setState(() {
      _isLoading = true;
    });
    try {
      final currentUser = _authService.currentUser;
      if (currentUser != null) {
        _currentUserPlateNumber = currentUser.platNomor;
        final transactions = await _transactionService.getTransactionsByPlate(
          currentUser.platNomor,
          token: _authService.token,
        );
        setState(() {
          _allTransactions = transactions;
          _filteredTransactions = transactions; // Initially show all
        });
      }
    } catch (e) {
      Fluttertoast.showToast(
        msg: "Gagal memuat transaksi: $e",
        backgroundColor: AppColors.errorColor,
      );
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  void _applyFilters() {
    setState(() {
      _filteredTransactions = _allTransactions.where((transaction) {
        bool statusMatch = _selectedStatusFilter == null ||
            transaction.transactionStatus == _selectedStatusFilter;
        bool dateMatch = true;

        if (_startDate != null && transaction.createdAt != null) {
          dateMatch = transaction.createdAt!.isAfter(_startDate!)
              ||
              transaction.createdAt!.isAtSameMomentAs(_startDate!);
        }

        if (_endDate != null && transaction.createdAt != null) {
          dateMatch = dateMatch &&
              (transaction.createdAt!.isBefore(_endDate!)
                  ||
                  transaction.createdAt!.isAtSameMomentAs(_endDate!));
        }

        return statusMatch && dateMatch;
      }).toList();
    });
  }

  Future<void> _selectDate(BuildContext context, bool isStartDate) async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime(2000),
      lastDate: DateTime.now(),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: ColorScheme.light(
              primary: AppColors.primaryColor, // header background color
              onPrimary: Colors.white, // header text color
              onSurface: AppColors.textColor, // body text color
            ),
            textButtonTheme: TextButtonThemeData(
              style: TextButton.styleFrom(
                foregroundColor: AppColors.primaryColor, // button text color
              ),
            ),
          ),
          child: child!,
        );
      },
    );
    if (picked != null) {
      setState(() {
        if (isStartDate) {
          _startDate = DateTime(picked.year, picked.month, picked.day, 0, 0, 0);
        } else {
          _endDate = DateTime(picked.year, picked.month, picked.day, 23, 59, 59);
        }
        _applyFilters();
      });
    }
  }

  void _clearFilters() {
    setState(() {
      _selectedStatusFilter = null;
      _startDate = null;
      _endDate = null;
      _filteredTransactions = _allTransactions;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          'Transaksi ${_currentUserPlateNumber ?? ''}',
          style: GoogleFonts.poppins(
            fontSize: 20,
            fontWeight: FontWeight.bold,
            color: AppColors.textColor,
          ),
        ),
        backgroundColor: Colors.white,
        elevation: 0,
      ),
      body: Column(
        children: [
          _buildFilterChips(),
          Expanded(
            child: _isLoading
                ? _buildShimmerList()
                : RefreshIndicator(
                    onRefresh: _loadUserDataAndTransactions,
                    color: AppColors.primaryColor,
                    child: _filteredTransactions.isEmpty
                        ? _buildEmptyState()
                        : ListView.separated(
                            padding: const EdgeInsets.all(16),
                            itemCount: _filteredTransactions.length,
                            separatorBuilder: (context, index) =>
                                const SizedBox(height: 12),
                            itemBuilder: (context, index) {
                              final transaction = _filteredTransactions[index];
                              return _buildTransactionCard(transaction);
                            },
                          ),
                  ),
          ),
        ],
      ),
    );
  }

  Widget _buildFilterChips() {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: Row(
        children: [
          // Status Filter
          _buildDropdownFilter(
            'Status',
            _selectedStatusFilter,
            TransactionStatus.values,
            (status) => status?.displayName ?? 'Semua Status',
            (TransactionStatus? newValue) {
              setState(() {
                _selectedStatusFilter = newValue;
                _applyFilters();
              });
            },
          ),
          const SizedBox(width: 8),

          // Date Range Filter
          _buildDateFilterChip(
            label: _startDate == null
                ? 'Tanggal Mulai'
                : DateFormat('dd/MM/yyyy', 'id_ID').format(_startDate!),
            onTap: () => _selectDate(context, true),
            onClear: _startDate == null
                ? null
                : () {
                    setState(() {
                      _startDate = null;
                      _applyFilters();
                    });
                  },
          ),
          const SizedBox(width: 8),
          _buildDateFilterChip(
            label: _endDate == null
                ? 'Tanggal Akhir'
                : DateFormat('dd/MM/yyyy', 'id_ID').format(_endDate!),
            onTap: () => _selectDate(context, false),
            onClear: _endDate == null
                ? null
                : () {
                    setState(() {
                      _endDate = null;
                      _applyFilters();
                    });
                  },
          ),
          const SizedBox(width: 8),

          // Clear Filters Button
          if (_selectedStatusFilter != null ||
              _startDate != null ||
              _endDate != null)
            ActionChip(
              avatar: const Icon(Icons.clear_all, color: Colors.white),
              label: Text(
                'Bersihkan Filter',
                style: GoogleFonts.poppins(
                    color: Colors.white, fontWeight: FontWeight.w500),
              ),
              onPressed: _clearFilters,
              backgroundColor: AppColors.errorColor,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(20),
              ),
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            ),
        ],
      ),
    );
  }

  Widget _buildDropdownFilter<T>(
    String hint,
    T? value,
    List<T> items,
    String Function(T?) itemLabelMapper,
    ValueChanged<T?> onChanged,
  ) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
      decoration: BoxDecoration(
        color: AppColors.primaryColor.withAlpha(20),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppColors.primaryColor.withAlpha(50)),
      ),
      child: DropdownButtonHideUnderline(
        child: DropdownButton<T>(
          value: value,
          hint: Text(hint, style: GoogleFonts.poppins(color: AppColors.primaryColor)),
          icon: Icon(Icons.arrow_drop_down, color: AppColors.primaryColor),
          style: GoogleFonts.poppins(color: AppColors.primaryColor, fontSize: 14),
          dropdownColor: Colors.white,
          borderRadius: BorderRadius.circular(12),
          items: [
            DropdownMenuItem<T>(
              value: null,
              child: Text(itemLabelMapper(null), style: GoogleFonts.poppins()),
            ),
            ...items.map((T item) {
              return DropdownMenuItem<T>(
                value: item,
                child: Text(itemLabelMapper(item), style: GoogleFonts.poppins()),
              );
            }).toList(),
          ],
          onChanged: onChanged,
        ),
      ),
    );
  }

  Widget _buildDateFilterChip({
    required String label,
    required VoidCallback onTap,
    VoidCallback? onClear,
  }) {
    return FilterChip(
      label: Text(
        label,
        style: GoogleFonts.poppins(
          color: onClear != null ? Colors.white : AppColors.primaryColor,
          fontWeight: FontWeight.w500,
        ),
      ),
      selected: onClear != null,
      onSelected: (bool selected) {
        if (selected && onClear != null) {
          onClear();
        } else {
          onTap();
        }
      },
      avatar: onClear != null
          ? const Icon(Icons.event_busy, color: Colors.white, size: 18)
          : const Icon(Icons.event, color: AppColors.primaryColor, size: 18),
      backgroundColor: AppColors.primaryColor.withAlpha(20),
      selectedColor: AppColors.primaryColor,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(20),
        side: BorderSide(
          color: onClear != null
              ? AppColors.primaryColor
              : AppColors.primaryColor.withAlpha(50),
        ),
      ),
      labelPadding: const EdgeInsets.symmetric(horizontal: 4, vertical: 0),
      materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
    );
  }

  Widget _buildTransactionCard(Transaction transaction) {
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      margin: EdgeInsets.zero,
      child: InkWell(
        onTap: () {
          Fluttertoast.showToast(
            msg: "Detail transaksi ${transaction.id} akan segera hadir!",
            backgroundColor: AppColors.accentColor,
          );
        },
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Row(
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: AppColors.primaryColor.withAlpha(20),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Icon(
                  Icons.receipt_long,
                  color: AppColors.primaryColor,
                  size: 24,
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Transaksi #${transaction.id}',
                      style: GoogleFonts.poppins(
                        fontWeight: FontWeight.w600,
                        fontSize: 16,
                        color: AppColors.textColor,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      transaction.description ?? 'Pembayaran Tol',
                      style: GoogleFonts.poppins(
                        fontSize: 13,
                        color: AppColors.textLightColor,
                      ),
                    ),
                  ],
                ),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    'Rp${transaction.amount.toStringAsFixed(0).replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (Match m) => '${m[1]}.')}',
                    style: GoogleFonts.poppins(
                      fontWeight: FontWeight.bold,
                      fontSize: 16,
                      color: AppColors.successColor,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    DateFormat('dd MMM yyyy, HH:mm', 'id_ID').format(transaction.createdAt ?? DateTime.now()),
                    style: GoogleFonts.poppins(
                      fontSize: 12,
                      color: AppColors.textLightColor,
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildShimmerList() {
    return ListView.separated(
      padding: const EdgeInsets.all(16),
      itemCount: 5, // Show 5 shimmer items
      separatorBuilder: (context, index) => const SizedBox(height: 12),
      itemBuilder: (context, index) {
        return Shimmer.fromColors(
          baseColor: Colors.grey[300]!,
          highlightColor: Colors.grey[100]!,
          child: Card(
            elevation: 2,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            margin: EdgeInsets.zero,
            child: Padding(
              padding: const EdgeInsets.all(16.0),
              child: Row(
                children: [
                  Container(
                    width: 40,
                    height: 40,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(8),
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Container(width: 150, height: 16, color: Colors.white),
                        const SizedBox(height: 4),
                        Container(width: 100, height: 12, color: Colors.white),
                      ],
                    ),
                  ),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Container(width: 80, height: 16, color: Colors.white),
                      const SizedBox(height: 4),
                      Container(width: 60, height: 12, color: Colors.white),
                    ],
                  ),
                ],
              ),
            ),
          ),
        );
      },
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            Icons.receipt_long,
            size: 100,
            color: AppColors.primaryColor.withAlpha(50),
          ),
          const SizedBox(height: 20),
          Text(
            'Belum Ada Transaksi',
            style: GoogleFonts.poppins(
              fontSize: 22,
              fontWeight: FontWeight.bold,
              color: AppColors.textColor,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Riwayat transaksi Anda akan muncul di sini. Lakukan transaksi pertama Anda sekarang!',
            textAlign: TextAlign.center,
            style: GoogleFonts.poppins(
              fontSize: 16,
              color: AppColors.textLightColor,
            ),
          ),
        ],
      ),
    );
  }
}
