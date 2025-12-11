import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../../../core/theme/colors.dart';
import '../../../../core/theme/spacing.dart';
import '../../../../core/theme/typography.dart';
import '../../../../core/widgets/badges/status_badge.dart';
import '../../../../core/widgets/progress/linear_progress_bar.dart';
import '../../../../core/widgets/common/itqan_avatar.dart';
import '../../../../mock/mock_data.dart';

class StudentCoursesPage extends StatelessWidget {
  const StudentCoursesPage({super.key});

  @override
  Widget build(BuildContext context) {
    final courses = MockDataProvider.courses;

    return Scaffold(
      appBar: AppBar(title: const Text('الدورات')),
      body: courses.isEmpty
          ? Center(child: Text('لا توجد دورات', style: AppTypography.bodyMedium))
          : ListView.separated(
              padding: AppSpacing.paddingScreen,
              itemCount: courses.length,
              separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.md),
              itemBuilder: (context, index) {
                final course = courses[index];
                return _CourseCard(
                  course: course,
                  onTap: () => context.push('/student/courses/${course.id}'),
                );
              },
            ),
    );
  }
}

class _CourseCard extends StatelessWidget {
  final MockCourse course;
  final VoidCallback onTap;

  const _CourseCard({required this.course, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: AppSpacing.borderRadiusMd,
          border: Border.all(color: AppColors.border),
          boxShadow: AppSpacing.shadowSm,
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Thumbnail placeholder
            Container(
              height: 140,
              decoration: BoxDecoration(
                gradient: AppColors.interactiveGradient,
                borderRadius: const BorderRadius.vertical(top: Radius.circular(12)),
              ),
              child: Center(
                child: Icon(Icons.play_circle, size: 48, color: Colors.white.withValues(alpha: 0.8)),
              ),
            ),
            Padding(
              padding: AppSpacing.paddingCard,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(child: Text(course.title, style: AppTypography.titleSmall)),
                      RatingBadge(rating: course.rating, isSmall: true),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Text(course.description, style: AppTypography.caption, maxLines: 2, overflow: TextOverflow.ellipsis),
                  const SizedBox(height: AppSpacing.md),
                  Row(
                    children: [
                      ItqanAvatar(name: course.teacher.fullName, size: 24),
                      const SizedBox(width: 8),
                      Text(course.teacher.fullName, style: AppTypography.caption),
                      const Spacer(),
                      Icon(Icons.group, size: 14, color: AppColors.textTertiary),
                      const SizedBox(width: 4),
                      Text('${course.enrolledCount}', style: AppTypography.caption),
                    ],
                  ),
                  const SizedBox(height: AppSpacing.md),
                  SessionProgressBar(completed: course.completedSessions, total: course.totalSessions),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
