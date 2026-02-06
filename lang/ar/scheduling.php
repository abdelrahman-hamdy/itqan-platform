<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Scheduling Validation Messages (Arabic)
    |--------------------------------------------------------------------------
    |
    | Translations for scheduling validators across all session types.
    |
    */

    // Day selection validation
    'days' => [
        'select_at_least_one' => 'يجب اختيار يوم واحد على الأقل',
        'max_per_week' => 'لا يمكن اختيار أكثر من :max أيام في الأسبوع',
        'max_per_week_course' => 'لا يمكن اختيار أكثر من :max أيام في الأسبوع للدورة التفاعلية',
        'count_suitable' => '✓ عدد الأيام مناسب (:count أيام أسبوعياً)',
        'exceeds_recommended' => '⚠️ اخترت :selected أيام أسبوعياً، وهو أكثر من الموصى به (:recommended أيام) بناءً على :context. :consequence',
        'consequence_fast_consumption' => 'قد تستهلك الجلسات بسرعة كبيرة.',
        'consequence_course_ends_early' => 'قد تنتهي الدورة قبل المدة المتوقعة.',
        'consequence_fast_finish' => 'قد يؤدي هذا لإنهاء الجلسات بسرعة كبيرة.',
        'consequence_more_than_usual' => 'سيتم إنشاء جلسات أكثر من المعتاد.',
        'context_subscription' => 'الاشتراك (:remaining جلسة متبقية خلال :weeks أسبوع)',
        'context_course' => 'الدورة (:total جلسة خلال :weeks أسبوع)',
        'context_monthly_target' => 'الهدف الشهري (:target جلسة/شهر)',
    ],

    // Session count validation
    'count' => [
        'must_be_positive' => 'يجب أن يكون عدد الجلسات أكبر من صفر',
        'no_remaining' => 'لا توجد جلسات متبقية في الاشتراك الحالي. يرجى تجديد الاشتراك.',
        'no_remaining_circle' => 'لا توجد جلسات متبقية في الاشتراك',
        'exceeds_remaining' => 'لا يمكن جدولة :count جلسة. الجلسات المتبقية في الاشتراك: :remaining',
        'exceeds_remaining_short' => 'لا يمكن جدولة :count جلسة. الجلسات المتبقية: :remaining فقط',
        'exceeds_remaining_course' => 'لا يمكن جدولة :count جلسة. الجلسات المتبقية: :remaining من أصل :total',
        'max_batch' => 'لا يمكن جدولة أكثر من :max جلسة دفعة واحدة لتجنب الأخطاء',
        'max_batch_simple' => 'لا يمكن جدولة أكثر من :max جلسة دفعة واحدة',
        'all_scheduled' => 'تم جدولة جميع جلسات الدورة بالفعل (:total جلسة)',
        'few_scheduled_warning' => '⚠️ تجدول :count جلسة فقط من أصل :remaining متبقية. قد تحتاج لجدولة المزيد قريباً قبل انتهاء الاشتراك.',
        'few_scheduled_warning_short' => '⚠️ تجدول :count جلسة فقط من أصل :remaining متبقية. قد تحتاج لجدولة المزيد قريباً.',
        'success' => '✓ سيتم جدولة :count من :remaining جلسة متبقية',
        'suitable' => '✓ عدد الجلسات مناسب (:count جلسة)',
        'suitable_of_remaining' => '✓ عدد الجلسات مناسب (:count من أصل :remaining متبقية)',
        'below_half_monthly' => '⚠️ عدد الجلسات (:count) أقل من نصف الهدف الشهري (:target). قد تحتاج لجدولة المزيد قريباً.',
        'exceeds_three_months' => '⚠️ عدد الجلسات (:count) كبير جداً (أكثر من 3 أشهر). قد ترغب في جدولة فترة أقصر.',
    ],

    // Date range validation
    'date' => [
        'before_subscription_start' => 'لا يمكن جدولة جلسات قبل تاريخ بدء الاشتراك (:date)',
        'before_course_start' => 'لا يمكن جدولة جلسات قبل تاريخ بدء الدورة (:date)',
        'cannot_schedule_past' => 'لا يمكن جدولة جلسات في الماضي',
        'cannot_schedule_trial_past' => 'لا يمكن جدولة الجلسة التجريبية في تاريخ ماضي',
        'exceeds_subscription_end' => '⚠️ بعض الجلسات قد تتجاوز تاريخ انتهاء الاشتراك (:date). تأكد من توزيع الجلسات بشكل مناسب.',
        'exceeds_subscription_end_auto' => '⚠️ بعض الجلسات ستتجاوز تاريخ انتهاء الاشتراك (:date). سيتم جدولة الجلسات حتى تاريخ الانتهاء فقط.',
        'exceeds_course_end' => '⚠️ بعض الجلسات قد تتجاوز تاريخ انتهاء الدورة (:date). تأكد من توزيع الجلسات بشكل مناسب.',
        'exceeds_duration' => '⚠️ فترة الجدولة (:weeks أسبوع) أطول من مدة الدورة المتوقعة (:duration أسبوع)',
        'exceeds_year' => '⚠️ تجاوزت سنة من الجدولة (:weeks أسبوع). قد ترغب في جدولة فترة أقصر.',
        'range_valid' => '✓ نطاق التاريخ صحيح (من :start إلى :end)',
        'range_valid_from' => '✓ نطاق التاريخ صحيح (ابتداءً من :start)',
        'no_active_subscription' => 'لا يوجد اشتراك نشط لهذه الحلقة',
        'subscription_inactive' => 'الاشتراك غير نشط. يجب تفعيل الاشتراك أولاً',
    ],

    // Weekly pacing validation
    'pacing' => [
        'exceeds_remaining' => 'الجدول المختار سينشئ :total جلسة، لكن المتبقي في الاشتراك فقط :remaining جلسة',
        'exceeds_remaining_short' => 'الجدول المختار سينشئ :total جلسة، لكن المتبقي فقط :remaining جلسة',
        'too_fast' => '⚠️ معدل :count جلسات أسبوعياً أسرع بكثير من الموصى به (:recommended جلسات). قد يؤدي هذا لاستنفاد الجلسات بسرعة أو إرهاق الطالب.',
        'too_fast_course' => '⚠️ معدل :count جلسات أسبوعياً قد يكون سريعاً جداً. الموصى به: :recommended جلسات أسبوعياً.',
        'too_slow' => '⚠️ معدل :count جلسات أسبوعياً بطيء جداً. قد لا تستطيع إنهاء :remaining جلسة قبل انتهاء الاشتراك خلال :weeks أسبوع.',
        'too_slow_course' => '⚠️ معدل :count جلسات أسبوعياً قد يكون بطيئاً. قد تستغرق الدورة وقتاً أطول من المتوقع.',
        'suitable' => '✓ الجدول الزمني مناسب (:total جلسة خلال :weeks أسبوع)',
        'suitable_simple' => '✓ الجدول الزمني مناسب',
        'suitable_count' => '✓ الجدول الزمني مناسب (:total جلسة)',
        'overflow_warning' => '⚠️ اخترت :days أيام لمدة :weeks أسابيع (:total جلسة)، لكن لديك :remaining جلسة متبقية فقط. سيتم جدولة :remaining جلسة وتوزيعها على الأيام المختارة.',
        'double_recommended' => '⚠️ اخترت :count أيام أسبوعياً، وهو ضعف الموصى به (:recommended). قد يكون هذا كثيراً على الطالب.',
        'below_expected' => '⚠️ عدد الجلسات المجدولة (:total) أقل من المتوقع (:expected) لمدة :months شهر.',
        'above_expected' => '⚠️ عدد الجلسات المجدولة (:total) أكثر من المتوقع (:expected) لمدة :months شهر.',
    ],

    // Recommendations
    'recommendations' => [
        'subscription_reason' => 'موصى به :recommended جلسات أسبوعياً لإكمال :remaining جلسة متبقية خلال :weeks أسبوع (قبل انتهاء الاشتراك في :date)',
        'circle_reason' => 'موصى به :recommended أيام أسبوعياً لتوزيع :remaining جلسة على :weeks أسبوع',
        'group_circle_reason' => 'موصى به :recommended أيام أسبوعياً لتحقيق :target جلسة شهرياً',
        'course_reason' => 'موصى به :recommended أيام أسبوعياً لإكمال :remaining جلسة متبقية خلال :weeks أسبوع (من أصل :total جلسة في الدورة)',
        'trial_reason' => 'الجلسة التجريبية تحتاج جلسة واحدة فقط مدتها 30 دقيقة',
    ],

    // Scheduling status
    'status' => [
        'inactive_subscription' => 'الاشتراك غير نشط',
        'expired_subscription' => 'انتهى الاشتراك في :date',
        'expired_subscription_short' => 'الاشتراك منتهي',
        'fully_scheduled' => 'تم جدولة جميع الجلسات',
        'fully_scheduled_count' => 'تم جدولة جميع الجلسات (:scheduled/:total)',
        'not_scheduled' => 'لا توجد جلسات مجدولة (:remaining جلسة متبقية)',
        'not_scheduled_course' => 'لا توجد جلسات مجدولة (:done/:total تمت)',
        'not_scheduled_circle' => 'لم يتم جدولة أي جلسات (:remaining متبقية)',
        'not_scheduled_month' => 'لا توجد جلسات مجدولة في الشهر القادم',
        'partially_scheduled' => ':scheduled جلسة مجدولة من :remaining متبقية',
        'partially_scheduled_circle' => ':scheduled جلسة مجدولة، :remaining متبقية',
        'needs_more' => 'جلسات قليلة مجدولة (:future جلسة قادمة، :remaining متبقية)',
        'needs_scheduling' => 'جلسات قليلة (:count فقط في الشهر القادم)',
        'well_scheduled' => ':future جلسة قادمة من :remaining متبقية',
        'actively_scheduled' => ':count جلسة مجدولة في الشهر القادم',
    ],

    // Trial session specific
    'trial' => [
        'select_one_day' => 'يجب اختيار يوم واحد للجلسة التجريبية',
        'one_day_only' => '⚠️ الجلسة التجريبية تحتاج يوم واحد فقط. سيتم استخدام اليوم الأول المختار.',
        'day_selected' => '✓ تم اختيار يوم واحد للجلسة التجريبية',
        'must_schedule_one' => 'يجب جدولة جلسة واحدة على الأقل',
        'one_session_only' => '⚠️ الجلسة التجريبية تتطلب جلسة واحدة فقط. سيتم إنشاء جلسة واحدة.',
        'will_schedule_one' => '✓ سيتم جدولة جلسة تجريبية واحدة',
        'cancelled_request' => 'لا يمكن جدولة جلسة لطلب تجريبي ملغي',
        'completed_request' => 'تم إكمال هذا الطلب التجريبي بالفعل',
        'status_not_allowed' => 'حالة الطلب التجريبي لا تسمح بالجدولة: :status',
        'scheduled_at' => '✓ موعد الجلسة التجريبية: :time',
        'one_week_max' => '⚠️ الجلسة التجريبية لا تحتاج لأكثر من أسبوع واحد للجدولة',
        'pacing_suitable' => '✓ الجدولة مناسبة للجلسة التجريبية',
        'completed' => 'تم إكمال الجلسة التجريبية',
        'scheduled' => 'مجدولة: :time',
        'cannot_schedule_status' => 'حالة الطلب لا تسمح بالجدولة',
        'ready_to_schedule' => 'جاهز للجدولة',
    ],

    // Capacity (Group circles)
    'capacity' => [
        'no_students' => '⚠️ لا يوجد طلاب مسجلين في هذه الحلقة. قد ترغب في تسجيل طلاب قبل جدولة الجلسات.',
        'low_students' => '⚠️ عدد الطلاب قليل (:current من :max). قد ترغب في قبول المزيد من الطلاب.',
        'full' => '✓ الحلقة ممتلئة (:current/:max طالب)',
        'suitable' => '✓ السعة مناسبة (:current/:max طالب، :available مقعد متاح)',
    ],
];
