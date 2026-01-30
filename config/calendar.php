<?php

/**
 * Calendar Configuration
 *
 * Centralized configuration for the unified calendar system.
 * These values control the FullCalendar widget display and behavior.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Time Display Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the visible time range in calendar views.
    | Times are in 24-hour format (HH:MM).
    |
    */
    'time_slice' => [
        'start' => env('CALENDAR_SLICE_START', '06:00'),
        'end' => env('CALENDAR_SLICE_END', '23:00'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Business Hours
    |--------------------------------------------------------------------------
    |
    | Define the working hours highlighted in the calendar.
    | Sessions scheduled outside these hours will appear differently.
    |
    */
    'business_hours' => [
        'start' => env('CALENDAR_BUSINESS_START', '08:00'),
        'end' => env('CALENDAR_BUSINESS_END', '22:00'),
        'days_of_week' => [6, 0, 1, 2, 3, 4, 5], // Sat-Fri (Arabic week)
    ],

    /*
    |--------------------------------------------------------------------------
    | Week Configuration
    |--------------------------------------------------------------------------
    |
    | first_day: 0 = Sunday, 1 = Monday, ..., 6 = Saturday
    | For Arabic calendars, Saturday (6) is typically the first day.
    |
    */
    'first_day' => env('CALENDAR_FIRST_DAY', 6),
    'show_week_numbers' => env('CALENDAR_SHOW_WEEK_NUMBERS', true),
    'show_weekends' => env('CALENDAR_SHOW_WEEKENDS', true),

    /*
    |--------------------------------------------------------------------------
    | Time Slot Configuration
    |--------------------------------------------------------------------------
    |
    | slot_duration: Duration of each time slot (HH:MM:SS format)
    | scroll_time: Initial scroll position when calendar loads
    |
    */
    'slot_duration' => env('CALENDAR_SLOT_DURATION', '00:30:00'),
    'scroll_time' => env('CALENDAR_SCROLL_TIME', '08:00:00'),

    /*
    |--------------------------------------------------------------------------
    | Default Timezone
    |--------------------------------------------------------------------------
    |
    | Fallback timezone if academy timezone is not set.
    | Should match the primary user base timezone.
    |
    */
    'default_timezone' => env('CALENDAR_TIMEZONE', 'Asia/Riyadh'),

    /*
    |--------------------------------------------------------------------------
    | Session Duration Defaults
    |--------------------------------------------------------------------------
    |
    | Default session durations in minutes for different session types.
    | Used when package/subscription doesn't specify duration.
    |
    */
    'default_durations' => [
        'quran_individual' => env('CALENDAR_DEFAULT_QURAN_INDIVIDUAL_DURATION', 45),
        'quran_group' => env('CALENDAR_DEFAULT_QURAN_GROUP_DURATION', 60),
        'quran_trial' => env('CALENDAR_DEFAULT_QURAN_TRIAL_DURATION', 30),
        'academic_private' => env('CALENDAR_DEFAULT_ACADEMIC_DURATION', 60),
        'interactive_course' => env('CALENDAR_DEFAULT_COURSE_DURATION', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Generation Limits
    |--------------------------------------------------------------------------
    |
    | Safety limits for automatic session generation to prevent runaway loops.
    |
    */
    'generation' => [
        'max_ahead_days' => env('CALENDAR_MAX_AHEAD_DAYS', 365),
        'max_sessions_per_batch' => env('CALENDAR_MAX_SESSIONS_BATCH', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Day Name Mappings
    |--------------------------------------------------------------------------
    |
    | Maps day names (English and Arabic) to Carbon day constants.
    | Used for session scheduling from weekly schedules.
    |
    */
    'day_mappings' => [
        'sunday' => 0,
        'monday' => 1,
        'tuesday' => 2,
        'wednesday' => 3,
        'thursday' => 4,
        'friday' => 5,
        'saturday' => 6,
        'الأحد' => 0,
        'الاثنين' => 1,
        'الثلاثاء' => 2,
        'الأربعاء' => 3,
        'الخميس' => 4,
        'الجمعة' => 5,
        'السبت' => 6,
    ],
];
