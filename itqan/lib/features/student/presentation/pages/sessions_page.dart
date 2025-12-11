import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../../../core/theme/colors.dart';
import '../../../../core/theme/spacing.dart';
import '../../../../core/theme/typography.dart';
import '../../../../core/widgets/cards/session_card.dart';
import '../../../../core/widgets/badges/status_badge.dart';
import '../../../../mock/mock_data.dart';

/// Student sessions list page
class StudentSessionsPage extends StatefulWidget {
  const StudentSessionsPage({super.key});

  @override
  State<StudentSessionsPage> createState() => _StudentSessionsPageState();
}

class _StudentSessionsPageState extends State<StudentSessionsPage>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  String _selectedFilter = 'all';

  final List<String> _filters = ['all', 'scheduled', 'completed', 'cancelled'];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 4, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('الجلسات'),
        bottom: TabBar(
          controller: _tabController,
          tabs: const [
            Tab(text: 'الكل'),
            Tab(text: 'قرآن'),
            Tab(text: 'أكاديمي'),
            Tab(text: 'تفاعلي'),
          ],
        ),
      ),
      body: Column(
        children: [
          // Filter chips
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.all(AppSpacing.base),
            child: Row(
              children: _filters.map((filter) {
                final isSelected = _selectedFilter == filter;
                return Padding(
                  padding: const EdgeInsetsDirectional.only(end: AppSpacing.sm),
                  child: FilterChip(
                    label: Text(_getFilterLabel(filter)),
                    selected: isSelected,
                    onSelected: (selected) {
                      setState(() => _selectedFilter = filter);
                    },
                    backgroundColor: AppColors.surface,
                    selectedColor: AppColors.primary100,
                    checkmarkColor: AppColors.primary,
                    labelStyle: AppTypography.labelMedium.copyWith(
                      color: isSelected ? AppColors.primary : AppColors.textSecondary,
                    ),
                  ),
                );
              }).toList(),
            ),
          ),

          // Sessions list
          Expanded(
            child: TabBarView(
              controller: _tabController,
              children: [
                _buildSessionsList(null),
                _buildSessionsList('quran'),
                _buildSessionsList('academic'),
                _buildSessionsList('interactive'),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSessionsList(String? typeFilter) {
    var sessions = MockDataProvider.sessions;

    // Filter by type
    if (typeFilter != null) {
      sessions = sessions.where((s) => s.type == typeFilter).toList();
    }

    // Filter by status
    if (_selectedFilter != 'all') {
      sessions = sessions.where((s) => s.status == _selectedFilter).toList();
    }

    if (sessions.isEmpty) {
      return _buildEmptyState();
    }

    return ListView.separated(
      padding: AppSpacing.paddingScreen,
      itemCount: sessions.length,
      separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.md),
      itemBuilder: (context, index) {
        return SessionCard(
          session: sessions[index],
          onTap: () {
            context.push('/student/sessions/${sessions[index].id}');
          },
        );
      },
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            Icons.event_busy,
            size: 64,
            color: AppColors.textTertiary,
          ),
          const SizedBox(height: AppSpacing.base),
          Text(
            'لا توجد جلسات',
            style: AppTypography.titleMedium.copyWith(
              color: AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: AppSpacing.sm),
          Text(
            'لم يتم العثور على جلسات تطابق البحث',
            style: AppTypography.bodyMedium.copyWith(
              color: AppColors.textTertiary,
            ),
          ),
        ],
      ),
    );
  }

  String _getFilterLabel(String filter) {
    switch (filter) {
      case 'all':
        return 'الكل';
      case 'scheduled':
        return 'مجدولة';
      case 'completed':
        return 'مكتملة';
      case 'cancelled':
        return 'ملغية';
      default:
        return filter;
    }
  }
}
