import 'package:flutter/material.dart';
import '../../theme/colors.dart';
import '../../theme/spacing.dart';
import '../../theme/typography.dart';
import '../badges/status_badge.dart';
import '../common/itqan_avatar.dart';
import '../../../mock/mock_data.dart';

/// Session card for session lists
class SessionCard extends StatelessWidget {
  final MockSession session;
  final VoidCallback? onTap;

  const SessionCard({
    super.key,
    required this.session,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: AppSpacing.paddingCard,
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: AppSpacing.borderRadiusMd,
          border: Border.all(color: AppColors.border),
          boxShadow: AppSpacing.shadowSm,
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header: Type badge + Status
            Row(
              children: [
                TypeBadge(type: session.type, isSmall: true),
                const Spacer(),
                StatusBadge(status: session.status, isSmall: true),
              ],
            ),
            const SizedBox(height: AppSpacing.md),

            // Title
            Text(
              session.title,
              style: AppTypography.textTheme.titleMedium,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
            const SizedBox(height: AppSpacing.md),

            // Teacher info
            Row(
              children: [
                ItqanAvatar(
                  name: session.teacher.fullName,
                  imageUrl: session.teacher.avatarUrl,
                  size: 36,
                ),
                const SizedBox(width: AppSpacing.sm),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        session.teacher.fullName,
                        style: AppTypography.labelLarge,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      Text(
                        _getTeacherTypeLabel(session.teacher.type),
                        style: AppTypography.caption,
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: AppSpacing.md),

            // Date and time
            Container(
              padding: const EdgeInsets.all(AppSpacing.sm),
              decoration: BoxDecoration(
                color: AppColors.surfaceVariant,
                borderRadius: AppSpacing.borderRadiusSm,
              ),
              child: Row(
                children: [
                  Icon(
                    Icons.calendar_today,
                    size: 16,
                    color: AppColors.textSecondary,
                  ),
                  const SizedBox(width: AppSpacing.sm),
                  Text(
                    _formatDate(session.scheduledAt),
                    style: AppTypography.bodySmall,
                  ),
                  const Spacer(),
                  Icon(
                    Icons.access_time,
                    size: 16,
                    color: AppColors.textSecondary,
                  ),
                  const SizedBox(width: AppSpacing.xs),
                  Text(
                    _formatTime(session.scheduledAt),
                    style: AppTypography.bodySmall,
                  ),
                  const SizedBox(width: AppSpacing.md),
                  Icon(
                    Icons.timer_outlined,
                    size: 16,
                    color: AppColors.textSecondary,
                  ),
                  const SizedBox(width: AppSpacing.xs),
                  Text(
                    '${session.durationMinutes} د',
                    style: AppTypography.bodySmall,
                  ),
                ],
              ),
            ),

            // Join button for live sessions
            if (session.status == 'live') ...[
              const SizedBox(height: AppSpacing.md),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: () {},
                  icon: const Icon(Icons.videocam, size: 18),
                  label: const Text('انضم للجلسة'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.ongoing,
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  String _getTeacherTypeLabel(String type) {
    switch (type) {
      case 'quran':
        return 'معلم قرآن';
      case 'academic':
        return 'معلم أكاديمي';
      default:
        return 'معلم';
    }
  }

  String _formatDate(DateTime date) {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final tomorrow = today.add(const Duration(days: 1));
    final sessionDate = DateTime(date.year, date.month, date.day);

    if (sessionDate == today) {
      return 'اليوم';
    } else if (sessionDate == tomorrow) {
      return 'غداً';
    } else {
      return '${date.day}/${date.month}/${date.year}';
    }
  }

  String _formatTime(DateTime date) {
    final hour = date.hour > 12 ? date.hour - 12 : date.hour;
    final period = date.hour >= 12 ? 'م' : 'ص';
    return '$hour:${date.minute.toString().padLeft(2, '0')} $period';
  }
}

/// Compact session card for horizontal lists
class CompactSessionCard extends StatelessWidget {
  final MockSession session;
  final VoidCallback? onTap;

  const CompactSessionCard({
    super.key,
    required this.session,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 280,
        padding: AppSpacing.paddingCardSmall,
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: AppSpacing.borderRadiusMd,
          border: Border.all(color: AppColors.border),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            Row(
              children: [
                TypeBadge(type: session.type, isSmall: true),
                const Spacer(),
                StatusBadge(status: session.status, isSmall: true, showIcon: false),
              ],
            ),
            const SizedBox(height: AppSpacing.sm),
            Text(
              session.title,
              style: AppTypography.titleSmall,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
            const SizedBox(height: AppSpacing.xs),
            Row(
              children: [
                Icon(Icons.person, size: 14, color: AppColors.textTertiary),
                const SizedBox(width: 4),
                Expanded(
                  child: Text(
                    session.teacher.fullName,
                    style: AppTypography.caption,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
            const SizedBox(height: AppSpacing.sm),
            Row(
              children: [
                Icon(Icons.access_time, size: 14, color: AppColors.textTertiary),
                const SizedBox(width: 4),
                Text(
                  _formatDateTime(session.scheduledAt),
                  style: AppTypography.caption,
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  String _formatDateTime(DateTime date) {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final sessionDate = DateTime(date.year, date.month, date.day);

    String dateStr;
    if (sessionDate == today) {
      dateStr = 'اليوم';
    } else if (sessionDate == today.add(const Duration(days: 1))) {
      dateStr = 'غداً';
    } else {
      dateStr = '${date.day}/${date.month}';
    }

    final hour = date.hour > 12 ? date.hour - 12 : date.hour;
    final period = date.hour >= 12 ? 'م' : 'ص';
    return '$dateStr - $hour:${date.minute.toString().padLeft(2, '0')} $period';
  }
}
