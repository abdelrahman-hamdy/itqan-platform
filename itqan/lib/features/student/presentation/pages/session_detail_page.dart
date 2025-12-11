import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../../../core/theme/colors.dart';
import '../../../../core/theme/spacing.dart';
import '../../../../core/theme/typography.dart';
import '../../../../core/widgets/badges/status_badge.dart';
import '../../../../core/widgets/buttons/itqan_primary_button.dart';
import '../../../../core/widgets/common/itqan_avatar.dart';
import '../../../../mock/mock_data.dart';

/// Session detail page
class StudentSessionDetailPage extends StatelessWidget {
  final String sessionId;

  const StudentSessionDetailPage({
    super.key,
    required this.sessionId,
  });

  @override
  Widget build(BuildContext context) {
    // Find session from mock data
    final session = MockDataProvider.sessions.firstWhere(
      (s) => s.id == sessionId,
      orElse: () => MockDataProvider.sessions.first,
    );

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios),
          onPressed: () => context.pop(),
        ),
        title: const Text('تفاصيل الجلسة'),
        actions: [
          IconButton(
            icon: const Icon(Icons.more_vert),
            onPressed: () {
              // TODO: Show options menu
            },
          ),
        ],
      ),
      body: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header with gradient
            _buildHeader(session),

            Padding(
              padding: AppSpacing.paddingScreen,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const SizedBox(height: AppSpacing.lg),

                  // Session info cards
                  _buildInfoCards(session),
                  const SizedBox(height: AppSpacing.xl),

                  // Teacher section
                  _buildTeacherSection(context, session),
                  const SizedBox(height: AppSpacing.xl),

                  // Session details
                  _buildSessionDetails(session),
                  const SizedBox(height: AppSpacing.xl),

                  // Homework section (if any)
                  if (session.status == 'completed')
                    _buildHomeworkSection(),
                  const SizedBox(height: AppSpacing.xxl),
                ],
              ),
            ),
          ],
        ),
      ),
      bottomNavigationBar: session.status == 'live' || session.status == 'scheduled'
          ? SafeArea(
              child: Padding(
                padding: AppSpacing.paddingScreen,
                child: ItqanPrimaryButton(
                  text: session.status == 'live' ? 'انضم الآن' : 'الجلسة تبدأ قريباً',
                  icon: Icons.videocam,
                  onPressed: session.status == 'live'
                      ? () {
                          // TODO: Join session
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('جاري الانضمام للجلسة...')),
                          );
                        }
                      : null,
                  gradient: session.status == 'live'
                      ? AppColors.successGradient
                      : null,
                ),
              ),
            )
          : null,
    );
  }

  Widget _buildHeader(MockSession session) {
    return Container(
      padding: const EdgeInsets.all(AppSpacing.lg),
      decoration: BoxDecoration(
        gradient: _getTypeGradient(session.type),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              TypeBadge(type: session.type),
              const SizedBox(width: AppSpacing.sm),
              StatusBadge(status: session.status),
            ],
          ),
          const SizedBox(height: AppSpacing.md),
          Text(
            session.title,
            style: AppTypography.textTheme.headlineSmall?.copyWith(
              color: Colors.white,
            ),
          ),
          const SizedBox(height: AppSpacing.sm),
          Row(
            children: [
              Icon(Icons.access_time, size: 16, color: Colors.white70),
              const SizedBox(width: 4),
              Text(
                _formatDateTime(session.scheduledAt),
                style: AppTypography.bodyMedium.copyWith(
                  color: Colors.white70,
                ),
              ),
              const SizedBox(width: AppSpacing.base),
              Icon(Icons.timer_outlined, size: 16, color: Colors.white70),
              const SizedBox(width: 4),
              Text(
                '${session.durationMinutes} دقيقة',
                style: AppTypography.bodyMedium.copyWith(
                  color: Colors.white70,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildInfoCards(MockSession session) {
    return Row(
      children: [
        Expanded(
          child: _InfoCard(
            icon: Icons.calendar_today,
            title: 'التاريخ',
            value: _formatDate(session.scheduledAt),
          ),
        ),
        const SizedBox(width: AppSpacing.md),
        Expanded(
          child: _InfoCard(
            icon: Icons.access_time,
            title: 'الوقت',
            value: _formatTime(session.scheduledAt),
          ),
        ),
        const SizedBox(width: AppSpacing.md),
        Expanded(
          child: _InfoCard(
            icon: Icons.timer,
            title: 'المدة',
            value: '${session.durationMinutes} د',
          ),
        ),
      ],
    );
  }

  Widget _buildTeacherSection(BuildContext context, MockSession session) {
    final teacher = session.teacher;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'المعلم',
          style: AppTypography.titleMedium,
        ),
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
              ItqanAvatar(
                name: teacher.fullName,
                imageUrl: teacher.avatarUrl,
                size: 56,
              ),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      teacher.fullName,
                      style: AppTypography.titleSmall,
                    ),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        Icon(Icons.star, size: 14, color: AppColors.warning),
                        const SizedBox(width: 4),
                        Text(
                          '${teacher.rating} (${teacher.reviewCount} تقييم)',
                          style: AppTypography.caption,
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      teacher.subjects.join(' • '),
                      style: AppTypography.caption.copyWith(
                        color: AppColors.textTertiary,
                      ),
                    ),
                  ],
                ),
              ),
              IconButton(
                icon: const Icon(Icons.arrow_forward_ios, size: 16),
                onPressed: () {
                  context.push('/student/teachers/${teacher.id}');
                },
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildSessionDetails(MockSession session) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'تفاصيل الجلسة',
          style: AppTypography.titleMedium,
        ),
        const SizedBox(height: AppSpacing.md),
        Container(
          padding: AppSpacing.paddingCard,
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: AppSpacing.borderRadiusMd,
            border: Border.all(color: AppColors.border),
          ),
          child: Column(
            children: [
              _DetailRow(
                label: 'نوع الجلسة',
                value: _getTypeName(session.type),
              ),
              const Divider(),
              _DetailRow(
                label: 'الصيغة',
                value: session.format == 'individual' ? 'فردية' : 'جماعية',
              ),
              const Divider(),
              _DetailRow(
                label: 'الحالة',
                value: _getStatusName(session.status),
              ),
              if (session.notes != null) ...[
                const Divider(),
                _DetailRow(
                  label: 'ملاحظات',
                  value: session.notes!,
                ),
              ],
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildHomeworkSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'الواجب',
          style: AppTypography.titleMedium,
        ),
        const SizedBox(height: AppSpacing.md),
        Container(
          padding: AppSpacing.paddingCard,
          decoration: BoxDecoration(
            color: AppColors.warningLight,
            borderRadius: AppSpacing.borderRadiusMd,
            border: Border.all(color: AppColors.warning.withValues(alpha: 0.3)),
          ),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(AppSpacing.sm),
                decoration: BoxDecoration(
                  color: AppColors.warning.withValues(alpha: 0.2),
                  borderRadius: AppSpacing.borderRadiusSm,
                ),
                child: Icon(
                  Icons.assignment,
                  color: AppColors.warningDark,
                ),
              ),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'واجب مرتبط بالجلسة',
                      style: AppTypography.titleSmall,
                    ),
                    Text(
                      'التسليم: غداً',
                      style: AppTypography.caption,
                    ),
                  ],
                ),
              ),
              Icon(
                Icons.arrow_forward_ios,
                size: 16,
                color: AppColors.textTertiary,
              ),
            ],
          ),
        ),
      ],
    );
  }

  LinearGradient _getTypeGradient(String type) {
    switch (type) {
      case 'quran':
        return AppColors.quranGradient;
      case 'academic':
        return AppColors.academicGradient;
      case 'interactive':
        return AppColors.interactiveGradient;
      default:
        return AppColors.primaryGradient;
    }
  }

  String _formatDateTime(DateTime date) {
    return '${_formatDate(date)} - ${_formatTime(date)}';
  }

  String _formatDate(DateTime date) {
    return '${date.day}/${date.month}/${date.year}';
  }

  String _formatTime(DateTime date) {
    final hour = date.hour > 12 ? date.hour - 12 : date.hour;
    final period = date.hour >= 12 ? 'م' : 'ص';
    return '$hour:${date.minute.toString().padLeft(2, '0')} $period';
  }

  String _getTypeName(String type) {
    switch (type) {
      case 'quran':
        return 'قرآن';
      case 'academic':
        return 'أكاديمي';
      case 'interactive':
        return 'تفاعلي';
      default:
        return type;
    }
  }

  String _getStatusName(String status) {
    switch (status) {
      case 'scheduled':
        return 'مجدولة';
      case 'live':
        return 'مباشرة';
      case 'completed':
        return 'مكتملة';
      case 'cancelled':
        return 'ملغية';
      default:
        return status;
    }
  }
}

class _InfoCard extends StatelessWidget {
  final IconData icon;
  final String title;
  final String value;

  const _InfoCard({
    required this.icon,
    required this.title,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(AppSpacing.md),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: AppSpacing.borderRadiusMd,
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        children: [
          Icon(icon, color: AppColors.primary, size: 20),
          const SizedBox(height: AppSpacing.sm),
          Text(title, style: AppTypography.caption),
          const SizedBox(height: 4),
          Text(
            value,
            style: AppTypography.labelLarge,
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;

  const _DetailRow({
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: AppSpacing.sm),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: AppTypography.bodyMedium),
          Text(
            value,
            style: AppTypography.bodyMedium.copyWith(
              color: AppColors.textPrimary,
              fontWeight: FontWeight.w500,
            ),
          ),
        ],
      ),
    );
  }
}
