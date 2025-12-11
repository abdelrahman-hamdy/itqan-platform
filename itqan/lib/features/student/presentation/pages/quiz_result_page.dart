import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../../../core/theme/colors.dart';
import '../../../../core/theme/spacing.dart';
import '../../../../core/theme/typography.dart';
import '../../../../core/widgets/buttons/itqan_primary_button.dart';
import '../../../../core/widgets/buttons/itqan_secondary_button.dart';
import '../../../../mock/mock_data.dart';

class StudentQuizResultPage extends StatelessWidget {
  final String quizId;

  const StudentQuizResultPage({super.key, required this.quizId});

  MockQuiz? get quiz {
    try {
      return MockDataProvider.quizzes.firstWhere((q) => q.id == quizId);
    } catch (_) {
      return null;
    }
  }

  @override
  Widget build(BuildContext context) {
    final q = quiz;
    if (q == null) {
      return Scaffold(
        appBar: AppBar(title: const Text('النتيجة')),
        body: const Center(child: Text('الاختبار غير موجود')),
      );
    }

    // Mock result data
    final score = q.score ?? 85.0;
    final isPassed = score >= q.passingScore;
    final correctAnswers = (score / 100 * q.questionCount).round();
    final wrongAnswers = q.questionCount - correctAnswers;

    return Scaffold(
      body: SafeArea(
        child: SingleChildScrollView(
          padding: AppSpacing.paddingScreenAll,
          child: Column(
            children: [
              const SizedBox(height: AppSpacing.xxl),

              // Result Icon
              Container(
                width: 120,
                height: 120,
                decoration: BoxDecoration(
                  gradient: isPassed ? AppColors.successGradient : AppColors.errorGradient,
                  shape: BoxShape.circle,
                  boxShadow: [
                    BoxShadow(
                      color: (isPassed ? AppColors.ongoing : AppColors.error).withValues(alpha: 0.3),
                      blurRadius: 20,
                      offset: const Offset(0, 10),
                    ),
                  ],
                ),
                child: Icon(
                  isPassed ? Icons.emoji_events : Icons.sentiment_dissatisfied,
                  size: 60,
                  color: Colors.white,
                ),
              ),
              const SizedBox(height: AppSpacing.xl),

              // Result Text
              Text(
                isPassed ? 'مبروك! نجحت في الاختبار' : 'للأسف، لم تجتز الاختبار',
                style: AppTypography.headlineSmall,
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: AppSpacing.sm),
              Text(
                q.title,
                style: AppTypography.bodyMedium.copyWith(color: AppColors.textSecondary),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: AppSpacing.xxxl),

              // Score Card
              Container(
                padding: AppSpacing.paddingCard,
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: AppSpacing.borderRadiusLg,
                  border: Border.all(color: AppColors.border),
                ),
                child: Column(
                  children: [
                    // Score Circle
                    SizedBox(
                      width: 150,
                      height: 150,
                      child: Stack(
                        alignment: Alignment.center,
                        children: [
                          SizedBox(
                            width: 150,
                            height: 150,
                            child: CircularProgressIndicator(
                              value: score / 100,
                              strokeWidth: 12,
                              backgroundColor: AppColors.border,
                              valueColor: AlwaysStoppedAnimation<Color>(
                                isPassed ? AppColors.ongoing : AppColors.error,
                              ),
                            ),
                          ),
                          Column(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Text(
                                '${score.toInt()}%',
                                style: AppTypography.headlineLarge.copyWith(
                                  color: isPassed ? AppColors.ongoing : AppColors.error,
                                ),
                              ),
                              Text('النتيجة', style: AppTypography.caption),
                            ],
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: AppSpacing.xl),

                    // Stats Row
                    Row(
                      children: [
                        Expanded(
                          child: _StatItem(
                            icon: Icons.check_circle,
                            iconColor: AppColors.ongoing,
                            label: 'إجابات صحيحة',
                            value: '$correctAnswers',
                          ),
                        ),
                        Container(
                          width: 1,
                          height: 50,
                          color: AppColors.divider,
                        ),
                        Expanded(
                          child: _StatItem(
                            icon: Icons.cancel,
                            iconColor: AppColors.error,
                            label: 'إجابات خاطئة',
                            value: '$wrongAnswers',
                          ),
                        ),
                      ],
                    ),
                    const Divider(height: AppSpacing.xl),

                    // Additional Info
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceAround,
                      children: [
                        _InfoItem(label: 'عدد الأسئلة', value: '${q.questionCount}'),
                        _InfoItem(label: 'درجة النجاح', value: '${q.passingScore.toInt()}%'),
                        _InfoItem(label: 'الوقت', value: '${q.timeLimitMinutes} د'),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(height: AppSpacing.xxxl),

              // Actions
              ItqanPrimaryButton(
                text: 'العودة للاختبارات',
                onPressed: () => context.go('/student/more/quizzes'),
              ),
              const SizedBox(height: AppSpacing.md),
              if (!isPassed)
                ItqanSecondaryButton(
                  text: 'إعادة الاختبار',
                  onPressed: () => context.go('/student/more/quizzes/$quizId/take'),
                ),
            ],
          ),
        ),
      ),
    );
  }
}

class _StatItem extends StatelessWidget {
  final IconData icon;
  final Color iconColor;
  final String label;
  final String value;

  const _StatItem({
    required this.icon,
    required this.iconColor,
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Icon(icon, color: iconColor, size: 28),
        const SizedBox(height: AppSpacing.sm),
        Text(value, style: AppTypography.titleLarge),
        Text(label, style: AppTypography.caption),
      ],
    );
  }
}

class _InfoItem extends StatelessWidget {
  final String label;
  final String value;

  const _InfoItem({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Text(value, style: AppTypography.titleSmall),
        Text(label, style: AppTypography.caption.copyWith(color: AppColors.textSecondary)),
      ],
    );
  }
}
