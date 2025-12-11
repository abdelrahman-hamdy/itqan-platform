import 'package:flutter/material.dart';
import '../../theme/colors.dart';
import '../../theme/spacing.dart';
import '../../theme/typography.dart';

/// Linear progress bar with label
class LinearProgressBar extends StatelessWidget {
  final double progress; // 0.0 to 1.0
  final Color? progressColor;
  final Color? backgroundColor;
  final double height;
  final bool showLabel;
  final String? labelPrefix;

  const LinearProgressBar({
    super.key,
    required this.progress,
    this.progressColor,
    this.backgroundColor,
    this.height = 8,
    this.showLabel = true,
    this.labelPrefix,
  });

  @override
  Widget build(BuildContext context) {
    final clampedProgress = progress.clamp(0.0, 1.0);
    final percentage = (clampedProgress * 100).round();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisSize: MainAxisSize.min,
      children: [
        if (showLabel) ...[
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              if (labelPrefix != null)
                Text(
                  labelPrefix!,
                  style: AppTypography.caption,
                ),
              Text(
                '$percentage%',
                style: AppTypography.labelSmall.copyWith(
                  color: progressColor ?? AppColors.primary,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.xs),
        ],
        Container(
          height: height,
          decoration: BoxDecoration(
            color: backgroundColor ?? AppColors.secondary100,
            borderRadius: BorderRadius.circular(height / 2),
          ),
          child: LayoutBuilder(
            builder: (context, constraints) {
              return Stack(
                children: [
                  AnimatedContainer(
                    duration: AppSpacing.durationStandard,
                    curve: Curves.easeInOut,
                    width: constraints.maxWidth * clampedProgress,
                    height: height,
                    decoration: BoxDecoration(
                      color: progressColor ?? AppColors.primary,
                      borderRadius: BorderRadius.circular(height / 2),
                    ),
                  ),
                ],
              );
            },
          ),
        ),
      ],
    );
  }
}

/// Gradient progress bar
class GradientProgressBar extends StatelessWidget {
  final double progress;
  final LinearGradient? gradient;
  final double height;
  final bool showLabel;

  const GradientProgressBar({
    super.key,
    required this.progress,
    this.gradient,
    this.height = 8,
    this.showLabel = true,
  });

  @override
  Widget build(BuildContext context) {
    final clampedProgress = progress.clamp(0.0, 1.0);
    final percentage = (clampedProgress * 100).round();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisSize: MainAxisSize.min,
      children: [
        if (showLabel) ...[
          Row(
            mainAxisAlignment: MainAxisAlignment.end,
            children: [
              Text(
                '$percentage%',
                style: AppTypography.labelSmall.copyWith(
                  color: AppColors.primary,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.xs),
        ],
        Container(
          height: height,
          decoration: BoxDecoration(
            color: AppColors.secondary100,
            borderRadius: BorderRadius.circular(height / 2),
          ),
          child: LayoutBuilder(
            builder: (context, constraints) {
              return Stack(
                children: [
                  AnimatedContainer(
                    duration: AppSpacing.durationStandard,
                    curve: Curves.easeInOut,
                    width: constraints.maxWidth * clampedProgress,
                    height: height,
                    decoration: BoxDecoration(
                      gradient: gradient ?? AppColors.primaryGradient,
                      borderRadius: BorderRadius.circular(height / 2),
                    ),
                  ),
                ],
              );
            },
          ),
        ),
      ],
    );
  }
}

/// Progress bar with sessions count
class SessionProgressBar extends StatelessWidget {
  final int completed;
  final int total;
  final Color? progressColor;

  const SessionProgressBar({
    super.key,
    required this.completed,
    required this.total,
    this.progressColor,
  });

  @override
  Widget build(BuildContext context) {
    final progress = total > 0 ? completed / total : 0.0;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisSize: MainAxisSize.min,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(
              '$completed من $total جلسة',
              style: AppTypography.caption,
            ),
            Text(
              '${(progress * 100).round()}%',
              style: AppTypography.labelSmall.copyWith(
                color: progressColor ?? AppColors.primary,
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
        const SizedBox(height: AppSpacing.xs),
        LinearProgressBar(
          progress: progress,
          progressColor: progressColor,
          showLabel: false,
        ),
      ],
    );
  }
}
