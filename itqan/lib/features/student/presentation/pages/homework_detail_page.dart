import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../../../core/theme/colors.dart';
import '../../../../core/theme/spacing.dart';
import '../../../../core/theme/typography.dart';
import '../../../../core/widgets/badges/status_badge.dart';
import '../../../../core/widgets/buttons/itqan_primary_button.dart';
import '../../../../mock/mock_data.dart';

class StudentHomeworkDetailPage extends StatefulWidget {
  final String homeworkId;

  const StudentHomeworkDetailPage({super.key, required this.homeworkId});

  @override
  State<StudentHomeworkDetailPage> createState() => _StudentHomeworkDetailPageState();
}

class _StudentHomeworkDetailPageState extends State<StudentHomeworkDetailPage> {
  final _submissionController = TextEditingController();
  bool _isSubmitting = false;

  MockHomework? get homework {
    try {
      return MockDataProvider.homework.firstWhere((h) => h.id == widget.homeworkId);
    } catch (_) {
      return null;
    }
  }

  @override
  void dispose() {
    _submissionController.dispose();
    super.dispose();
  }

  void _handleSubmit() {
    setState(() => _isSubmitting = true);
    Future.delayed(const Duration(seconds: 1), () {
      if (!mounted) return;
      setState(() => _isSubmitting = false);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('تم تسليم الواجب بنجاح')),
      );
    });
  }

  @override
  Widget build(BuildContext context) {
    final h = homework;
    if (h == null) {
      return Scaffold(
        appBar: AppBar(title: const Text('الواجب')),
        body: const Center(child: Text('الواجب غير موجود')),
      );
    }

    final isPending = h.status == 'pending';
    final isGraded = h.status == 'graded';
    final daysLeft = h.dueDate.difference(DateTime.now()).inDays;

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios),
          onPressed: () => context.pop(),
        ),
        title: const Text('تفاصيل الواجب'),
      ),
      body: SingleChildScrollView(
        padding: AppSpacing.paddingScreenAll,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header Card
            Container(
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
                      TypeBadge(type: h.type),
                      const Spacer(),
                      StatusBadge(status: h.status),
                    ],
                  ),
                  const SizedBox(height: AppSpacing.md),
                  Text(h.title, style: AppTypography.titleLarge),
                  if (h.description != null) ...[
                    const SizedBox(height: AppSpacing.sm),
                    Text(h.description!, style: AppTypography.bodyMedium.copyWith(color: AppColors.textSecondary)),
                  ],
                ],
              ),
            ),
            const SizedBox(height: AppSpacing.lg),

            // Due Date Section
            Container(
              padding: AppSpacing.paddingCard,
              decoration: BoxDecoration(
                color: daysLeft < 0 ? AppColors.error.withValues(alpha: 0.1) :
                       daysLeft <= 1 ? AppColors.warning.withValues(alpha: 0.1) : AppColors.primary100,
                borderRadius: AppSpacing.borderRadiusMd,
              ),
              child: Row(
                children: [
                  Icon(
                    Icons.calendar_today,
                    color: daysLeft < 0 ? AppColors.error :
                           daysLeft <= 1 ? AppColors.warning : AppColors.primary,
                  ),
                  const SizedBox(width: AppSpacing.md),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('موعد التسليم', style: AppTypography.caption),
                      Text(
                        '${h.dueDate.day}/${h.dueDate.month}/${h.dueDate.year}',
                        style: AppTypography.titleSmall,
                      ),
                    ],
                  ),
                  const Spacer(),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md, vertical: AppSpacing.sm),
                    decoration: BoxDecoration(
                      color: daysLeft < 0 ? AppColors.error :
                             daysLeft <= 1 ? AppColors.warning : AppColors.primary,
                      borderRadius: AppSpacing.borderRadiusFull,
                    ),
                    child: Text(
                      daysLeft < 0 ? 'متأخر' :
                      daysLeft == 0 ? 'اليوم' :
                      daysLeft == 1 ? 'غداً' : '$daysLeft أيام',
                      style: AppTypography.labelSmall.copyWith(color: Colors.white),
                    ),
                  ),
                ],
              ),
            ),

            // Grade Section (if graded)
            if (isGraded && h.grade != null) ...[
              const SizedBox(height: AppSpacing.lg),
              Container(
                padding: AppSpacing.paddingCard,
                decoration: BoxDecoration(
                  gradient: AppColors.successGradient,
                  borderRadius: AppSpacing.borderRadiusMd,
                ),
                child: Row(
                  children: [
                    Container(
                      width: 60,
                      height: 60,
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.2),
                        shape: BoxShape.circle,
                      ),
                      child: Center(
                        child: Text(
                          '${h.grade!.toInt()}',
                          style: AppTypography.headlineMedium.copyWith(color: Colors.white),
                        ),
                      ),
                    ),
                    const SizedBox(width: AppSpacing.md),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('الدرجة', style: AppTypography.caption.copyWith(color: Colors.white70)),
                          Text('${h.grade}/10', style: AppTypography.titleLarge.copyWith(color: Colors.white)),
                        ],
                      ),
                    ),
                    Icon(Icons.emoji_events, color: Colors.white.withValues(alpha: 0.8), size: 40),
                  ],
                ),
              ),
            ],

            // Feedback Section (if graded)
            if (isGraded && h.feedback != null) ...[
              const SizedBox(height: AppSpacing.lg),
              Text('ملاحظات المعلم', style: AppTypography.titleSmall),
              const SizedBox(height: AppSpacing.sm),
              Container(
                padding: AppSpacing.paddingCard,
                decoration: BoxDecoration(
                  color: AppColors.surfaceVariant,
                  borderRadius: AppSpacing.borderRadiusMd,
                  border: Border.all(color: AppColors.border),
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Icon(Icons.format_quote, color: AppColors.primary, size: 24),
                    const SizedBox(width: AppSpacing.sm),
                    Expanded(
                      child: Text(h.feedback!, style: AppTypography.bodyMedium),
                    ),
                  ],
                ),
              ),
            ],

            // Submission Section (if pending)
            if (isPending) ...[
              const SizedBox(height: AppSpacing.xl),
              Text('تسليم الواجب', style: AppTypography.titleSmall),
              const SizedBox(height: AppSpacing.md),
              TextField(
                controller: _submissionController,
                maxLines: 5,
                decoration: const InputDecoration(
                  hintText: 'اكتب إجابتك هنا...',
                  alignLabelWithHint: true,
                ),
              ),
              const SizedBox(height: AppSpacing.md),
              OutlinedButton.icon(
                onPressed: () {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('سيتم إضافة رفع الملفات قريباً')),
                  );
                },
                icon: const Icon(Icons.attach_file),
                label: const Text('إرفاق ملف'),
              ),
              const SizedBox(height: AppSpacing.xl),
              ItqanPrimaryButton(
                text: 'تسليم الواجب',
                onPressed: _handleSubmit,
                isLoading: _isSubmitting,
              ),
            ],
          ],
        ),
      ),
    );
  }
}
