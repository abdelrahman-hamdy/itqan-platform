<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Meeting Interface Language Lines (Arabic)
    |--------------------------------------------------------------------------
    |
    | The following language lines are used throughout the LiveKit meeting
    | interface, including session status, controls, attendance tracking,
    | and all related functionality.
    |
    */

    // Session Status Messages
    'status' => [
        'session_ready' => 'الجلسة جاهزة - يمكنك الانضمام الآن',
        'session_ongoing' => 'الجلسة جارية الآن - انضم للمشاركة',
        'preparing_meeting' => 'جاري تحضير الاجتماع - يمكنك الانضمام الآن',
        'preparing_in_time' => 'سيتم تحضير الاجتماع خلال :time (:minutes دقيقة قبل الموعد)',
        'preparing' => 'جاري تحضير الاجتماع...',
        'scheduled_no_time' => 'الجلسة مجدولة لكن لم يتم تحديد الوقت بعد',
        'waiting_preparation' => 'في انتظار تحضير الاجتماع',
        'session_completed' => 'تم إنهاء الجلسة بنجاح',
        'session_ended' => 'الجلسة منتهية',
        'session_cancelled' => 'تم إلغاء الجلسة',
        'session_cancelled_short' => 'الجلسة ملغية',
        'absent_can_join' => 'تم تسجيل غيابك ولكن يمكنك الانضمام الآن',
        'student_absent' => 'تم تسجيل غياب الطالب',
        'student_absent_short' => 'غياب الطالب',
        'session_status_prefix' => 'حالة الجلسة:',
        'unavailable' => 'غير متاح',
        'unknown_status' => 'حالة غير معروفة:',
        'session_unscheduled' => 'الجلسة غير مجدولة بعد',
        'not_scheduled' => 'غير مجدولة',
    ],

    // Button Labels
    'buttons' => [
        'join_session' => 'انضم للجلسة',
        'join_ongoing' => 'انضمام للجلسة الجارية',
        'join_as_absent' => 'انضم للجلسة (غائب)',
        'leave_session' => 'مغادرة الجلسة',
        'connecting' => 'جاري الاتصال...',
        'connected' => 'متصل',
        'retry' => 'إعادة المحاولة',
        'livekit_unavailable' => 'LiveKit غير متوفر',
        'init_error' => 'خطأ في التهيئة',
    ],

    // Timer and Phase Labels
    'timer' => [
        'waiting_session' => 'في انتظار الجلسة',
        'preparation_time' => 'وقت التحضير - استعد للجلسة',
        'session_active' => 'الجلسة جارية الآن',
        'overtime' => 'وقت إضافي - اختتم الجلسة قريباً',
        'session_ended' => 'انتهت الجلسة',
        'time_until_start' => 'بداية الجلسة خلال',
        'waiting_start' => 'في انتظار بداية الجلسة',
        'session_active_since' => 'الجلسة جارية منذ',
        'session_currently_active' => 'الجلسة نشطة حالياً',
        'overtime_since' => 'وقت إضافي منذ',
        'session_in_overtime' => 'الجلسة في الوقت الإضافي',
        'starting_soon' => 'الجلسة ستبدأ خلال :time',
    ],

    // Session Info Labels
    'info' => [
        'session_info' => 'معلومات الجلسة',
        'session_time' => 'وقت الجلسة:',
        'duration' => 'المدة:',
        'minute' => 'دقيقة',
        'minutes' => 'دقيقة',
        'preparation_period' => 'فترة التحضير:',
        'buffer_time' => 'الوقت الإضافي:',
        'room_number' => 'رقم الغرفة:',
        'not_specified' => 'غير محدد',
        'participant' => 'مشارك',
        'participants' => 'مشاركين',
        'fullscreen' => 'ملء الشاشة',
        'exit_fullscreen' => 'الخروج من ملء الشاشة',
    ],

    // Session Management (Teacher)
    'management' => [
        'session_management' => 'إدارة حالة الجلسة',
        'cancel_session_teacher' => 'إلغاء الجلسة (عدم حضور المعلم)',
        'cancel_session' => 'إلغاء الجلسة',
        'mark_student_absent' => 'تسجيل غياب الطالب',
        'end_session' => 'إنهاء الجلسة',
        'session_ended_success' => 'تم إنهاء الجلسة بنجاح',
        'session_cancelled' => 'تم إلغاء الجلسة',
        'student_marked_absent' => 'تم تسجيل غياب الطالب',
    ],

    // Confirmation Dialogs
    'confirm' => [
        'cancel_session' => 'هل أنت متأكد من إلغاء هذه الجلسة؟ لن يتم احتساب هذه الجلسة في الاشتراك.',
        'mark_absent' => 'هل أنت متأكد من تسجيل غياب الطالب؟',
        'end_session' => 'هل أنت متأكد من إنهاء هذه الجلسة؟',
    ],

    // Success/Error Messages
    'messages' => [
        'session_cancelled_success' => 'تم إلغاء الجلسة بنجاح',
        'cancel_failed' => 'فشل في إلغاء الجلسة:',
        'unknown_error' => 'خطأ غير معروف',
        'cancel_error' => 'حدث خطأ أثناء إلغاء الجلسة',
        'absent_marked_success' => 'تم تسجيل غياب الطالب بنجاح',
        'absent_mark_failed' => 'فشل في تسجيل غياب الطالب:',
        'absent_mark_error' => 'حدث خطأ أثناء تسجيل غياب الطالب',
        'session_ended_success' => 'تم إنهاء الجلسة بنجاح',
        'end_failed' => 'فشل في إنهاء الجلسة:',
        'end_error' => 'حدث خطأ أثناء إنهاء الجلسة',
        'connection_failed' => 'فشل في الاتصال بالجلسة:',
        'unexpected_error' => 'حدث خطأ غير متوقع',
        'auto_terminated' => 'انتهى وقت الجلسة وتم إنهاؤها تلقائياً',
        'auto_terminated_description' => 'تم إنهاء الجلسة تلقائياً بانتهاء الوقت المحدد',
    ],

    // Attendance Status
    'attendance' => [
        'present' => 'حاضر',
        'late' => 'متأخر',
        'left_early' => 'غادر مبكراً',
        'absent' => 'غائب',
        'attended_before' => 'حضر سابقاً',
        'not_joined_yet' => 'لم تنضم بعد',
        'in_session_now' => '(في الجلسة الآن)',
        'duration_prefix' => 'مدة الحضور:',
        'not_started' => 'الجلسة لم تبدأ بعد',
        'starting_in' => 'ستبدأ خلال :minutes دقيقة',
        'waiting_start' => 'في انتظار البدء',
        'did_not_attend' => 'لم تحضر الجلسة',
        'session_ended' => 'الجلسة انتهت',
        'attended_session' => 'حضرت الجلسة',
        'attended_late' => 'حضرت متأخراً',
        'in_session' => 'في الجلسة الآن',
        'session_ongoing' => 'الجلسة جارية الآن',
        'attendance_tracked' => 'الحضور يتم تتبعه تلقائياً',
        'attendance_failed' => 'فشل في تسجيل الحضور',
        'join_failed' => 'فشل في تسجيل دخولك للجلسة',
        'leave_failed' => 'فشل في تسجيل خروجك من الجلسة',
        'attended_minutes' => 'حضرت :minutes دقيقة',
        'joined_times' => ':minutes دقيقة - انضم :count مرة',
        'not_attended_label' => 'لم تحضر',
    ],

    // Network Status
    'network' => [
        'offline' => 'غير متصل بالشبكة',
        'reconnecting' => 'إعادة الاتصال...',
        'reconnecting_session' => 'إعادة الاتصال بالجلسة...',
        'reconnect_failed' => 'فشل في إعادة الاتصال - يرجى إعادة تحميل الصفحة',
        'reconnect_error' => 'فشل في إعادة الاتصال',
        'connected' => 'متصل',
    ],

    // Loading States
    'loading' => [
        'connecting_meeting' => 'جاري الاتصال بالاجتماع...',
        'please_wait' => 'يرجى الانتظار قليلاً...',
        'loading_devices' => 'جاري التحميل...',
    ],

    // Control Bar (Tooltips)
    'controls' => [
        'toggle_mic' => 'إيقاف/تشغيل الميكروفون',
        'toggle_camera' => 'إيقاف/تشغيل الكاميرا',
        'share_screen' => 'مشاركة الشاشة',
        'raise_hand' => 'رفع اليد',
        'toggle_chat' => 'إظهار/إخفاء الدردشة',
        'toggle_participants' => 'إظهار/إخفاء المشاركين',
        'manage_raised_hands' => 'إدارة الأيدي المرفوعة',
        'start_recording' => 'بدء تسجيل الدورة',
        'stop_recording' => 'إيقاف تسجيل الدورة',
        'toggle_recording' => 'بدء/إيقاف تسجيل الدورة',
        'settings' => 'الإعدادات',
        'leave_meeting' => 'مغادرة الجلسة',
    ],

    // Sidebar Panels
    'sidebar' => [
        'chat' => 'الدردشة',
        'close_sidebar' => 'إغلاق الشريط الجانبي',
        'type_message' => 'اكتب رسالة...',
        'raised_hands' => 'الأيدي المرفوعة',
        'hide_all' => 'إخفاء الكل',
        'no_raised_hands' => 'لا يوجد طلاب رفعوا أيديهم',
        'student_controls' => 'التحكم في الطلاب',
        'allow_microphone' => 'السماح بالميكروفون',
        'allow_mic_description' => 'السماح للطلاب بإستخدام الميكروفون',
        'allow_camera' => 'السماح بالكاميرا',
        'allow_camera_description' => 'السماح للطلاب بإستخدام الكاميرا',
        'camera_settings' => 'إعدادات الكاميرا',
        'camera_label' => 'الكاميرا',
        'quality_label' => 'الجودة',
        'quality_low' => 'منخفضة (480p)',
        'quality_medium' => 'متوسطة (720p)',
        'quality_high' => 'عالية (1080p)',
        'mic_settings' => 'إعدادات الميكروفون',
        'microphone_label' => 'الميكروفون',
        'mute_on_join' => 'كتم الصوت عند الدخول',
    ],

    // Recording
    'recording' => [
        'start_recording' => 'بدء تسجيل الدورة',
        'stop_recording' => 'إيقاف تسجيل الدورة',
        'recording_stopped' => 'تم إيقاف التسجيل وحفظه بنجاح',
        'recording_started' => 'بدأ تسجيل الدورة التفاعلية',
        'recording_error' => 'خطأ في التسجيل:',
        'start_failed' => 'فشل في بدء التسجيل',
        'no_active_recording' => 'لا يوجد تسجيل نشط',
        'stop_failed' => 'فشل في إيقاف التسجيل',
    ],

    // Headers
    'headers' => [
        'meeting_management' => 'إدارة الاجتماع المباشر',
        'join_live_session' => 'الانضمام للجلسة المباشرة',
    ],

    // System Status (for device/browser checks)
    'system' => [
        'allowed' => 'مسموح',
        'denied' => 'مرفوض',
        'needs_permission' => 'يحتاج إذن',
        'unknown' => 'غير معروف',
        'connected' => 'متصل',
        'not_connected' => 'غير متصل',
        'compatible' => 'متوافق',
        'not_compatible' => 'غير متوافق',
    ],
];
