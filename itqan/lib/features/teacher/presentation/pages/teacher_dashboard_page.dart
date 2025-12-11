import 'package:flutter/material.dart';
import '../../../../core/theme/colors.dart';
import '../../../../core/theme/spacing.dart';
import '../../../../core/theme/typography.dart';
import '../../../../core/widgets/cards/stat_card.dart';
import '../../../../core/widgets/cards/session_card.dart';
import '../../../../core/widgets/common/itqan_avatar.dart';
import '../../../../mock/mock_data.dart';

class TeacherDashboardPage extends StatelessWidget {
  const TeacherDashboardPage({super.key});

  @override
  Widget build(BuildContext context) {
    final user = MockDataProvider.currentTeacher;
    final upcomingSessions = MockDataProvider.sessions
        .where((s) => s.status == 'scheduled' || s.status == 'live')
        .take(3)
        .toList();

    return Scaffold(
      appBar: AppBar(
        title: Row(
          children: [
            ItqanAvatar(name: user.fullName, size: 36),
            const SizedBox(width: AppSpacing.md),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('مرحباً،', style: AppTypography.caption),
                Text(user.firstName, style: AppTypography.titleSmall),
              ],
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.notifications_outlined),
            onPressed: () {},
          ),
        ],
      ),
      body: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Stats Row
            Padding(
              padding: AppSpacing.paddingScreen,
              child: Row(
                children: [
                  Expanded(
                    child: CompactStatCard(
                      title: 'طلابي',
                      value: '24',
                      icon: Icons.people,
                      accentColor: AppColors.primary,
                    ),
                  ),
                  const SizedBox(width: AppSpacing.md),
                  Expanded(
                    child: CompactStatCard(
                      title: 'جلسات اليوم',
                      value: '5',
                      icon: Icons.videocam,
                      accentColor: AppColors.scheduled,
                    ),
                  ),
                ],
              ),
            ),
            Padding(
              padding: AppSpacing.paddingHorizontalBase,
              child: Row(
                children: [
                  Expanded(
                    child: CompactStatCard(
                      title: 'بانتظار التصحيح',
                      value: '8',
                      icon: Icons.assignment,
                      accentColor: AppColors.warning,
                    ),
                  ),
                  const SizedBox(width: AppSpacing.md),
                  Expanded(
                    child: CompactStatCard(
                      title: 'الأرباح الشهرية',
                      value: '3,250',
                      icon: Icons.account_balance_wallet,
                      accentColor: AppColors.ongoing,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: AppSpacing.xl),

            // Today's Schedule
            Padding(
              padding: AppSpacing.paddingHorizontalBase,
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text('جدول اليوم', style: AppTypography.titleSmall),
                  TextButton(
                    onPressed: () {},
                    child: const Text('عرض الكل'),
                  ),
                ],
              ),
            ),
            const SizedBox(height: AppSpacing.md),

            // Sessions List
            ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              padding: AppSpacing.paddingHorizontalBase,
              itemCount: upcomingSessions.length,
              separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.md),
              itemBuilder: (context, index) {
                final session = upcomingSessions[index];
                return _TeacherSessionCard(session: session);
              },
            ),
            const SizedBox(height: AppSpacing.xl),

            // Pending Homework
            Padding(
              padding: AppSpacing.paddingHorizontalBase,
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text('واجبات بانتظار التصحيح', style: AppTypography.titleSmall),
                  TextButton(
                    onPressed: () {},
                    child: const Text('عرض الكل'),
                  ),
                ],
              ),
            ),
            const SizedBox(height: AppSpacing.md),

            // Homework List
            ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              padding: AppSpacing.paddingHorizontalBase,
              itemCount: 3,
              separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.md),
              itemBuilder: (context, index) {
                return _PendingHomeworkCard(
                  studentName: ['أحمد محمد', 'سارة أحمد', 'عمر خالد'][index],
                  homeworkTitle: ['حفظ سورة الكهف', 'تمارين الرياضيات', 'مراجعة التجويد'][index],
                  submittedAt: DateTime.now().subtract(Duration(hours: index * 3 + 1)),
                );
              },
            ),
            const SizedBox(height: AppSpacing.xxl),
          ],
        ),
      ),
    );
  }
}

class _TeacherSessionCard extends StatelessWidget {
  final MockSession session;

  const _TeacherSessionCard({required this.session});

  @override
  Widget build(BuildContext context) {
    final isLive = session.status == 'live';

    return Container(
      padding: AppSpacing.paddingCard,
      decoration: BoxDecoration(
        color: isLive ? AppColors.ongoing.withValues(alpha: 0.1) : AppColors.surface,
        borderRadius: AppSpacing.borderRadiusMd,
        border: Border.all(
          color: isLive ? AppColors.ongoing : AppColors.border,
          width: isLive ? 2 : 1,
        ),
      ),
      child: Row(
        children: [
          // Time Column
          Container(
            width: 60,
            padding: const EdgeInsets.symmetric(vertical: AppSpacing.sm),
            decoration: BoxDecoration(
              color: isLive ? AppColors.ongoing : AppColors.primary100,
              borderRadius: AppSpacing.borderRadiusSm,
            ),
            child: Column(
              children: [
                Text(
                  '${session.scheduledAt.hour}:${session.scheduledAt.minute.toString().padLeft(2, '0')}',
                  style: AppTypography.titleSmall.copyWith(
                    color: isLive ? Colors.white : AppColors.primary,
                  ),
                ),
                Text(
                  '${session.durationMinutes} د',
                  style: AppTypography.caption.copyWith(
                    color: isLive ? Colors.white70 : AppColors.textSecondary,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: AppSpacing.md),

          // Session Info
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(session.title, style: AppTypography.labelMedium),
                const SizedBox(height: 4),
                Text(
                  'طالب: أحمد محمد', // Mock student name
                  style: AppTypography.caption.copyWith(color: AppColors.textSecondary),
                ),
              ],
            ),
          ),

          // Action Button
          if (isLive)
            ElevatedButton(
              onPressed: () {},
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.ongoing,
                padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md),
              ),
              child: const Text('انضمام'),
            )
          else
            OutlinedButton(
              onPressed: () {},
              child: const Text('التفاصيل'),
            ),
        ],
      ),
    );
  }
}

class _PendingHomeworkCard extends StatelessWidget {
  final String studentName;
  final String homeworkTitle;
  final DateTime submittedAt;

  const _PendingHomeworkCard({
    required this.studentName,
    required this.homeworkTitle,
    required this.submittedAt,
  });

  @override
  Widget build(BuildContext context) {
    final hoursAgo = DateTime.now().difference(submittedAt).inHours;

    return Container(
      padding: AppSpacing.paddingCard,
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: AppSpacing.borderRadiusMd,
        border: Border.all(color: AppColors.border),
      ),
      child: Row(
        children: [
          ItqanAvatar(name: studentName, size: 44),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(studentName, style: AppTypography.labelMedium),
                const SizedBox(height: 2),
                Text(homeworkTitle, style: AppTypography.caption.copyWith(color: AppColors.textSecondary)),
                const SizedBox(height: 4),
                Row(
                  children: [
                    Icon(Icons.access_time, size: 12, color: AppColors.textTertiary),
                    const SizedBox(width: 4),
                    Text(
                      'منذ $hoursAgo ساعة',
                      style: AppTypography.caption.copyWith(color: AppColors.textTertiary, fontSize: 11),
                    ),
                  ],
                ),
              ],
            ),
          ),
          ElevatedButton(
            onPressed: () {},
            style: ElevatedButton.styleFrom(
              padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md),
            ),
            child: const Text('تصحيح'),
          ),
        ],
      ),
    );
  }
}
