<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Attendance Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for session attendance tracking and calculations.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Late Tolerance (Grace Period)
    |--------------------------------------------------------------------------
    |
    | Number of minutes after session start time before a student is marked
    | as "late". Students joining within this window are considered on-time.
    |
    */
    'late_tolerance_minutes' => env('ATTENDANCE_LATE_TOLERANCE', 15),

    /*
    |--------------------------------------------------------------------------
    | Minimum Attendance Percentage
    |--------------------------------------------------------------------------
    |
    | Minimum percentage of session duration a student must attend to be
    | counted as "present" rather than "absent".
    |
    */
    'minimum_attendance_percentage' => env('ATTENDANCE_MIN_PERCENTAGE', 30),

    /*
    |--------------------------------------------------------------------------
    | Reconnection Threshold
    |--------------------------------------------------------------------------
    |
    | Number of seconds within which a student rejoining is considered a
    | reconnection (same session) rather than a new join.
    |
    */
    'reconnection_threshold_seconds' => env('ATTENDANCE_RECONNECTION_THRESHOLD', 120),

    /*
    |--------------------------------------------------------------------------
    | Session Buffer Time
    |--------------------------------------------------------------------------
    |
    | Minutes before session end to stop counting attendance (buffer for
    | natural session wrap-up).
    |
    */
    'session_end_buffer_minutes' => env('ATTENDANCE_END_BUFFER', 5),

    /*
    |--------------------------------------------------------------------------
    | Calculation Delay (Post-Session Grace Period)
    |--------------------------------------------------------------------------
    |
    | Minutes to wait after session end before calculating attendance.
    | This gives time for late participants to fully leave and for
    | meeting data to be finalized.
    |
    | Default: 5 minutes (300 seconds)
    | In local/testing: Can be set lower for faster testing
    |
    */
    'calculation_delay_minutes' => env('ATTENDANCE_CALCULATION_DELAY', 5),
];
