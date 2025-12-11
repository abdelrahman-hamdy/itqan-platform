import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../../../core/theme/colors.dart';
import '../../../../core/theme/spacing.dart';
import '../../../../core/theme/typography.dart';
import '../../../../core/widgets/badges/status_badge.dart';
import '../../../../core/widgets/buttons/itqan_primary_button.dart';
import '../../../../core/widgets/progress/circular_progress.dart';
import '../../../../core/widgets/common/itqan_avatar.dart';
import '../../../../mock/mock_data.dart';

class StudentCourseDetailPage extends StatelessWidget {
  final String courseId;

  const StudentCourseDetailPage({super.key, required this.courseId});

  @override
  Widget build(BuildContext context) {
    final course = MockDataProvider.courses.firstWhere(
      (c) => c.id == courseId,
      orElse: () => MockDataProvider.courses.first,
    );

    return Scaffold(
      body: CustomScrollView(
        slivers: [
          SliverAppBar(
            expandedHeight: 200,
            pinned: true,
            leading: IconButton(
              icon: Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(color: Colors.black26, shape: BoxShape.circle),
                child: const Icon(Icons.arrow_back_ios, color: Colors.white, size: 18),
              ),
              onPressed: () => context.pop(),
            ),
            flexibleSpace: FlexibleSpaceBar(
              background: Container(
                decoration: BoxDecoration(gradient: AppColors.interactiveGradient),
                child: Center(child: Icon(Icons.play_circle, size: 64, color: Colors.white70)),
              ),
            ),
          ),
          SliverPadding(
            padding: AppSpacing.paddingScreen,
            sliver: SliverList(
              delegate: SliverChildListDelegate([
                const SizedBox(height: AppSpacing.base),
                Row(
                  children: [
                    Expanded(child: Text(course.title, style: AppTypography.textTheme.headlineSmall)),
                    RatingBadge(rating: course.rating, reviewCount: 45),
                  ],
                ),
                const SizedBox(height: AppSpacing.md),
                Text(course.description, style: AppTypography.bodyMedium),
                const SizedBox(height: AppSpacing.xl),

                // Progress
                Container(
                  padding: AppSpacing.paddingCard,
                  decoration: BoxDecoration(
                    color: AppColors.primary50,
                    borderRadius: AppSpacing.borderRadiusMd,
                  ),
                  child: Row(
                    children: [
                      CircularProgressIndicatorWithLabel(progress: course.progress, size: 60),
                      const SizedBox(width: AppSpacing.base),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('تقدمك في الدورة', style: AppTypography.titleSmall),
                            Text('${course.completedSessions} من ${course.totalSessions} جلسة', style: AppTypography.caption),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: AppSpacing.xl),

                // Teacher
                Text('المعلم', style: AppTypography.titleMedium),
                const SizedBox(height: AppSpacing.md),
                Container(
                  padding: AppSpacing.paddingCard,
                  decoration: BoxDecoration(
                    color: AppColors.surface,
                    borderRadius: AppSpacing.borderRadiusMd,
                    border: Border.all(color: AppColors.border),
                  ),
                  child: Row(
                    children: [
                      ItqanAvatar(name: course.teacher.fullName, size: 48),
                      const SizedBox(width: AppSpacing.md),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(course.teacher.fullName, style: AppTypography.titleSmall),
                            Text(course.teacher.subjects.join(' • '), style: AppTypography.caption),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: AppSpacing.xl),

                // Sessions
                Text('الجلسات', style: AppTypography.titleMedium),
                const SizedBox(height: AppSpacing.md),
                ...List.generate(5, (i) => Container(
                  margin: const EdgeInsets.only(bottom: AppSpacing.md),
                  padding: AppSpacing.paddingCard,
                  decoration: BoxDecoration(
                    color: AppColors.surface,
                    borderRadius: AppSpacing.borderRadiusMd,
                    border: Border.all(color: AppColors.border),
                  ),
                  child: Row(
                    children: [
                      Container(
                        width: 36, height: 36,
                        decoration: BoxDecoration(
                          color: i < course.completedSessions ? AppColors.ongoingLight : AppColors.secondary100,
                          shape: BoxShape.circle,
                        ),
                        child: Center(
                          child: i < course.completedSessions
                              ? Icon(Icons.check, color: AppColors.ongoing, size: 20)
                              : Text('${i + 1}', style: AppTypography.labelLarge),
                        ),
                      ),
                      const SizedBox(width: AppSpacing.md),
                      Expanded(
                        child: Text('الجلسة ${i + 1}', style: AppTypography.titleSmall),
                      ),
                      StatusBadge(
                        status: i < course.completedSessions ? 'completed' : (i == course.completedSessions ? 'scheduled' : 'pending'),
                        isSmall: true,
                      ),
                    ],
                  ),
                )),
                const SizedBox(height: AppSpacing.xxl),
              ]),
            ),
          ),
        ],
      ),
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: AppSpacing.paddingScreen,
          child: ItqanPrimaryButton(
            text: 'متابعة الدورة',
            icon: Icons.play_arrow,
            onPressed: () {},
            gradient: AppColors.interactiveGradient,
          ),
        ),
      ),
    );
  }
}
