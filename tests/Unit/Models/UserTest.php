<?php

use App\Models\Academy;
use App\Models\User;

describe('User Model', function () {
    describe('factory', function () {
        it('creates a user with default attributes', function () {
            $user = User::factory()->create();

            expect($user)->toBeInstanceOf(User::class)
                ->and($user->exists)->toBeTrue()
                ->and($user->first_name)->not->toBeEmpty()
                ->and($user->last_name)->not->toBeEmpty()
                ->and($user->email)->not->toBeEmpty()
                ->and($user->active_status)->toBeTrue();
        });

        it('creates a super admin user', function () {
            $user = User::factory()->superAdmin()->create();

            expect($user->user_type)->toBe('super_admin')
                ->and($user->academy_id)->toBeNull();
        });

        it('creates an admin user', function () {
            $user = User::factory()->admin()->create();

            expect($user->user_type)->toBe('admin');
        });

        it('creates a supervisor user', function () {
            $user = User::factory()->supervisor()->create();

            expect($user->user_type)->toBe('supervisor');
        });

        it('creates a quran teacher user', function () {
            $user = User::factory()->quranTeacher()->create();

            expect($user->user_type)->toBe('quran_teacher');
        });

        it('creates an academic teacher user', function () {
            $user = User::factory()->academicTeacher()->create();

            expect($user->user_type)->toBe('academic_teacher');
        });

        it('creates a student user', function () {
            $user = User::factory()->student()->create();

            expect($user->user_type)->toBe('student');
        });

        it('creates a parent user', function () {
            $user = User::factory()->parent()->create();

            expect($user->user_type)->toBe('parent');
        });

        it('creates an inactive user', function () {
            $user = User::factory()->inactive()->create();

            expect($user->active_status)->toBeFalse();
        });

        it('creates a user for a specific academy', function () {
            $academy = Academy::factory()->create();
            $user = User::factory()->forAcademy($academy)->create();

            expect($user->academy_id)->toBe($academy->id);
        });
    });

    describe('role constants', function () {
        it('has correct role constants defined', function () {
            expect(User::ROLE_SUPER_ADMIN)->toBe('super_admin')
                ->and(User::ROLE_ACADEMY_ADMIN)->toBe('academy_admin')
                ->and(User::ROLE_QURAN_TEACHER)->toBe('quran_teacher')
                ->and(User::ROLE_ACADEMIC_TEACHER)->toBe('academic_teacher')
                ->and(User::ROLE_SUPERVISOR)->toBe('supervisor')
                ->and(User::ROLE_STUDENT)->toBe('student')
                ->and(User::ROLE_PARENT)->toBe('parent');
        });
    });

    describe('hasRole()', function () {
        it('returns true when user has the specified role', function () {
            $user = User::factory()->create(['user_type' => 'quran_teacher']);

            expect($user->hasRole('quran_teacher'))->toBeTrue();
        });

        it('returns false when user does not have the specified role', function () {
            $user = User::factory()->create(['user_type' => 'student']);

            expect($user->hasRole('quran_teacher'))->toBeFalse();
        });

        it('accepts an array of roles and returns true if user has any', function () {
            $user = User::factory()->create(['user_type' => 'quran_teacher']);

            expect($user->hasRole(['quran_teacher', 'academic_teacher']))->toBeTrue();
        });

        it('returns false when user has none of the specified roles', function () {
            $user = User::factory()->create(['user_type' => 'student']);

            expect($user->hasRole(['quran_teacher', 'academic_teacher']))->toBeFalse();
        });
    });

    describe('getIdentifier()', function () {
        it('returns a unique identifier for LiveKit', function () {
            $user = User::factory()->create([
                'first_name' => 'John',
                'last_name' => 'Doe',
            ]);

            $identifier = $user->getIdentifier();

            expect($identifier)->toContain($user->id)
                ->and($identifier)->toContain('john_doe');
        });
    });

    describe('relationships', function () {
        it('belongs to an academy', function () {
            $academy = Academy::factory()->create();
            $user = User::factory()->create(['academy_id' => $academy->id]);

            expect($user->academy)->toBeInstanceOf(Academy::class)
                ->and($user->academy->id)->toBe($academy->id);
        });

        it('can exist without an academy for super admin', function () {
            $user = User::factory()->superAdmin()->create();

            expect($user->academy)->toBeNull()
                ->and($user->user_type)->toBe('super_admin');
        });
    });

    describe('fillable attributes', function () {
        it('allows mass assignment of expected fields', function () {
            $academy = Academy::factory()->create();

            $user = User::factory()->create([
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
                'phone' => '0512345678',
                'user_type' => 'student',
                'academy_id' => $academy->id,
            ]);

            expect($user->first_name)->toBe('Test')
                ->and($user->last_name)->toBe('User')
                ->and($user->email)->toBe('test@example.com')
                ->and($user->phone)->toBe('0512345678')
                ->and($user->user_type)->toBe('student')
                ->and($user->academy_id)->toBe($academy->id);
        });
    });

    describe('soft deletes', function () {
        it('soft deletes user instead of permanently deleting', function () {
            $user = User::factory()->create();
            $userId = $user->id;

            $user->delete();

            expect(User::find($userId))->toBeNull()
                ->and(User::withTrashed()->find($userId))->not->toBeNull()
                ->and(User::withTrashed()->find($userId)->deleted_at)->not->toBeNull();
        });

        it('can restore a soft deleted user', function () {
            $user = User::factory()->create();
            $userId = $user->id;

            $user->delete();
            User::withTrashed()->find($userId)->restore();

            expect(User::find($userId))->not->toBeNull()
                ->and(User::find($userId)->deleted_at)->toBeNull();
        });
    });

    describe('authentication', function () {
        it('uses HasApiTokens trait for Sanctum', function () {
            $user = User::factory()->create();

            expect(method_exists($user, 'tokens'))->toBeTrue()
                ->and(method_exists($user, 'createToken'))->toBeTrue();
        });

        it('uses Notifiable trait', function () {
            $user = User::factory()->create();

            expect(method_exists($user, 'notify'))->toBeTrue()
                ->and(method_exists($user, 'notifications'))->toBeTrue();
        });
    });
});
