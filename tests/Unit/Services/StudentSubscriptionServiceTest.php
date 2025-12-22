<?php

use App\Enums\SubscriptionStatus;
use App\Models\Academy;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\User;
use App\Services\StudentSubscriptionService;
use Illuminate\Support\Facades\Log;

describe('StudentSubscriptionService', function () {
    beforeEach(function () {
        $this->service = new StudentSubscriptionService();
        $this->academy = Academy::factory()->create();
        $this->student = User::factory()->student()->forAcademy($this->academy)->create();
    });

    describe('getSubscriptions()', function () {
        it('returns array with three subscription types', function () {
            $subscriptions = $this->service->getSubscriptions($this->student);

            expect($subscriptions)->toBeArray()
                ->and($subscriptions)->toHaveKeys(['individual_quran', 'group_quran', 'academic']);
        });

        it('returns empty collections when student has no subscriptions', function () {
            $subscriptions = $this->service->getSubscriptions($this->student);

            expect($subscriptions['individual_quran'])->toBeInstanceOf(\Illuminate\Support\Collection::class)
                ->and($subscriptions['individual_quran'])->toBeEmpty()
                ->and($subscriptions['group_quran'])->toBeEmpty()
                ->and($subscriptions['academic'])->toBeEmpty();
        });

        it('returns individual quran subscriptions when they exist', function () {
            QuranSubscription::factory()
                ->forStudent($this->student)
                ->create([
                    'academy_id' => $this->academy->id,
                    'subscription_type' => 'individual',
                ]);

            $subscriptions = $this->service->getSubscriptions($this->student);

            expect($subscriptions['individual_quran'])->toHaveCount(1);
        });

        it('returns group quran subscriptions when they exist', function () {
            QuranSubscription::factory()
                ->forStudent($this->student)
                ->create([
                    'academy_id' => $this->academy->id,
                    'subscription_type' => 'group',
                ]);

            $subscriptions = $this->service->getSubscriptions($this->student);

            expect($subscriptions['group_quran'])->toHaveCount(1);
        });

        it('returns circle quran subscriptions as group subscriptions', function () {
            QuranSubscription::factory()
                ->forStudent($this->student)
                ->create([
                    'academy_id' => $this->academy->id,
                    'subscription_type' => 'group',
                ]);

            $subscriptions = $this->service->getSubscriptions($this->student);

            expect($subscriptions['group_quran'])->toHaveCount(1);
        });
    });

    describe('getIndividualQuranSubscriptions()', function () {
        it('returns only individual quran subscriptions', function () {
            QuranSubscription::factory()
                ->forStudent($this->student)
                ->create([
                    'academy_id' => $this->academy->id,
                    'subscription_type' => 'individual',
                ]);

            QuranSubscription::factory()
                ->forStudent($this->student)
                ->create([
                    'academy_id' => $this->academy->id,
                    'subscription_type' => 'group',
                ]);

            $subscriptions = $this->service->getIndividualQuranSubscriptions($this->student, $this->academy);

            expect($subscriptions)->toHaveCount(1)
                ->and($subscriptions->first()->subscription_type)->toBe('individual');
        });

        it('eager loads relationships', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            QuranSubscription::factory()
                ->forStudent($this->student)
                ->forTeacher($teacher)
                ->create([
                    'academy_id' => $this->academy->id,
                    'subscription_type' => 'individual',
                ]);

            $subscriptions = $this->service->getIndividualQuranSubscriptions($this->student, $this->academy);

            expect($subscriptions->first()->relationLoaded('quranTeacher'))->toBeTrue()
                ->and($subscriptions->first()->relationLoaded('package'))->toBeTrue()
                ->and($subscriptions->first()->relationLoaded('sessions'))->toBeTrue();
        });

        it('limits sessions to 5 most recent', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $subscription = QuranSubscription::factory()
                ->forStudent($this->student)
                ->forTeacher($teacher)
                ->create([
                    'academy_id' => $this->academy->id,
                    'subscription_type' => 'individual',
                ]);

            // Create 10 sessions
            QuranSession::factory()->count(10)->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $this->student->id,
                'quran_subscription_id' => $subscription->id,
                'scheduled_at' => now()->addDays(rand(1, 30)),
            ]);

            $subscriptions = $this->service->getIndividualQuranSubscriptions($this->student, $this->academy);

            expect($subscriptions->first()->sessions)->toHaveCount(5);
        });

        it('orders subscriptions by created_at descending', function () {
            $sub1 = QuranSubscription::factory()
                ->forStudent($this->student)
                ->create([
                    'academy_id' => $this->academy->id,
                    'subscription_type' => 'individual',
                    'created_at' => now()->subDays(2),
                ]);

            $sub2 = QuranSubscription::factory()
                ->forStudent($this->student)
                ->create([
                    'academy_id' => $this->academy->id,
                    'subscription_type' => 'individual',
                    'created_at' => now()->subDay(),
                ]);

            $subscriptions = $this->service->getIndividualQuranSubscriptions($this->student, $this->academy);

            expect($subscriptions->first()->id)->toBe($sub2->id)
                ->and($subscriptions->last()->id)->toBe($sub1->id);
        });
    });

    describe('getGroupQuranSubscriptions()', function () {
        it('returns both group and circle subscriptions', function () {
            QuranSubscription::factory()
                ->forStudent($this->student)
                ->create([
                    'academy_id' => $this->academy->id,
                    'subscription_type' => 'group',
                ]);

            $subscriptions = $this->service->getGroupQuranSubscriptions($this->student, $this->academy);

            expect($subscriptions)->toHaveCount(1);
        });

        it('excludes individual subscriptions', function () {
            QuranSubscription::factory()
                ->forStudent($this->student)
                ->create([
                    'academy_id' => $this->academy->id,
                    'subscription_type' => 'individual',
                ]);

            QuranSubscription::factory()
                ->forStudent($this->student)
                ->create([
                    'academy_id' => $this->academy->id,
                    'subscription_type' => 'group',
                ]);

            $subscriptions = $this->service->getGroupQuranSubscriptions($this->student, $this->academy);

            expect($subscriptions)->toHaveCount(1)
                ->and($subscriptions->first()->subscription_type)->not->toBe('individual');
        });

        it('eager loads relationships', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            QuranSubscription::factory()
                ->forStudent($this->student)
                ->forTeacher($teacher)
                ->create([
                    'academy_id' => $this->academy->id,
                    'subscription_type' => 'group',
                ]);

            $subscriptions = $this->service->getGroupQuranSubscriptions($this->student, $this->academy);

            expect($subscriptions->first()->relationLoaded('quranTeacher'))->toBeTrue()
                ->and($subscriptions->first()->relationLoaded('package'))->toBeTrue()
                ->and($subscriptions->first()->relationLoaded('sessions'))->toBeTrue();
        });

        it('orders subscriptions by created_at descending', function () {
            $sub1 = QuranSubscription::factory()
                ->forStudent($this->student)
                ->create([
                    'academy_id' => $this->academy->id,
                    'subscription_type' => 'group',
                    'created_at' => now()->subDays(2),
                ]);

            $sub2 = QuranSubscription::factory()
                ->forStudent($this->student)
                ->create([
                    'academy_id' => $this->academy->id,
                    'subscription_type' => 'group',
                    'created_at' => now()->subDay(),
                ]);

            $subscriptions = $this->service->getGroupQuranSubscriptions($this->student, $this->academy);

            expect($subscriptions->first()->id)->toBe($sub2->id);
        });
    });

    describe('toggleAutoRenew()', function () {
        it('toggles auto_renew for quran subscription', function () {
            $subscription = QuranSubscription::factory()
                ->forStudent($this->student)
                ->create([
                    'academy_id' => $this->academy->id,
                    'auto_renew' => true,
                ]);

            $result = $this->service->toggleAutoRenew($this->student, 'quran', $subscription->id);

            expect($result['success'])->toBeTrue()
                ->and($result['auto_renew'])->toBeFalse()
                ->and($result)->toHaveKey('message');

            $subscription->refresh();
            expect($subscription->auto_renew)->toBeFalse();
        });

        it('toggles auto_renew from false to true', function () {
            $subscription = QuranSubscription::factory()
                ->forStudent($this->student)
                ->create([
                    'academy_id' => $this->academy->id,
                    'auto_renew' => false,
                ]);

            $result = $this->service->toggleAutoRenew($this->student, 'quran', $subscription->id);

            expect($result['success'])->toBeTrue()
                ->and($result['auto_renew'])->toBeTrue();

            $subscription->refresh();
            expect($subscription->auto_renew)->toBeTrue();
        });

        it('returns error when subscription not found', function () {
            $result = $this->service->toggleAutoRenew($this->student, 'quran', 'non-existent-id');

            expect($result['success'])->toBeFalse()
                ->and($result)->toHaveKey('error')
                ->and($result['error'])->toBe('الاشتراك غير موجود');
        });

        it('returns error for invalid subscription type', function () {
            $result = $this->service->toggleAutoRenew($this->student, 'invalid_type', 'some-id');

            expect($result['success'])->toBeFalse()
                ->and($result)->toHaveKey('error');
        });

        it('verifies subscription belongs to student', function () {
            $otherStudent = User::factory()->student()->forAcademy($this->academy)->create();
            $subscription = QuranSubscription::factory()
                ->forStudent($otherStudent)
                ->create([
                    'academy_id' => $this->academy->id,
                ]);

            $result = $this->service->toggleAutoRenew($this->student, 'quran', $subscription->id);

            expect($result['success'])->toBeFalse();
        });

        it('verifies subscription belongs to student academy', function () {
            $otherAcademy = Academy::factory()->create();
            $subscription = QuranSubscription::factory()
                ->forStudent($this->student)
                ->create([
                    'academy_id' => $otherAcademy->id,
                ]);

            $result = $this->service->toggleAutoRenew($this->student, 'quran', $subscription->id);

            expect($result['success'])->toBeFalse();
        });

        it('logs the toggle action', function () {
            Log::spy();

            $subscription = QuranSubscription::factory()
                ->forStudent($this->student)
                ->create([
                    'academy_id' => $this->academy->id,
                    'auto_renew' => true,
                ]);

            $this->service->toggleAutoRenew($this->student, 'quran', $subscription->id);

            Log::shouldHaveReceived('info')
                ->atLeast()
                ->once()
                ->withArgs(function ($message, $context) use ($subscription) {
                    return $message === 'Auto-renew toggled'
                        && $context['subscription_id'] === $subscription->id
                        && $context['old_value'] === true
                        && $context['new_value'] === false;
                });
        });

        it('returns appropriate message when enabling auto_renew', function () {
            $subscription = QuranSubscription::factory()
                ->forStudent($this->student)
                ->create([
                    'academy_id' => $this->academy->id,
                    'auto_renew' => false,
                ]);

            $result = $this->service->toggleAutoRenew($this->student, 'quran', $subscription->id);

            expect($result['message'])->toBe('تم تفعيل التجديد التلقائي بنجاح');
        });

        it('returns appropriate message when disabling auto_renew', function () {
            $subscription = QuranSubscription::factory()
                ->forStudent($this->student)
                ->create([
                    'academy_id' => $this->academy->id,
                    'auto_renew' => true,
                ]);

            $result = $this->service->toggleAutoRenew($this->student, 'quran', $subscription->id);

            expect($result['message'])->toBe('تم إيقاف التجديد التلقائي بنجاح');
        });
    });

    describe('cancelSubscription()', function () {
        it('cancels quran subscription', function () {
            $subscription = QuranSubscription::factory()
                ->forStudent($this->student)
                ->active()
                ->create([
                    'academy_id' => $this->academy->id,
                ]);

            $result = $this->service->cancelSubscription($this->student, 'quran', $subscription->id);

            expect($result['success'])->toBeTrue()
                ->and($result)->toHaveKey('message');

            $subscription->refresh();
            expect($subscription->status)->toBe(SubscriptionStatus::CANCELLED);
        });

        it('sets cancelled_at timestamp', function () {
            $subscription = QuranSubscription::factory()
                ->forStudent($this->student)
                ->active()
                ->create([
                    'academy_id' => $this->academy->id,
                ]);

            $this->service->cancelSubscription($this->student, 'quran', $subscription->id);

            $subscription->refresh();
            expect($subscription->cancelled_at)->not->toBeNull();
        });

        it('sets cancellation reason', function () {
            $subscription = QuranSubscription::factory()
                ->forStudent($this->student)
                ->active()
                ->create([
                    'academy_id' => $this->academy->id,
                ]);

            $this->service->cancelSubscription($this->student, 'quran', $subscription->id);

            $subscription->refresh();
            expect($subscription->cancellation_reason)->toBe('إلغاء من قبل الطالب');
        });

        it('disables auto_renew when cancelling', function () {
            $subscription = QuranSubscription::factory()
                ->forStudent($this->student)
                ->active()
                ->create([
                    'academy_id' => $this->academy->id,
                    'auto_renew' => true,
                ]);

            $this->service->cancelSubscription($this->student, 'quran', $subscription->id);

            $subscription->refresh();
            expect($subscription->auto_renew)->toBeFalse();
        });

        it('returns error when subscription not found', function () {
            $result = $this->service->cancelSubscription($this->student, 'quran', 'non-existent-id');

            expect($result['success'])->toBeFalse()
                ->and($result)->toHaveKey('error')
                ->and($result['error'])->toBe('الاشتراك غير موجود');
        });

        it('returns error for invalid subscription type', function () {
            $result = $this->service->cancelSubscription($this->student, 'invalid_type', 'some-id');

            expect($result['success'])->toBeFalse()
                ->and($result)->toHaveKey('error');
        });

        it('verifies subscription belongs to student', function () {
            $otherStudent = User::factory()->student()->forAcademy($this->academy)->create();
            $subscription = QuranSubscription::factory()
                ->forStudent($otherStudent)
                ->active()
                ->create([
                    'academy_id' => $this->academy->id,
                ]);

            $result = $this->service->cancelSubscription($this->student, 'quran', $subscription->id);

            expect($result['success'])->toBeFalse();
        });

        it('verifies subscription belongs to student academy', function () {
            $otherAcademy = Academy::factory()->create();
            $subscription = QuranSubscription::factory()
                ->forStudent($this->student)
                ->active()
                ->create([
                    'academy_id' => $otherAcademy->id,
                ]);

            $result = $this->service->cancelSubscription($this->student, 'quran', $subscription->id);

            expect($result['success'])->toBeFalse();
        });

        it('logs the cancellation', function () {
            Log::spy();

            $subscription = QuranSubscription::factory()
                ->forStudent($this->student)
                ->active()
                ->create([
                    'academy_id' => $this->academy->id,
                ]);

            $this->service->cancelSubscription($this->student, 'quran', $subscription->id);

            Log::shouldHaveReceived('info')
                ->atLeast()
                ->once()
                ->withArgs(function ($message, $context) use ($subscription) {
                    return $message === 'Student cancelled subscription'
                        && $context['subscription_id'] === $subscription->id
                        && $context['subscription_type'] === 'quran'
                        && $context['student_id'] === $this->student->id;
                });
        });

        it('returns success message', function () {
            $subscription = QuranSubscription::factory()
                ->forStudent($this->student)
                ->active()
                ->create([
                    'academy_id' => $this->academy->id,
                ]);

            $result = $this->service->cancelSubscription($this->student, 'quran', $subscription->id);

            expect($result['message'])->toBe('تم إلغاء الاشتراك بنجاح');
        });
    });

    describe('getActiveSubscriptionsCount()', function () {
        it('returns zero counts when no subscriptions exist', function () {
            $counts = $this->service->getActiveSubscriptionsCount($this->student);

            expect($counts)->toBeArray()
                ->and($counts['quran'])->toBe(0)
                ->and($counts['academic'])->toBe(0);
        });

        it('counts active quran subscriptions', function () {
            QuranSubscription::factory()
                ->forStudent($this->student)
                ->active()
                ->count(3)
                ->create([
                    'academy_id' => $this->academy->id,
                ]);

            $counts = $this->service->getActiveSubscriptionsCount($this->student);

            expect($counts['quran'])->toBe(3);
        });

        it('excludes non-active quran subscriptions', function () {
            QuranSubscription::factory()
                ->forStudent($this->student)
                ->active()
                ->create([
                    'academy_id' => $this->academy->id,
                ]);

            QuranSubscription::factory()
                ->forStudent($this->student)
                ->expired()
                ->create([
                    'academy_id' => $this->academy->id,
                ]);

            QuranSubscription::factory()
                ->forStudent($this->student)
                ->cancelled()
                ->create([
                    'academy_id' => $this->academy->id,
                ]);

            $counts = $this->service->getActiveSubscriptionsCount($this->student);

            expect($counts['quran'])->toBe(1);
        });

        it('scopes to student academy', function () {
            $otherAcademy = Academy::factory()->create();

            QuranSubscription::factory()
                ->forStudent($this->student)
                ->active()
                ->create([
                    'academy_id' => $this->academy->id,
                ]);

            QuranSubscription::factory()
                ->forStudent($this->student)
                ->active()
                ->create([
                    'academy_id' => $otherAcademy->id,
                ]);

            $counts = $this->service->getActiveSubscriptionsCount($this->student);

            expect($counts['quran'])->toBe(1);
        });
    });

    describe('findSubscription()', function () {
        it('finds quran subscription by id', function () {
            $subscription = QuranSubscription::factory()
                ->forStudent($this->student)
                ->create([
                    'academy_id' => $this->academy->id,
                ]);

            $reflection = new \ReflectionClass($this->service);
            $method = $reflection->getMethod('findSubscription');
            $method->setAccessible(true);

            $found = $method->invoke($this->service, $this->student, 'quran', $subscription->id);

            expect($found)->not->toBeNull()
                ->and($found->id)->toBe($subscription->id);
        });

        it('returns null for invalid type', function () {
            $reflection = new \ReflectionClass($this->service);
            $method = $reflection->getMethod('findSubscription');
            $method->setAccessible(true);

            $found = $method->invoke($this->service, $this->student, 'invalid', 'some-id');

            expect($found)->toBeNull();
        });

        it('returns null when subscription not found', function () {
            $reflection = new \ReflectionClass($this->service);
            $method = $reflection->getMethod('findSubscription');
            $method->setAccessible(true);

            $found = $method->invoke($this->service, $this->student, 'quran', 'non-existent-id');

            expect($found)->toBeNull();
        });

        it('returns null when subscription belongs to different student', function () {
            $otherStudent = User::factory()->student()->forAcademy($this->academy)->create();
            $subscription = QuranSubscription::factory()
                ->forStudent($otherStudent)
                ->create([
                    'academy_id' => $this->academy->id,
                ]);

            $reflection = new \ReflectionClass($this->service);
            $method = $reflection->getMethod('findSubscription');
            $method->setAccessible(true);

            $found = $method->invoke($this->service, $this->student, 'quran', $subscription->id);

            expect($found)->toBeNull();
        });

        it('returns null when subscription belongs to different academy', function () {
            $otherAcademy = Academy::factory()->create();
            $subscription = QuranSubscription::factory()
                ->forStudent($this->student)
                ->create([
                    'academy_id' => $otherAcademy->id,
                ]);

            $reflection = new \ReflectionClass($this->service);
            $method = $reflection->getMethod('findSubscription');
            $method->setAccessible(true);

            $found = $method->invoke($this->service, $this->student, 'quran', $subscription->id);

            expect($found)->toBeNull();
        });
    });
});
