<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception for enrollment-related errors (circles, courses)
 */
class EnrollmentException extends Exception
{
    public static function circleFull(): self
    {
        return new self('الحلقة مكتملة العدد');
    }

    public static function studentNotEligible(): self
    {
        return new self('لا يمكن تسجيل هذا الطالب في الحلقة');
    }

    public static function insufficientStudents(int $required, int $current): self
    {
        return new self("عدد الطلاب غير كافي لبدء الحلقة (مطلوب {$required}، متوفر {$current})");
    }

    public static function alreadyEnrolled(): self
    {
        return new self('الطالب مسجل بالفعل في هذه الحلقة');
    }

    public static function notEnrolled(): self
    {
        return new self('الطالب غير مسجل في هذه الحلقة');
    }

    public static function courseCapacityReached(): self
    {
        return new self('الدورة مكتملة العدد');
    }

    public static function courseEnrollmentClosed(): self
    {
        return new self('التسجيل في الدورة مغلق');
    }

    public static function subscriptionRequired(): self
    {
        return new self('يجب الاشتراك أولاً للتسجيل');
    }

    public static function duplicateEnrollment(): self
    {
        return new self('لا يمكن التسجيل مرتين في نفس الدورة');
    }
}
