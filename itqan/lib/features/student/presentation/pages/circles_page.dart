import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../../../core/theme/colors.dart';
import '../../../../core/theme/spacing.dart';
import '../../../../core/theme/typography.dart';
import '../../../../core/widgets/badges/status_badge.dart';
import '../../../../core/widgets/progress/linear_progress_bar.dart';
import '../../../../core/widgets/common/itqan_avatar.dart';
import '../../../../mock/mock_data.dart';

/// Quran Circles page
class StudentCirclesPage extends StatelessWidget {
  const StudentCirclesPage({super.key});

  @override
  Widget build(BuildContext context) {
    final circles = MockDataProvider.circles;

    return DefaultTabController(
      length: 2,
      child: Scaffold(
        appBar: AppBar(
          leading: IconButton(
            icon: const Icon(Icons.arrow_back_ios),
            onPressed: () => context.pop(),
          ),
          title: const Text('حلقات القرآن'),
          bottom: const TabBar(
            tabs: [
              Tab(text: 'الحلقات الجماعية'),
              Tab(text: 'الحلقات الفردية'),
            ],
          ),
        ),
        body: TabBarView(
          children: [
            _buildCirclesList(context, circles.where((c) => c.type == 'group').toList()),
            _buildCirclesList(context, circles.where((c) => c.type == 'individual').toList()),
          ],
        ),
      ),
    );
  }

  Widget _buildCirclesList(BuildContext context, List<MockCircle> circles) {
    if (circles.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.menu_book, size: 64, color: AppColors.textTertiary),
            const SizedBox(height: AppSpacing.base),
            Text(
              'لا توجد حلقات',
              style: AppTypography.titleMedium.copyWith(color: AppColors.textSecondary),
            ),
          ],
        ),
      );
    }

    return ListView.separated(
      padding: AppSpacing.paddingScreen,
      itemCount: circles.length,
      separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.md),
      itemBuilder: (context, index) {
        final circle = circles[index];
        return _CircleCard(
          circle: circle,
          onTap: () => context.push('/student/more/circles/${circle.id}'),
        );
      },
    );
  }
}

class _CircleCard extends StatelessWidget {
  final MockCircle circle;
  final VoidCallback onTap;

  const _CircleCard({required this.circle, required this.onTap});

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
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(AppSpacing.sm),
                  decoration: BoxDecoration(
                    color: AppColors.quranLight,
                    borderRadius: AppSpacing.borderRadiusSm,
                  ),
                  child: Icon(Icons.menu_book, color: AppColors.accent, size: 24),
                ),
                const SizedBox(width: AppSpacing.md),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(circle.name, style: AppTypography.titleSmall),
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          ItqanAvatar(name: circle.teacher.fullName, size: 20),
                          const SizedBox(width: 6),
                          Text(circle.teacher.fullName, style: AppTypography.caption),
                        ],
                      ),
                    ],
                  ),
                ),
                StatusBadge(status: circle.status, isSmall: true),
              ],
            ),
            const SizedBox(height: AppSpacing.md),
            LinearProgressBar(progress: circle.progress, labelPrefix: 'التقدم'),
            const SizedBox(height: AppSpacing.md),
            Row(
              children: [
                Icon(Icons.group, size: 16, color: AppColors.textTertiary),
                const SizedBox(width: 4),
                Text('${circle.studentCount} طالب', style: AppTypography.caption),
                const SizedBox(width: AppSpacing.base),
                Icon(Icons.schedule, size: 16, color: AppColors.textTertiary),
                const SizedBox(width: 4),
                Expanded(
                  child: Text(circle.schedule, style: AppTypography.caption, overflow: TextOverflow.ellipsis),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
