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
        'connection_failed' => 'فشل في الاتصال بالجلسة',
        'unexpected_error' => 'حدث خطأ غير متوقع',
        'email_not_verified' => 'يجب تأكيد بريدك الإلكتروني قبل الانضمام للجلسة. يرجى التحقق من بريدك الإلكتروني والضغط على رابط التأكيد.',
        'session_not_found' => 'الجلسة غير موجودة أو تم حذفها',
        'not_authorized' => 'غير مصرح لك بالانضمام لهذه الجلسة',
        'session_not_started' => 'لم يحن وقت الجلسة بعد',
        'session_already_ended' => 'انتهت الجلسة',
        'auto_terminated' => 'انتهى وقت الجلسة وتم إنهاؤها تلقائياً',
        'auto_terminated_description' => 'تم إنهاء الجلسة تلقائياً بانتهاء الوقت المحدد',
        'return_to_session' => 'العودة إلى الجلسة',
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
        'duration_minutes' => 'مدة الحضور: :minutes دقيقة',

        // Status labels for JavaScript (used in livekit-interface)
        'status_labels' => [
            'attended' => 'حاضر',
            'present' => 'حاضر',
            'late' => 'متأخر',
            'left' => 'غادر مبكراً',
            'partial' => 'غادر مبكراً',
            'absent' => 'غائب',
        ],
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
        'lower_hand' => 'خفض اليد',
        'toggle_chat' => 'إظهار/إخفاء الدردشة',
        'toggle_participants' => 'إظهار/إخفاء المشاركين',
        'manage_raised_hands' => 'إدارة الأيدي المرفوعة',
        'start_recording' => 'بدء تسجيل الدورة',
        'stop_recording' => 'إيقاف تسجيل الدورة',
        'toggle_recording' => 'بدء/إيقاف تسجيل الدورة',
        'settings' => 'الإعدادات',
        'leave_meeting' => 'مغادرة الجلسة',
        'start_mic' => 'تشغيل الميكروفون',
        'stop_mic' => 'إيقاف الميكروفون',
        'mic_disabled_by_teacher' => 'الميكروفون معطل من قبل المعلم',
        'start_camera' => 'تشغيل الكاميرا',
        'stop_camera' => 'إيقاف الكاميرا',
        'start_screen_share' => 'مشاركة الشاشة',
        'stop_screen_share' => 'إيقاف مشاركة الشاشة',
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
        'title' => 'التسجيل',
        'started' => 'بدأ',
        'stopped' => 'توقف',
        'error' => 'خطأ في التسجيل',
    ],

    // Headers
    'headers' => [
        'meeting_management' => 'إدارة الاجتماع المباشر',
        'join_live_session' => 'الانضمام للجلسة المباشرة',
    ],

    // System Status (for device/browser checks)
    'system' => [
        'title' => 'حالة النظام',
        'camera' => 'كاميرا المتصفح',
        'microphone' => 'ميكروفون المتصفح',
        'connection_status' => 'حالة الاتصال',
        'browser_compatibility' => 'توافق المتصفح',
        'grant_permission' => 'منح الإذن',
        'reset_in_browser' => 'أعد تعيين الإذن من إعدادات المتصفح',
        'click_lock_icon' => 'اضغط على أيقونة القفل في شريط العنوان ← أعد تعيين الأذونات',
        'how_to_reset_title' => 'كيفية إعادة تعيين الأذونات',
        'how_to_reset_step1' => 'اضغط على أيقونة القفل 🔒 أو أيقونة الإعدادات ⚙ في شريط العنوان بالأعلى',
        'how_to_reset_step2' => 'ابحث عن الكاميرا والميكروفون وغيّر الإعداد من "حظر" إلى "سماح"',
        'how_to_reset_step3' => 'أعد تحميل الصفحة',
        'allowed' => 'مسموح',
        'denied' => 'مرفوض',
        'needs_permission' => 'يحتاج إذن',
        'unknown' => 'غير معروف',
        'connected' => 'متصل',
        'not_connected' => 'غير متصل',
        'compatible' => 'متوافق',
        'not_compatible' => 'غير متوافق',
    ],

    // Participants & Roles
    'participants' => [
        'teacher' => 'معلم',
        'student' => 'طالب',
        'admin' => 'مشرف',
        'participant' => 'مشارك',
        'you' => '(أنت)',
        'joined' => 'انضم :name إلى الجلسة',
        'left' => 'غادر :name الجلسة',
        'no_other_participants' => 'لا يوجد مشاركين آخرين في الجلسة',
    ],

    // Permission Messages
    'permissions' => [
        'mic_not_allowed_by_teacher' => 'المعلم لم يسمح بإستخدام الميكروفون',
        'camera_not_allowed_by_teacher' => 'المعلم لم يسمح بإستخدام الكاميرا',
        'mic_disabled_by_teacher' => 'الميكروفون معطل من قبل المعلم',
        'cannot_unmute' => 'غير مسموح لك بتفعيل الميكروفون',
        'teacher_muted_all' => 'المعلم قام بكتم جميع الطلاب - انتظر الإذن',
        'teacher_controls_audio' => 'المعلم يتحكم في صلاحيات الصوت - ارفع يدك للحصول على الإذن',
        'speaking_permission_granted' => '✅ تم منحك إذن التحدث - الميكروفون مفعل الآن',
        'auto_unmute_error' => 'خطأ في تفعيل الميكروفون تلقائياً',
        'cannot_manage_hands' => 'غير مسموح لك بإدارة الأيدي المرفوعة',
        'cannot_raise_hand' => 'غير مسموح لك برفع اليد',
        'cannot_manage_audio' => 'غير مسموح لك بإدارة صلاحيات الصوت',
        'cannot_manage_camera' => 'غير مسموح لك بإدارة صلاحيات الكاميرا',
        'cannot_record' => 'غير مسموح لك بالتسجيل',
        'no_media_permissions' => 'لم يتم منح أي صلاحيات للوسائط. ستتمكن من المشاركة بالدردشة فقط.',
        'camera_control_not_allowed' => 'غير مسموح لك بإدارة صلاحيات الكاميرا',
        'recording_not_allowed' => 'غير مسموح لك بالتسجيل',
        'mic_permission_granted' => 'تم منحك إذن استخدام الميكروفون',
    ],

    // Control States
    'control_states' => [
        'mic_enabled' => 'مفعل',
        'mic_disabled' => 'معطل',
        'camera_enabled' => 'مفعلة',
        'camera_disabled' => 'معطلة',
        'microphone' => 'الميكروفون',
        'camera' => 'الكاميرا',
        'screen_share' => 'مشاركة الشاشة',
        'hand' => 'اليد',
        'raised' => 'مرفوعة',
        'lowered' => 'مخفضة',
        'recording' => 'التسجيل',
        'started' => 'بدأ',
        'stopped' => 'توقف',
        'toggle_mic' => 'تشغيل/إيقاف الميكروفون',
        'toggle_camera' => 'تشغيل/إيقاف الكاميرا',
        'enable_mic' => 'تشغيل الميكروفون',
        'disable_mic' => 'إيقاف الميكروفون',
        'enable_camera' => 'تشغيل الكاميرا',
        'disable_camera' => 'إيقاف الكاميرا',
        'start_screen_share' => 'مشاركة الشاشة',
        'stop_screen_share' => 'إيقاف مشاركة الشاشة',
        'raise_hand' => 'رفع اليد',
        'lower_hand' => 'خفض اليد',
        'start_recording' => 'بدء التسجيل',
        'stop_recording' => 'إيقاف التسجيل',
        'hand_raised' => 'يد مرفوعة',
    ],

    // Control Errors
    'control_errors' => [
        'not_connected' => 'خطأ: لم يتم الاتصال بالجلسة بعد',
        'mic_error' => 'خطأ في التحكم بالميكروفون',
        'camera_error' => 'خطأ في التحكم بالكاميرا',
        'screen_share_error' => 'خطأ في مشاركة الشاشة',
        'screen_share_denied' => 'تم رفض إذن مشاركة الشاشة',
        'screen_share_not_supported' => 'مشاركة الشاشة غير مدعومة في هذا المتصفح',
        'hand_raise_error' => 'خطأ في رفع اليد',
        'recording_error' => 'خطأ في التسجيل',
        'send_message_error' => 'خطأ في إرسال الرسالة',
        'chat_data_error' => 'خطأ في تلقي بيانات الدردشة',
    ],

    // Hand Raise
    'hand_raise' => [
        'hand_raised_notification' => '👋 :name رفع يده',
        'granted_permission' => '✅ تم منح :name إذن التحدث',
        'grant_error' => 'خطأ في منح إذن التحدث',
        'hand_raised_label' => '✋ يد مرفوعة',
        'hand_raised' => 'يد مرفوعة',
        'hide_hand' => '✓ إخفاء اليد',
        'all_hands_cleared' => 'تم إخفاء جميع الأيدي المرفوعة بنجاح',
        'clear_hands_error' => 'حدث خطأ أثناء إخفاء الأيدي المرفوعة',
        'minutes_ago' => 'قبل :minutes دقيقة',
        'seconds_ago' => 'قبل :seconds ثانية',
        'teacher_dismissed_hand' => 'قام المعلم بإخفاء يدك المرفوعة',
        'all_hands_cleared_by_teacher' => 'تم إخفاء جميع الأيدي المرفوعة من قبل المعلم',
    ],

    // Student Control
    'student_control' => [
        'all_students_muted' => 'تم كتم جميع الطلاب',
        'students_mic_allowed' => 'تم السماح للطلاب بإستخدام الميكروفون',
        'students_can_use_mic' => 'تم السماح للطلاب بإستخدام الميكروفون',
        'manage_students_mic_error' => 'خطأ في إدارة ميكروفونات الطلاب',
        'mic_control_error' => 'خطأ في إدارة ميكروفونات الطلاب',
        'all_students_camera_disabled' => 'تم تعطيل كاميرات جميع الطلاب',
        'all_cameras_disabled' => 'تم تعطيل كاميرات جميع الطلاب',
        'students_camera_allowed' => 'تم السماح للطلاب بإستخدام الكاميرا',
        'students_can_use_camera' => 'تم السماح للطلاب بإستخدام الكاميرا',
        'manage_students_camera_error' => 'خطأ في إدارة كاميرات الطلاب',
        'camera_control_error' => 'خطأ في إدارة كاميرات الطلاب',
        'mic_permission_granted_by' => '🎤 تم منحك إذن التحدث من قبل :name',
        'mic_revoked_by' => '🔇 تم إيقاف الميكروفون من قبل :name',
        'all_muted_by' => '🔇 تم كتم جميع الطلاب من قبل :name',
        'mic_allowed_by' => '🔊 يمكنك الآن استخدام الميكروفون - تم السماح للجميع من قبل :name',
    ],

    // Data Channel Messages
    'data_channel' => [
        'all_students_muted' => 'تم كتم جميع الطلاب',
        'mic_allowed' => 'تم السماح باستخدام الميكروفون',
        'all_hands_cleared' => 'تم مسح جميع الأيدي المرفوعة',
        'teacher_hid_hand' => 'قام المعلم بإخفاء يدك المرفوعة',
        'mic_permission_granted' => 'تم منحك إذن استخدام الميكروفون',
        'session_ended_by_teacher' => 'تم إنهاء الجلسة من قبل المعلم',
        'removed_from_session' => 'تم إخراجك من الجلسة',
    ],

    // Screen Share
    'screen_share' => [
        'click_to_enlarge' => 'انقر للتكبير',
        'your_shared_screen' => 'شاشتك المشتركة',
        'your_screen' => 'شاشتك المشتركة',
        'screen_of' => 'شاشة',
        'screen_share_paused' => 'تم إيقاف مشاركة الشاشة مؤقتاً',
        'screen_share_stopped' => 'تم إيقاف مشاركة الشاشة',
    ],

    // Sidebar & UI
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
        'participants' => 'المشاركين',
        'settings' => 'الإعدادات',
        'fullscreen' => 'ملء الشاشة',
        'exit_fullscreen' => 'إغلاق ملء الشاشة',
    ],

    // Leave Dialog
    'leave' => [
        'title' => 'مغادرة الجلسة',
        'confirm_message' => 'هل أنت متأكد من أنك تريد مغادرة الجلسة؟',
        'cancel' => 'إلغاء',
        'leave' => 'مغادرة',
    ],

    // Connection Messages
    'connection' => [
        'failed' => 'فشل في الاتصال بالجلسة. يرجى المحاولة مرة أخرى.',
        'setup_failed' => 'فشل في إعداد الجلسة. يرجى المحاولة مرة أخرى.',
        'joined_successfully' => 'تم الانضمام للجلسة بنجاح.',
        'joined_may_need_camera' => 'تم الانضمام للجلسة بنجاح. قد تحتاج لتفعيل الكاميرا يدوياً.',
        'mic_access_denied' => 'تم حظر الميكروفون — لا يمكن المشاركة بالصوت',
        'mic_blocked_chrome' => 'اضغط على أيقونة القفل بجانب عنوان الموقع ← إعدادات الموقع ← الميكروفون ← السماح، ثم أعد تحميل الصفحة.',
        'mic_blocked_safari' => 'Safari ← الإعدادات ← مواقع الويب ← الميكروفون ← السماح لهذا الموقع، ثم أعد تحميل الصفحة.',
        'mic_blocked_firefox' => 'اضغط على أيقونة القفل بجانب عنوان الموقع ← امسح إذن الميكروفون، ثم أعد تحميل الصفحة.',
        'mic_blocked_generic' => 'افتح إعدادات المتصفح ← أذونات الموقع ← الميكروفون ← السماح لهذا الموقع، ثم أعد تحميل الصفحة.',
        'mic_not_found_instructions' => 'لم يتم العثور على ميكروفون. يرجى توصيل ميكروفون وإعادة تحميل الصفحة.',
        'reload_page' => 'إعادة التحميل',
        'camera_access_denied' => 'لا يمكن الوصول إلى الكاميرا. يرجى السماح بالوصول في المتصفح.',
        'permission_denied' => 'تم رفض الوصول للكاميرا أو الميكروفون',
        'joined_teacher_mic_on' => 'تم الانضمام بنجاح. الميكروفون مفعّل.',
        'joined_student_muted' => 'تم الانضمام بنجاح. الميكروفون والكاميرا مغلقان.',
        'connected' => 'تم الاتصال بالجلسة بنجاح',
        'disconnected' => 'تم قطع الاتصال بالجلسة',
        'disconnected_reconnecting' => 'انقطع الاتصال... جاري المحاولة مرة أخرى',
        'reconnecting' => 'جاري إعادة الاتصال...',
        'reconnected' => 'تم إعادة الاتصال بنجاح',
    ],

    // Chat
    'chat' => [
        'you' => 'أنت',
        'send_error' => 'خطأ في إرسال الرسالة',
        'no_other_participants' => 'لا يوجد مشاركين آخرين في الجلسة',
    ],

    // Fullscreen
    'fullscreen' => [
        'enter' => 'ملء الشاشة',
        'exit' => 'إغلاق ملء الشاشة',
    ],

    // Session
    'session' => [
        'ended_by_teacher' => 'تم إنهاء الجلسة من قبل المعلم',
        'kicked_from_session' => 'تم إخراجك من الجلسة',
    ],

    // Meeting Link Management
    'link' => [
        'unauthorized' => 'غير مصرح لك بالوصول',
        'session_not_found' => 'لم يتم العثور على الجلسة',
        'trial_not_found' => 'لم يتم العثور على طلب الجلسة التجريبية',
        'link_required' => 'رابط الاجتماع مطلوب',
        'link_invalid' => 'يجب أن يكون رابط الاجتماع صحيحاً',
        'invalid_data' => 'بيانات غير صحيحة',
        'updated_success' => 'تم تحديث رابط الاجتماع بنجاح',
        'update_error' => 'حدث خطأ أثناء تحديث رابط الاجتماع',
        'created_success' => 'تم إنشاء رابط الاجتماع بنجاح',
        'create_error' => 'حدث خطأ أثناء إنشاء رابط الاجتماع',
        'invalid_format' => 'رابط الاجتماع غير صحيح',
    ],

    // Meeting Platforms
    'platforms' => [
        'google_meet' => 'Google Meet',
        'zoom' => 'Zoom',
        'teams' => 'Microsoft Teams',
        'webex' => 'Cisco Webex',
    ],

    // API Messages (UnifiedMeetingController, MeetingController, LiveKitMeetingController)
    'api' => [
        'invalid_data' => 'بيانات غير صحيحة',
        'session_not_found' => 'الجلسة غير موجودة',
        'session_id_required' => 'معرف الجلسة مطلوب',
        'validation_failed' => 'فشل في التحقق من البيانات',
        'not_authorized_manage' => 'غير مصرح لك بإدارة هذه الجلسة',
        'not_authorized_join' => 'غير مصرح لك بالانضمام إلى هذه الجلسة',
        'not_authorized_view' => 'غير مصرح لك بعرض معلومات هذه الجلسة',
        'not_authorized_end' => 'غير مصرح لك بإنهاء هذه الجلسة',
        'meeting_exists' => 'الاجتماع موجود بالفعل',
        'meeting_already_exists' => 'يوجد اجتماع بالفعل لهذه الجلسة',
        'meeting_created' => 'تم إنشاء الاجتماع بنجاح',
        'meeting_create_error' => 'حدث خطأ أثناء إنشاء الاجتماع',
        'meeting_create_failed' => 'فشل في إنشاء الاجتماع',
        'meeting_not_created' => 'لم يتم إنشاء الاجتماع بعد',
        'meeting_not_created_wait' => 'لم يتم إنشاء الاجتماع بعد. يرجى انتظار المعلم لبدء الجلسة.',
        'meeting_available' => 'الاجتماع متاح للانضمام',
        'token_created' => 'تم إنشاء رمز الوصول بنجاح',
        'token_create_error' => 'حدث خطأ أثناء إنشاء رمز الوصول',
        'token_create_failed' => 'فشل في إنشاء رمز الوصول',
        'meeting_not_found' => 'الاجتماع غير موجود أو غير نشط',
        'meeting_info_error' => 'حدث خطأ أثناء جلب معلومات الاجتماع',
        'room_info_failed' => 'فشل في جلب معلومات الغرفة',
        'room_info_unavailable' => 'تعذر الحصول على معلومات الغرفة بعد محاولة إعادة الإنشاء',
        'meeting_ended' => 'تم إنهاء الاجتماع بنجاح',
        'meeting_end_failed' => 'فشل في إنهاء الاجتماع',
        'meeting_end_error' => 'حدث خطأ أثناء إنهاء الاجتماع',
        'room_prepare_failed' => 'فشل في تحضير غرفة الاجتماع',
        'logout_success' => 'تم تسجيل الخروج بنجاح',
        'logout_error' => 'حدث خطأ أثناء تسجيل الخروج',
    ],

    // User Roles
    'roles' => [
        'teacher' => 'المعلم',
        'student' => 'الطالب',
        'parent' => 'ولي الأمر',
        'admin' => 'المدير',
        'super_admin' => 'المدير العام',
        'participant' => 'مشارك',
    ],
];
