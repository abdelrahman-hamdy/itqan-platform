<?php

namespace App\Policies;

use App\Models\Certificate;
use App\Models\Payment;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Parent Policy
 *
 * Authorization policy for parent access to student data.
 * Ensures parents can only access data for their linked children.
 */
class ParentPolicy
{
    /**
     * Can user access parent dashboard?
     *
     * @param User $user
     * @return bool
     */
    public function viewDashboard(User $user): bool
    {
        return $user->isParent() && $user->parentProfile !== null;
    }

    /**
     * Can parent view this child's data?
     *
     * @param User $user
     * @param StudentProfile $student
     * @return bool
     */
    public function viewChild(User $user, StudentProfile $student): bool
    {
        if (!$user->isParent()) {
            return false;
        }

        $parent = $user->parentProfile;
        if (!$parent) {
            return false;
        }

        // Check if parent is linked to this student in same academy
        return $parent->students()
            ->where('student_profiles.id', $student->id)
            ->forAcademy($parent->academy_id)
            ->exists();
    }

    /**
     * Can parent view child's subscriptions?
     *
     * @param User $user
     * @param StudentProfile $student
     * @return bool
     */
    public function viewChildSubscriptions(User $user, StudentProfile $student): bool
    {
        return $this->viewChild($user, $student);
    }

    /**
     * Can parent view child's sessions?
     *
     * @param User $user
     * @param StudentProfile $student
     * @return bool
     */
    public function viewChildSessions(User $user, StudentProfile $student): bool
    {
        return $this->viewChild($user, $student);
    }

    /**
     * Can parent view child's payments?
     *
     * @param User $user
     * @param StudentProfile $student
     * @return bool
     */
    public function viewChildPayments(User $user, StudentProfile $student): bool
    {
        return $this->viewChild($user, $student);
    }

    /**
     * Can parent view child's certificates?
     *
     * @param User $user
     * @param StudentProfile $student
     * @return bool
     */
    public function viewChildCertificates(User $user, StudentProfile $student): bool
    {
        return $this->viewChild($user, $student);
    }

    /**
     * Can parent view child's quiz results?
     *
     * @param User $user
     * @param StudentProfile $student
     * @return bool
     */
    public function viewChildQuizResults(User $user, StudentProfile $student): bool
    {
        return $this->viewChild($user, $student);
    }

    /**
     * Can parent view child's homework?
     *
     * @param User $user
     * @param StudentProfile $student
     * @return bool
     */
    public function viewChildHomework(User $user, StudentProfile $student): bool
    {
        return $this->viewChild($user, $student);
    }

    /**
     * Can parent view child's reports?
     *
     * @param User $user
     * @param StudentProfile $student
     * @return bool
     */
    public function viewChildReports(User $user, StudentProfile $student): bool
    {
        return $this->viewChild($user, $student);
    }

    /**
     * Can parent view specific certificate?
     *
     * @param User $user
     * @param Certificate $certificate
     * @return bool
     */
    public function viewCertificate(User $user, Certificate $certificate): bool
    {
        if (!$user->isParent()) {
            return false;
        }

        $parent = $user->parentProfile;
        if (!$parent) {
            return false;
        }

        // Check if certificate belongs to one of parent's children
        $student = $certificate->student;
        if (!$student) {
            return false;
        }

        $studentProfile = $student->studentProfileUnscoped;
        if (!$studentProfile) {
            return false;
        }

        return $parent->students()
            ->where('student_profiles.id', $studentProfile->id)
            ->forAcademy($parent->academy_id)
            ->exists();
    }

    /**
     * Can parent download certificate?
     *
     * @param User $user
     * @param Certificate $certificate
     * @return bool
     */
    public function downloadCertificate(User $user, Certificate $certificate): bool
    {
        return $this->viewCertificate($user, $certificate);
    }

    /**
     * Can parent view specific payment?
     *
     * @param User $user
     * @param Payment $payment
     * @return bool
     */
    public function viewPayment(User $user, Payment $payment): bool
    {
        if (!$user->isParent()) {
            return false;
        }

        $parent = $user->parentProfile;
        if (!$parent) {
            return false;
        }

        // Check if payment belongs to one of parent's children
        $student = $payment->user;
        if (!$student) {
            return false;
        }

        $studentProfile = $student->studentProfileUnscoped;
        if (!$studentProfile) {
            return false;
        }

        return $parent->students()
            ->where('student_profiles.id', $studentProfile->id)
            ->forAcademy($parent->academy_id)
            ->exists();
    }

    /**
     * Can parent download payment receipt?
     *
     * @param User $user
     * @param Payment $payment
     * @return bool
     */
    public function downloadReceipt(User $user, Payment $payment): bool
    {
        return $this->viewPayment($user, $payment);
    }

    /**
     * Parents CANNOT update any student data
     *
     * @param User $user
     * @param StudentProfile $student
     * @return bool
     */
    public function update(User $user, StudentProfile $student): bool
    {
        return false;
    }

    /**
     * Parents CANNOT delete any student data
     *
     * @param User $user
     * @param StudentProfile $student
     * @return bool
     */
    public function delete(User $user, StudentProfile $student): bool
    {
        return false;
    }

    /**
     * Parents CANNOT create any data for students
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return false;
    }
}
