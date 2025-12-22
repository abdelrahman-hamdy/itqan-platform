<?php

use App\Models\Academy;
use App\Models\QuranCircle;
use App\Models\QuranSubscription;
use App\Models\User;
use App\Services\CircleEnrollmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

describe('CircleEnrollmentService', function () {
    beforeEach(function () {
        $this->service = new CircleEnrollmentService();
        $this->academy = Academy::factory()->create();
    });

    describe('enroll()', function () {
        it('successfully enrolls a student in an open circle', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
                'enrollment_status' => 'open',
                'max_students' => 10,
                'enrolled_students' => 0,
                'monthly_fee' => 200,
            ]);

            $result = $this->service->enroll($student, $circle);

            expect($result)->toHaveKey('success')
                ->and($result['success'])->toBeTrue()
                ->and($result)->toHaveKey('message');
        });

        it('attaches student to circle with correct pivot data', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
                'enrollment_status' => 'open',
                'max_students' => 10,
                'enrolled_students' => 0,
            ]);

            $this->service->enroll($student, $circle);

            $pivotData = DB::table('quran_circle_students')
                ->where('circle_id', $circle->id)
                ->where('student_id', $student->id)
                ->first();

            expect($pivotData)->not->toBeNull()
                ->and($pivotData->status)->toBe('enrolled')
                ->and($pivotData->attendance_count)->toBe(0)
                ->and($pivotData->missed_sessions)->toBe(0)
                ->and($pivotData->makeup_sessions_used)->toBe(0)
                ->and($pivotData->current_level)->toBe('beginner');
        });

        it('creates a group subscription for the enrollment', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
                'enrollment_status' => 'open',
                'max_students' => 10,
                'enrolled_students' => 0,
                'sessions_per_month' => 8,
                'monthly_fee' => 200,
            ]);

            $this->service->enroll($student, $circle);

            $subscription = QuranSubscription::where('student_id', $student->id)
                ->where('quran_teacher_id', $teacher->id)
                ->where('subscription_type', 'group')
                ->first();

            expect($subscription)->not->toBeNull()
                ->and($subscription->academy_id)->toBe($this->academy->id)
                ->and($subscription->total_sessions)->toBe(8)
                ->and($subscription->sessions_remaining)->toBe(8)
                ->and($subscription->final_price)->toBe('200.00')
                ->and($subscription->status->value)->toBe('active');
        });

        it('increments circle enrolled_students count', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
                'enrollment_status' => 'open',
                'max_students' => 10,
                'enrolled_students' => 3,
            ]);

            $this->service->enroll($student, $circle);

            expect($circle->fresh()->enrolled_students)->toBe(4);
        });

        it('marks circle as full when max capacity is reached', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
                'enrollment_status' => 'open',
                'max_students' => 5,
                'enrolled_students' => 4,
            ]);

            $this->service->enroll($student, $circle);

            expect($circle->fresh()->enrollment_status)->toBe('full');
        });

        it('sets payment status to paid when circle is free', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
                'enrollment_status' => 'open',
                'max_students' => 10,
                'enrolled_students' => 0,
                'monthly_fee' => 0,
            ]);

            $this->service->enroll($student, $circle);

            $subscription = QuranSubscription::where('student_id', $student->id)
                ->where('subscription_type', 'group')
                ->first();

            expect($subscription->payment_status->value)->toBe('paid');
        });

        it('sets payment status to pending when circle has fee', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
                'enrollment_status' => 'open',
                'max_students' => 10,
                'enrolled_students' => 0,
                'monthly_fee' => 200,
            ]);

            $this->service->enroll($student, $circle);

            $subscription = QuranSubscription::where('student_id', $student->id)
                ->where('subscription_type', 'group')
                ->first();

            expect($subscription->payment_status->value)->toBe('pending');
        });

        it('returns error when student is already enrolled', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
                'enrollment_status' => 'open',
                'max_students' => 10,
                'enrolled_students' => 0,
            ]);

            $this->service->enroll($student, $circle);
            $result = $this->service->enroll($student, $circle);

            expect($result['success'])->toBeFalse()
                ->and($result)->toHaveKey('error')
                ->and($result['error'])->toBe('You are already enrolled in this circle');
        });

        it('returns error when circle is not active', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => false,
                'enrollment_status' => 'open',
            ]);

            $result = $this->service->enroll($student, $circle);

            expect($result['success'])->toBeFalse()
                ->and($result['error'])->toBe('This circle is not active');
        });

        it('returns error when circle is not open for enrollment', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
                'enrollment_status' => 'closed',
            ]);

            $result = $this->service->enroll($student, $circle);

            expect($result['success'])->toBeFalse()
                ->and($result['error'])->toBe('This circle is not open for enrollment');
        });

        it('returns error when circle is full', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
                'enrollment_status' => 'open',
                'max_students' => 5,
                'enrolled_students' => 5,
            ]);

            $result = $this->service->enroll($student, $circle);

            expect($result['success'])->toBeFalse()
                ->and($result['error'])->toBe('This circle is full');
        });

        it('executes enrollment in database transaction', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
                'enrollment_status' => 'open',
                'max_students' => 10,
                'enrolled_students' => 0,
            ]);

            DB::shouldReceive('transaction')
                ->once()
                ->andReturnUsing(function ($callback) {
                    return $callback();
                });

            $this->service->enroll($student, $circle);

            expect(true)->toBeTrue();
        });

        it('logs error and throws exception on failure', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
                'enrollment_status' => 'open',
                'max_students' => 10,
                'enrolled_students' => 0,
            ]);

            DB::shouldReceive('transaction')
                ->once()
                ->andThrow(new \Exception('Database error'));

            Log::shouldReceive('error')
                ->once()
                ->withArgs(function ($message, $context) use ($student, $circle) {
                    return $message === 'Error enrolling student in circle'
                        && $context['user_id'] === $student->id
                        && $context['circle_id'] === $circle->id;
                });

            expect(fn () => $this->service->enroll($student, $circle))
                ->toThrow(\Exception::class);
        });
    });

    describe('leave()', function () {
        it('successfully removes a student from a circle', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
                'enrollment_status' => 'open',
                'max_students' => 10,
                'enrolled_students' => 1,
            ]);

            $this->service->enroll($student, $circle);
            $result = $this->service->leave($student, $circle);

            expect($result['success'])->toBeTrue()
                ->and($result)->toHaveKey('message');
        });

        it('detaches student from circle', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
                'enrollment_status' => 'open',
                'max_students' => 10,
                'enrolled_students' => 1,
            ]);

            $this->service->enroll($student, $circle);
            $this->service->leave($student, $circle);

            $pivotData = DB::table('quran_circle_students')
                ->where('circle_id', $circle->id)
                ->where('student_id', $student->id)
                ->first();

            expect($pivotData)->toBeNull();
        });

        it('cancels active group subscription when leaving', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
                'enrollment_status' => 'open',
                'max_students' => 10,
                'enrolled_students' => 0,
            ]);

            $this->service->enroll($student, $circle);
            $this->service->leave($student, $circle);

            $subscription = QuranSubscription::where('student_id', $student->id)
                ->where('quran_teacher_id', $teacher->id)
                ->where('subscription_type', 'group')
                ->first();

            expect($subscription->status->value)->toBe('cancelled');
        });

        it('decrements circle enrolled_students count', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
                'enrollment_status' => 'open',
                'max_students' => 10,
                'enrolled_students' => 5,
            ]);

            $this->service->enroll($student, $circle);
            $enrolledCount = $circle->fresh()->enrolled_students;
            $this->service->leave($student, $circle);

            expect($circle->fresh()->enrolled_students)->toBe($enrolledCount - 1);
        });

        it('changes circle status from full to open when student leaves', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
                'enrollment_status' => 'full',
                'max_students' => 5,
                'enrolled_students' => 5,
            ]);

            $circle->students()->attach($student->id, [
                'enrolled_at' => now(),
                'status' => 'enrolled',
            ]);

            $this->service->leave($student, $circle);

            expect($circle->fresh()->enrollment_status)->toBe('open');
        });

        it('returns error when student is not enrolled', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
                'enrollment_status' => 'open',
            ]);

            $result = $this->service->leave($student, $circle);

            expect($result['success'])->toBeFalse()
                ->and($result['error'])->toBe('You are not enrolled in this circle');
        });

        it('executes leave in database transaction', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
                'enrollment_status' => 'open',
            ]);

            $this->service->enroll($student, $circle);

            DB::shouldReceive('transaction')
                ->once()
                ->andReturnUsing(function ($callback) {
                    return $callback();
                });

            $this->service->leave($student, $circle);

            expect(true)->toBeTrue();
        });

        it('logs error and throws exception on failure', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
                'enrollment_status' => 'open',
            ]);

            $this->service->enroll($student, $circle);

            DB::shouldReceive('transaction')
                ->once()
                ->andThrow(new \Exception('Database error'));

            Log::shouldReceive('error')
                ->once()
                ->withArgs(function ($message, $context) use ($student, $circle) {
                    return $message === 'Error removing student from circle'
                        && $context['user_id'] === $student->id
                        && $context['circle_id'] === $circle->id;
                });

            expect(fn () => $this->service->leave($student, $circle))
                ->toThrow(\Exception::class);
        });
    });

    describe('isEnrolled()', function () {
        it('returns true when student is enrolled in circle', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
                'enrollment_status' => 'open',
            ]);

            $circle->students()->attach($student->id, [
                'enrolled_at' => now(),
                'status' => 'enrolled',
            ]);

            $result = $this->service->isEnrolled($student, $circle);

            expect($result)->toBeTrue();
        });

        it('returns false when student is not enrolled in circle', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
                'enrollment_status' => 'open',
            ]);

            $result = $this->service->isEnrolled($student, $circle);

            expect($result)->toBeFalse();
        });
    });

    describe('canEnroll()', function () {
        it('returns true when student can enroll', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
                'enrollment_status' => 'open',
                'max_students' => 10,
                'enrolled_students' => 3,
            ]);

            $result = $this->service->canEnroll($student, $circle);

            expect($result)->toBeTrue();
        });

        it('returns false when student is already enrolled', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
                'enrollment_status' => 'open',
            ]);

            $circle->students()->attach($student->id, [
                'enrolled_at' => now(),
                'status' => 'enrolled',
            ]);

            $result = $this->service->canEnroll($student, $circle);

            expect($result)->toBeFalse();
        });

        it('returns false when circle is not active', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => false,
                'enrollment_status' => 'open',
            ]);

            $result = $this->service->canEnroll($student, $circle);

            expect($result)->toBeFalse();
        });

        it('returns false when circle is not open for enrollment', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
                'enrollment_status' => 'closed',
            ]);

            $result = $this->service->canEnroll($student, $circle);

            expect($result)->toBeFalse();
        });

        it('returns false when circle is full', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
                'enrollment_status' => 'open',
                'max_students' => 5,
                'enrolled_students' => 5,
            ]);

            $result = $this->service->canEnroll($student, $circle);

            expect($result)->toBeFalse();
        });
    });

    describe('getOrCreateSubscription()', function () {
        it('returns null when student is not enrolled', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            $result = $this->service->getOrCreateSubscription($student, $circle);

            expect($result)->toBeNull();
        });

        it('returns existing subscription when student is enrolled', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
                'enrollment_status' => 'open',
            ]);

            $this->service->enroll($student, $circle);

            $result = $this->service->getOrCreateSubscription($student, $circle);

            expect($result)->toBeInstanceOf(QuranSubscription::class)
                ->and($result->student_id)->toBe($student->id)
                ->and($result->quran_teacher_id)->toBe($teacher->id)
                ->and($result->subscription_type)->toBe('group');
        });

        it('creates new subscription when student is enrolled but has no subscription', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
                'enrollment_status' => 'open',
                'sessions_per_month' => 8,
                'monthly_fee' => 200,
            ]);

            $circle->students()->attach($student->id, [
                'enrolled_at' => now(),
                'status' => 'enrolled',
            ]);

            QuranSubscription::where('student_id', $student->id)
                ->where('quran_teacher_id', $teacher->id)
                ->delete();

            $result = $this->service->getOrCreateSubscription($student, $circle);

            expect($result)->toBeInstanceOf(QuranSubscription::class)
                ->and($result->student_id)->toBe($student->id)
                ->and($result->total_sessions)->toBe(8)
                ->and($result->final_price)->toBe('200.00');
        });

        it('uses circle monthly_fee for subscription price', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
                'enrollment_status' => 'open',
                'monthly_fee' => 350,
            ]);

            $circle->students()->attach($student->id, [
                'enrolled_at' => now(),
                'status' => 'enrolled',
            ]);

            $result = $this->service->getOrCreateSubscription($student, $circle);

            expect($result->final_price)->toBe('350.00');
        });

        it('uses circle sessions_per_month for total sessions', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
                'enrollment_status' => 'open',
                'sessions_per_month' => 12,
            ]);

            $circle->students()->attach($student->id, [
                'enrolled_at' => now(),
                'status' => 'enrolled',
            ]);

            $result = $this->service->getOrCreateSubscription($student, $circle);

            expect($result->total_sessions)->toBe(12)
                ->and($result->sessions_remaining)->toBe(12);
        });

        it('sets payment status to paid for free circles', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
                'enrollment_status' => 'open',
                'monthly_fee' => 0,
            ]);

            $circle->students()->attach($student->id, [
                'enrolled_at' => now(),
                'status' => 'enrolled',
            ]);

            $result = $this->service->getOrCreateSubscription($student, $circle);

            expect($result->payment_status->value)->toBe('paid');
        });

        it('sets payment status to pending for paid circles', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
                'enrollment_status' => 'open',
                'monthly_fee' => 200,
            ]);

            $circle->students()->attach($student->id, [
                'enrolled_at' => now(),
                'status' => 'enrolled',
            ]);

            $result = $this->service->getOrCreateSubscription($student, $circle);

            expect($result->payment_status->value)->toBe('pending');
        });

        it('eager loads package and teacher relationships', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
                'enrollment_status' => 'open',
            ]);

            $this->service->enroll($student, $circle);

            $result = $this->service->getOrCreateSubscription($student, $circle);

            expect($result->relationLoaded('package'))->toBeTrue()
                ->and($result->relationLoaded('quranTeacherUser'))->toBeTrue();
        });
    });
});
