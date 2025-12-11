import 'package:flutter/material.dart';
import '../../../../core/theme/colors.dart';
import '../../../../core/theme/spacing.dart';
import '../../../../core/theme/typography.dart';
import '../../../../mock/mock_data.dart';

class StudentCertificatesPage extends StatelessWidget {
  const StudentCertificatesPage({super.key});

  @override
  Widget build(BuildContext context) {
    final certificates = MockDataProvider.certificates;

    return Scaffold(
      appBar: AppBar(title: const Text('الشهادات')),
      body: certificates.isEmpty
          ? Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.workspace_premium_outlined, size: 64, color: AppColors.textTertiary),
                  const SizedBox(height: AppSpacing.md),
                  Text(
                    'لم تحصل على شهادات بعد',
                    style: AppTypography.bodyMedium.copyWith(color: AppColors.textSecondary),
                  ),
                  const SizedBox(height: AppSpacing.sm),
                  Text(
                    'أكمل دوراتك للحصول على شهادات',
                    style: AppTypography.caption.copyWith(color: AppColors.textTertiary),
                  ),
                ],
              ),
            )
          : ListView.separated(
              padding: AppSpacing.paddingScreen,
              itemCount: certificates.length,
              separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.md),
              itemBuilder: (context, index) {
                final cert = certificates[index];
                return _CertificateCard(certificate: cert);
              },
            ),
    );
  }
}

class _CertificateCard extends StatelessWidget {
  final MockCertificate certificate;

  const _CertificateCard({required this.certificate});

  Color get _typeColor {
    switch (certificate.type) {
      case 'quran':
        return AppColors.quranColor;
      case 'academic':
        return AppColors.academicColor;
      case 'course':
        return AppColors.interactiveColor;
      default:
        return AppColors.primary;
    }
  }

  String get _typeLabel {
    switch (certificate.type) {
      case 'quran':
        return 'قرآن كريم';
      case 'academic':
        return 'أكاديمي';
      case 'course':
        return 'دورة';
      default:
        return certificate.type;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: AppSpacing.borderRadiusMd,
        border: Border.all(color: AppColors.border),
        boxShadow: AppShadows.sm,
      ),
      child: Column(
        children: [
          // Certificate Preview
          Container(
            height: 120,
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [
                  _typeColor.withValues(alpha: 0.1),
                  _typeColor.withValues(alpha: 0.05),
                ],
              ),
              borderRadius: const BorderRadius.only(
                topLeft: Radius.circular(11),
                topRight: Radius.circular(11),
              ),
            ),
            child: Stack(
              children: [
                // Decorative pattern
                Positioned(
                  right: -20,
                  top: -20,
                  child: Container(
                    width: 100,
                    height: 100,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      border: Border.all(color: _typeColor.withValues(alpha: 0.1), width: 20),
                    ),
                  ),
                ),
                Positioned(
                  left: -30,
                  bottom: -30,
                  child: Container(
                    width: 80,
                    height: 80,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      border: Border.all(color: _typeColor.withValues(alpha: 0.1), width: 15),
                    ),
                  ),
                ),
                // Certificate Icon
                Center(
                  child: Container(
                    width: 60,
                    height: 60,
                    decoration: BoxDecoration(
                      color: _typeColor.withValues(alpha: 0.1),
                      shape: BoxShape.circle,
                    ),
                    child: Icon(
                      Icons.workspace_premium,
                      size: 32,
                      color: _typeColor,
                    ),
                  ),
                ),
              ],
            ),
          ),
          // Certificate Info
          Padding(
            padding: AppSpacing.paddingCard,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: AppSpacing.sm, vertical: AppSpacing.xs),
                      decoration: BoxDecoration(
                        color: _typeColor.withValues(alpha: 0.1),
                        borderRadius: AppSpacing.borderRadiusFull,
                      ),
                      child: Text(
                        _typeLabel,
                        style: AppTypography.labelSmall.copyWith(color: _typeColor),
                      ),
                    ),
                    const Spacer(),
                    Text(
                      '${certificate.issuedAt.day}/${certificate.issuedAt.month}/${certificate.issuedAt.year}',
                      style: AppTypography.caption.copyWith(color: AppColors.textTertiary),
                    ),
                  ],
                ),
                const SizedBox(height: AppSpacing.md),
                Text(certificate.title, style: AppTypography.titleSmall),
                const SizedBox(height: AppSpacing.sm),
                Row(
                  children: [
                    Icon(Icons.tag, size: 14, color: AppColors.textTertiary),
                    const SizedBox(width: 4),
                    Text(
                      certificate.certificateNumber,
                      style: AppTypography.caption.copyWith(color: AppColors.textSecondary),
                    ),
                  ],
                ),
                const SizedBox(height: AppSpacing.md),
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: () {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('سيتم عرض الشهادة قريباً')),
                          );
                        },
                        icon: const Icon(Icons.visibility, size: 18),
                        label: const Text('عرض'),
                      ),
                    ),
                    const SizedBox(width: AppSpacing.md),
                    Expanded(
                      child: ElevatedButton.icon(
                        onPressed: () {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('سيتم تحميل الشهادة قريباً')),
                          );
                        },
                        icon: const Icon(Icons.download, size: 18),
                        label: const Text('تحميل'),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
