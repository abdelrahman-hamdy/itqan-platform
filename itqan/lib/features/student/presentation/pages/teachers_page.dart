import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../../../core/theme/colors.dart';
import '../../../../core/theme/spacing.dart';
import '../../../../core/theme/typography.dart';
import '../../../../core/widgets/badges/status_badge.dart';
import '../../../../core/widgets/common/itqan_avatar.dart';
import '../../../../mock/mock_data.dart';

class StudentTeachersPage extends StatefulWidget {
  const StudentTeachersPage({super.key});

  @override
  State<StudentTeachersPage> createState() => _StudentTeachersPageState();
}

class _StudentTeachersPageState extends State<StudentTeachersPage> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  String _searchQuery = '';

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final teachers = MockDataProvider.teachers;
    final quranTeachers = teachers.where((t) => t.type == 'quran').where((t) =>
        _searchQuery.isEmpty || t.fullName.contains(_searchQuery)).toList();
    final academicTeachers = teachers.where((t) => t.type == 'academic').where((t) =>
        _searchQuery.isEmpty || t.fullName.contains(_searchQuery)).toList();

    return Scaffold(
      appBar: AppBar(
        title: const Text('المعلمون'),
        bottom: TabBar(
          controller: _tabController,
          tabs: const [
            Tab(text: 'معلمو القرآن'),
            Tab(text: 'المعلمون الأكاديميون'),
          ],
        ),
      ),
      body: Column(
        children: [
          // Search Bar
          Padding(
            padding: AppSpacing.paddingScreen,
            child: TextField(
              onChanged: (value) => setState(() => _searchQuery = value),
              decoration: InputDecoration(
                hintText: 'ابحث عن معلم...',
                prefixIcon: const Icon(Icons.search),
                suffixIcon: _searchQuery.isNotEmpty
                    ? IconButton(
                        icon: const Icon(Icons.clear),
                        onPressed: () => setState(() => _searchQuery = ''),
                      )
                    : null,
              ),
            ),
          ),

          // Teachers List
          Expanded(
            child: TabBarView(
              controller: _tabController,
              children: [
                // Quran Teachers
                _buildTeachersList(quranTeachers),
                // Academic Teachers
                _buildTeachersList(academicTeachers),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTeachersList(List<MockTeacher> teachers) {
    if (teachers.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.person_search, size: 64, color: AppColors.textTertiary),
            const SizedBox(height: AppSpacing.md),
            Text(
              'لا يوجد معلمون',
              style: AppTypography.bodyMedium.copyWith(color: AppColors.textSecondary),
            ),
          ],
        ),
      );
    }

    return ListView.separated(
      padding: AppSpacing.paddingScreen,
      itemCount: teachers.length,
      separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.md),
      itemBuilder: (context, index) {
        final teacher = teachers[index];
        return _TeacherCard(
          teacher: teacher,
          onTap: () => context.push('/student/more/teachers/${teacher.id}'),
        );
      },
    );
  }
}

class _TeacherCard extends StatelessWidget {
  final MockTeacher teacher;
  final VoidCallback onTap;

  const _TeacherCard({required this.teacher, required this.onTap});

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
          boxShadow: AppShadows.sm,
        ),
        child: Column(
          children: [
            Row(
              children: [
                ItqanAvatar(name: teacher.fullName, size: 56),
                const SizedBox(width: AppSpacing.md),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(teacher.fullName, style: AppTypography.titleSmall),
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          RatingBadge(rating: teacher.rating, reviewCount: teacher.reviewCount),
                          const SizedBox(width: AppSpacing.md),
                          Icon(Icons.work_outline, size: 14, color: AppColors.textTertiary),
                          const SizedBox(width: 4),
                          Text(
                            '${teacher.experience} سنوات',
                            style: AppTypography.caption,
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md, vertical: AppSpacing.sm),
                  decoration: BoxDecoration(
                    color: AppColors.primary.withValues(alpha: 0.1),
                    borderRadius: AppSpacing.borderRadiusFull,
                  ),
                  child: Text(
                    '${teacher.sessionPrice.toInt()} ر.س',
                    style: AppTypography.labelSmall.copyWith(color: AppColors.primary, fontWeight: FontWeight.bold),
                  ),
                ),
              ],
            ),
            const SizedBox(height: AppSpacing.md),

            // Subjects
            Wrap(
              spacing: AppSpacing.sm,
              runSpacing: AppSpacing.sm,
              children: teacher.subjects.map((subject) => Container(
                padding: const EdgeInsets.symmetric(horizontal: AppSpacing.sm, vertical: AppSpacing.xs),
                decoration: BoxDecoration(
                  color: AppColors.surfaceVariant,
                  borderRadius: AppSpacing.borderRadiusFull,
                ),
                child: Text(subject, style: AppTypography.caption),
              )).toList(),
            ),

            if (teacher.bio != null) ...[
              const SizedBox(height: AppSpacing.md),
              Text(
                teacher.bio!,
                style: AppTypography.bodySmall.copyWith(color: AppColors.textSecondary),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
            ],
          ],
        ),
      ),
    );
  }
}
