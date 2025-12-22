<?php

use App\Models\Academy;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranTeacherProfile;
use App\Models\TeacherEarning;
use App\Models\TeacherPayout;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\PayoutService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

describe('PayoutService', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
        $this->notificationService = Mockery::mock(NotificationService::class);
        $this->service = new PayoutService($this->notificationService);

        $this->quranTeacher = QuranTeacherProfile::factory()->create();
        $this->academicTeacher = AcademicTeacherProfile::factory()->create();
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('generateMonthlyPayout()', function () {
        it('creates a payout for teacher with unpaid earnings', function () {
            TeacherEarning::factory()->count(5)->create([
                'academy_id' => $this->academy->id,
                'teacher_type' => 'quran_teacher',
                'teacher_id' => $this->quranTeacher->id,
                'earning_month' => '2024-01-01',
                'payout_id' => null,
                'is_finalized' => false,
                'is_disputed' => false,
                'amount' => 100.00,
            ]);

            $payout = $this->service->generateMonthlyPayout(
                'quran_teacher',
                $this->quranTeacher->id,
                2024,
                1,
                $this->academy->id
            );

            expect($payout)->toBeInstanceOf(TeacherPayout::class)
                ->and($payout->total_amount)->toBe('500.00')
                ->and($payout->sessions_count)->toBe(5)
                ->and($payout->status)->toBe('pending')
                ->and($payout->teacher_type)->toBe('quran_teacher')
                ->and($payout->teacher_id)->toBe($this->quranTeacher->id);
        });

        it('returns existing payout if already generated for month', function () {
            $existingPayout = TeacherPayout::factory()->create([
                'academy_id' => $this->academy->id,
                'teacher_type' => 'quran_teacher',
                'teacher_id' => $this->quranTeacher->id,
                'payout_month' => '2024-01-01',
            ]);

            Log::shouldReceive('info')
                ->once()
                ->withArgs(function ($message, $context) use ($existingPayout) {
                    return str_contains($message, 'Payout already exists')
                        && $context['payout_id'] === $existingPayout->id;
                });

            $payout = $this->service->generateMonthlyPayout(
                'quran_teacher',
                $this->quranTeacher->id,
                2024,
                1,
                $this->academy->id
            );

            expect($payout->id)->toBe($existingPayout->id);
        });

        it('returns null when no unpaid earnings exist', function () {
            Log::shouldReceive('info')
                ->once()
                ->withArgs(function ($message) {
                    return str_contains($message, 'No unpaid earnings found');
                });

            $payout = $this->service->generateMonthlyPayout(
                'quran_teacher',
                $this->quranTeacher->id,
                2024,
                1,
                $this->academy->id
            );

            expect($payout)->toBeNull();
        });

        it('links earnings to payout and finalizes them', function () {
            $earnings = TeacherEarning::factory()->count(3)->create([
                'academy_id' => $this->academy->id,
                'teacher_type' => 'academic_teacher',
                'teacher_id' => $this->academicTeacher->id,
                'earning_month' => '2024-02-01',
                'payout_id' => null,
                'is_finalized' => false,
                'is_disputed' => false,
                'amount' => 150.00,
            ]);

            $payout = $this->service->generateMonthlyPayout(
                'academic_teacher',
                $this->academicTeacher->id,
                2024,
                2,
                $this->academy->id
            );

            $updatedEarnings = TeacherEarning::whereIn('id', $earnings->pluck('id'))->get();

            expect($updatedEarnings)->each->toHaveKey('payout_id', $payout->id)
                ->and($updatedEarnings)->each->toHaveKey('is_finalized', true);
        });

        it('excludes disputed earnings from payout', function () {
            TeacherEarning::factory()->count(2)->create([
                'academy_id' => $this->academy->id,
                'teacher_type' => 'quran_teacher',
                'teacher_id' => $this->quranTeacher->id,
                'earning_month' => '2024-03-01',
                'payout_id' => null,
                'is_finalized' => false,
                'is_disputed' => false,
                'amount' => 100.00,
            ]);

            TeacherEarning::factory()->create([
                'academy_id' => $this->academy->id,
                'teacher_type' => 'quran_teacher',
                'teacher_id' => $this->quranTeacher->id,
                'earning_month' => '2024-03-01',
                'payout_id' => null,
                'is_finalized' => false,
                'is_disputed' => true,
                'amount' => 100.00,
            ]);

            $payout = $this->service->generateMonthlyPayout(
                'quran_teacher',
                $this->quranTeacher->id,
                2024,
                3,
                $this->academy->id
            );

            expect($payout->sessions_count)->toBe(2)
                ->and($payout->total_amount)->toBe('200.00');
        });

        it('calculates breakdown by calculation method', function () {
            TeacherEarning::factory()->count(2)->create([
                'academy_id' => $this->academy->id,
                'teacher_type' => 'quran_teacher',
                'teacher_id' => $this->quranTeacher->id,
                'earning_month' => '2024-04-01',
                'calculation_method' => 'individual_rate',
                'amount' => 100.00,
            ]);

            TeacherEarning::factory()->count(3)->create([
                'academy_id' => $this->academy->id,
                'teacher_type' => 'quran_teacher',
                'teacher_id' => $this->quranTeacher->id,
                'earning_month' => '2024-04-01',
                'calculation_method' => 'group_rate',
                'amount' => 50.00,
            ]);

            $payout = $this->service->generateMonthlyPayout(
                'quran_teacher',
                $this->quranTeacher->id,
                2024,
                4,
                $this->academy->id
            );

            expect($payout->breakdown)->toBeArray()
                ->and($payout->breakdown)->toHaveKey('individual_rate')
                ->and($payout->breakdown['individual_rate'])->toMatchArray([
                    'count' => 2,
                    'total' => 200.00,
                ])
                ->and($payout->breakdown)->toHaveKey('group_rate')
                ->and($payout->breakdown['group_rate'])->toMatchArray([
                    'count' => 3,
                    'total' => 150.00,
                ]);
        });
    });

    describe('generatePayoutsForMonth()', function () {
        it('generates payouts for all teachers with earnings', function () {
            $teacher1 = QuranTeacherProfile::factory()->create();
            $teacher2 = QuranTeacherProfile::factory()->create();
            $teacher3 = AcademicTeacherProfile::factory()->create();

            TeacherEarning::factory()->count(2)->create([
                'academy_id' => $this->academy->id,
                'teacher_type' => 'quran_teacher',
                'teacher_id' => $teacher1->id,
                'earning_month' => '2024-05-01',
                'amount' => 100.00,
            ]);

            TeacherEarning::factory()->count(3)->create([
                'academy_id' => $this->academy->id,
                'teacher_type' => 'quran_teacher',
                'teacher_id' => $teacher2->id,
                'earning_month' => '2024-05-01',
                'amount' => 100.00,
            ]);

            TeacherEarning::factory()->create([
                'academy_id' => $this->academy->id,
                'teacher_type' => 'academic_teacher',
                'teacher_id' => $teacher3->id,
                'earning_month' => '2024-05-01',
                'amount' => 100.00,
            ]);

            Log::shouldReceive('info')->zeroOrMoreTimes();

            $payouts = $this->service->generatePayoutsForMonth($this->academy->id, 2024, 5);

            expect($payouts)->toHaveCount(3)
                ->and($payouts->pluck('teacher_id')->toArray())->toContain($teacher1->id, $teacher2->id, $teacher3->id);
        });

        it('returns empty collection when no earnings exist', function () {
            Log::shouldReceive('info')->once();

            $payouts = $this->service->generatePayoutsForMonth($this->academy->id, 2024, 6);

            expect($payouts)->toBeEmpty();
        });

        it('excludes teachers with only disputed earnings', function () {
            $teacher = QuranTeacherProfile::factory()->create();

            TeacherEarning::factory()->count(2)->create([
                'academy_id' => $this->academy->id,
                'teacher_type' => 'quran_teacher',
                'teacher_id' => $teacher->id,
                'earning_month' => '2024-07-01',
                'is_disputed' => true,
                'amount' => 100.00,
            ]);

            Log::shouldReceive('info')->once();

            $payouts = $this->service->generatePayoutsForMonth($this->academy->id, 2024, 7);

            expect($payouts)->toBeEmpty();
        });
    });

    describe('approvePayout()', function () {
        it('approves a pending payout', function () {
            $payout = TeacherPayout::factory()->pending()->create([
                'academy_id' => $this->academy->id,
            ]);

            TeacherEarning::factory()->count(2)->create([
                'academy_id' => $this->academy->id,
                'payout_id' => $payout->id,
                'calculated_at' => now(),
                'is_disputed' => false,
                'amount' => 100.00,
            ]);

            $approver = User::factory()->create();

            Log::shouldReceive('info')->once();
            $this->notificationService->shouldReceive('sendPayoutApprovedNotification')->once();

            $result = $this->service->approvePayout($payout, $approver, 'Approved for payment');

            expect($result)->toBeTrue()
                ->and($payout->fresh()->status)->toBe('approved')
                ->and($payout->fresh()->approved_by)->toBe($approver->id)
                ->and($payout->fresh()->approved_at)->not->toBeNull()
                ->and($payout->fresh()->approval_notes)->toBe('Approved for payment');
        });

        it('throws exception when payout is not pending', function () {
            $payout = TeacherPayout::factory()->paid()->create([
                'academy_id' => $this->academy->id,
            ]);

            $approver = User::factory()->create();

            expect(fn() => $this->service->approvePayout($payout, $approver))
                ->toThrow(\InvalidArgumentException::class, 'Payout cannot be approved in current status: paid');
        });

        it('throws exception when payout has uncalculated earnings', function () {
            $payout = TeacherPayout::factory()->pending()->create([
                'academy_id' => $this->academy->id,
            ]);

            TeacherEarning::factory()->create([
                'academy_id' => $this->academy->id,
                'payout_id' => $payout->id,
                'calculated_at' => null,
                'amount' => 100.00,
            ]);

            $approver = User::factory()->create();

            expect(fn() => $this->service->approvePayout($payout, $approver))
                ->toThrow(\DomainException::class);
        });

        it('throws exception when payout has disputed earnings', function () {
            $payout = TeacherPayout::factory()->pending()->create([
                'academy_id' => $this->academy->id,
            ]);

            TeacherEarning::factory()->create([
                'academy_id' => $this->academy->id,
                'payout_id' => $payout->id,
                'calculated_at' => now(),
                'is_disputed' => true,
                'amount' => 100.00,
            ]);

            $approver = User::factory()->create();

            expect(fn() => $this->service->approvePayout($payout, $approver))
                ->toThrow(\DomainException::class);
        });

        it('throws exception when total amount mismatch', function () {
            $payout = TeacherPayout::factory()->pending()->create([
                'academy_id' => $this->academy->id,
                'total_amount' => 500.00,
            ]);

            TeacherEarning::factory()->count(2)->create([
                'academy_id' => $this->academy->id,
                'payout_id' => $payout->id,
                'calculated_at' => now(),
                'is_disputed' => false,
                'amount' => 100.00,
            ]);

            $approver = User::factory()->create();

            expect(fn() => $this->service->approvePayout($payout, $approver))
                ->toThrow(\DomainException::class);
        });
    });

    describe('rejectPayout()', function () {
        it('rejects a pending payout', function () {
            $payout = TeacherPayout::factory()->pending()->create([
                'academy_id' => $this->academy->id,
            ]);

            $earnings = TeacherEarning::factory()->count(2)->create([
                'academy_id' => $this->academy->id,
                'payout_id' => $payout->id,
                'is_finalized' => true,
            ]);

            $rejector = User::factory()->create();

            Log::shouldReceive('info')->once();
            $this->notificationService->shouldReceive('sendPayoutRejectedNotification')->once();

            $result = $this->service->rejectPayout($payout, $rejector, 'Incorrect calculation');

            expect($result)->toBeTrue()
                ->and($payout->fresh()->status)->toBe('rejected')
                ->and($payout->fresh()->rejected_by)->toBe($rejector->id)
                ->and($payout->fresh()->rejected_at)->not->toBeNull()
                ->and($payout->fresh()->rejection_reason)->toBe('Incorrect calculation');

            $updatedEarnings = TeacherEarning::whereIn('id', $earnings->pluck('id'))->get();
            expect($updatedEarnings)->each->toHaveKey('payout_id', null)
                ->and($updatedEarnings)->each->toHaveKey('is_finalized', false);
        });

        it('rejects an approved payout', function () {
            $payout = TeacherPayout::factory()->approved()->create([
                'academy_id' => $this->academy->id,
            ]);

            TeacherEarning::factory()->count(2)->create([
                'academy_id' => $this->academy->id,
                'payout_id' => $payout->id,
                'is_finalized' => true,
            ]);

            $rejector = User::factory()->create();

            Log::shouldReceive('info')->once();
            $this->notificationService->shouldReceive('sendPayoutRejectedNotification')->once();

            $result = $this->service->rejectPayout($payout, $rejector, 'Payment authorization failed');

            expect($result)->toBeTrue()
                ->and($payout->fresh()->status)->toBe('rejected');
        });

        it('throws exception when payout cannot be rejected', function () {
            $payout = TeacherPayout::factory()->paid()->create([
                'academy_id' => $this->academy->id,
            ]);

            $rejector = User::factory()->create();

            expect(fn() => $this->service->rejectPayout($payout, $rejector, 'Test reason'))
                ->toThrow(\InvalidArgumentException::class, 'Payout cannot be rejected in current status: paid');
        });

        it('unlinks earnings from rejected payout', function () {
            $payout = TeacherPayout::factory()->pending()->create([
                'academy_id' => $this->academy->id,
            ]);

            $earnings = TeacherEarning::factory()->count(3)->create([
                'academy_id' => $this->academy->id,
                'payout_id' => $payout->id,
                'is_finalized' => true,
            ]);

            $rejector = User::factory()->create();

            Log::shouldReceive('info')->once();
            $this->notificationService->shouldReceive('sendPayoutRejectedNotification')->once();

            $this->service->rejectPayout($payout, $rejector, 'Needs revision');

            $updatedEarnings = TeacherEarning::whereIn('id', $earnings->pluck('id'))->get();

            expect($updatedEarnings->pluck('payout_id')->filter()->toArray())->toBeEmpty()
                ->and($updatedEarnings->where('is_finalized', true)->count())->toBe(0);
        });
    });

    describe('markAsPaid()', function () {
        it('marks approved payout as paid', function () {
            $payout = TeacherPayout::factory()->approved()->create([
                'academy_id' => $this->academy->id,
            ]);

            $payer = User::factory()->create();
            $paymentDetails = [
                'method' => 'bank_transfer',
                'reference' => 'TXN123456',
                'notes' => 'Payment processed successfully',
            ];

            Log::shouldReceive('info')->once();
            $this->notificationService->shouldReceive('sendPayoutPaidNotification')->once();

            $result = $this->service->markAsPaid($payout, $payer, $paymentDetails);

            expect($result)->toBeTrue()
                ->and($payout->fresh()->status)->toBe('paid')
                ->and($payout->fresh()->paid_by)->toBe($payer->id)
                ->and($payout->fresh()->paid_at)->not->toBeNull()
                ->and($payout->fresh()->payment_method)->toBe('bank_transfer')
                ->and($payout->fresh()->payment_reference)->toBe('TXN123456')
                ->and($payout->fresh()->payment_notes)->toBe('Payment processed successfully');
        });

        it('throws exception when payout is not approved', function () {
            $payout = TeacherPayout::factory()->pending()->create([
                'academy_id' => $this->academy->id,
            ]);

            $payer = User::factory()->create();
            $paymentDetails = ['method' => 'bank_transfer'];

            expect(fn() => $this->service->markAsPaid($payout, $payer, $paymentDetails))
                ->toThrow(\InvalidArgumentException::class, 'Payout cannot be marked as paid in current status: pending');
        });

        it('handles payment details with missing fields', function () {
            $payout = TeacherPayout::factory()->approved()->create([
                'academy_id' => $this->academy->id,
            ]);

            $payer = User::factory()->create();
            $paymentDetails = ['method' => 'cash'];

            Log::shouldReceive('info')->once();
            $this->notificationService->shouldReceive('sendPayoutPaidNotification')->once();

            $result = $this->service->markAsPaid($payout, $payer, $paymentDetails);

            expect($result)->toBeTrue()
                ->and($payout->fresh()->payment_method)->toBe('cash')
                ->and($payout->fresh()->payment_reference)->toBeNull()
                ->and($payout->fresh()->payment_notes)->toBeNull();
        });
    });

    describe('validateForApproval()', function () {
        it('returns empty array for valid payout', function () {
            $payout = TeacherPayout::factory()->pending()->create([
                'academy_id' => $this->academy->id,
                'total_amount' => 300.00,
                'sessions_count' => 3,
            ]);

            TeacherEarning::factory()->count(3)->create([
                'academy_id' => $this->academy->id,
                'payout_id' => $payout->id,
                'calculated_at' => now(),
                'is_disputed' => false,
                'amount' => 100.00,
            ]);

            $errors = $this->service->validateForApproval($payout);

            expect($errors)->toBeArray()
                ->and($errors)->toBeEmpty();
        });

        it('detects uncalculated earnings', function () {
            $payout = TeacherPayout::factory()->pending()->create([
                'academy_id' => $this->academy->id,
            ]);

            TeacherEarning::factory()->count(2)->create([
                'academy_id' => $this->academy->id,
                'payout_id' => $payout->id,
                'calculated_at' => null,
            ]);

            $errors = $this->service->validateForApproval($payout);

            expect($errors)->toContain('Has 2 uncalculated earnings');
        });

        it('detects disputed earnings', function () {
            $payout = TeacherPayout::factory()->pending()->create([
                'academy_id' => $this->academy->id,
            ]);

            TeacherEarning::factory()->count(3)->create([
                'academy_id' => $this->academy->id,
                'payout_id' => $payout->id,
                'calculated_at' => now(),
                'is_disputed' => true,
            ]);

            $errors = $this->service->validateForApproval($payout);

            expect($errors)->toContain('Has 3 disputed earnings');
        });

        it('detects total amount mismatch', function () {
            $payout = TeacherPayout::factory()->pending()->create([
                'academy_id' => $this->academy->id,
                'total_amount' => 500.00,
                'sessions_count' => 2,
            ]);

            TeacherEarning::factory()->count(2)->create([
                'academy_id' => $this->academy->id,
                'payout_id' => $payout->id,
                'calculated_at' => now(),
                'is_disputed' => false,
                'amount' => 100.00,
            ]);

            $errors = $this->service->validateForApproval($payout);

            expect($errors)->toContain('Total mismatch: expected 500.00, got 200');
        });

        it('detects sessions count mismatch', function () {
            $payout = TeacherPayout::factory()->pending()->create([
                'academy_id' => $this->academy->id,
                'total_amount' => 300.00,
                'sessions_count' => 5,
            ]);

            TeacherEarning::factory()->count(3)->create([
                'academy_id' => $this->academy->id,
                'payout_id' => $payout->id,
                'calculated_at' => now(),
                'is_disputed' => false,
                'amount' => 100.00,
            ]);

            $errors = $this->service->validateForApproval($payout);

            expect($errors)->toContain('Sessions count mismatch: expected 5, got 3');
        });

        it('returns multiple validation errors', function () {
            $payout = TeacherPayout::factory()->pending()->create([
                'academy_id' => $this->academy->id,
                'total_amount' => 500.00,
                'sessions_count' => 5,
            ]);

            TeacherEarning::factory()->create([
                'academy_id' => $this->academy->id,
                'payout_id' => $payout->id,
                'calculated_at' => null,
                'is_disputed' => false,
                'amount' => 100.00,
            ]);

            TeacherEarning::factory()->create([
                'academy_id' => $this->academy->id,
                'payout_id' => $payout->id,
                'calculated_at' => now(),
                'is_disputed' => true,
                'amount' => 100.00,
            ]);

            $errors = $this->service->validateForApproval($payout);

            expect($errors)->toHaveCount(3)
                ->and($errors)->toContain('Has 1 uncalculated earnings')
                ->and($errors)->toContain('Has 1 disputed earnings');
        });
    });

    describe('getTeacherPayoutStats()', function () {
        it('calculates correct statistics for teacher', function () {
            TeacherPayout::factory()->pending()->create([
                'academy_id' => $this->academy->id,
                'teacher_type' => 'quran_teacher',
                'teacher_id' => $this->quranTeacher->id,
                'total_amount' => 200.00,
            ]);

            TeacherPayout::factory()->approved()->create([
                'academy_id' => $this->academy->id,
                'teacher_type' => 'quran_teacher',
                'teacher_id' => $this->quranTeacher->id,
                'total_amount' => 300.00,
            ]);

            TeacherPayout::factory()->paid()->count(2)->create([
                'academy_id' => $this->academy->id,
                'teacher_type' => 'quran_teacher',
                'teacher_id' => $this->quranTeacher->id,
                'total_amount' => 150.00,
            ]);

            $stats = $this->service->getTeacherPayoutStats(
                'quran_teacher',
                $this->quranTeacher->id,
                $this->academy->id
            );

            expect($stats)->toBeArray()
                ->and($stats['total_payouts'])->toBe(4)
                ->and($stats['total_paid'])->toBe(300.00)
                ->and($stats['total_pending'])->toBe(200.00)
                ->and($stats['total_approved'])->toBe(300.00)
                ->and($stats['last_payout'])->toBeInstanceOf(TeacherPayout::class);
        });

        it('returns zeros when teacher has no payouts', function () {
            $stats = $this->service->getTeacherPayoutStats(
                'academic_teacher',
                $this->academicTeacher->id,
                $this->academy->id
            );

            expect($stats)->toBeArray()
                ->and($stats['total_payouts'])->toBe(0)
                ->and($stats['total_paid'])->toBe(0)
                ->and($stats['total_pending'])->toBe(0)
                ->and($stats['total_approved'])->toBe(0)
                ->and($stats['last_payout'])->toBeNull();
        });

        it('returns last payout sorted by month', function () {
            TeacherPayout::factory()->create([
                'academy_id' => $this->academy->id,
                'teacher_type' => 'quran_teacher',
                'teacher_id' => $this->quranTeacher->id,
                'payout_month' => '2024-01-01',
            ]);

            $latestPayout = TeacherPayout::factory()->create([
                'academy_id' => $this->academy->id,
                'teacher_type' => 'quran_teacher',
                'teacher_id' => $this->quranTeacher->id,
                'payout_month' => '2024-03-01',
            ]);

            TeacherPayout::factory()->create([
                'academy_id' => $this->academy->id,
                'teacher_type' => 'quran_teacher',
                'teacher_id' => $this->quranTeacher->id,
                'payout_month' => '2024-02-01',
            ]);

            $stats = $this->service->getTeacherPayoutStats(
                'quran_teacher',
                $this->quranTeacher->id,
                $this->academy->id
            );

            expect($stats['last_payout']->id)->toBe($latestPayout->id);
        });
    });

    describe('sendPayoutNotification()', function () {
        it('sends approved notification with correct data', function () {
            $user = User::factory()->create();
            $profile = QuranTeacherProfile::factory()->create(['user_id' => $user->id]);

            $payout = TeacherPayout::factory()->approved()->create([
                'academy_id' => $this->academy->id,
                'teacher_type' => 'quran_teacher',
                'teacher_id' => $profile->id,
                'payout_month' => '2024-01-01',
                'total_amount' => 500.00,
            ]);

            $this->notificationService->shouldReceive('sendPayoutApprovedNotification')
                ->once()
                ->withArgs(function ($notifiableUser, $data) use ($user, $payout) {
                    return $notifiableUser->id === $user->id
                        && $data['payout_id'] === $payout->id
                        && $data['payout_code'] === $payout->payout_code
                        && $data['amount'] === number_format($payout->total_amount, 2)
                        && $data['currency'] === 'SAR';
                });

            $this->service->approvePayout($payout, User::factory()->create());
        });

        it('sends rejected notification with reason', function () {
            $user = User::factory()->create();
            $profile = AcademicTeacherProfile::factory()->create(['user_id' => $user->id]);

            $payout = TeacherPayout::factory()->pending()->create([
                'academy_id' => $this->academy->id,
                'teacher_type' => 'academic_teacher',
                'teacher_id' => $profile->id,
            ]);

            TeacherEarning::factory()->count(2)->create([
                'academy_id' => $this->academy->id,
                'payout_id' => $payout->id,
            ]);

            Log::shouldReceive('info')->once();
            $this->notificationService->shouldReceive('sendPayoutRejectedNotification')
                ->once()
                ->withArgs(function ($notifiableUser, $data) use ($user) {
                    return $notifiableUser->id === $user->id
                        && $data['reason'] === 'Test rejection reason';
                });

            $this->service->rejectPayout($payout, User::factory()->create(), 'Test rejection reason');
        });

        it('sends paid notification with payment reference', function () {
            $user = User::factory()->create();
            $profile = QuranTeacherProfile::factory()->create(['user_id' => $user->id]);

            $payout = TeacherPayout::factory()->approved()->create([
                'academy_id' => $this->academy->id,
                'teacher_type' => 'quran_teacher',
                'teacher_id' => $profile->id,
            ]);

            Log::shouldReceive('info')->once();
            $this->notificationService->shouldReceive('sendPayoutPaidNotification')
                ->once()
                ->withArgs(function ($notifiableUser, $data) use ($user) {
                    return $notifiableUser->id === $user->id
                        && $data['reference'] === 'REF123456';
                });

            $this->service->markAsPaid($payout, User::factory()->create(), [
                'method' => 'bank_transfer',
                'reference' => 'REF123456',
            ]);
        });

        it('logs warning when teacher user not found', function () {
            $payout = TeacherPayout::factory()->pending()->create([
                'academy_id' => $this->academy->id,
                'teacher_type' => 'quran_teacher',
                'teacher_id' => 99999,
            ]);

            Log::shouldReceive('warning')
                ->once()
                ->withArgs(function ($message, $context) use ($payout) {
                    return str_contains($message, 'Could not find teacher user')
                        && $context['payout_id'] === $payout->id;
                });

            $this->service->approvePayout($payout, User::factory()->create());
        });

        it('logs error but does not throw on notification failure', function () {
            $user = User::factory()->create();
            $profile = QuranTeacherProfile::factory()->create(['user_id' => $user->id]);

            $payout = TeacherPayout::factory()->approved()->create([
                'academy_id' => $this->academy->id,
                'teacher_type' => 'quran_teacher',
                'teacher_id' => $profile->id,
            ]);

            $this->notificationService->shouldReceive('sendPayoutPaidNotification')
                ->once()
                ->andThrow(new \Exception('Notification service error'));

            Log::shouldReceive('error')
                ->once()
                ->withArgs(function ($message, $context) use ($payout) {
                    return str_contains($message, 'Failed to send payout notification')
                        && $context['payout_id'] === $payout->id
                        && $context['type'] === 'paid';
                });

            Log::shouldReceive('info')->zeroOrMoreTimes();

            $result = $this->service->markAsPaid($payout, User::factory()->create(), [
                'method' => 'bank_transfer',
            ]);

            expect($result)->toBeTrue();
        });
    });
});
