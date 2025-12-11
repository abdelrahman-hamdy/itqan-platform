import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../../../core/theme/colors.dart';
import '../../../../core/theme/spacing.dart';
import '../../../../core/theme/typography.dart';
import '../../../../core/widgets/badges/status_badge.dart';
import '../../../../core/widgets/buttons/itqan_primary_button.dart';
import '../../../../core/widgets/common/itqan_avatar.dart';
import '../../../../mock/mock_data.dart';

class StudentTeacherDetailPage extends StatelessWidget {
  final String teacherId;

  const StudentTeacherDetailPage({super.key, required this.teacherId});

  MockTeacher? get teacher {
    try {
      return MockDataProvider.teachers.firstWhere((t) => t.id == teacherId);
    } catch (_) {
      return null;
    }
  }

  @override
  Widget build(BuildContext context) {
    final t = teacher;
    if (t == null) {
      return Scaffold(
        appBar: AppBar(title: const Text('المعلم')),
        body: const Center(child: Text('المعلم غير موجود')),
      );
    }

    return Scaffold(
      body: CustomScrollView(
        slivers: [
          // App Bar with gradient
          SliverAppBar(
            expandedHeight: 200,
            pinned: true,
            leading: IconButton(
              icon: Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.9),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.arrow_back_ios, size: 18),
              ),
              onPressed: () => context.pop(),
            ),
            flexibleSpace: FlexibleSpaceBar(
              background: Container(
                decoration: BoxDecoration(
                  gradient: t.type == 'quran'
                      ? AppColors.quranGradient
                      : AppColors.educationGradient,
                ),
                child: SafeArea(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const SizedBox(height: AppSpacing.xl),
                      ItqanAvatar(name: t.fullName, size: 80),
                      const SizedBox(height: AppSpacing.md),
                      Text(
                        t.fullName,
                        style: AppTypography.headlineSmall.copyWith(color: Colors.white),
                      ),
                      const SizedBox(height: AppSpacing.xs),
                      Text(
                        t.type == 'quran' ? 'معلم قرآن كريم' : 'معلم أكاديمي',
                        style: AppTypography.bodyMedium.copyWith(color: Colors.white70),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),

          SliverToBoxAdapter(
            child: Padding(
              padding: AppSpacing.paddingScreenAll,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Stats Row
                  Container(
                    padding: AppSpacing.paddingCard,
                    decoration: BoxDecoration(
                      color: AppColors.surface,
                      borderRadius: AppSpacing.borderRadiusMd,
                      border: Border.all(color: AppColors.border),
                    ),
                    child: Row(
                      children: [
                        Expanded(
                          child: _StatItem(
                            icon: Icons.star,
                            iconColor: AppColors.warning,
                            value: '${t.rating}',
                            label: 'التقييم',
                          ),
                        ),
                        Container(width: 1, height: 50, color: AppColors.divider),
                        Expanded(
                          child: _StatItem(
                            icon: Icons.reviews,
                            iconColor: AppColors.primary,
                            value: '${t.reviewCount}',
                            label: 'تقييم',
                          ),
                        ),
                        Container(width: 1, height: 50, color: AppColors.divider),
                        Expanded(
                          child: _StatItem(
                            icon: Icons.work_history,
                            iconColor: AppColors.ongoing,
                            value: '${t.experience}',
                            label: 'سنوات خبرة',
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: AppSpacing.xl),

                  // Bio
                  if (t.bio != null) ...[
                    Text('نبذة', style: AppTypography.titleSmall),
                    const SizedBox(height: AppSpacing.sm),
                    Text(
                      t.bio!,
                      style: AppTypography.bodyMedium.copyWith(color: AppColors.textSecondary),
                    ),
                    const SizedBox(height: AppSpacing.xl),
                  ],

                  // Subjects
                  Text('التخصصات', style: AppTypography.titleSmall),
                  const SizedBox(height: AppSpacing.md),
                  Wrap(
                    spacing: AppSpacing.sm,
                    runSpacing: AppSpacing.sm,
                    children: t.subjects.map((subject) => Container(
                      padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md, vertical: AppSpacing.sm),
                      decoration: BoxDecoration(
                        color: AppColors.primary.withValues(alpha: 0.1),
                        borderRadius: AppSpacing.borderRadiusFull,
                        border: Border.all(color: AppColors.primary.withValues(alpha: 0.3)),
                      ),
                      child: Text(subject, style: AppTypography.labelSmall.copyWith(color: AppColors.primary)),
                    )).toList(),
                  ),
                  const SizedBox(height: AppSpacing.xl),

                  // Packages
                  Text('الباقات المتاحة', style: AppTypography.titleSmall),
                  const SizedBox(height: AppSpacing.md),
                  _PackageCard(
                    title: 'جلسة فردية',
                    description: 'جلسة واحدة مدتها 45 دقيقة',
                    price: t.sessionPrice,
                    sessions: 1,
                    onSelect: () {},
                  ),
                  const SizedBox(height: AppSpacing.md),
                  _PackageCard(
                    title: 'باقة شهرية',
                    description: '8 جلسات شهرياً (جلستان أسبوعياً)',
                    price: t.sessionPrice * 8 * 0.9, // 10% discount
                    sessions: 8,
                    isPopular: true,
                    onSelect: () {},
                  ),
                  const SizedBox(height: AppSpacing.md),
                  _PackageCard(
                    title: 'باقة مكثفة',
                    description: '12 جلسة شهرياً (3 جلسات أسبوعياً)',
                    price: t.sessionPrice * 12 * 0.85, // 15% discount
                    sessions: 12,
                    onSelect: () {},
                  ),
                  const SizedBox(height: AppSpacing.xl),

                  // Reviews
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text('التقييمات', style: AppTypography.titleSmall),
                      TextButton(
                        onPressed: () {},
                        child: const Text('عرض الكل'),
                      ),
                    ],
                  ),
                  const SizedBox(height: AppSpacing.md),
                  _ReviewCard(
                    name: 'أحمد محمد',
                    rating: 5,
                    date: DateTime.now().subtract(const Duration(days: 3)),
                    comment: 'معلم ممتاز، أسلوبه رائع في الشرح وصبور جداً. أنصح به بشدة.',
                  ),
                  const SizedBox(height: AppSpacing.md),
                  _ReviewCard(
                    name: 'سارة أحمد',
                    rating: 4,
                    date: DateTime.now().subtract(const Duration(days: 10)),
                    comment: 'تجربة جيدة جداً، المعلم ملتزم بالمواعيد ومتعاون.',
                  ),
                  const SizedBox(height: AppSpacing.xxxl),
                ],
              ),
            ),
          ),
        ],
      ),
      bottomNavigationBar: Container(
        padding: AppSpacing.paddingScreenAll,
        decoration: const BoxDecoration(
          color: AppColors.surface,
          border: Border(top: BorderSide(color: AppColors.border)),
        ),
        child: SafeArea(
          child: ItqanPrimaryButton(
            text: 'طلب حجز جلسة',
            onPressed: () {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('سيتم إضافة حجز الجلسات قريباً')),
              );
            },
          ),
        ),
      ),
    );
  }
}

class _StatItem extends StatelessWidget {
  final IconData icon;
  final Color iconColor;
  final String value;
  final String label;

  const _StatItem({
    required this.icon,
    required this.iconColor,
    required this.value,
    required this.label,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Icon(icon, color: iconColor, size: 24),
        const SizedBox(height: AppSpacing.sm),
        Text(value, style: AppTypography.titleMedium),
        Text(label, style: AppTypography.caption.copyWith(color: AppColors.textSecondary)),
      ],
    );
  }
}

class _PackageCard extends StatelessWidget {
  final String title;
  final String description;
  final double price;
  final int sessions;
  final bool isPopular;
  final VoidCallback onSelect;

  const _PackageCard({
    required this.title,
    required this.description,
    required this.price,
    required this.sessions,
    this.isPopular = false,
    required this.onSelect,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: AppSpacing.paddingCard,
      decoration: BoxDecoration(
        color: isPopular ? AppColors.primary.withValues(alpha: 0.05) : AppColors.surface,
        borderRadius: AppSpacing.borderRadiusMd,
        border: Border.all(color: isPopular ? AppColors.primary : AppColors.border, width: isPopular ? 2 : 1),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Text(title, style: AppTypography.titleSmall),
                        if (isPopular) ...[
                          const SizedBox(width: AppSpacing.sm),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: AppSpacing.sm, vertical: 2),
                            decoration: BoxDecoration(
                              color: AppColors.primary,
                              borderRadius: AppSpacing.borderRadiusFull,
                            ),
                            child: Text('الأكثر شيوعاً', style: AppTypography.labelSmall.copyWith(color: Colors.white, fontSize: 10)),
                          ),
                        ],
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(description, style: AppTypography.caption.copyWith(color: AppColors.textSecondary)),
                  ],
                ),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    '${price.toInt()} ر.س',
                    style: AppTypography.titleMedium.copyWith(color: AppColors.primary),
                  ),
                  Text(
                    '$sessions جلسات',
                    style: AppTypography.caption,
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.md),
          SizedBox(
            width: double.infinity,
            child: isPopular
                ? ElevatedButton(
                    onPressed: onSelect,
                    child: const Text('اختيار الباقة'),
                  )
                : OutlinedButton(
                    onPressed: onSelect,
                    child: const Text('اختيار الباقة'),
                  ),
          ),
        ],
      ),
    );
  }
}

class _ReviewCard extends StatelessWidget {
  final String name;
  final int rating;
  final DateTime date;
  final String comment;

  const _ReviewCard({
    required this.name,
    required this.rating,
    required this.date,
    required this.comment,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
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
              ItqanAvatar(name: name, size: 36),
              const SizedBox(width: AppSpacing.sm),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(name, style: AppTypography.labelMedium),
                    Row(
                      children: List.generate(5, (index) => Icon(
                        index < rating ? Icons.star : Icons.star_border,
                        size: 14,
                        color: AppColors.warning,
                      )),
                    ),
                  ],
                ),
              ),
              Text(
                '${date.day}/${date.month}',
                style: AppTypography.caption.copyWith(color: AppColors.textTertiary),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.md),
          Text(comment, style: AppTypography.bodySmall.copyWith(color: AppColors.textSecondary)),
        ],
      ),
    );
  }
}
