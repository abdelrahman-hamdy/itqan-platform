import 'package:flutter/material.dart';
import '../../theme/colors.dart';
import '../../theme/spacing.dart';
import '../../theme/typography.dart';

/// Session/Subscription status badge
class StatusBadge extends StatelessWidget {
  final String status;
  final bool showIcon;
  final bool isSmall;

  const StatusBadge({
    super.key,
    required this.status,
    this.showIcon = true,
    this.isSmall = false,
  });

  @override
  Widget build(BuildContext context) {
    final config = _getStatusConfig(status);

    return Container(
      padding: EdgeInsets.symmetric(
        horizontal: isSmall ? AppSpacing.sm : AppSpacing.md,
        vertical: isSmall ? AppSpacing.xxs : AppSpacing.xs,
      ),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            config.backgroundColor,
            config.backgroundColor.withOpacity(0.8),
          ],
        ),
        borderRadius: AppSpacing.borderRadiusSm,
        border: Border.all(color: config.borderColor, width: 1),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (showIcon) ...[
            Icon(
              config.icon,
              size: isSmall ? 12 : 14,
              color: config.textColor,
            ),
            SizedBox(width: isSmall ? 4 : 6),
          ],
          Text(
            config.label,
            style: (isSmall ? AppTypography.badge : AppTypography.labelSmall)
                .copyWith(color: config.textColor),
          ),
        ],
      ),
    );
  }

  _StatusConfig _getStatusConfig(String status) {
    switch (status.toLowerCase()) {
      case 'scheduled':
        return _StatusConfig(
          label: 'مجدول',
          icon: Icons.schedule,
          backgroundColor: AppColors.scheduledLight,
          borderColor: AppColors.scheduled.withOpacity(0.3),
          textColor: AppColors.scheduledDark,
        );
      case 'live':
      case 'ongoing':
        return _StatusConfig(
          label: 'مباشر',
          icon: Icons.fiber_manual_record,
          backgroundColor: AppColors.ongoingLight,
          borderColor: AppColors.ongoing.withOpacity(0.3),
          textColor: AppColors.ongoingDark,
        );
      case 'completed':
        return _StatusConfig(
          label: 'مكتمل',
          icon: Icons.check_circle_outline,
          backgroundColor: AppColors.completedLight,
          borderColor: AppColors.completed.withOpacity(0.3),
          textColor: AppColors.ongoingDark,
        );
      case 'cancelled':
        return _StatusConfig(
          label: 'ملغي',
          icon: Icons.cancel_outlined,
          backgroundColor: AppColors.cancelledLight,
          borderColor: AppColors.cancelled.withOpacity(0.3),
          textColor: AppColors.cancelledDark,
        );
      case 'pending':
        return _StatusConfig(
          label: 'قيد الانتظار',
          icon: Icons.hourglass_empty,
          backgroundColor: AppColors.warningLight,
          borderColor: AppColors.warning.withOpacity(0.3),
          textColor: AppColors.warningDark,
        );
      case 'absent':
        return _StatusConfig(
          label: 'غائب',
          icon: Icons.person_off_outlined,
          backgroundColor: AppColors.errorLight,
          borderColor: AppColors.error.withOpacity(0.3),
          textColor: AppColors.errorDark,
        );
      case 'active':
        return _StatusConfig(
          label: 'نشط',
          icon: Icons.check_circle,
          backgroundColor: AppColors.ongoingLight,
          borderColor: AppColors.ongoing.withOpacity(0.3),
          textColor: AppColors.ongoingDark,
        );
      case 'expired':
        return _StatusConfig(
          label: 'منتهي',
          icon: Icons.timer_off_outlined,
          backgroundColor: AppColors.cancelledLight,
          borderColor: AppColors.cancelled.withOpacity(0.3),
          textColor: AppColors.cancelledDark,
        );
      case 'paused':
        return _StatusConfig(
          label: 'متوقف',
          icon: Icons.pause_circle_outline,
          backgroundColor: AppColors.warningLight,
          borderColor: AppColors.warning.withOpacity(0.3),
          textColor: AppColors.warningDark,
        );
      default:
        return _StatusConfig(
          label: status,
          icon: Icons.info_outline,
          backgroundColor: AppColors.secondary100,
          borderColor: AppColors.secondary300,
          textColor: AppColors.textSecondary,
        );
    }
  }
}

class _StatusConfig {
  final String label;
  final IconData icon;
  final Color backgroundColor;
  final Color borderColor;
  final Color textColor;

  const _StatusConfig({
    required this.label,
    required this.icon,
    required this.backgroundColor,
    required this.borderColor,
    required this.textColor,
  });
}

/// Session type badge (Quran, Academic, Interactive)
class TypeBadge extends StatelessWidget {
  final String type;
  final bool isSmall;

  const TypeBadge({
    super.key,
    required this.type,
    this.isSmall = false,
  });

  @override
  Widget build(BuildContext context) {
    final config = _getTypeConfig(type);

    return Container(
      padding: EdgeInsets.symmetric(
        horizontal: isSmall ? AppSpacing.sm : AppSpacing.md,
        vertical: isSmall ? AppSpacing.xxs : AppSpacing.xs,
      ),
      decoration: BoxDecoration(
        color: config.backgroundColor,
        borderRadius: AppSpacing.borderRadiusFull,
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            config.icon,
            size: isSmall ? 12 : 14,
            color: config.textColor,
          ),
          SizedBox(width: isSmall ? 4 : 6),
          Text(
            config.label,
            style: (isSmall ? AppTypography.badge : AppTypography.labelSmall)
                .copyWith(color: config.textColor),
          ),
        ],
      ),
    );
  }

  _TypeConfig _getTypeConfig(String type) {
    switch (type.toLowerCase()) {
      case 'quran':
        return _TypeConfig(
          label: 'قرآن',
          icon: Icons.menu_book,
          backgroundColor: AppColors.quranLight,
          textColor: AppColors.accent700,
        );
      case 'academic':
        return _TypeConfig(
          label: 'أكاديمي',
          icon: Icons.school,
          backgroundColor: AppColors.academicLight,
          textColor: AppColors.scheduledDark,
        );
      case 'interactive':
        return _TypeConfig(
          label: 'تفاعلي',
          icon: Icons.groups,
          backgroundColor: AppColors.interactiveLight,
          textColor: AppColors.gradientVioletEnd,
        );
      default:
        return _TypeConfig(
          label: type,
          icon: Icons.bookmark,
          backgroundColor: AppColors.secondary100,
          textColor: AppColors.textSecondary,
        );
    }
  }
}

class _TypeConfig {
  final String label;
  final IconData icon;
  final Color backgroundColor;
  final Color textColor;

  const _TypeConfig({
    required this.label,
    required this.icon,
    required this.backgroundColor,
    required this.textColor,
  });
}

/// Rating badge with star
class RatingBadge extends StatelessWidget {
  final double rating;
  final int? reviewCount;
  final bool isSmall;

  const RatingBadge({
    super.key,
    required this.rating,
    this.reviewCount,
    this.isSmall = false,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.symmetric(
        horizontal: isSmall ? AppSpacing.sm : AppSpacing.md,
        vertical: isSmall ? AppSpacing.xxs : AppSpacing.xs,
      ),
      decoration: BoxDecoration(
        color: AppColors.warningLight,
        borderRadius: AppSpacing.borderRadiusFull,
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            Icons.star,
            size: isSmall ? 12 : 14,
            color: AppColors.warning,
          ),
          const SizedBox(width: 4),
          Text(
            rating.toStringAsFixed(1),
            style: (isSmall ? AppTypography.badge : AppTypography.labelSmall)
                .copyWith(
              color: AppColors.warningDark,
              fontWeight: FontWeight.w600,
            ),
          ),
          if (reviewCount != null) ...[
            Text(
              ' ($reviewCount)',
              style: (isSmall ? AppTypography.badge : AppTypography.labelSmall)
                  .copyWith(color: AppColors.textTertiary),
            ),
          ],
        ],
      ),
    );
  }
}
