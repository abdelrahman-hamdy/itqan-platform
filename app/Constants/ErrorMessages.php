<?php

namespace App\Constants;

/**
 * Centralized error messages for consistent error handling across the application.
 * All messages are in Arabic as this is the primary locale.
 */
final class ErrorMessages
{
    // ============================================
    // Parent Authorization Messages
    // ============================================

    /** Parents have view-only permissions */
    public const PARENT_VIEW_ONLY = 'أولياء الأمور لديهم صلاحيات مشاهدة فقط';

    /** Cannot access parent data */
    public const PARENT_DATA_ACCESS_DENIED = 'لا يمكن الوصول إلى بيانات ولي الأمر';

    /** Parent profile not found */
    public const PARENT_PROFILE_NOT_FOUND = 'ملف ولي الأمر غير موجود';

    // ============================================
    // Student Access Messages
    // ============================================

    /** Cannot access this student's data */
    public const STUDENT_ACCESS_DENIED = 'لا يمكنك الوصول إلى بيانات هذا الطالب';

    /** Student not enrolled in this circle */
    public const STUDENT_NOT_ENROLLED = 'الطالب غير مسجل في هذه الحلقة';

    // ============================================
    // Payment Messages
    // ============================================

    /** Cannot access this payment */
    public const PAYMENT_ACCESS_DENIED = 'لا يمكنك الوصول إلى هذا الدفع';

    /** Payment receipt not available */
    public const RECEIPT_UNAVAILABLE = 'إيصال الدفع غير متوفر';

    /** Cannot download receipt for incomplete payment */
    public const INCOMPLETE_PAYMENT_RECEIPT = 'لا يمكن تحميل إيصال لدفعة غير مكتملة';

    // ============================================
    // Subscription Messages
    // ============================================

    /** Cannot access this subscription */
    public const SUBSCRIPTION_ACCESS_DENIED = 'لا يمكنك الوصول إلى هذا الاشتراك';

    /** Invalid subscription type */
    public const INVALID_SUBSCRIPTION_TYPE = 'نوع الاشتراك غير صحيح';

    // ============================================
    // Session Messages
    // ============================================

    /** Cannot access this session */
    public const SESSION_ACCESS_DENIED = 'لا يمكنك الوصول إلى هذه الجلسة';

    // ============================================
    // Homework Messages
    // ============================================

    /** Cannot access this homework */
    public const HOMEWORK_ACCESS_DENIED = 'لا يمكنك الوصول إلى هذا الواجب';

    // ============================================
    // Report Messages
    // ============================================

    /** Cannot access this report */
    public const REPORT_ACCESS_DENIED = 'لا يمكنك الوصول إلى هذا التقرير';

    /** Report not found */
    public const REPORT_NOT_FOUND = 'التقرير غير موجود';

    // ============================================
    // Certificate Messages
    // ============================================

    /** Cannot access this certificate */
    public const CERTIFICATE_ACCESS_DENIED = 'لا يمكنك الوصول إلى هذه الشهادة';

    /** Certificate file not available */
    public const CERTIFICATE_FILE_UNAVAILABLE = 'ملف الشهادة غير متوفر';

    // ============================================
    // Quiz Messages
    // ============================================

    /** Cannot access quiz results */
    public const QUIZ_RESULTS_ACCESS_DENIED = 'لا يمكنك الوصول إلى نتائج هذا الاختبار';

    // ============================================
    // Lesson/Course Messages
    // ============================================

    /** Cannot access lesson materials */
    public const LESSON_MATERIALS_ACCESS_DENIED = 'لا يمكنك الوصول لمواد هذا الدرس';

    /** Cannot access this video */
    public const VIDEO_ACCESS_DENIED = 'لا يمكنك الوصول لهذا الفيديو';

    /** No downloadable materials available */
    public const NO_DOWNLOADABLE_MATERIALS = 'لا توجد مواد قابلة للتحميل';

    // ============================================
    // Teacher Messages
    // ============================================

    /** Teacher profile not found */
    public const TEACHER_PROFILE_NOT_FOUND = 'ملف المعلم غير موجود';

    // ============================================
    // Circle Messages
    // ============================================

    /** Quran circle not found */
    public const QURAN_CIRCLE_NOT_FOUND = 'دائرة القرآن غير موجودة';

    // ============================================
    // Generic Not Found Messages (English - used for API/system errors)
    // ============================================

    public const ACADEMY_NOT_FOUND = 'Academy not found';

    public const COURSE_NOT_FOUND = 'Course not found';

    public const TEACHER_NOT_FOUND = 'Teacher not found';

    public const PACKAGE_NOT_FOUND = 'Package not found';

    // ============================================
    // Generic Authorization Messages
    // ============================================

    public const UNAUTHORIZED = 'غير مصرح بالوصول';

    public const FORBIDDEN = 'الوصول محظور';
}
