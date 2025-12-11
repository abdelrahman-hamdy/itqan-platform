import 'package:flutter/material.dart';
import '../../../../core/theme/colors.dart';
import '../../../../core/theme/spacing.dart';
import '../../../../core/theme/typography.dart';
import '../../../../core/widgets/badges/status_badge.dart';
import '../../../../core/widgets/common/itqan_avatar.dart';
import '../../../../mock/mock_data.dart';

class StudentSubscriptionsPage extends StatelessWidget {
  const StudentSubscriptionsPage({super.key});

  @override
  Widget build(BuildContext context) {
    final subscriptions = MockDataProvider.subscriptions;
    final active = subscriptions.where((s) => s.status == 'active').toList();
    final others = subscriptions.where((s) => s.status != 'active').toList();

    return Scaffold(
      appBar: AppBar(title: const Text('اشتراكاتي')),
      body: ListView(
        padding: AppSpacing.paddingScreen,
        children: [
          // Active Subscriptions
          if (active.isNotEmpty) ...[
            Text('الاشتراكات النشطة', style: AppTypography.titleSmall),
            const SizedBox(height: AppSpacing.md),
            ...active.map((sub) => Padding(
              padding: const EdgeInsets.only(bottom: AppSpacing.md),
              child: _SubscriptionCard(subscription: sub),
            )),
            const SizedBox(height: AppSpacing.lg),
          ],

          // Other Subscriptions
          if (others.isNotEmpty) ...[
            Text('الاشتراكات السابقة', style: AppTypography.titleSmall),
            const SizedBox(height: AppSpacing.md),
            ...others.map((sub) => Padding(
              padding: const EdgeInsets.only(bottom: AppSpacing.md),
              child: _SubscriptionCard(subscription: sub, isInactive: true),
            )),
          ],

          if (subscriptions.isEmpty)
            Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const SizedBox(height: AppSpacing.xxxl),
                  Icon(Icons.card_membership_outlined, size: 64, color: AppColors.textTertiary),
                  const SizedBox(height: AppSpacing.md),
                  Text(
                    'لا توجد اشتراكات',
                    style: AppTypography.bodyMedium.copyWith(color: AppColors.textSecondary),
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }
}

class _SubscriptionCard extends StatelessWidget {
  final MockSubscription subscription;
  final bool isInactive;

  const _SubscriptionCard({required this.subscription, this.isInactive = false});

  String get _typeLabel {
    switch (subscription.type) {
      case 'quran_individual':
        return 'قرآن فردي';
      case 'quran_group':
        return 'حلقة جماعية';
      case 'academic':
        return 'دروس أكاديمية';
      case 'course':
        return 'دورة تفاعلية';
      default:
        return subscription.type;
    }
  }

  Color get _typeColor {
    switch (subscription.type) {
      case 'quran_individual':
      case 'quran_group':
        return AppColors.quranColor;
      case 'academic':
        return AppColors.academicColor;
      case 'course':
        return AppColors.interactiveColor;
      default:
        return AppColors.primary;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: AppSpacing.paddingCard,
      decoration: BoxDecoration(
        color: isInactive ? AppColors.surfaceVariant : AppColors.surface,
        borderRadius: AppSpacing.borderRadiusMd,
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Row(
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: AppSpacing.sm, vertical: AppSpacing.xs),
                decoration: BoxDecoration(
                  color: _typeColor.withValues(alpha: 0.1),
                  borderRadius: AppSpacing.borderRadiusFull,
                ),
                child: Text(
                  _typeLabel,
                  style: AppTypography.labelSmall.copyWith(color: _typeColor),
                ),
              ),
              const Spacer(),
              StatusBadge(status: subscription.status, isSmall: true),
            ],
          ),
          const SizedBox(height: AppSpacing.md),

          // Title
          Text(subscription.title, style: AppTypography.titleSmall),
          const SizedBox(height: AppSpacing.md),

          // Teacher (if any)
          if (subscription.teacher != null) ...[
            Row(
              children: [
                ItqanAvatar(name: subscription.teacher!.fullName, size: 32),
                const SizedBox(width: AppSpacing.sm),
                Text(
                  subscription.teacher!.fullName,
                  style: AppTypography.bodySmall.copyWith(color: AppColors.textSecondary),
                ),
              ],
            ),
            const SizedBox(height: AppSpacing.md),
          ],

          // Progress
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text('الجلسات المستخدمة', style: AppTypography.caption),
                  Text(
                    '${subscription.usedSessions}/${subscription.totalSessions}',
                    style: AppTypography.labelSmall.copyWith(fontWeight: FontWeight.bold),
                  ),
                ],
              ),
              const SizedBox(height: AppSpacing.sm),
              ClipRRect(
                borderRadius: AppSpacing.borderRadiusFull,
                child: LinearProgressIndicator(
                  value: subscription.progress,
                  backgroundColor: AppColors.border,
                  valueColor: AlwaysStoppedAnimation<Color>(
                    subscription.remainingSessions <= 2 ? AppColors.warning : AppColors.primary,
                  ),
                  minHeight: 8,
                ),
              ),
            ],
          ),

          // Expiry & Auto-renewal
          if (subscription.expiresAt != null || subscription.autoRenewal) ...[
            const SizedBox(height: AppSpacing.md),
            const Divider(),
            const SizedBox(height: AppSpacing.sm),
            Row(
              children: [
                if (subscription.expiresAt != null) ...[
                  Icon(Icons.calendar_today, size: 14, color: AppColors.textTertiary),
                  const SizedBox(width: 4),
                  Text(
                    'ينتهي: ${subscription.expiresAt!.day}/${subscription.expiresAt!.month}',
                    style: AppTypography.caption,
                  ),
                ],
                const Spacer(),
                if (subscription.autoRenewal)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: AppSpacing.sm, vertical: 2),
                    decoration: BoxDecoration(
                      color: AppColors.ongoing.withValues(alpha: 0.1),
                      borderRadius: AppSpacing.borderRadiusFull,
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.autorenew, size: 12, color: AppColors.ongoing),
                        const SizedBox(width: 4),
                        Text(
                          'تجديد تلقائي',
                          style: AppTypography.labelSmall.copyWith(color: AppColors.ongoing),
                        ),
                      ],
                    ),
                  ),
              ],
            ),
          ],
        ],
      ),
    );
  }
}
