import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../../../core/theme/colors.dart';
import '../../../../core/theme/spacing.dart';
import '../../../../core/theme/typography.dart';
import '../../../../core/widgets/cards/stat_card.dart';
import '../../../../core/widgets/cards/session_card.dart';
import '../../../../core/widgets/common/itqan_avatar.dart';
import '../../../../mock/mock_data.dart';

/// Student dashboard page
class StudentDashboardPage extends StatelessWidget {
  const StudentDashboardPage({super.key});

  @override
  Widget build(BuildContext context) {
    final user = MockDataProvider.currentStudent;
    final stats = MockDataProvider.studentStats;
    final upcomingSessions = MockDataProvider.sessions
        .where((s) => s.status == 'scheduled' || s.status == 'live')
        .toList();
    final subscriptions = MockDataProvider.subscriptions;

    return Scaffold(
      body: SafeArea(
        child: CustomScrollView(
          slivers: [
            // App Bar
            SliverAppBar(
              floating: true,
              backgroundColor: AppColors.surface,
              surfaceTintColor: Colors.transparent,
              title: Row(
                children: [
                  ItqanAvatar(
                    name: user.fullName,
                    imageUrl: user.avatarUrl,
                    size: 40,
                  ),
                  const SizedBox(width: AppSpacing.md),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'مرحباً 👋',
                          style: AppTypography.caption,
                        ),
                        Text(
                          user.firstName,
                          style: AppTypography.titleMedium,
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              actions: [
                IconButton(
                  icon: const Badge(
                    smallSize: 8,
                    child: Icon(Icons.notifications_outlined),
                  ),
                  onPressed: () {
                    // TODO: Navigate to notifications
                  },
                ),
              ],
            ),

            // Content
            SliverPadding(
              padding: AppSpacing.paddingScreen,
              sliver: SliverList(
                delegate: SliverChildListDelegate([
                  const SizedBox(height: AppSpacing.md),

                  // Quick Stats
                  _buildQuickStats(stats),
                  const SizedBox(height: AppSpacing.xl),

                  // Upcoming Sessions
                  _buildSectionHeader(
                    context,
                    'الجلسات القادمة',
                    onSeeAll: () => context.go('/student/sessions'),
                  ),
                  const SizedBox(height: AppSpacing.md),
                  _buildUpcomingSessions(context, upcomingSessions),
                  const SizedBox(height: AppSpacing.xl),

                  // Active Subscriptions
                  _buildSectionHeader(
                    context,
                    'الاشتراكات النشطة',
                    onSeeAll: () => context.go('/student/more/subscriptions'),
                  ),
                  const SizedBox(height: AppSpacing.md),
                  _buildActiveSubscriptions(subscriptions),
                  const SizedBox(height: AppSpacing.xl),

                  // Quick Actions
                  _buildSectionHeader(context, 'إجراءات سريعة'),
                  const SizedBox(height: AppSpacing.md),
                  _buildQuickActions(context),
                  const SizedBox(height: AppSpacing.xxl),
                ]),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildQuickStats(Map<String, dynamic> stats) {
    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      mainAxisSpacing: AppSpacing.md,
      crossAxisSpacing: AppSpacing.md,
      childAspectRatio: 1.4,
      children: [
        StatCard(
          title: 'الجلسة القادمة',
          value: 'اليوم 4:00 م',
          icon: Icons.videocam,
          iconColor: AppColors.primary,
        ),
        StatCard(
          title: 'واجبات معلقة',
          value: '${stats['pendingHomework']}',
          icon: Icons.assignment,
          iconColor: AppColors.warning,
        ),
        StatCard(
          title: 'اختبارات معلقة',
          value: '${stats['pendingQuizzes']}',
          icon: Icons.quiz,
          iconColor: AppColors.gradientVioletStart,
        ),
        StatCard(
          title: 'نسبة الحضور',
          value: '${stats['attendanceRate']}%',
          icon: Icons.check_circle,
          iconColor: AppColors.accent,
        ),
      ],
    );
  }

  Widget _buildSectionHeader(
    BuildContext context,
    String title, {
    VoidCallback? onSeeAll,
  }) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          title,
          style: AppTypography.textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w600,
          ),
        ),
        if (onSeeAll != null)
          TextButton(
            onPressed: onSeeAll,
            child: Text(
              'عرض الكل',
              style: AppTypography.caption.copyWith(
                color: AppColors.primary,
              ),
            ),
          ),
      ],
    );
  }

  Widget _buildUpcomingSessions(
    BuildContext context,
    List<MockSession> sessions,
  ) {
    if (sessions.isEmpty) {
      return Container(
        padding: AppSpacing.paddingCard,
        decoration: BoxDecoration(
          color: AppColors.surfaceVariant,
          borderRadius: AppSpacing.borderRadiusMd,
        ),
        child: Column(
          children: [
            const Icon(
              Icons.event_busy,
              size: 48,
              color: AppColors.textTertiary,
            ),
            const SizedBox(height: AppSpacing.md),
            Text(
              'لا توجد جلسات قادمة',
              style: AppTypography.bodyMedium,
            ),
          ],
        ),
      );
    }

    return SizedBox(
      height: 160,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: sessions.length,
        separatorBuilder: (_, __) => const SizedBox(width: AppSpacing.md),
        itemBuilder: (context, index) {
          return CompactSessionCard(
            session: sessions[index],
            onTap: () {
              context.push('/student/sessions/${sessions[index].id}');
            },
          );
        },
      ),
    );
  }

  Widget _buildActiveSubscriptions(List<MockSubscription> subscriptions) {
    final activeSubscriptions =
        subscriptions.where((s) => s.status == 'active').toList();

    return Column(
      children: activeSubscriptions.map((subscription) {
        return Container(
          margin: const EdgeInsets.only(bottom: AppSpacing.md),
          padding: AppSpacing.paddingCard,
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: AppSpacing.borderRadiusMd,
            border: Border.all(color: AppColors.border),
          ),
          child: Row(
            children: [
              // Type icon
              Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  color: _getSubscriptionColor(subscription.type)
                      .withValues(alpha: 0.1),
                  borderRadius: AppSpacing.borderRadiusSm,
                ),
                child: Icon(
                  _getSubscriptionIcon(subscription.type),
                  color: _getSubscriptionColor(subscription.type),
                  size: 24,
                ),
              ),
              const SizedBox(width: AppSpacing.md),
              // Details
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      subscription.title,
                      style: AppTypography.titleSmall,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 4),
                    Text(
                      '${subscription.usedSessions} من ${subscription.totalSessions} جلسة',
                      style: AppTypography.caption,
                    ),
                  ],
                ),
              ),
              // Progress
              SizedBox(
                width: 48,
                height: 48,
                child: Stack(
                  alignment: Alignment.center,
                  children: [
                    CircularProgressIndicator(
                      value: subscription.progress,
                      backgroundColor: AppColors.secondary100,
                      color: _getSubscriptionColor(subscription.type),
                      strokeWidth: 4,
                    ),
                    Text(
                      '${(subscription.progress * 100).round()}%',
                      style: AppTypography.badge,
                    ),
                  ],
                ),
              ),
            ],
          ),
        );
      }).toList(),
    );
  }

  Widget _buildQuickActions(BuildContext context) {
    final actions = [
      _QuickAction(
        title: 'حلقات القرآن',
        icon: Icons.menu_book,
        color: AppColors.accent,
        onTap: () => context.go('/student/more/circles'),
      ),
      _QuickAction(
        title: 'الاختبارات',
        icon: Icons.quiz,
        color: AppColors.gradientVioletStart,
        onTap: () => context.go('/student/more/quizzes'),
      ),
      _QuickAction(
        title: 'التقويم',
        icon: Icons.calendar_month,
        color: AppColors.scheduled,
        onTap: () => context.go('/student/more/calendar'),
      ),
      _QuickAction(
        title: 'الشهادات',
        icon: Icons.workspace_premium,
        color: AppColors.warning,
        onTap: () => context.go('/student/more/certificates'),
      ),
    ];

    return GridView.count(
      crossAxisCount: 4,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      mainAxisSpacing: AppSpacing.md,
      crossAxisSpacing: AppSpacing.md,
      children: actions.map((action) {
        return GestureDetector(
          onTap: action.onTap,
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                width: 56,
                height: 56,
                decoration: BoxDecoration(
                  color: action.color.withValues(alpha: 0.1),
                  borderRadius: AppSpacing.borderRadiusMd,
                ),
                child: Icon(
                  action.icon,
                  color: action.color,
                  size: 28,
                ),
              ),
              const SizedBox(height: AppSpacing.sm),
              Text(
                action.title,
                style: AppTypography.caption,
                textAlign: TextAlign.center,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ],
          ),
        );
      }).toList(),
    );
  }

  Color _getSubscriptionColor(String type) {
    switch (type) {
      case 'quran_individual':
      case 'quran_group':
        return AppColors.accent;
      case 'academic':
        return AppColors.scheduled;
      case 'course':
        return AppColors.gradientVioletStart;
      default:
        return AppColors.primary;
    }
  }

  IconData _getSubscriptionIcon(String type) {
    switch (type) {
      case 'quran_individual':
      case 'quran_group':
        return Icons.menu_book;
      case 'academic':
        return Icons.school;
      case 'course':
        return Icons.play_circle;
      default:
        return Icons.bookmark;
    }
  }
}

class _QuickAction {
  final String title;
  final IconData icon;
  final Color color;
  final VoidCallback onTap;

  const _QuickAction({
    required this.title,
    required this.icon,
    required this.color,
    required this.onTap,
  });
}
