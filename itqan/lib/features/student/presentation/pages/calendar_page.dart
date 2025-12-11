import 'package:flutter/material.dart';
import 'package:table_calendar/table_calendar.dart';
import '../../../../core/theme/colors.dart';
import '../../../../core/theme/spacing.dart';
import '../../../../core/theme/typography.dart';
import '../../../../core/widgets/badges/status_badge.dart';
import '../../../../mock/mock_data.dart';

class StudentCalendarPage extends StatefulWidget {
  const StudentCalendarPage({super.key});

  @override
  State<StudentCalendarPage> createState() => _StudentCalendarPageState();
}

class _StudentCalendarPageState extends State<StudentCalendarPage> {
  CalendarFormat _calendarFormat = CalendarFormat.month;
  DateTime _focusedDay = DateTime.now();
  DateTime? _selectedDay;

  List<_CalendarEvent> _getEventsForDay(DateTime day) {
    final events = <_CalendarEvent>[];

    // Add sessions
    for (final session in MockDataProvider.sessions) {
      if (isSameDay(session.scheduledAt, day)) {
        events.add(_CalendarEvent(
          title: session.title,
          type: 'session',
          sessionType: session.type,
          time: '${session.scheduledAt.hour}:${session.scheduledAt.minute.toString().padLeft(2, '0')}',
          status: session.status,
        ));
      }
    }

    // Add homework due dates
    for (final homework in MockDataProvider.homework) {
      if (isSameDay(homework.dueDate, day)) {
        events.add(_CalendarEvent(
          title: homework.title,
          type: 'homework',
          sessionType: homework.type,
          time: 'موعد التسليم',
          status: homework.status,
        ));
      }
    }

    return events;
  }

  @override
  Widget build(BuildContext context) {
    final selectedEvents = _selectedDay != null ? _getEventsForDay(_selectedDay!) : <_CalendarEvent>[];

    return Scaffold(
      appBar: AppBar(title: const Text('التقويم')),
      body: Column(
        children: [
          // Calendar
          Container(
            margin: AppSpacing.paddingHorizontalBase,
            decoration: BoxDecoration(
              color: AppColors.surface,
              borderRadius: AppSpacing.borderRadiusMd,
              border: Border.all(color: AppColors.border),
            ),
            child: TableCalendar<_CalendarEvent>(
              locale: 'ar',
              firstDay: DateTime.now().subtract(const Duration(days: 365)),
              lastDay: DateTime.now().add(const Duration(days: 365)),
              focusedDay: _focusedDay,
              calendarFormat: _calendarFormat,
              selectedDayPredicate: (day) => isSameDay(_selectedDay, day),
              eventLoader: _getEventsForDay,
              startingDayOfWeek: StartingDayOfWeek.saturday,
              calendarStyle: CalendarStyle(
                outsideDaysVisible: false,
                weekendTextStyle: AppTypography.bodyMedium,
                defaultTextStyle: AppTypography.bodyMedium,
                todayDecoration: BoxDecoration(
                  color: AppColors.primary.withValues(alpha: 0.2),
                  shape: BoxShape.circle,
                ),
                todayTextStyle: AppTypography.bodyMedium.copyWith(color: AppColors.primary),
                selectedDecoration: const BoxDecoration(
                  color: AppColors.primary,
                  shape: BoxShape.circle,
                ),
                selectedTextStyle: AppTypography.bodyMedium.copyWith(color: Colors.white),
                markerDecoration: const BoxDecoration(
                  color: AppColors.accent,
                  shape: BoxShape.circle,
                ),
                markersMaxCount: 3,
                markerSize: 6,
                markerMargin: const EdgeInsets.symmetric(horizontal: 1),
              ),
              headerStyle: HeaderStyle(
                formatButtonVisible: true,
                titleCentered: true,
                formatButtonShowsNext: false,
                formatButtonDecoration: BoxDecoration(
                  border: Border.all(color: AppColors.border),
                  borderRadius: AppSpacing.borderRadiusSm,
                ),
                titleTextStyle: AppTypography.titleSmall,
              ),
              onDaySelected: (selectedDay, focusedDay) {
                setState(() {
                  _selectedDay = selectedDay;
                  _focusedDay = focusedDay;
                });
              },
              onFormatChanged: (format) {
                setState(() {
                  _calendarFormat = format;
                });
              },
              onPageChanged: (focusedDay) {
                _focusedDay = focusedDay;
              },
            ),
          ),
          const SizedBox(height: AppSpacing.md),

          // Events List
          Expanded(
            child: selectedEvents.isEmpty
                ? Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.event_available, size: 48, color: AppColors.textTertiary),
                        const SizedBox(height: AppSpacing.md),
                        Text(
                          _selectedDay == null ? 'اختر يوماً لعرض الأحداث' : 'لا توجد أحداث في هذا اليوم',
                          style: AppTypography.bodyMedium.copyWith(color: AppColors.textSecondary),
                        ),
                      ],
                    ),
                  )
                : ListView.separated(
                    padding: AppSpacing.paddingScreen,
                    itemCount: selectedEvents.length,
                    separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.md),
                    itemBuilder: (context, index) {
                      final event = selectedEvents[index];
                      return _EventCard(event: event);
                    },
                  ),
          ),
        ],
      ),
    );
  }
}

class _CalendarEvent {
  final String title;
  final String type; // session, homework, quiz
  final String sessionType; // quran, academic, interactive
  final String time;
  final String status;

  _CalendarEvent({
    required this.title,
    required this.type,
    required this.sessionType,
    required this.time,
    required this.status,
  });
}

class _EventCard extends StatelessWidget {
  final _CalendarEvent event;

  const _EventCard({required this.event});

  Color get _typeColor {
    switch (event.sessionType) {
      case 'quran':
        return AppColors.quranColor;
      case 'academic':
        return AppColors.academicColor;
      case 'interactive':
        return AppColors.interactiveColor;
      default:
        return AppColors.primary;
    }
  }

  IconData get _typeIcon {
    switch (event.type) {
      case 'session':
        return Icons.videocam;
      case 'homework':
        return Icons.assignment;
      case 'quiz':
        return Icons.quiz;
      default:
        return Icons.event;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: AppSpacing.paddingCard,
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: AppSpacing.borderRadiusMd,
        border: Border.all(color: AppColors.border),
        boxShadow: AppShadows.sm,
      ),
      child: Row(
        children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              color: _typeColor.withValues(alpha: 0.1),
              borderRadius: AppSpacing.borderRadiusSm,
            ),
            child: Icon(_typeIcon, color: _typeColor),
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(event.title, style: AppTypography.titleSmall, maxLines: 1, overflow: TextOverflow.ellipsis),
                const SizedBox(height: 4),
                Row(
                  children: [
                    Icon(Icons.access_time, size: 14, color: AppColors.textTertiary),
                    const SizedBox(width: 4),
                    Text(event.time, style: AppTypography.caption),
                  ],
                ),
              ],
            ),
          ),
          StatusBadge(status: event.status, isSmall: true),
        ],
      ),
    );
  }
}
