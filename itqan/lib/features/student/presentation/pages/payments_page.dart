import 'package:flutter/material.dart';
import '../../../../core/theme/colors.dart';
import '../../../../core/theme/spacing.dart';
import '../../../../core/theme/typography.dart';
import '../../../../core/widgets/badges/status_badge.dart';
import '../../../../mock/mock_data.dart';

class StudentPaymentsPage extends StatelessWidget {
  const StudentPaymentsPage({super.key});

  @override
  Widget build(BuildContext context) {
    final payments = MockDataProvider.payments;
    final totalPaid = payments
        .where((p) => p.status == 'completed')
        .fold(0.0, (sum, p) => sum + p.amount);

    return Scaffold(
      appBar: AppBar(title: const Text('المدفوعات')),
      body: Column(
        children: [
          // Summary Card
          Container(
            margin: AppSpacing.paddingScreen,
            padding: AppSpacing.paddingCard,
            decoration: BoxDecoration(
              gradient: AppColors.primaryGradient,
              borderRadius: AppSpacing.borderRadiusMd,
            ),
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'إجمالي المدفوعات',
                        style: AppTypography.caption.copyWith(color: Colors.white70),
                      ),
                      const SizedBox(height: AppSpacing.xs),
                      Text(
                        '${totalPaid.toInt()} ر.س',
                        style: AppTypography.headlineMedium.copyWith(color: Colors.white),
                      ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.all(AppSpacing.md),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.2),
                    borderRadius: AppSpacing.borderRadiusMd,
                  ),
                  child: const Icon(Icons.account_balance_wallet, color: Colors.white, size: 32),
                ),
              ],
            ),
          ),

          // Payments List
          Expanded(
            child: payments.isEmpty
                ? Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.receipt_long_outlined, size: 64, color: AppColors.textTertiary),
                        const SizedBox(height: AppSpacing.md),
                        Text(
                          'لا توجد مدفوعات',
                          style: AppTypography.bodyMedium.copyWith(color: AppColors.textSecondary),
                        ),
                      ],
                    ),
                  )
                : ListView.separated(
                    padding: AppSpacing.paddingScreen,
                    itemCount: payments.length,
                    separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.md),
                    itemBuilder: (context, index) {
                      final payment = payments[index];
                      return _PaymentCard(payment: payment);
                    },
                  ),
          ),
        ],
      ),
    );
  }
}

class _PaymentCard extends StatelessWidget {
  final MockPayment payment;

  const _PaymentCard({required this.payment});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: AppSpacing.paddingCard,
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: AppSpacing.borderRadiusMd,
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  color: _getStatusColor(payment.status).withValues(alpha: 0.1),
                  borderRadius: AppSpacing.borderRadiusSm,
                ),
                child: Icon(
                  _getStatusIcon(payment.status),
                  color: _getStatusColor(payment.status),
                ),
              ),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      payment.subscriptionTitle ?? 'دفعة',
                      style: AppTypography.titleSmall,
                    ),
                    const SizedBox(height: 2),
                    Text(
                      payment.code,
                      style: AppTypography.caption.copyWith(color: AppColors.textTertiary),
                    ),
                  ],
                ),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    '${payment.amount.toInt()} ر.س',
                    style: AppTypography.titleSmall.copyWith(
                      color: payment.status == 'completed' ? AppColors.ongoing : AppColors.textPrimary,
                    ),
                  ),
                  const SizedBox(height: 2),
                  StatusBadge(status: payment.status, isSmall: true),
                ],
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.md),
          const Divider(height: 1),
          const SizedBox(height: AppSpacing.md),
          Row(
            children: [
              Icon(Icons.calendar_today, size: 14, color: AppColors.textTertiary),
              const SizedBox(width: 4),
              Text(
                '${payment.date.day}/${payment.date.month}/${payment.date.year}',
                style: AppTypography.caption,
              ),
              const Spacer(),
              if (payment.status == 'completed')
                TextButton.icon(
                  onPressed: () {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('سيتم تحميل الفاتورة قريباً')),
                    );
                  },
                  icon: const Icon(Icons.download, size: 16),
                  label: const Text('تحميل الفاتورة'),
                  style: TextButton.styleFrom(
                    padding: EdgeInsets.zero,
                    minimumSize: Size.zero,
                    tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                  ),
                ),
            ],
          ),
        ],
      ),
    );
  }

  Color _getStatusColor(String status) {
    switch (status) {
      case 'completed':
        return AppColors.ongoing;
      case 'pending':
        return AppColors.warning;
      case 'failed':
        return AppColors.error;
      default:
        return AppColors.textTertiary;
    }
  }

  IconData _getStatusIcon(String status) {
    switch (status) {
      case 'completed':
        return Icons.check_circle;
      case 'pending':
        return Icons.hourglass_empty;
      case 'failed':
        return Icons.error;
      default:
        return Icons.receipt;
    }
  }
}
