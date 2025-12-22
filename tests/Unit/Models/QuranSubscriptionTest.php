<?php

use App\Enums\BillingCycle;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Academy;
use App\Models\Payment;
use App\Models\QuranIndividualCircle;
use App\Models\QuranPackage;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\User;

describe('QuranSubscription Model', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
        $this->teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
        $this->teacherProfile = QuranTeacherProfile::factory()->create([
            'user_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
        ]);
        $this->student = User::factory()->student()->forAcademy($this->academy)->create();
    });

    describe('factory', function () {
        it('can create a quran subscription using factory', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            expect($subscription)->toBeInstanceOf(QuranSubscription::class)
                ->and($subscription->id)->toBeInt();
        });

        it('creates subscription with default status as active', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
            ]);

            expect($subscription->status)->toBe(SubscriptionStatus::ACTIVE);
        });

        it('creates subscription with session counts', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'total_sessions' => 10,
                'sessions_used' => 2,
                'sessions_remaining' => 8,
            ]);

            expect($subscription->total_sessions)->toBe(10)
                ->and($subscription->sessions_used)->toBe(2)
                ->and($subscription->sessions_remaining)->toBe(8);
        });
    });

    describe('relationships', function () {
        it('belongs to an academy', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
            ]);

            expect($subscription->academy)->toBeInstanceOf(Academy::class)
                ->and($subscription->academy->id)->toBe($this->academy->id);
        });

        it('belongs to a student', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
            ]);

            expect($subscription->student)->toBeInstanceOf(User::class)
                ->and($subscription->student->id)->toBe($this->student->id);
        });

        it('belongs to a quran teacher user', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            expect($subscription->quranTeacherUser)->toBeInstanceOf(User::class)
                ->and($subscription->quranTeacherUser->id)->toBe($this->teacher->id);
        });

        it('has many sessions', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            QuranSession::factory()->count(2)->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'quran_subscription_id' => $subscription->id,
            ]);

            expect($subscription->sessions)->toHaveCount(2);
        });

        it('has many payments', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
            ]);

            Payment::factory()->count(2)->create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->student->id,
                'subscription_id' => $subscription->id,
            ]);

            expect($subscription->payments)->toHaveCount(2);
        });
    });

    describe('scopes', function () {
        it('can filter individual subscriptions', function () {
            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'subscription_type' => 'individual',
            ]);

            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'subscription_type' => 'group',
            ]);

            expect(QuranSubscription::individual()->count())->toBe(1);
        });

        it('can filter circle subscriptions', function () {
            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'subscription_type' => 'individual',
            ]);

            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'subscription_type' => 'group',
            ]);

            expect(QuranSubscription::circle()->count())->toBe(1);
        });

        it('can filter trial subscriptions', function () {
            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'is_trial_active' => true,
            ]);

            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'is_trial_active' => false,
            ]);

            expect(QuranSubscription::inTrial()->count())->toBe(1);
        });

        it('can filter subscriptions needing session renewal', function () {
            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'sessions_remaining' => 2,
                'status' => SubscriptionStatus::ACTIVE,
            ]);

            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'sessions_remaining' => 10,
                'status' => SubscriptionStatus::ACTIVE,
            ]);

            expect(QuranSubscription::needsSessionRenewal(3)->count())->toBe(1);
        });

        it('can filter subscriptions by teacher', function () {
            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            $otherTeacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $otherTeacher->id,
            ]);

            expect(QuranSubscription::forTeacher($this->teacher->id)->count())->toBe(1);
        });
    });

    describe('attributes and casts', function () {
        it('casts status to SubscriptionStatus enum', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'status' => SubscriptionStatus::ACTIVE,
            ]);

            expect($subscription->status)->toBeInstanceOf(SubscriptionStatus::class);
        });

        it('casts payment_status to enum', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'payment_status' => SubscriptionPaymentStatus::PAID,
            ]);

            expect($subscription->payment_status)->toBeInstanceOf(SubscriptionPaymentStatus::class);
        });

        it('casts billing_cycle to enum', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'billing_cycle' => BillingCycle::MONTHLY,
            ]);

            expect($subscription->billing_cycle)->toBeInstanceOf(BillingCycle::class);
        });

        it('casts is_trial_active to boolean', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'is_trial_active' => 1,
            ]);

            expect($subscription->is_trial_active)->toBeBool();
        });

        it('casts session counts to integer', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'total_sessions' => '10',
                'sessions_used' => '2',
                'sessions_remaining' => '8',
            ]);

            expect($subscription->total_sessions)->toBeInt()
                ->and($subscription->sessions_used)->toBeInt()
                ->and($subscription->sessions_remaining)->toBeInt();
        });
    });

    describe('accessors', function () {
        it('returns memorization level label', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'memorization_level' => 'beginner',
            ]);

            expect($subscription->memorization_level_label)->toBe('مبتدئ');
        });

        it('returns completion rate', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'total_sessions' => 10,
                'sessions_used' => 5,
            ]);

            expect($subscription->completion_rate)->toBe(50.0);
        });

        it('returns zero completion rate when no sessions', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'total_sessions' => 0,
                'sessions_used' => 0,
            ]);

            expect($subscription->completion_rate)->toBe(0.0);
        });

        it('returns price per session', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'total_sessions' => 10,
                'final_price' => 1000,
            ]);

            expect($subscription->price_per_session)->toBe(100.0);
        });

        it('checks if in trial', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'is_trial_active' => true,
                'trial_used' => 1,
            ]);

            expect($subscription->is_in_trial)->toBeTrue();
        });
    });

    describe('methods', function () {
        it('returns subscription type as quran', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
            ]);

            expect($subscription->getSubscriptionType())->toBe('quran');
        });

        it('returns subscription type label for individual', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'subscription_type' => 'individual',
            ]);

            expect($subscription->getSubscriptionTypeLabel())->toBe('اشتراك قرآن فردي');
        });

        it('returns subscription type label for circle', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'subscription_type' => 'group',
            ]);

            expect($subscription->getSubscriptionTypeLabel())->toBe('اشتراك حلقة قرآن');
        });

        it('can get teacher', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            expect($subscription->getTeacher())->toBeInstanceOf(User::class)
                ->and($subscription->getTeacher()->id)->toBe($this->teacher->id);
        });

        it('can get total sessions', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'total_sessions' => 12,
            ]);

            expect($subscription->getTotalSessions())->toBe(12);
        });

        it('can get sessions used', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'sessions_used' => 5,
            ]);

            expect($subscription->getSessionsUsed())->toBe(5);
        });

        it('can get sessions remaining', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'sessions_remaining' => 7,
            ]);

            expect($subscription->getSessionsRemaining())->toBe(7);
        });
    });

    describe('session management', function () {
        it('can use a session', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'total_sessions' => 10,
                'sessions_used' => 2,
                'sessions_remaining' => 8,
                'status' => SubscriptionStatus::ACTIVE,
            ]);

            $subscription->useSession();

            expect($subscription->sessions_used)->toBe(3)
                ->and($subscription->sessions_remaining)->toBe(7);
        });

        it('can add sessions', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'total_sessions' => 10,
                'sessions_remaining' => 8,
            ]);

            $subscription->addSessions(5);

            expect($subscription->total_sessions)->toBe(15)
                ->and($subscription->sessions_remaining)->toBe(13);
        });
    });

    describe('default attributes', function () {
        it('has default status as pending', function () {
            $subscription = new QuranSubscription();

            expect($subscription->status)->toBe(SubscriptionStatus::PENDING);
        });

        it('has default payment status as pending', function () {
            $subscription = new QuranSubscription();

            expect($subscription->payment_status)->toBe(SubscriptionPaymentStatus::PENDING);
        });

        it('has default currency as SAR', function () {
            $subscription = new QuranSubscription();

            expect($subscription->currency)->toBe('SAR');
        });

        it('has default billing cycle as monthly', function () {
            $subscription = new QuranSubscription();

            expect($subscription->billing_cycle)->toBe(BillingCycle::MONTHLY);
        });

        it('has auto_renew enabled by default', function () {
            $subscription = new QuranSubscription();

            expect($subscription->auto_renew)->toBeTrue();
        });

        it('has default subscription type as individual', function () {
            $subscription = new QuranSubscription();

            expect($subscription->subscription_type)->toBe('individual');
        });

        it('has is_trial_active as false by default', function () {
            $subscription = new QuranSubscription();

            expect($subscription->is_trial_active)->toBeFalse();
        });
    });

    describe('constants', function () {
        it('has subscription type constants', function () {
            expect(QuranSubscription::SUBSCRIPTION_TYPE_INDIVIDUAL)->toBe('individual')
                ->and(QuranSubscription::SUBSCRIPTION_TYPE_CIRCLE)->toBe('group');
        });

        it('has memorization levels', function () {
            expect(QuranSubscription::MEMORIZATION_LEVELS)->toBeArray()
                ->and(QuranSubscription::MEMORIZATION_LEVELS)->toHaveKey('beginner')
                ->and(QuranSubscription::MEMORIZATION_LEVELS)->toHaveKey('hafiz');
        });
    });
});
