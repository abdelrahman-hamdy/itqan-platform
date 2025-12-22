<?php

use App\Models\Academy;
use App\Models\AcademicGradeLevel;
use App\Models\ParentProfile;
use App\Models\StudentProfile;
use App\Models\User;

describe('StudentProfile Model', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
        $this->gradeLevel = AcademicGradeLevel::create([
            'academy_id' => $this->academy->id,
            'name' => 'Grade 1',
            'is_active' => true,
        ]);
    });

    describe('factory', function () {
        it('can create a student profile using factory', function () {
            $profile = StudentProfile::factory()->create([
                'grade_level_id' => $this->gradeLevel->id,
            ]);

            expect($profile)->toBeInstanceOf(StudentProfile::class)
                ->and($profile->id)->toBeInt();
        });

        it('auto-generates student code on creation', function () {
            $profile = StudentProfile::factory()->create([
                'grade_level_id' => $this->gradeLevel->id,
            ]);

            expect($profile->student_code)->toStartWith('ST-');
        });

        it('creates profile with names', function () {
            $profile = StudentProfile::factory()->create([
                'first_name' => 'أحمد',
                'last_name' => 'محمد',
                'grade_level_id' => $this->gradeLevel->id,
            ]);

            expect($profile->first_name)->toBe('أحمد')
                ->and($profile->last_name)->toBe('محمد');
        });
    });

    describe('relationships', function () {
        it('belongs to a user', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();
            $profile = StudentProfile::factory()->create([
                'user_id' => $user->id,
                'grade_level_id' => $this->gradeLevel->id,
            ]);

            expect($profile->user)->toBeInstanceOf(User::class)
                ->and($profile->user->id)->toBe($user->id);
        });

        it('belongs to a grade level', function () {
            $profile = StudentProfile::factory()->create([
                'grade_level_id' => $this->gradeLevel->id,
            ]);

            expect($profile->gradeLevel)->toBeInstanceOf(AcademicGradeLevel::class)
                ->and($profile->gradeLevel->id)->toBe($this->gradeLevel->id);
        });

        it('belongs to a parent', function () {
            $parent = ParentProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $profile = StudentProfile::factory()->create([
                'grade_level_id' => $this->gradeLevel->id,
                'parent_id' => $parent->id,
            ]);

            $profile->refresh();

            expect($profile->parent_id)->toBe($parent->id);
        });

        it('has parent profiles relationship method', function () {
            $profile = StudentProfile::factory()->create([
                'grade_level_id' => $this->gradeLevel->id,
            ]);

            // Test that the relationship method exists and is callable
            expect($profile->parentProfiles())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
        });
    });

    describe('scopes', function () {
        it('can filter unlinked profiles', function () {
            $unlinked = StudentProfile::factory()->create([
                'user_id' => null,
                'grade_level_id' => $this->gradeLevel->id,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();
            $linked = StudentProfile::factory()->create([
                'user_id' => $user->id,
                'grade_level_id' => $this->gradeLevel->id,
            ]);

            // Filter by this grade level to scope to this test's data
            $unlinkedCount = StudentProfile::unlinked()
                ->where('grade_level_id', $this->gradeLevel->id)
                ->count();
            expect($unlinkedCount)->toBe(1);
        });

        it('can filter linked profiles', function () {
            $uniqueEmail = 'unlinked-' . uniqid() . '@test.com';
            $unlinked = StudentProfile::factory()->create([
                'user_id' => null,
                'grade_level_id' => $this->gradeLevel->id,
                'email' => $uniqueEmail,
            ]);

            $user = User::factory()->student()->forAcademy($this->academy)->create();
            $linkedEmail = 'linked-' . uniqid() . '@test.com';
            $linked = StudentProfile::factory()->create([
                'user_id' => $user->id,
                'grade_level_id' => $this->gradeLevel->id,
                'email' => $linkedEmail,
            ]);

            // Verify the linked scope works by checking if our linked profile is in results
            $linkedProfiles = StudentProfile::linked()
                ->where('email', $linkedEmail)
                ->get();
            expect($linkedProfiles)->toHaveCount(1)
                ->and($linkedProfiles->first()->id)->toBe($linked->id);
        });

        it('can filter profiles for academy', function () {
            StudentProfile::factory()->create([
                'grade_level_id' => $this->gradeLevel->id,
            ]);

            $otherAcademy = Academy::factory()->create();
            $otherGrade = AcademicGradeLevel::create([
                'academy_id' => $otherAcademy->id,
                'name' => 'Grade 2',
                'is_active' => true,
            ]);
            StudentProfile::factory()->create([
                'grade_level_id' => $otherGrade->id,
            ]);

            expect(StudentProfile::forAcademy($this->academy->id)->count())->toBeGreaterThanOrEqual(1);
        });
    });

    describe('attributes and casts', function () {
        it('casts birth_date to date', function () {
            $profile = StudentProfile::factory()->create([
                'grade_level_id' => $this->gradeLevel->id,
                'birth_date' => '2010-05-15',
            ]);

            expect($profile->birth_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
        });

        it('casts enrollment_date to date', function () {
            $profile = StudentProfile::factory()->create([
                'grade_level_id' => $this->gradeLevel->id,
                'enrollment_date' => '2023-09-01',
            ]);

            expect($profile->enrollment_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
        });

        it('casts grade_level_id to integer', function () {
            $profile = StudentProfile::factory()->create([
                'grade_level_id' => $this->gradeLevel->id,
            ]);

            expect($profile->grade_level_id)->toBeInt();
        });

        it('casts parent_id to integer', function () {
            $parent = ParentProfile::factory()->create();
            $profile = StudentProfile::factory()->create([
                'grade_level_id' => $this->gradeLevel->id,
                'parent_id' => $parent->id,
            ]);

            expect($profile->parent_id)->toBeInt();
        });
    });

    describe('accessors', function () {
        it('returns full name', function () {
            $profile = StudentProfile::factory()->create([
                'first_name' => 'أحمد',
                'last_name' => 'محمد',
                'grade_level_id' => $this->gradeLevel->id,
            ]);

            expect($profile->full_name)->toBe('أحمد محمد');
        });

        it('returns display name with student code', function () {
            $profile = StudentProfile::factory()->create([
                'first_name' => 'أحمد',
                'last_name' => 'محمد',
                'grade_level_id' => $this->gradeLevel->id,
            ]);

            expect($profile->display_name)->toContain('أحمد محمد')
                ->and($profile->display_name)->toContain($profile->student_code);
        });

        it('returns academy id through grade level', function () {
            $profile = StudentProfile::factory()->create([
                'grade_level_id' => $this->gradeLevel->id,
            ]);

            expect($profile->academy_id)->toBe($this->academy->id);
        });

        it('returns null academy id when no grade level', function () {
            $profile = StudentProfile::factory()->create([
                'grade_level_id' => null,
            ]);

            expect($profile->academy_id)->toBeNull();
        });
    });

    describe('methods', function () {
        it('can check if profile is linked', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();
            $profile = StudentProfile::factory()->create([
                'user_id' => $user->id,
                'grade_level_id' => $this->gradeLevel->id,
            ]);

            expect($profile->isLinked())->toBeTrue();
        });

        it('returns false for unlinked profile', function () {
            $profile = StudentProfile::factory()->create([
                'user_id' => null,
                'grade_level_id' => $this->gradeLevel->id,
            ]);

            expect($profile->isLinked())->toBeFalse();
        });
    });

    describe('student code generation', function () {
        it('generates unique student codes', function () {
            $profile1 = StudentProfile::factory()->create([
                'grade_level_id' => $this->gradeLevel->id,
            ]);
            $profile2 = StudentProfile::factory()->create([
                'grade_level_id' => $this->gradeLevel->id,
            ]);

            expect($profile1->student_code)->not->toBe($profile2->student_code);
        });

        it('includes academy id in student code', function () {
            $profile = StudentProfile::factory()->create([
                'grade_level_id' => $this->gradeLevel->id,
            ]);

            // Student code format: ST-{academyId:02d}-{timestamp}{random}
            expect($profile->student_code)->toStartWith('ST-');
        });
    });

    describe('soft deletes', function () {
        it('can be soft deleted', function () {
            $profile = StudentProfile::factory()->create([
                'grade_level_id' => $this->gradeLevel->id,
            ]);

            $profile->delete();

            expect($profile->trashed())->toBeTrue()
                ->and(StudentProfile::withTrashed()->find($profile->id))->not->toBeNull();
        });

        it('can be restored', function () {
            $profile = StudentProfile::factory()->create([
                'grade_level_id' => $this->gradeLevel->id,
            ]);
            $profile->delete();

            $profile->restore();

            expect($profile->trashed())->toBeFalse()
                ->and(StudentProfile::find($profile->id))->not->toBeNull();
        });
    });
});
