import 'package:flutter/material.dart';
import '../../../../core/theme/colors.dart';
import '../../../../core/theme/spacing.dart';
import '../../../../core/theme/typography.dart';
import '../../../../core/widgets/cards/stat_card.dart';
import '../../../../core/widgets/common/itqan_avatar.dart';
import '../../../../mock/mock_data.dart';

class ParentDashboardPage extends StatelessWidget {
  const ParentDashboardPage({super.key});

  @override
  Widget build(BuildContext context) {
    final user = MockDataProvider.currentParent;

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
            // Children Section
            Padding(
              padding: AppSpacing.paddingScreen,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('أبنائي', style: AppTypography.titleSmall),
                  const SizedBox(height: AppSpacing.md),
                  _ChildCard(
                    name: 'أحمد عبدالله',
                    nextSession: 'جلسة تحفيظ - خلال ساعتين',
                    attendanceRate: 92,
                    pendingHomework: 2,
                  ),
                  const SizedBox(height: AppSpacing.md),
                  _ChildCard(
                    name: 'سارة عبدالله',
                    nextSession: 'درس رياضيات - غداً',
                    attendanceRate: 88,
                    pendingHomework: 1,
                  ),
                ],
              ),
            ),
            const SizedBox(height: AppSpacing.lg),

            // Quick Stats
            Padding(
              padding: AppSpacing.paddingHorizontalBase,
              child: Text('ملخص الأسبوع', style: AppTypography.titleSmall),
            ),
            const SizedBox(height: AppSpacing.md),
            Padding(
              padding: AppSpacing.paddingHorizontalBase,
              child: Row(
                children: [
                  Expanded(
                    child: CompactStatCard(
                      title: 'جلسات مكتملة',
                      value: '8',
                      icon: Icons.check_circle,
                      accentColor: AppColors.ongoing,
                    ),
                  ),
                  const SizedBox(width: AppSpacing.md),
                  Expanded(
                    child: CompactStatCard(
                      title: 'جلسات قادمة',
                      value: '5',
                      icon: Icons.calendar_today,
                      accentColor: AppColors.scheduled,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: AppSpacing.md),
            Padding(
              padding: AppSpacing.paddingHorizontalBase,
              child: Row(
                children: [
                  Expanded(
                    child: CompactStatCard(
                      title: 'واجبات معلقة',
                      value: '3',
                      icon: Icons.assignment_late,
                      accentColor: AppColors.warning,
                    ),
                  ),
                  const SizedBox(width: AppSpacing.md),
                  Expanded(
                    child: CompactStatCard(
                      title: 'المدفوعات المعلقة',
                      value: '1',
                      icon: Icons.payment,
                      accentColor: AppColors.error,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: AppSpacing.xl),

            // Upcoming Sessions
            Padding(
              padding: AppSpacing.paddingHorizontalBase,
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text('الجلسات القادمة', style: AppTypography.titleSmall),
                  TextButton(
                    onPressed: () {},
                    child: const Text('عرض الكل'),
                  ),
                ],
              ),
            ),
            const SizedBox(height: AppSpacing.md),

            // Sessions
            ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              padding: AppSpacing.paddingHorizontalBase,
              itemCount: 3,
              separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.md),
              itemBuilder: (context, index) {
                return _UpcomingSessionCard(
                  childName: index == 0 ? 'أحمد' : 'سارة',
                  sessionTitle: ['جلسة تحفيظ القرآن', 'درس الرياضيات', 'حلقة التجويد'][index],
                  teacherName: ['الشيخ محمد', 'أ. عمر', 'الشيخة فاطمة'][index],
                  time: DateTime.now().add(Duration(hours: index * 2 + 1)),
                  type: ['quran', 'academic', 'quran'][index],
                );
              },
            ),
            const SizedBox(height: AppSpacing.xl),

            // Recent Activity
            Padding(
              padding: AppSpacing.paddingHorizontalBase,
              child: Text('النشاط الأخير', style: AppTypography.titleSmall),
            ),
            const SizedBox(height: AppSpacing.md),

            // Activity Timeline
            Padding(
              padding: AppSpacing.paddingHorizontalBase,
              child: Column(
                children: [
                  _ActivityItem(
                    icon: Icons.check_circle,
                    iconColor: AppColors.ongoing,
                    title: 'أكمل أحمد جلسة التحفيظ',
                    subtitle: 'منذ ساعتين',
                  ),
                  _ActivityItem(
                    icon: Icons.assignment_turned_in,
                    iconColor: AppColors.primary,
                    title: 'سلمت سارة واجب الرياضيات',
                    subtitle: 'منذ 3 ساعات',
                  ),
                  _ActivityItem(
                    icon: Icons.grade,
                    iconColor: AppColors.warning,
                    title: 'حصل أحمد على 85% في اختبار التجويد',
                    subtitle: 'أمس',
                  ),
                  _ActivityItem(
                    icon: Icons.payment,
                    iconColor: AppColors.ongoing,
                    title: 'تم دفع اشتراك سارة',
                    subtitle: 'منذ يومين',
                    isLast: true,
                  ),
                ],
              ),
            ),
            const SizedBox(height: AppSpacing.xxl),
          ],
        ),
      ),
    );
  }
}

class _ChildCard extends StatelessWidget {
  final String name;
  final String nextSession;
  final int attendanceRate;
  final int pendingHomework;

  const _ChildCard({
    required this.name,
    required this.nextSession,
    required this.attendanceRate,
    required this.pendingHomework,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: AppSpacing.paddingCard,
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: AppSpacing.borderRadiusMd,
        border: Border.all(color: AppColors.border),
        boxShadow: AppShadows.sm,
      ),
      child: Column(
        children: [
          Row(
            children: [
              ItqanAvatar(name: name, size: 48),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(name, style: AppTypography.titleSmall),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        Icon(Icons.schedule, size: 14, color: AppColors.textTertiary),
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(
                            nextSession,
                            style: AppTypography.caption.copyWith(color: AppColors.textSecondary),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              IconButton(
                icon: const Icon(Icons.arrow_forward_ios, size: 16),
                onPressed: () {},
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.md),
          const Divider(height: 1),
          const SizedBox(height: AppSpacing.md),
          Row(
            children: [
              Expanded(
                child: Row(
                  children: [
                    Icon(Icons.pie_chart, size: 16, color: AppColors.ongoing),
                    const SizedBox(width: 4),
                    Text('الحضور: $attendanceRate%', style: AppTypography.caption),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: AppSpacing.sm, vertical: 2),
                decoration: BoxDecoration(
                  color: pendingHomework > 0 ? AppColors.warning.withValues(alpha: 0.1) : AppColors.ongoing.withValues(alpha: 0.1),
                  borderRadius: AppSpacing.borderRadiusFull,
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(
                      Icons.assignment,
                      size: 14,
                      color: pendingHomework > 0 ? AppColors.warning : AppColors.ongoing,
                    ),
                    const SizedBox(width: 4),
                    Text(
                      '$pendingHomework واجبات',
                      style: AppTypography.labelSmall.copyWith(
                        color: pendingHomework > 0 ? AppColors.warning : AppColors.ongoing,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _UpcomingSessionCard extends StatelessWidget {
  final String childName;
  final String sessionTitle;
  final String teacherName;
  final DateTime time;
  final String type;

  const _UpcomingSessionCard({
    required this.childName,
    required this.sessionTitle,
    required this.teacherName,
    required this.time,
    required this.type,
  });

  @override
  Widget build(BuildContext context) {
    final typeColor = type == 'quran' ? AppColors.quranColor : AppColors.academicColor;

    return Container(
      padding: AppSpacing.paddingCard,
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: AppSpacing.borderRadiusMd,
        border: Border.all(color: AppColors.border),
      ),
      child: Row(
        children: [
          Container(
            width: 4,
            height: 60,
            decoration: BoxDecoration(
              color: typeColor,
              borderRadius: AppSpacing.borderRadiusFull,
            ),
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: AppSpacing.sm, vertical: 2),
                      decoration: BoxDecoration(
                        color: AppColors.primary100,
                        borderRadius: AppSpacing.borderRadiusFull,
                      ),
                      child: Text(childName, style: AppTypography.labelSmall.copyWith(color: AppColors.primary)),
                    ),
                    const SizedBox(width: AppSpacing.sm),
                    Expanded(
                      child: Text(sessionTitle, style: AppTypography.labelMedium, overflow: TextOverflow.ellipsis),
                    ),
                  ],
                ),
                const SizedBox(height: AppSpacing.sm),
                Row(
                  children: [
                    Icon(Icons.person, size: 14, color: AppColors.textTertiary),
                    const SizedBox(width: 4),
                    Text(teacherName, style: AppTypography.caption),
                    const Spacer(),
                    Icon(Icons.access_time, size: 14, color: AppColors.textTertiary),
                    const SizedBox(width: 4),
                    Text(
                      '${time.hour}:${time.minute.toString().padLeft(2, '0')}',
                      style: AppTypography.caption,
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _ActivityItem extends StatelessWidget {
  final IconData icon;
  final Color iconColor;
  final String title;
  final String subtitle;
  final bool isLast;

  const _ActivityItem({
    required this.icon,
    required this.iconColor,
    required this.title,
    required this.subtitle,
    this.isLast = false,
  });

  @override
  Widget build(BuildContext context) {
    return IntrinsicHeight(
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Column(
            children: [
              Container(
                width: 32,
                height: 32,
                decoration: BoxDecoration(
                  color: iconColor.withValues(alpha: 0.1),
                  shape: BoxShape.circle,
                ),
                child: Icon(icon, size: 16, color: iconColor),
              ),
              if (!isLast)
                Expanded(
                  child: Container(
                    width: 2,
                    color: AppColors.border,
                    margin: const EdgeInsets.symmetric(vertical: 4),
                  ),
                ),
            ],
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Padding(
              padding: EdgeInsets.only(bottom: isLast ? 0 : AppSpacing.md),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: AppTypography.bodySmall),
                  const SizedBox(height: 2),
                  Text(subtitle, style: AppTypography.caption.copyWith(color: AppColors.textTertiary)),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
