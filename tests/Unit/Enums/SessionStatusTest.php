<?php

use App\Enums\SessionStatus;

describe('SessionStatus Enum', function () {
    it('has all expected status cases', function () {
        $cases = SessionStatus::cases();

        expect($cases)->toHaveCount(7)
            ->and(SessionStatus::UNSCHEDULED->value)->toBe('unscheduled')
            ->and(SessionStatus::SCHEDULED->value)->toBe('scheduled')
            ->and(SessionStatus::READY->value)->toBe('ready')
            ->and(SessionStatus::ONGOING->value)->toBe('ongoing')
            ->and(SessionStatus::COMPLETED->value)->toBe('completed')
            ->and(SessionStatus::CANCELLED->value)->toBe('cancelled')
            ->and(SessionStatus::ABSENT->value)->toBe('absent');
    });

    it('returns Arabic labels for each status', function () {
        expect(SessionStatus::UNSCHEDULED->label())->toBe('غير مجدولة')
            ->and(SessionStatus::SCHEDULED->label())->toBe('مجدولة')
            ->and(SessionStatus::READY->label())->toBe('جاهزة للبدء')
            ->and(SessionStatus::ONGOING->label())->toBe('جارية الآن')
            ->and(SessionStatus::COMPLETED->label())->toBe('مكتملة')
            ->and(SessionStatus::CANCELLED->label())->toBe('ملغية')
            ->and(SessionStatus::ABSENT->label())->toBe('غياب الطالب');
    });

    it('returns icons for each status', function () {
        expect(SessionStatus::UNSCHEDULED->icon())->toBe('ri-draft-line')
            ->and(SessionStatus::SCHEDULED->icon())->toBe('ri-calendar-line')
            ->and(SessionStatus::READY->icon())->toBe('ri-video-line')
            ->and(SessionStatus::ONGOING->icon())->toBe('ri-live-line')
            ->and(SessionStatus::COMPLETED->icon())->toBe('ri-check-circle-line')
            ->and(SessionStatus::CANCELLED->icon())->toBe('ri-close-circle-line')
            ->and(SessionStatus::ABSENT->icon())->toBe('ri-user-x-line');
    });

    it('returns Filament colors for each status', function () {
        expect(SessionStatus::UNSCHEDULED->color())->toBe('gray')
            ->and(SessionStatus::SCHEDULED->color())->toBe('info')
            ->and(SessionStatus::READY->color())->toBe('success')
            ->and(SessionStatus::ONGOING->color())->toBe('primary')
            ->and(SessionStatus::COMPLETED->color())->toBe('success')
            ->and(SessionStatus::CANCELLED->color())->toBe('danger')
            ->and(SessionStatus::ABSENT->color())->toBe('warning');
    });

    it('returns hex colors for calendar display', function () {
        expect(SessionStatus::UNSCHEDULED->hexColor())->toBe('#6B7280')
            ->and(SessionStatus::SCHEDULED->hexColor())->toBe('#3B82F6')
            ->and(SessionStatus::READY->hexColor())->toBe('#22c55e')
            ->and(SessionStatus::ONGOING->hexColor())->toBe('#3b82f6')
            ->and(SessionStatus::COMPLETED->hexColor())->toBe('#22c55e')
            ->and(SessionStatus::CANCELLED->hexColor())->toBe('#ef4444')
            ->and(SessionStatus::ABSENT->hexColor())->toBe('#f59e0b');
    });

    describe('canStart()', function () {
        it('returns true for SCHEDULED status', function () {
            expect(SessionStatus::SCHEDULED->canStart())->toBeTrue();
        });

        it('returns true for READY status', function () {
            expect(SessionStatus::READY->canStart())->toBeTrue();
        });

        it('returns false for other statuses', function () {
            expect(SessionStatus::UNSCHEDULED->canStart())->toBeFalse()
                ->and(SessionStatus::ONGOING->canStart())->toBeFalse()
                ->and(SessionStatus::COMPLETED->canStart())->toBeFalse()
                ->and(SessionStatus::CANCELLED->canStart())->toBeFalse()
                ->and(SessionStatus::ABSENT->canStart())->toBeFalse();
        });
    });

    describe('canComplete()', function () {
        it('returns true for SCHEDULED, READY, and ONGOING statuses', function () {
            expect(SessionStatus::SCHEDULED->canComplete())->toBeTrue()
                ->and(SessionStatus::READY->canComplete())->toBeTrue()
                ->and(SessionStatus::ONGOING->canComplete())->toBeTrue();
        });

        it('returns false for other statuses', function () {
            expect(SessionStatus::UNSCHEDULED->canComplete())->toBeFalse()
                ->and(SessionStatus::COMPLETED->canComplete())->toBeFalse()
                ->and(SessionStatus::CANCELLED->canComplete())->toBeFalse()
                ->and(SessionStatus::ABSENT->canComplete())->toBeFalse();
        });
    });

    describe('canCancel()', function () {
        it('returns true for SCHEDULED and READY statuses', function () {
            expect(SessionStatus::SCHEDULED->canCancel())->toBeTrue()
                ->and(SessionStatus::READY->canCancel())->toBeTrue();
        });

        it('returns false for other statuses', function () {
            expect(SessionStatus::UNSCHEDULED->canCancel())->toBeFalse()
                ->and(SessionStatus::ONGOING->canCancel())->toBeFalse()
                ->and(SessionStatus::COMPLETED->canCancel())->toBeFalse()
                ->and(SessionStatus::CANCELLED->canCancel())->toBeFalse()
                ->and(SessionStatus::ABSENT->canCancel())->toBeFalse();
        });
    });

    describe('canReschedule()', function () {
        it('returns true for SCHEDULED and READY statuses', function () {
            expect(SessionStatus::SCHEDULED->canReschedule())->toBeTrue()
                ->and(SessionStatus::READY->canReschedule())->toBeTrue();
        });

        it('returns false for other statuses', function () {
            expect(SessionStatus::UNSCHEDULED->canReschedule())->toBeFalse()
                ->and(SessionStatus::ONGOING->canReschedule())->toBeFalse()
                ->and(SessionStatus::COMPLETED->canReschedule())->toBeFalse()
                ->and(SessionStatus::CANCELLED->canReschedule())->toBeFalse()
                ->and(SessionStatus::ABSENT->canReschedule())->toBeFalse();
        });
    });

    describe('countsTowardsSubscription()', function () {
        it('returns true for COMPLETED and ABSENT statuses', function () {
            expect(SessionStatus::COMPLETED->countsTowardsSubscription())->toBeTrue()
                ->and(SessionStatus::ABSENT->countsTowardsSubscription())->toBeTrue();
        });

        it('returns false for other statuses', function () {
            expect(SessionStatus::UNSCHEDULED->countsTowardsSubscription())->toBeFalse()
                ->and(SessionStatus::SCHEDULED->countsTowardsSubscription())->toBeFalse()
                ->and(SessionStatus::READY->countsTowardsSubscription())->toBeFalse()
                ->and(SessionStatus::ONGOING->countsTowardsSubscription())->toBeFalse()
                ->and(SessionStatus::CANCELLED->countsTowardsSubscription())->toBeFalse();
        });
    });

    describe('individualCircleStatuses()', function () {
        it('returns all statuses for individual circles', function () {
            $statuses = SessionStatus::individualCircleStatuses();

            expect($statuses)->toHaveCount(7)
                ->and($statuses)->toContain(SessionStatus::UNSCHEDULED)
                ->and($statuses)->toContain(SessionStatus::SCHEDULED)
                ->and($statuses)->toContain(SessionStatus::READY)
                ->and($statuses)->toContain(SessionStatus::ONGOING)
                ->and($statuses)->toContain(SessionStatus::COMPLETED)
                ->and($statuses)->toContain(SessionStatus::CANCELLED)
                ->and($statuses)->toContain(SessionStatus::ABSENT);
        });
    });

    describe('groupCircleStatuses()', function () {
        it('returns statuses for group circles (excluding ABSENT)', function () {
            $statuses = SessionStatus::groupCircleStatuses();

            expect($statuses)->toHaveCount(6)
                ->and($statuses)->toContain(SessionStatus::UNSCHEDULED)
                ->and($statuses)->toContain(SessionStatus::SCHEDULED)
                ->and($statuses)->toContain(SessionStatus::READY)
                ->and($statuses)->toContain(SessionStatus::ONGOING)
                ->and($statuses)->toContain(SessionStatus::COMPLETED)
                ->and($statuses)->toContain(SessionStatus::CANCELLED)
                ->and($statuses)->not->toContain(SessionStatus::ABSENT);
        });
    });

    describe('values()', function () {
        it('returns all status values as strings', function () {
            $values = SessionStatus::values();

            expect($values)->toBeArray()
                ->and($values)->toHaveCount(7)
                ->and($values)->toContain('unscheduled')
                ->and($values)->toContain('scheduled')
                ->and($values)->toContain('ready')
                ->and($values)->toContain('ongoing')
                ->and($values)->toContain('completed')
                ->and($values)->toContain('cancelled')
                ->and($values)->toContain('absent');
        });
    });

    describe('options()', function () {
        it('returns associative array of value => label pairs', function () {
            $options = SessionStatus::options();

            expect($options)->toBeArray()
                ->and($options)->toHaveCount(7)
                ->and($options['scheduled'])->toBe('مجدولة')
                ->and($options['completed'])->toBe('مكتملة');
        });
    });

    describe('teacherIndividualOptions()', function () {
        it('returns status options for teachers managing individual sessions', function () {
            $options = SessionStatus::teacherIndividualOptions();

            expect($options)->toHaveCount(3)
                ->and($options)->toHaveKey(SessionStatus::COMPLETED->value)
                ->and($options)->toHaveKey(SessionStatus::CANCELLED->value)
                ->and($options)->toHaveKey(SessionStatus::ABSENT->value);
        });
    });

    describe('teacherGroupOptions()', function () {
        it('returns status options for teachers managing group sessions', function () {
            $options = SessionStatus::teacherGroupOptions();

            expect($options)->toHaveCount(2)
                ->and($options)->toHaveKey(SessionStatus::COMPLETED->value)
                ->and($options)->toHaveKey(SessionStatus::CANCELLED->value)
                ->and($options)->not->toHaveKey(SessionStatus::ABSENT->value);
        });
    });

    describe('colorOptions()', function () {
        it('returns color to value mapping', function () {
            $options = SessionStatus::colorOptions();

            expect($options)->toBeArray()
                ->and($options)->toHaveKey('gray')
                ->and($options)->toHaveKey('info')
                ->and($options)->toHaveKey('success')
                ->and($options)->toHaveKey('primary')
                ->and($options)->toHaveKey('danger')
                ->and($options)->toHaveKey('warning');
        });
    });
});
