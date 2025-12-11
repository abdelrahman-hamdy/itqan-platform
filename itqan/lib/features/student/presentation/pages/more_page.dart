import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../../../core/theme/colors.dart';
import '../../../../core/theme/spacing.dart';
import '../../../../core/theme/typography.dart';
import '../../../../core/widgets/common/itqan_avatar.dart';
import '../../../../mock/mock_data.dart';

class StudentMorePage extends StatelessWidget {
  const StudentMorePage({super.key});

  @override
  Widget build(BuildContext context) {
    final user = MockDataProvider.currentStudent;

    return Scaffold(
      appBar: AppBar(title: const Text('المزيد')),
      body: ListView(
        padding: AppSpacing.paddingScreen,
        children: [
          // User Card
          GestureDetector(
            onTap: () => context.push('/student/more/profile'),
            child: Container(
              padding: AppSpacing.paddingCard,
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: AppSpacing.borderRadiusMd,
                border: Border.all(color: AppColors.border),
              ),
              child: Row(
                children: [
                  ItqanAvatar(name: user.fullName, size: 56),
                  const SizedBox(width: AppSpacing.md),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(user.fullName, style: AppTypography.titleSmall),
                        const SizedBox(height: 4),
                        Text(user.email, style: AppTypography.caption.copyWith(color: AppColors.textSecondary)),
                      ],
                    ),
                  ),
                  const Icon(Icons.arrow_forward_ios, size: 16, color: AppColors.textTertiary),
                ],
              ),
            ),
          ),
          const SizedBox(height: AppSpacing.xl),

          // Menu Sections
          _MenuSection(
            title: 'التعليم',
            items: [
              _MenuItem(
                icon: Icons.mosque,
                iconColor: AppColors.quranColor,
                title: 'حلقات القرآن',
                subtitle: 'حلقاتي الجماعية والفردية',
                onTap: () => context.push('/student/more/circles'),
              ),
              _MenuItem(
                icon: Icons.quiz,
                iconColor: AppColors.warning,
                title: 'الاختبارات',
                subtitle: 'اختباراتي ونتائجي',
                badge: '2',
                onTap: () => context.push('/student/more/quizzes'),
              ),
              _MenuItem(
                icon: Icons.person_search,
                iconColor: AppColors.primary,
                title: 'تصفح المعلمين',
                subtitle: 'ابحث عن معلم جديد',
                onTap: () => context.push('/student/more/teachers'),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.lg),

          _MenuSection(
            title: 'الحساب',
            items: [
              _MenuItem(
                icon: Icons.card_membership,
                iconColor: AppColors.accent,
                title: 'اشتراكاتي',
                subtitle: 'إدارة اشتراكاتي وباقاتي',
                onTap: () => context.push('/student/more/subscriptions'),
              ),
              _MenuItem(
                icon: Icons.payment,
                iconColor: AppColors.ongoing,
                title: 'المدفوعات',
                subtitle: 'سجل المدفوعات والفواتير',
                onTap: () => context.push('/student/more/payments'),
              ),
              _MenuItem(
                icon: Icons.workspace_premium,
                iconColor: AppColors.warning,
                title: 'الشهادات',
                subtitle: 'شهاداتي وإنجازاتي',
                onTap: () => context.push('/student/more/certificates'),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.lg),

          _MenuSection(
            title: 'أدوات',
            items: [
              _MenuItem(
                icon: Icons.calendar_month,
                iconColor: AppColors.scheduled,
                title: 'التقويم',
                subtitle: 'جدول جلساتي ومواعيدي',
                onTap: () => context.push('/student/more/calendar'),
              ),
              _MenuItem(
                icon: Icons.person,
                iconColor: AppColors.textSecondary,
                title: 'الملف الشخصي',
                subtitle: 'معلوماتي وإعداداتي',
                onTap: () => context.push('/student/more/profile'),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.xxl),
        ],
      ),
    );
  }
}

class _MenuSection extends StatelessWidget {
  final String title;
  final List<_MenuItem> items;

  const _MenuSection({required this.title, required this.items});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(title, style: AppTypography.titleSmall.copyWith(color: AppColors.textSecondary)),
        const SizedBox(height: AppSpacing.md),
        Container(
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: AppSpacing.borderRadiusMd,
            border: Border.all(color: AppColors.border),
          ),
          child: Column(
            children: items.asMap().entries.map((entry) {
              final index = entry.key;
              final item = entry.value;
              return Column(
                children: [
                  item,
                  if (index < items.length - 1)
                    const Divider(height: 1, indent: 56),
                ],
              );
            }).toList(),
          ),
        ),
      ],
    );
  }
}

class _MenuItem extends StatelessWidget {
  final IconData icon;
  final Color iconColor;
  final String title;
  final String subtitle;
  final String? badge;
  final VoidCallback onTap;

  const _MenuItem({
    required this.icon,
    required this.iconColor,
    required this.title,
    required this.subtitle,
    this.badge,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: Container(
        width: 40,
        height: 40,
        decoration: BoxDecoration(
          color: iconColor.withValues(alpha: 0.1),
          borderRadius: AppSpacing.borderRadiusSm,
        ),
        child: Icon(icon, color: iconColor, size: 20),
      ),
      title: Text(title, style: AppTypography.labelMedium),
      subtitle: Text(subtitle, style: AppTypography.caption.copyWith(color: AppColors.textSecondary)),
      trailing: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (badge != null)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: AppSpacing.sm, vertical: 2),
              decoration: BoxDecoration(
                color: AppColors.error,
                borderRadius: AppSpacing.borderRadiusFull,
              ),
              child: Text(badge!, style: AppTypography.labelSmall.copyWith(color: Colors.white, fontSize: 10)),
            ),
          const SizedBox(width: AppSpacing.sm),
          const Icon(Icons.arrow_forward_ios, size: 14, color: AppColors.textTertiary),
        ],
      ),
      onTap: onTap,
    );
  }
}
