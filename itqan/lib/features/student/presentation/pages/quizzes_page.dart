import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../../../core/theme/colors.dart';
import '../../../../core/theme/spacing.dart';
import '../../../../core/theme/typography.dart';
import '../../../../core/widgets/badges/status_badge.dart';
import '../../../../mock/mock_data.dart';

class StudentQuizzesPage extends StatelessWidget {
  const StudentQuizzesPage({super.key});

  @override
  Widget build(BuildContext context) {
    final quizzes = MockDataProvider.quizzes;
    final available = quizzes.where((q) => q.status == 'available').toList();
    final completed = quizzes.where((q) => q.status == 'completed').toList();

    return DefaultTabController(
      length: 2,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('الاختبارات'),
          bottom: const TabBar(
            tabs: [
              Tab(text: 'متاحة'),
              Tab(text: 'مكتملة'),
            ],
          ),
        ),
        body: TabBarView(
          children: [
            // Available Quizzes
            available.isEmpty
                ? _buildEmptyState('لا توجد اختبارات متاحة')
                : ListView.separated(
                    padding: AppSpacing.paddingScreen,
                    itemCount: available.length,
                    separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.md),
                    itemBuilder: (context, index) {
                      final quiz = available[index];
                      return _QuizCard(
                        quiz: quiz,
                        onTap: () => context.push('/student/more/quizzes/${quiz.id}/take'),
                      );
                    },
                  ),
            // Completed Quizzes
            completed.isEmpty
                ? _buildEmptyState('لم تكمل أي اختبار بعد')
                : ListView.separated(
                    padding: AppSpacing.paddingScreen,
                    itemCount: completed.length,
                    separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.md),
                    itemBuilder: (context, index) {
                      final quiz = completed[index];
                      return _QuizCard(
                        quiz: quiz,
                        onTap: () => context.push('/student/more/quizzes/${quiz.id}/result'),
                      );
                    },
                  ),
          ],
        ),
      ),
    );
  }

  Widget _buildEmptyState(String message) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.quiz_outlined, size: 64, color: AppColors.textTertiary),
          const SizedBox(height: AppSpacing.md),
          Text(message, style: AppTypography.bodyMedium.copyWith(color: AppColors.textSecondary)),
        ],
      ),
    );
  }
}

class _QuizCard extends StatelessWidget {
  final MockQuiz quiz;
  final VoidCallback onTap;

  const _QuizCard({required this.quiz, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final isCompleted = quiz.status == 'completed';

    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: AppSpacing.paddingCard,
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: AppSpacing.borderRadiusMd,
          border: Border.all(color: AppColors.border),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(quiz.title, style: AppTypography.titleSmall),
                ),
                if (isCompleted && quiz.score != null)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md, vertical: AppSpacing.xs),
                    decoration: BoxDecoration(
                      color: quiz.isPassed ? AppColors.ongoing.withValues(alpha: 0.1) : AppColors.error.withValues(alpha: 0.1),
                      borderRadius: AppSpacing.borderRadiusFull,
                    ),
                    child: Text(
                      '${quiz.score!.toInt()}%',
                      style: AppTypography.labelSmall.copyWith(
                        color: quiz.isPassed ? AppColors.ongoing : AppColors.error,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
              ],
            ),
            if (quiz.description != null) ...[
              const SizedBox(height: AppSpacing.sm),
              Text(
                quiz.description!,
                style: AppTypography.caption.copyWith(color: AppColors.textSecondary),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
            ],
            const SizedBox(height: AppSpacing.md),
            Row(
              children: [
                _InfoChip(icon: Icons.help_outline, label: '${quiz.questionCount} سؤال'),
                const SizedBox(width: AppSpacing.md),
                _InfoChip(icon: Icons.timer_outlined, label: '${quiz.timeLimitMinutes} دقيقة'),
                const Spacer(),
                if (!isCompleted)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md, vertical: AppSpacing.sm),
                    decoration: BoxDecoration(
                      color: AppColors.primary,
                      borderRadius: AppSpacing.borderRadiusFull,
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text('ابدأ', style: AppTypography.labelSmall.copyWith(color: Colors.white)),
                        const SizedBox(width: 4),
                        const Icon(Icons.arrow_forward_ios, size: 12, color: Colors.white),
                      ],
                    ),
                  )
                else
                  StatusBadge(status: quiz.isPassed ? 'passed' : 'failed', isSmall: true),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _InfoChip extends StatelessWidget {
  final IconData icon;
  final String label;

  const _InfoChip({required this.icon, required this.label});

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 16, color: AppColors.textTertiary),
        const SizedBox(width: 4),
        Text(label, style: AppTypography.caption),
      ],
    );
  }
}
