<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class SyncTranslations extends Command
{
    protected $signature = 'translations:sync
                            {file : The translation file to sync (e.g., components, teacher)}
                            {--dry-run : Show what would be changed without making changes}';

    protected $description = 'Sync missing translation keys between Arabic and English files';

    // Common English to Arabic translations for auto-translation
    protected array $translations = [
        // Status words
        'Session is ready' => 'الجلسة جاهزة',
        'You can join now' => 'يمكنك الانضمام الآن',
        'Join Session' => 'انضم للجلسة',
        'Join Ongoing Session' => 'انضم للجلسة الجارية',
        'Session is ongoing' => 'الجلسة جارية',
        'Join to participate' => 'انضم للمشاركة',
        'Preparing meeting' => 'جاري تحضير الاجتماع',
        'Meeting will be prepared' => 'سيتم تحضير الاجتماع',
        'minutes before scheduled time' => 'دقائق قبل الموعد المحدد',
        'Session is scheduled but time is not set yet' => 'الجلسة مجدولة ولكن لم يتم تحديد الوقت بعد',
        'Waiting for meeting preparation' => 'في انتظار تحضير الاجتماع',
        'Session completed successfully' => 'اكتملت الجلسة بنجاح',
        'Session Ended' => 'انتهت الجلسة',
        'Session has been cancelled' => 'تم إلغاء الجلسة',
        'Session Cancelled' => 'الجلسة ملغاة',
        'You were marked absent but can still join' => 'تم تسجيلك غائباً ولكن يمكنك الانضمام',
        'Join Session (Absent)' => 'انضم للجلسة (غائب)',
        'Student absence recorded' => 'تم تسجيل غياب الطالب',
        'Student Absent' => 'الطالب غائب',
        'Session status' => 'حالة الجلسة',
        'Not Available' => 'غير متاح',
        'Session ended' => 'انتهت الجلسة',
        'Session will auto-terminate' => 'ستنتهي الجلسة تلقائياً',
        'Session will start' => 'ستبدأ الجلسة',
        'Connecting' => 'جاري الاتصال',
        'Connected' => 'متصل',
        'Retry' => 'إعادة المحاولة',
        'Unexpected error occurred' => 'حدث خطأ غير متوقع',
        'LiveKit service is not available' => 'خدمة لايف كيت غير متاحة',
        'Error initializing' => 'خطأ في التهيئة',
        'Fullscreen' => 'ملء الشاشة',
        'Session starts in' => 'تبدأ الجلسة في',
        'Waiting for start' => 'في انتظار البدء',
        'Session ongoing since' => 'الجلسة جارية منذ',
        'Session active' => 'الجلسة نشطة',
        'Overtime since' => 'وقت إضافي منذ',
        'Session in overtime' => 'الجلسة في وقت إضافي',
        'Session ended status' => 'حالة انتهاء الجلسة',
        'Preparation' => 'التحضير',
        'Ongoing' => 'جارية',
        'Overtime' => 'وقت إضافي',
        'Ended' => 'انتهت',
        'Attendance tracked automatically' => 'يتم تتبع الحضور تلقائياً',
        'Failed to record join' => 'فشل في تسجيل الانضمام',
        'Failed to record leave' => 'فشل في تسجيل المغادرة',
        'Session has not started yet' => 'لم تبدأ الجلسة بعد',
        'Waiting for session to start' => 'في انتظار بدء الجلسة',
        'Did not attend' => 'لم يحضر',
        'Session ended - not attended' => 'انتهت الجلسة - لم يحضر',
        'Left early' => 'غادر مبكراً',
        'Attended session' => 'حضر الجلسة',
        'Attended late' => 'حضر متأخراً',
        'Session ended with duration' => 'انتهت الجلسة بمدة',
        'Attended duration' => 'مدة الحضور',
        'Did not attend yet' => 'لم يحضر بعد',
        'In session now' => 'في الجلسة الآن',
        'Not joined yet' => 'لم ينضم بعد',
        'Session ongoing now' => 'الجلسة جارية الآن',
        'Attended' => 'حضر',
        'Present' => 'حاضر',
        'Late' => 'متأخر',
        'Left' => 'غادر',
        'Partial' => 'جزئي',
        'Absent' => 'غائب',
        'Unknown' => 'غير معروف',
        'Offline' => 'غير متصل',
        'Reconnecting' => 'إعادة الاتصال',
        'Reconnection failed' => 'فشل إعادة الاتصال',
        'Online' => 'متصل',
        'Start Recording' => 'بدء التسجيل',
        'Stop Recording' => 'إيقاف التسجيل',
        'Recording started' => 'بدأ التسجيل',
        'Recording stopped' => 'توقف التسجيل',
        'Recording error' => 'خطأ في التسجيل',
        'Failed to start recording' => 'فشل بدء التسجيل',
        // Common words
        'Session' => 'الجلسة',
        'Student' => 'الطالب',
        'Teacher' => 'المعلم',
        'Circle' => 'الحلقة',
        'Active' => 'نشط',
        'Available' => 'متاح',
        'Full' => 'ممتلئ',
        'Closed' => 'مغلق',
        'Inactive' => 'غير نشط',
        'Private Lesson' => 'درس خصوصي',
        'Individual Circle' => 'حلقة فردية',
        'Trial Session' => 'جلسة تجريبية',
        'Trial Description' => 'وصف الجلسة التجريبية',
        'Students Count' => 'عدد الطلاب',
        'Progress' => 'التقدم',
        'Schedule' => 'الجدول',
        'Memorization Level' => 'مستوى الحفظ',
        'Monthly Fee' => 'الرسوم الشهرية',
        'View Circle' => 'عرض الحلقة',
        'View Details' => 'عرض التفاصيل',
        'View Session' => 'عرض الجلسة',
        'Default Title' => 'العنوان الافتراضي',
        'Title' => 'العنوان',
        'Reset Filters' => 'إعادة تعيين الفلاتر',
        'Search' => 'بحث',
        'Search Placeholder' => 'ابحث هنا...',
        'Enrollment Status' => 'حالة التسجيل',
        'All' => 'الكل',
        'Enrolled' => 'مسجل',
        'Open' => 'مفتوح',
        'All Levels' => 'جميع المستويات',
        'Beginner' => 'مبتدئ',
        'Intermediate' => 'متوسط',
        'Advanced' => 'متقدم',
        'All Teachers' => 'جميع المعلمين',
        'Schedule Day' => 'يوم الجدول',
        'All Days' => 'جميع الأيام',
        'Saturday' => 'السبت',
        'Sunday' => 'الأحد',
        'Monday' => 'الاثنين',
        'Tuesday' => 'الثلاثاء',
        'Wednesday' => 'الأربعاء',
        'Thursday' => 'الخميس',
        'Friday' => 'الجمعة',
        'Specialization' => 'التخصص',
        'All Specializations' => 'جميع التخصصات',
        'Quran' => 'قرآن',
        'Academic' => 'أكاديمي',
        'Apply Filters' => 'تطبيق الفلاتر',
        'Individual Sessions' => 'الجلسات الفردية',
        'Sessions Used' => 'الجلسات المستخدمة',
        'Last Session' => 'آخر جلسة',
        'days ago' => 'منذ أيام',
        'Sessions Remaining' => 'الجلسات المتبقية',
        'Subscription Cycle' => 'دورة الاشتراك',
        'Monthly' => 'شهري',
        'Quarterly' => 'ربع سنوي',
        'Yearly' => 'سنوي',
        'Preparing Circle' => 'جاري تحضير الحلقة',
        'Group Circle' => 'حلقة جماعية',
        'Students Enrolled' => 'الطلاب المسجلون',
        'Remaining' => 'المتبقي',
        'Renewal Date' => 'تاريخ التجديد',
        'Submit Homework' => 'تسليم الواجب',
        'Due Date' => 'تاريخ الاستحقاق',
        'Cannot Submit' => 'لا يمكن التسليم',
        'Pages Placeholder' => 'أدخل الصفحات',
        'Pages Placeholder Review' => 'أدخل صفحات المراجعة',
    ];

    public function handle(): void
    {
        $file = $this->argument('file');
        $dryRun = $this->option('dry-run');

        $langDir = base_path('lang');
        $arFile = "{$langDir}/ar/{$file}.php";
        $enFile = "{$langDir}/en/{$file}.php";

        if (! file_exists($arFile) || ! file_exists($enFile)) {
            $this->error("Translation file not found: {$file}.php");

            return;
        }

        $arArray = include $arFile;
        $enArray = include $enFile;

        $arDot = Arr::dot($arArray);
        $enDot = Arr::dot($enArray);

        $missingInAr = array_diff_key($enDot, $arDot);
        $missingInEn = array_diff_key($arDot, $enDot);

        $this->info("File: {$file}.php");
        $this->info('Missing in Arabic: '.count($missingInAr).' keys');
        $this->info('Missing in English: '.count($missingInEn).' keys');

        if (count($missingInAr) === 0 && count($missingInEn) === 0) {
            $this->info('✓ Files are already in sync!');

            return;
        }

        // Add missing keys to Arabic
        foreach ($missingInAr as $key => $value) {
            // Skip array values (nested structures)
            if (is_array($value)) {
                continue;
            }
            $translatedValue = $this->autoTranslate($value, 'ar');
            Arr::set($arArray, $key, $translatedValue);
            if ($dryRun) {
                $this->line("Would add to AR: {$key} => {$translatedValue}");
            }
        }

        // Add missing keys to English (reverse translate or use original)
        foreach ($missingInEn as $key => $value) {
            // Skip array values (nested structures)
            if (is_array($value)) {
                continue;
            }
            $translatedValue = $this->autoTranslate($value, 'en');
            Arr::set($enArray, $key, $translatedValue);
            if ($dryRun) {
                $this->line("Would add to EN: {$key} => {$translatedValue}");
            }
        }

        if (! $dryRun) {
            // Write updated arrays back to files
            $this->writeTranslationFile($arFile, $arArray);
            $this->writeTranslationFile($enFile, $enArray);

            $this->info("✓ Successfully synced {$file}.php");
            $this->info('  Added '.count($missingInAr).' keys to Arabic');
            $this->info('  Added '.count($missingInEn).' keys to English');
        } else {
            $this->warn('Dry run - no changes made');
        }
    }

    protected function autoTranslate(string $value, string $targetLang): string
    {
        // Additional translations for better coverage
        $extraTranslations = [
            'An unexpected error occurred' => 'حدث خطأ غير متوقع',
            'LiveKit not available' => 'خدمة لايف كيت غير متاحة',
            'Initialization error' => 'خطأ في التهيئة',
            'Attendance is tracked automatically' => 'يتم تتبع الحضور تلقائياً',
            'Failed to record your entry to the session' => 'فشل في تسجيل دخولك للجلسة',
            'Failed to record your exit from the session' => 'فشل في تسجيل خروجك من الجلسة',
            'Waiting to start' => 'في انتظار البدء',
            'Did not join yet' => 'لم ينضم بعد',
            'Not specified' => 'غير محدد',
            'Start course recording' => 'بدء تسجيل الدورة',
            'Stop course recording' => 'إيقاف تسجيل الدورة',
            'Interactive course recording started' => 'بدأ تسجيل الدورة التفاعلية',
            'No active recording' => 'لا يوجد تسجيل نشط',
            'Failed to stop recording' => 'فشل إيقاف التسجيل',
            'Denied' => 'مرفوض',
            'Needs Permission' => 'يحتاج إذن',
            'Compatible' => 'متوافق',
            'Not Compatible' => 'غير متوافق',
            'Are you sure you want to cancel this session?' => 'هل أنت متأكد من إلغاء هذه الجلسة؟',
            'This session will not be counted in the subscription' => 'لن يتم احتساب هذه الجلسة في الاشتراك',
            'Failed to cancel session' => 'فشل في إلغاء الجلسة',
            'An error occurred while cancelling the session' => 'حدث خطأ أثناء إلغاء الجلسة',
            'cancelled successfully' => 'تم الإلغاء بنجاح',
            'session' => 'جلسة',
            'minutes' => 'دقائق',
            'time' => 'الوقت',
            'now' => 'الآن',
            'for' => 'لمدة',
            'in' => 'في',
            'the' => 'ال',
            'Get ready for the session' => 'استعد للجلسة',
            'Conclude session soon' => 'أنهِ الجلسة قريباً',
            'Please reload the page' => 'يرجى إعادة تحميل الصفحة',
            'to session' => 'بالجلسة',
            'and saved successfully' => 'وتم الحفظ بنجاح',
            'Allowed' => 'مسموح',
            'Scheduled Time' => 'الوقت المجدول',
            'Not Specified' => 'غير محدد',
            'State' => 'الحالة',
            'ongoing' => 'جارية',
            'currently active' => 'نشطة حالياً',
            // Arabic to English reverse
            'أدخل الصفحات' => 'Enter pages',
            'أدخل صفحات المراجعة' => 'Enter review pages',
            'نشط' => 'Active',
            'متاح' => 'Available',
            'ممتلئ' => 'Full',
            'مغلق' => 'Closed',
            'غير نشط' => 'Inactive',
            'درس خصوصي' => 'Private Lesson',
            'حلقة فردية' => 'Individual Circle',
            'جلسة تجريبية' => 'Trial Session',
            'معلم' => 'Teacher',
            'عدد الطلاب' => 'Students Count',
            'التقدم' => 'Progress',
            'جلسة' => 'Session',
            'الجدول' => 'Schedule',
            'مستوى الحفظ' => 'Memorization Level',
            'الرسوم الشهرية' => 'Monthly Fee',
            'عرض الحلقة' => 'View Circle',
            'عرض التفاصيل' => 'View Details',
            'عرض الجلسة' => 'View Session',
            'العنوان الافتراضي' => 'Default Title',
            'العنوان' => 'Title',
            'إعادة تعيين الفلاتر' => 'Reset Filters',
            'بحث' => 'Search',
            'ابحث هنا...' => 'Search here...',
            'حالة التسجيل' => 'Enrollment Status',
            'الكل' => 'All',
            'مسجل' => 'Enrolled',
            'مفتوح' => 'Open',
            'جميع المستويات' => 'All Levels',
            'مبتدئ' => 'Beginner',
            'متوسط' => 'Intermediate',
            'متقدم' => 'Advanced',
            'جميع المعلمين' => 'All Teachers',
            'يوم الجدول' => 'Schedule Day',
            'جميع الأيام' => 'All Days',
            'السبت' => 'Saturday',
            'الأحد' => 'Sunday',
            'الاثنين' => 'Monday',
            'الثلاثاء' => 'Tuesday',
            'الأربعاء' => 'Wednesday',
            'الخميس' => 'Thursday',
            'الجمعة' => 'Friday',
            'التخصص' => 'Specialization',
            'جميع التخصصات' => 'All Specializations',
            'قرآن' => 'Quran',
            'أكاديمي' => 'Academic',
            'تطبيق الفلاتر' => 'Apply Filters',
            'الجلسات الفردية' => 'Individual Sessions',
            'الجلسات المستخدمة' => 'Sessions Used',
            'منذ أيام' => 'days ago',
            'الجلسات المتبقية' => 'Sessions Remaining',
            'دورة الاشتراك' => 'Subscription Cycle',
            'شهري' => 'Monthly',
            'ربع سنوي' => 'Quarterly',
            'سنوي' => 'Yearly',
            'جاري تحضير الحلقة' => 'Preparing Circle',
            'حلقة جماعية' => 'Group Circle',
            'الطلاب المسجلون' => 'Students Enrolled',
            'المتبقي' => 'Remaining',
            'تاريخ التجديد' => 'Renewal Date',
            'تسليم الواجب' => 'Submit Homework',
            'تاريخ الاستحقاق' => 'Due Date',
            'متأخر' => 'Late',
            'لا يمكن التسليم' => 'Cannot Submit',
            'آخر جلسة' => 'Last Session',
        ];

        $allTranslations = array_merge($this->translations, $extraTranslations);

        if ($targetLang === 'ar') {
            // Try exact match first
            if (isset($allTranslations[$value])) {
                return $allTranslations[$value];
            }

            // Try partial replacement
            $result = $value;
            foreach ($allTranslations as $en => $ar) {
                if (strlen($en) > 2) { // Only replace longer strings to avoid issues
                    $result = str_replace($en, $ar, $result);
                }
            }

            // Clean up any remaining English artifacts
            $result = preg_replace('/\s+/', ' ', trim($result));

            // If significant translation happened, return it
            if ($result !== $value && strlen($result) > 0) {
                return $result;
            }

            // Return the original with TODO marker
            return "[AR: {$value}]";
        }

        // For English, reverse lookup
        $reverseTranslations = array_flip($allTranslations);
        if (isset($reverseTranslations[$value])) {
            return $reverseTranslations[$value];
        }

        // Try partial replacement for Arabic to English
        $result = $value;
        foreach ($allTranslations as $en => $ar) {
            if (strlen($ar) > 2) {
                $result = str_replace($ar, $en, $result);
            }
        }

        if ($result !== $value && strlen($result) > 0) {
            return $result;
        }

        return "[EN: {$value}]";
    }

    protected function writeTranslationFile(string $path, array $array): void
    {
        $export = var_export($array, true);

        // Clean up the export formatting
        $export = preg_replace('/array \(/', '[', $export);
        $export = preg_replace('/\)$/', ']', $export);
        $export = preg_replace('/\)(\s*,?)/', ']$1', $export);
        $export = preg_replace('/\'\s*=>\s*\[/', '\' => [', $export);

        $content = "<?php\n\nreturn {$export};\n";

        file_put_contents($path, $content);
    }
}
