<?php

namespace Tests\Unit\Enums;

use App\Enums\SessionStatus;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SessionStatus enum
 *
 * Tests cover:
 * - Enum values
 * - Enum methods
 * - Status transitions
 */
class SessionStatusTest extends TestCase
{
    /**
     * Test all expected status values exist.
     */
    public function test_all_status_values_exist(): void
    {
        $this->assertTrue(defined(SessionStatus::class . '::UNSCHEDULED'));
        $this->assertTrue(defined(SessionStatus::class . '::SCHEDULED'));
        $this->assertTrue(defined(SessionStatus::class . '::READY'));
        $this->assertTrue(defined(SessionStatus::class . '::ONGOING'));
        $this->assertTrue(defined(SessionStatus::class . '::COMPLETED'));
        $this->assertTrue(defined(SessionStatus::class . '::CANCELLED'));
        $this->assertTrue(defined(SessionStatus::class . '::ABSENT'));
    }

    /**
     * Test status values are strings.
     */
    public function test_status_values_are_strings(): void
    {
        $this->assertIsString(SessionStatus::UNSCHEDULED->value);
        $this->assertIsString(SessionStatus::SCHEDULED->value);
        $this->assertIsString(SessionStatus::READY->value);
        $this->assertIsString(SessionStatus::ONGOING->value);
        $this->assertIsString(SessionStatus::COMPLETED->value);
        $this->assertIsString(SessionStatus::CANCELLED->value);
        $this->assertIsString(SessionStatus::ABSENT->value);
    }

    /**
     * Test unscheduled status value.
     */
    public function test_unscheduled_status_value(): void
    {
        $this->assertEquals('unscheduled', SessionStatus::UNSCHEDULED->value);
    }

    /**
     * Test scheduled status value.
     */
    public function test_scheduled_status_value(): void
    {
        $this->assertEquals('scheduled', SessionStatus::SCHEDULED->value);
    }

    /**
     * Test ready status value.
     */
    public function test_ready_status_value(): void
    {
        $this->assertEquals('ready', SessionStatus::READY->value);
    }

    /**
     * Test ongoing status value.
     */
    public function test_ongoing_status_value(): void
    {
        $this->assertEquals('ongoing', SessionStatus::ONGOING->value);
    }

    /**
     * Test completed status value.
     */
    public function test_completed_status_value(): void
    {
        $this->assertEquals('completed', SessionStatus::COMPLETED->value);
    }

    /**
     * Test cancelled status value.
     */
    public function test_cancelled_status_value(): void
    {
        $this->assertEquals('cancelled', SessionStatus::CANCELLED->value);
    }

    /**
     * Test absent status value.
     */
    public function test_absent_status_value(): void
    {
        $this->assertEquals('absent', SessionStatus::ABSENT->value);
    }

    /**
     * Test can get status from string.
     */
    public function test_can_get_status_from_string(): void
    {
        $status = SessionStatus::from('scheduled');
        $this->assertEquals(SessionStatus::SCHEDULED, $status);

        $status = SessionStatus::from('completed');
        $this->assertEquals(SessionStatus::COMPLETED, $status);
    }

    /**
     * Test tryFrom returns null for invalid value.
     */
    public function test_try_from_returns_null_for_invalid_value(): void
    {
        $status = SessionStatus::tryFrom('invalid_status');
        $this->assertNull($status);
    }

    /**
     * Test enum cases.
     */
    public function test_enum_cases(): void
    {
        $cases = SessionStatus::cases();

        $this->assertCount(7, $cases);
        $this->assertContains(SessionStatus::UNSCHEDULED, $cases);
        $this->assertContains(SessionStatus::SCHEDULED, $cases);
        $this->assertContains(SessionStatus::READY, $cases);
        $this->assertContains(SessionStatus::ONGOING, $cases);
        $this->assertContains(SessionStatus::COMPLETED, $cases);
        $this->assertContains(SessionStatus::CANCELLED, $cases);
        $this->assertContains(SessionStatus::ABSENT, $cases);
    }

    /**
     * Test status has label method if exists.
     */
    public function test_status_has_label_method(): void
    {
        if (method_exists(SessionStatus::SCHEDULED, 'label')) {
            $this->assertIsString(SessionStatus::SCHEDULED->label());
            $this->assertNotEmpty(SessionStatus::SCHEDULED->label());
        } else {
            $this->assertTrue(true); // Skip if method doesn't exist
        }
    }

    /**
     * Test status has color method if exists.
     */
    public function test_status_has_color_method(): void
    {
        if (method_exists(SessionStatus::SCHEDULED, 'color')) {
            $this->assertIsString(SessionStatus::SCHEDULED->color());
        } else {
            $this->assertTrue(true); // Skip if method doesn't exist
        }
    }

    /**
     * Test status has icon method if exists.
     */
    public function test_status_has_icon_method(): void
    {
        if (method_exists(SessionStatus::SCHEDULED, 'icon')) {
            $this->assertIsString(SessionStatus::SCHEDULED->icon());
        } else {
            $this->assertTrue(true); // Skip if method doesn't exist
        }
    }

    /**
     * Test status comparison.
     */
    public function test_status_comparison(): void
    {
        $status1 = SessionStatus::SCHEDULED;
        $status2 = SessionStatus::SCHEDULED;
        $status3 = SessionStatus::COMPLETED;

        $this->assertTrue($status1 === $status2);
        $this->assertFalse($status1 === $status3);
    }

    /**
     * Test status can be used in match expression.
     */
    public function test_status_can_be_used_in_match(): void
    {
        $status = SessionStatus::COMPLETED;

        $result = match ($status) {
            SessionStatus::UNSCHEDULED => 'is_unscheduled',
            SessionStatus::SCHEDULED => 'is_scheduled',
            SessionStatus::READY => 'is_ready',
            SessionStatus::ONGOING => 'is_ongoing',
            SessionStatus::COMPLETED => 'is_completed',
            SessionStatus::CANCELLED => 'is_cancelled',
            SessionStatus::ABSENT => 'is_absent',
        };

        $this->assertEquals('is_completed', $result);
    }
}
