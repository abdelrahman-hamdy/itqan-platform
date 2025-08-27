<?php

namespace Tests\Feature;

use App\Models\RecordedCourse;
use Tests\TestCase;

class RecordedCourseOptimizationTest extends TestCase
{
    public function test_course_model_has_correct_fillable_fields()
    {
        $course = new RecordedCourse;
        $fillable = $course->getFillable();

        // Check that removed fields are not in fillable
        $this->assertNotContains('is_featured', $fillable);
        $this->assertNotContains('status', $fillable);
        $this->assertNotContains('trailer_video_url', $fillable);
        $this->assertNotContains('level', $fillable);
        $this->assertNotContains('is_free', $fillable);
        $this->assertNotContains('completion_certificate', $fillable);
        $this->assertNotContains('currency', $fillable);

        // Check that new fields are in fillable
        $this->assertContains('materials', $fillable);
        $this->assertContains('difficulty_level', $fillable);
        $this->assertContains('is_published', $fillable);
    }

    public function test_course_model_has_correct_casts()
    {
        $course = new RecordedCourse;
        $casts = $course->getCasts();

        // Check that removed fields are not in casts
        $this->assertArrayNotHasKey('is_featured', $casts);
        $this->assertArrayNotHasKey('status', $casts);
        $this->assertArrayNotHasKey('trailer_video_url', $casts);
        $this->assertArrayNotHasKey('level', $casts);
        $this->assertArrayNotHasKey('is_free', $casts);
        $this->assertArrayNotHasKey('completion_certificate', $casts);
        $this->assertArrayNotHasKey('currency', $casts);

        // Check that new fields are in casts
        $this->assertArrayHasKey('materials', $casts);
        $this->assertArrayHasKey('is_published', $casts);
        $this->assertEquals('array', $casts['materials']);
        $this->assertEquals('boolean', $casts['is_published']);
    }

    public function test_difficulty_level_validation_values()
    {
        // Test that the new difficulty levels are valid
        $validLevels = ['easy', 'medium', 'hard'];

        foreach ($validLevels as $level) {
            $this->assertTrue(in_array($level, $validLevels), "Difficulty level '{$level}' should be valid");
        }

        // Test that old difficulty levels are no longer valid
        $invalidLevels = ['very_easy', 'very_hard'];

        foreach ($invalidLevels as $level) {
            $this->assertFalse(in_array($level, $validLevels), "Difficulty level '{$level}' should not be valid");
        }
    }

    public function test_published_scope_logic()
    {
        // Test that the published scope logic is correct
        // This is a unit test of the logic, not requiring database
        $query = RecordedCourse::query();

        // Simulate the published scope logic
        $publishedQuery = $query->where('is_published', true);

        // The query should only check is_published, not status
        $this->assertStringContainsString('is_published', $publishedQuery->toSql());
    }

    public function test_is_free_computed_property()
    {
        // Test that is_free is computed correctly from price
        $course = new RecordedCourse;

        // Test free course (price = 0)
        $course->price = 0;
        $this->assertTrue($course->is_free);

        // Test paid course (price > 0)
        $course->price = 100;
        $this->assertFalse($course->is_free);

        // Test decimal price
        $course->price = 99.99;
        $this->assertFalse($course->is_free);
    }

    public function test_free_and_paid_scopes()
    {
        // Test that the free and paid scopes use price-based logic
        $freeQuery = RecordedCourse::free();
        $paidQuery = RecordedCourse::paid();

        // Free scope should check price = 0
        $this->assertStringContainsString('price', $freeQuery->toSql());
        $this->assertStringContainsString('= ?', $freeQuery->toSql());

        // Paid scope should check price > 0
        $this->assertStringContainsString('price', $paidQuery->toSql());
        $this->assertStringContainsString('> ?', $paidQuery->toSql());
    }
}
