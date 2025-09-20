import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';
import '../../core/utils/constants.dart';
import '../../data/models/transaction_model.dart';

class TransactionItem extends StatelessWidget {
  final Transaction transaction;
  final VoidCallback? onTap;

  const TransactionItem({
    super.key,
    required this.transaction,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    // Format tanggal dengan format Indonesia
    final dateFormat = DateFormat('dd MMM yyyy, HH:mm', 'id_ID');
    
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withAlpha(5),
              blurRadius: 10,
              offset: const Offset(0, 2),
            ),
          ],
          border: Border.all(
            color: Colors.grey.shade100,
            width: 1,
          ),
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            // Icon
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: _getStatusColor(transaction.transactionStatus).withAlpha(20),
                shape: BoxShape.circle,
              ),
              child: Center(
                child: Icon(
                  _getStatusIcon(transaction.transactionStatus),
                  color: _getStatusColor(transaction.transactionStatus),
                  size: 20,
                ),
              ),
            ),
            const SizedBox(width: 12),
            // Details
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    transaction.tollGateName,
                    style: GoogleFonts.poppins(
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                      color: AppColors.textColor,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    dateFormat.format(transaction.date),
                    style: GoogleFonts.poppins(
                      fontSize: 12,
                      color: AppColors.textLightColor,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    transaction.plateNumber,
                    style: GoogleFonts.poppins(
                      fontSize: 12,
                      color: AppColors.primaryColor,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ),
            ),
            // Amount
            Text(
              'Rp${transaction.amount.toStringAsFixed(0).replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (Match m) => '${m[1]}.')}',
              style: GoogleFonts.poppins(
                fontSize: 16,
                fontWeight: FontWeight.bold,
                color: _getStatusColor(transaction.transactionStatus),
              ),
            ),
          ],
        ),
      ),
    );
  }
  
  Color _getStatusColor(TransactionStatus status) {
    switch (status) {
      case TransactionStatus.success:
        return AppColors.successColor;
      case TransactionStatus.pending:
        return Colors.amber;
      case TransactionStatus.failed:
        return AppColors.errorColor;
    }
  }
  
  IconData _getStatusIcon(TransactionStatus status) {
    switch (status) {
      case TransactionStatus.success:
        return Icons.check_circle_outline;
      case TransactionStatus.pending:
        return Icons.timer_outlined;
      case TransactionStatus.failed:
        return Icons.cancel_outlined;
    }
  }
}
