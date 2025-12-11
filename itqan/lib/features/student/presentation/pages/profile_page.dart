import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../../../core/theme/colors.dart';
import '../../../../core/theme/spacing.dart';
import '../../../../core/theme/typography.dart';
import '../../../../core/widgets/common/itqan_avatar.dart';
import '../../../../mock/mock_data.dart';

class StudentProfilePage extends StatelessWidget {
  const StudentProfilePage({super.key});

  @override
  Widget build(BuildContext context) {
    final user = MockDataProvider.currentStudent;
    final stats = MockDataProvider.studentStats;

    return Scaffold(
      appBar: AppBar(
        title: const Text('الملف الشخصي'),
        actions: [
          IconButton(
            icon: const Icon(Icons.edit),
            onPressed: () {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('سيتم إضافة تعديل الملف قريباً')),
              );
            },
          ),
        ],
      ),
      body: SingleChildScrollView(
        child: Column(
          children: [
            // Profile Header
            Container(
              width: double.infinity,
              padding: AppSpacing.paddingScreenAll,
              decoration: BoxDecoration(
                gradient: AppColors.primaryGradient,
              ),
              child: Column(
                children: [
                  ItqanAvatar(name: user.fullName, size: 80),
                  const SizedBox(height: AppSpacing.md),
                  Text(
                    user.fullName,
                    style: AppTypography.headlineSmall.copyWith(color: Colors.white),
                  ),
                  const SizedBox(height: AppSpacing.xs),
                  Text(
                    user.email,
                    style: AppTypography.bodyMedium.copyWith(color: Colors.white70),
                  ),
                ],
              ),
            ),

            // Stats Row
            Container(
              margin: AppSpacing.paddingScreen,
              padding: AppSpacing.paddingCard,
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: AppSpacing.borderRadiusMd,
                border: Border.all(color: AppColors.border),
                boxShadow: AppShadows.sm,
              ),
              child: Row(
                children: [
                  Expanded(
                    child: _StatItem(
                      value: '${stats['completedSessions']}',
                      label: 'جلسة مكتملة',
                      icon: Icons.check_circle,
                      color: AppColors.ongoing,
                    ),
                  ),
                  Container(width: 1, height: 50, color: AppColors.divider),
                  Expanded(
                    child: _StatItem(
                      value: '${stats['attendanceRate']}%',
                      label: 'نسبة الحضور',
                      icon: Icons.pie_chart,
                      color: AppColors.primary,
                    ),
                  ),
                  Container(width: 1, height: 50, color: AppColors.divider),
                  Expanded(
                    child: _StatItem(
                      value: '${MockDataProvider.certificates.length}',
                      label: 'شهادة',
                      icon: Icons.workspace_premium,
                      color: AppColors.warning,
                    ),
                  ),
                ],
              ),
            ),

            // Settings Sections
            Padding(
              padding: AppSpacing.paddingHorizontalBase,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('الإعدادات', style: AppTypography.titleSmall),
                  const SizedBox(height: AppSpacing.md),
                  _SettingsSection(
                    items: [
                      _SettingsItem(
                        icon: Icons.person_outline,
                        title: 'المعلومات الشخصية',
                        onTap: () {},
                      ),
                      _SettingsItem(
                        icon: Icons.notifications_outlined,
                        title: 'الإشعارات',
                        onTap: () {},
                      ),
                      _SettingsItem(
                        icon: Icons.lock_outline,
                        title: 'الأمان وكلمة المرور',
                        onTap: () {},
                      ),
                    ],
                  ),
                  const SizedBox(height: AppSpacing.lg),

                  Text('التطبيق', style: AppTypography.titleSmall),
                  const SizedBox(height: AppSpacing.md),
                  _SettingsSection(
                    items: [
                      _SettingsItem(
                        icon: Icons.language,
                        title: 'اللغة',
                        trailing: const Text('العربية'),
                        onTap: () {},
                      ),
                      _SettingsItem(
                        icon: Icons.dark_mode_outlined,
                        title: 'الوضع الليلي',
                        trailing: Switch(
                          value: false,
                          onChanged: (_) {},
                        ),
                        onTap: () {},
                      ),
                    ],
                  ),
                  const SizedBox(height: AppSpacing.lg),

                  Text('الدعم', style: AppTypography.titleSmall),
                  const SizedBox(height: AppSpacing.md),
                  _SettingsSection(
                    items: [
                      _SettingsItem(
                        icon: Icons.help_outline,
                        title: 'المساعدة',
                        onTap: () {},
                      ),
                      _SettingsItem(
                        icon: Icons.info_outline,
                        title: 'عن التطبيق',
                        onTap: () {},
                      ),
                      _SettingsItem(
                        icon: Icons.description_outlined,
                        title: 'الشروط والأحكام',
                        onTap: () {},
                      ),
                    ],
                  ),
                  const SizedBox(height: AppSpacing.xl),

                  // Logout Button
                  SizedBox(
                    width: double.infinity,
                    child: OutlinedButton.icon(
                      onPressed: () => _showLogoutDialog(context),
                      icon: const Icon(Icons.logout, color: AppColors.error),
                      label: Text(
                        'تسجيل الخروج',
                        style: TextStyle(color: AppColors.error),
                      ),
                      style: OutlinedButton.styleFrom(
                        side: const BorderSide(color: AppColors.error),
                        padding: AppSpacing.paddingButton,
                      ),
                    ),
                  ),
                  const SizedBox(height: AppSpacing.xl),

                  // App Version
                  Center(
                    child: Text(
                      'الإصدار 1.0.0',
                      style: AppTypography.caption.copyWith(color: AppColors.textTertiary),
                    ),
                  ),
                  const SizedBox(height: AppSpacing.xxl),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showLogoutDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('تسجيل الخروج'),
        content: const Text('هل أنت متأكد من رغبتك في تسجيل الخروج؟'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('إلغاء'),
          ),
          TextButton(
            onPressed: () {
              Navigator.pop(context);
              context.go('/');
            },
            child: Text('تسجيل الخروج', style: TextStyle(color: AppColors.error)),
          ),
        ],
      ),
    );
  }
}

class _StatItem extends StatelessWidget {
  final String value;
  final String label;
  final IconData icon;
  final Color color;

  const _StatItem({
    required this.value,
    required this.label,
    required this.icon,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Icon(icon, color: color, size: 24),
        const SizedBox(height: AppSpacing.sm),
        Text(value, style: AppTypography.titleMedium),
        Text(label, style: AppTypography.caption.copyWith(color: AppColors.textSecondary)),
      ],
    );
  }
}

class _SettingsSection extends StatelessWidget {
  final List<_SettingsItem> items;

  const _SettingsSection({required this.items});

  @override
  Widget build(BuildContext context) {
    return Container(
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
    );
  }
}

class _SettingsItem extends StatelessWidget {
  final IconData icon;
  final String title;
  final Widget? trailing;
  final VoidCallback onTap;

  const _SettingsItem({
    required this.icon,
    required this.title,
    this.trailing,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: Icon(icon, color: AppColors.textSecondary),
      title: Text(title, style: AppTypography.bodyMedium),
      trailing: trailing ?? const Icon(Icons.arrow_forward_ios, size: 16, color: AppColors.textTertiary),
      onTap: onTap,
    );
  }
}
