import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../../../core/theme/colors.dart';
import '../../../../core/theme/spacing.dart';
import '../../../../core/theme/typography.dart';
import '../../../../core/widgets/badges/status_badge.dart';
import '../../../../core/widgets/cards/stat_card.dart';
import '../../../../mock/mock_data.dart';

class StudentHomeworkPage extends StatefulWidget {
  const StudentHomeworkPage({super.key});

  @override
  State<StudentHomeworkPage> createState() => _StudentHomeworkPageState();
}

class _StudentHomeworkPageState extends State<StudentHomeworkPage> {
  String _selectedFilter = 'all';

  @override
  Widget build(BuildContext context) {
    final homework = MockDataProvider.homework;
    final filtered = _selectedFilter == 'all'
        ? homework
        : homework.where((h) => h.status == _selectedFilter).toList();

    return Scaffold(
      appBar: AppBar(title: const Text('الواجبات')),
      body: Column(
        children: [
          // Stats
          Padding(
            padding: AppSpacing.paddingScreen,
            child: Row(
              children: [
                Expanded(child: CompactStatCard(
                  title: 'معلقة', value: '${homework.where((h) => h.status == 'pending').length}',
                  icon: Icons.hourglass_empty, accentColor: AppColors.warning,
                )),
                const SizedBox(width: AppSpacing.md),
                Expanded(child: CompactStatCard(
                  title: 'مكتملة', value: '${homework.where((h) => h.status == 'graded').length}',
                  icon: Icons.check_circle, accentColor: AppColors.ongoing,
                )),
              ],
            ),
          ),

          // Filters
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            padding: AppSpacing.paddingHorizontalBase,
            child: Row(
              children: ['all', 'pending', 'submitted', 'graded', 'overdue'].map((f) {
                final selected = _selectedFilter == f;
                return Padding(
                  padding: const EdgeInsetsDirectional.only(end: AppSpacing.sm),
                  child: FilterChip(
                    label: Text(_getFilterLabel(f)),
                    selected: selected,
                    onSelected: (_) => setState(() => _selectedFilter = f),
                  ),
                );
              }).toList(),
            ),
          ),
          const SizedBox(height: AppSpacing.md),

          // List
          Expanded(
            child: filtered.isEmpty
                ? Center(child: Text('لا توجد واجبات', style: AppTypography.bodyMedium))
                : ListView.separated(
                    padding: AppSpacing.paddingScreen,
                    itemCount: filtered.length,
                    separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.md),
                    itemBuilder: (context, index) {
                      final h = filtered[index];
                      return _HomeworkCard(homework: h, onTap: () => context.push('/student/homework/${h.id}'));
                    },
                  ),
          ),
        ],
      ),
    );
  }

  String _getFilterLabel(String f) => {'all': 'الكل', 'pending': 'معلقة', 'submitted': 'مسلمة', 'graded': 'مصححة', 'overdue': 'متأخرة'}[f] ?? f;
}

class _HomeworkCard extends StatelessWidget {
  final MockHomework homework;
  final VoidCallback onTap;

  const _HomeworkCard({required this.homework, required this.onTap});

  @override
  Widget build(BuildContext context) {
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
                TypeBadge(type: homework.type, isSmall: true),
                const Spacer(),
                StatusBadge(status: homework.status, isSmall: true),
              ],
            ),
            const SizedBox(height: AppSpacing.md),
            Text(homework.title, style: AppTypography.titleSmall),
            if (homework.description != null) ...[
              const SizedBox(height: 4),
              Text(homework.description!, style: AppTypography.caption, maxLines: 2),
            ],
            const SizedBox(height: AppSpacing.md),
            Row(
              children: [
                Icon(Icons.calendar_today, size: 14, color: AppColors.textTertiary),
                const SizedBox(width: 4),
                Text('التسليم: ${homework.dueDate.day}/${homework.dueDate.month}', style: AppTypography.caption),
                const Spacer(),
                if (homework.grade != null) ...[
                  Icon(Icons.grade, size: 14, color: AppColors.warning),
                  const SizedBox(width: 4),
                  Text('${homework.grade}/10', style: AppTypography.labelSmall.copyWith(color: AppColors.warning)),
                ],
              ],
            ),
          ],
        ),
      ),
    );
  }
}
