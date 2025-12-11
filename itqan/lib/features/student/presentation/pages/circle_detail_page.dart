import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../../../core/theme/colors.dart';
import '../../../../core/theme/spacing.dart';
import '../../../../core/theme/typography.dart';
import '../../../../core/widgets/badges/status_badge.dart';
import '../../../../core/widgets/progress/circular_progress.dart';
import '../../../../core/widgets/common/itqan_avatar.dart';
import '../../../../mock/mock_data.dart';

/// Circle detail page
class StudentCircleDetailPage extends StatelessWidget {
  final String circleId;

  const StudentCircleDetailPage({super.key, required this.circleId});

  @override
  Widget build(BuildContext context) {
    final circle = MockDataProvider.circles.firstWhere(
      (c) => c.id == circleId,
      orElse: () => MockDataProvider.circles.first,
    );

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios),
          onPressed: () => context.pop(),
        ),
        title: Text(circle.name),
      ),
      body: SingleChildScrollView(
        padding: AppSpacing.paddingScreen,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Progress Card
            Container(
              padding: AppSpacing.paddingCard,
              decoration: BoxDecoration(
                gradient: AppColors.quranGradient,
                borderRadius: AppSpacing.borderRadiusLg,
              ),
              child: Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('تقدمك في الحلقة',
                            style: AppTypography.bodyMedium.copyWith(color: Colors.white70)),
                        const SizedBox(height: 8),
                        Text('${(circle.progress * 100).round()}%',
                            style: AppTypography.priceStyle.copyWith(color: Colors.white)),
                        const SizedBox(height: 4),
                        Text(circle.schedule,
                            style: AppTypography.caption.copyWith(color: Colors.white70)),
                      ],
                    ),
                  ),
                  CircularProgressIndicatorWithLabel(
                    progress: circle.progress,
                    size: 80,
                    progressColor: Colors.white,
                    backgroundColor: Colors.white24,
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
                  ItqanAvatar(name: circle.teacher.fullName, size: 56),
                  const SizedBox(width: AppSpacing.md),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(circle.teacher.fullName, style: AppTypography.titleSmall),
                        Row(
                          children: [
                            Icon(Icons.star, size: 14, color: AppColors.warning),
                            const SizedBox(width: 4),
                            Text('${circle.teacher.rating}', style: AppTypography.caption),
                          ],
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: AppSpacing.xl),

            // Sessions
            Text('الجلسات الأخيرة', style: AppTypography.titleMedium),
            const SizedBox(height: AppSpacing.md),
            ...List.generate(3, (index) {
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
                    Container(
                      padding: const EdgeInsets.all(AppSpacing.sm),
                      decoration: BoxDecoration(
                        color: index == 0 ? AppColors.scheduledLight : AppColors.ongoingLight,
                        borderRadius: AppSpacing.borderRadiusSm,
                      ),
                      child: Icon(
                        index == 0 ? Icons.schedule : Icons.check_circle,
                        color: index == 0 ? AppColors.scheduled : AppColors.ongoing,
                        size: 20,
                      ),
                    ),
                    const SizedBox(width: AppSpacing.md),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('جلسة ${index + 1}', style: AppTypography.titleSmall),
                          Text('${12 + index}/12/2024', style: AppTypography.caption),
                        ],
                      ),
                    ),
                    StatusBadge(
                      status: index == 0 ? 'scheduled' : 'completed',
                      isSmall: true,
                    ),
                  ],
                ),
              );
            }),
          ],
        ),
      ),
    );
  }
}
