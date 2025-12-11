import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../../../core/theme/colors.dart';
import '../../../../core/theme/spacing.dart';
import '../../../../core/theme/typography.dart';
import '../../../../core/router/routes.dart';

/// Role selection page - choose between Student, Teacher, Parent
class RoleSelectionPage extends StatelessWidget {
  const RoleSelectionPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: AppSpacing.paddingScreenAll,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const SizedBox(height: AppSpacing.xxl),

              // Header
              Text(
                'مرحباً بك في إتقان',
                style: AppTypography.textTheme.headlineMedium,
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: AppSpacing.sm),
              Text(
                'اختر نوع حسابك للمتابعة',
                style: AppTypography.textTheme.bodyLarge?.copyWith(
                  color: AppColors.textSecondary,
                ),
                textAlign: TextAlign.center,
              ),

              const SizedBox(height: AppSpacing.xxxl),

              // Role cards
              Expanded(
                child: Column(
                  children: [
                    // Student
                    _RoleCard(
                      title: 'طالب',
                      subtitle: 'تعلم القرآن والعلوم الأكاديمية',
                      icon: Icons.school,
                      gradient: AppColors.primaryGradient,
                      onTap: () {
                        // Bypass login, go directly to student home
                        context.go(AppRoutes.studentHome);
                      },
                    ),
                    const SizedBox(height: AppSpacing.base),

                    // Teacher
                    _RoleCard(
                      title: 'معلم',
                      subtitle: 'إدارة الحلقات والدروس',
                      icon: Icons.person,
                      gradient: AppColors.successGradient,
                      onTap: () {
                        // Bypass login, go directly to teacher home
                        context.go(AppRoutes.teacherHome);
                      },
                    ),
                    const SizedBox(height: AppSpacing.base),

                    // Parent
                    _RoleCard(
                      title: 'ولي أمر',
                      subtitle: 'متابعة تقدم الأبناء',
                      icon: Icons.family_restroom,
                      gradient: AppColors.violetGradient,
                      onTap: () {
                        // Bypass login, go directly to parent home
                        context.go(AppRoutes.parentHome);
                      },
                    ),
                  ],
                ),
              ),

              // Login link
              Center(
                child: TextButton(
                  onPressed: () {
                    context.push(AppRoutes.login);
                  },
                  child: Text(
                    'لديك حساب؟ تسجيل الدخول',
                    style: AppTypography.link,
                  ),
                ),
              ),
              const SizedBox(height: AppSpacing.base),
            ],
          ),
        ),
      ),
    );
  }
}

class _RoleCard extends StatelessWidget {
  final String title;
  final String subtitle;
  final IconData icon;
  final LinearGradient gradient;
  final VoidCallback onTap;

  const _RoleCard({
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.gradient,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(AppSpacing.lg),
        decoration: BoxDecoration(
          gradient: gradient,
          borderRadius: AppSpacing.borderRadiusLg,
          boxShadow: AppSpacing.shadowMd,
        ),
        child: Row(
          children: [
            Container(
              width: 56,
              height: 56,
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.2),
                borderRadius: AppSpacing.borderRadiusMd,
              ),
              child: Icon(
                icon,
                color: Colors.white,
                size: 28,
              ),
            ),
            const SizedBox(width: AppSpacing.base),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: AppTypography.textTheme.titleLarge?.copyWith(
                      color: Colors.white,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    subtitle,
                    style: AppTypography.textTheme.bodyMedium?.copyWith(
                      color: Colors.white.withValues(alpha: 0.9),
                    ),
                  ),
                ],
              ),
            ),
            Icon(
              Icons.arrow_forward_ios,
              color: Colors.white.withValues(alpha: 0.7),
              size: 20,
            ),
          ],
        ),
      ),
    );
  }
}
