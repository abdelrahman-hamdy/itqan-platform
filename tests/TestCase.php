<?php

namespace Tests;

use App\Models\Academy;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * The default academy used for testing.
     */
    protected ?Academy $academy = null;

    /**
     * Set up the test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Set default locale to Arabic for consistent testing
        app()->setLocale('ar');
    }

    /**
     * Create a test academy.
     */
    protected function createAcademy(array $attributes = []): Academy
    {
        $this->academy = Academy::factory()->create($attributes);

        return $this->academy;
    }

    /**
     * Get the test academy, creating one if it doesn't exist.
     */
    protected function getAcademy(): Academy
    {
        return $this->academy ??= $this->createAcademy();
    }

    /**
     * Create a super admin user.
     */
    protected function createSuperAdmin(array $attributes = []): User
    {
        return User::factory()->superAdmin()->create($attributes);
    }

    /**
     * Create an academy admin user.
     */
    protected function createAdmin(array $attributes = []): User
    {
        return User::factory()->admin()->create(array_merge([
            'academy_id' => $this->getAcademy()->id,
        ], $attributes));
    }

    /**
     * Create a Quran teacher user.
     */
    protected function createQuranTeacher(array $attributes = []): User
    {
        return User::factory()->quranTeacher()->create(array_merge([
            'academy_id' => $this->getAcademy()->id,
        ], $attributes));
    }

    /**
     * Create an academic teacher user.
     */
    protected function createAcademicTeacher(array $attributes = []): User
    {
        return User::factory()->academicTeacher()->create(array_merge([
            'academy_id' => $this->getAcademy()->id,
        ], $attributes));
    }

    /**
     * Create a student user.
     */
    protected function createStudent(array $attributes = []): User
    {
        return User::factory()->student()->create(array_merge([
            'academy_id' => $this->getAcademy()->id,
        ], $attributes));
    }

    /**
     * Create a parent user.
     */
    protected function createParent(array $attributes = []): User
    {
        return User::factory()->parent()->create(array_merge([
            'academy_id' => $this->getAcademy()->id,
        ], $attributes));
    }

    /**
     * Act as a super admin.
     */
    protected function actingAsSuperAdmin(array $attributes = []): User
    {
        $user = $this->createSuperAdmin($attributes);
        $this->actingAs($user);

        return $user;
    }

    /**
     * Act as an academy admin.
     */
    protected function actingAsAdmin(array $attributes = []): User
    {
        $user = $this->createAdmin($attributes);
        $this->actingAs($user);

        return $user;
    }

    /**
     * Act as a Quran teacher.
     */
    protected function actingAsQuranTeacher(array $attributes = []): User
    {
        $user = $this->createQuranTeacher($attributes);
        $this->actingAs($user);

        return $user;
    }

    /**
     * Act as an academic teacher.
     */
    protected function actingAsAcademicTeacher(array $attributes = []): User
    {
        $user = $this->createAcademicTeacher($attributes);
        $this->actingAs($user);

        return $user;
    }

    /**
     * Act as a student.
     */
    protected function actingAsStudent(array $attributes = []): User
    {
        $user = $this->createStudent($attributes);
        $this->actingAs($user);

        return $user;
    }

    /**
     * Act as a parent.
     */
    protected function actingAsParent(array $attributes = []): User
    {
        $user = $this->createParent($attributes);
        $this->actingAs($user);

        return $user;
    }

    /**
     * Assert that a model has the expected attributes.
     */
    protected function assertModelHasAttributes($model, array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            $this->assertEquals($value, $model->$key, "Attribute '{$key}' does not match expected value.");
        }
    }

    /**
     * Assert that a collection contains a model with specific attributes.
     */
    protected function assertCollectionContainsModel($collection, $model): void
    {
        $this->assertTrue(
            $collection->contains('id', $model->id),
            'Collection does not contain the expected model.'
        );
    }

    /**
     * Assert that a collection does not contain a model.
     */
    protected function assertCollectionDoesNotContainModel($collection, $model): void
    {
        $this->assertFalse(
            $collection->contains('id', $model->id),
            'Collection unexpectedly contains the model.'
        );
    }

    /**
     * Travel to Saudi Arabia timezone time.
     */
    protected function travelToSaudiTime(string $datetime): void
    {
        $this->travelTo(\Carbon\Carbon::parse($datetime, 'Asia/Riyadh'));
    }
}
