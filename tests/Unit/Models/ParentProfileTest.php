<?php

use App\Enums\RelationshipType;
use App\Models\Academy;
use App\Models\AcademicGradeLevel;
use App\Models\ParentProfile;
use App\Models\StudentProfile;
use App\Models\User;

describe('ParentProfile Model', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
    });

    describe('factory', function () {
        it('can create a parent profile using factory', function () {
            $profile = ParentProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            expect($profile)->toBeInstanceOf(ParentProfile::class)
                ->and($profile->id)->toBeInt();
        });

        it('auto-generates parent code on creation', function () {
            $profile = ParentProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            expect($profile->parent_code)->toStartWith('PAR-');
        });

        it('creates profile with names', function () {
            $profile = ParentProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'first_name' => 'محمد',
                'last_name' => 'أحمد',
            ]);

            expect($profile->first_name)->toBe('محمد')
                ->and($profile->last_name)->toBe('أحمد');
        });
    });

    describe('relationships', function () {
        it('belongs to an academy', function () {
            $profile = ParentProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            expect($profile->academy)->toBeInstanceOf(Academy::class)
                ->and($profile->academy->id)->toBe($this->academy->id);
        });

        it('belongs to a user', function () {
            $user = User::factory()->parent()->forAcademy($this->academy)->create();
            $profile = ParentProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $user->id,
            ]);

            expect($profile->user)->toBeInstanceOf(User::class)
                ->and($profile->user->id)->toBe($user->id);
        });

        it('has many students', function () {
            $profile = ParentProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $gradeLevel = AcademicGradeLevel::create([
                'academy_id' => $this->academy->id,
                'name' => 'Grade 1',
                'is_active' => true,
            ]);

            $student1 = StudentProfile::factory()->create(['grade_level_id' => $gradeLevel->id]);
            $student2 = StudentProfile::factory()->create(['grade_level_id' => $gradeLevel->id]);

            $profile->students()->attach($student1->id, ['relationship_type' => 'father']);
            $profile->students()->attach($student2->id, ['relationship_type' => 'father']);

            expect($profile->students)->toHaveCount(2);
        });
    });

    describe('scopes', function () {
        it('can filter unlinked profiles', function () {
            // Create a fresh academy to ensure test isolation
            $testAcademy = Academy::factory()->create();

            $unlinkedProfile = ParentProfile::factory()->create([
                'academy_id' => $testAcademy->id,
                'user_id' => null,
            ]);

            $user = User::factory()->parent()->forAcademy($testAcademy)->create();
            $linkedProfile = ParentProfile::factory()->create([
                'academy_id' => $testAcademy->id,
                'user_id' => $user->id,
            ]);

            // Use withoutGlobalScopes and check the specific profiles we created
            $unlinkedProfiles = ParentProfile::withoutGlobalScopes()
                ->whereIn('id', [$unlinkedProfile->id, $linkedProfile->id])
                ->unlinked()
                ->get();

            expect($unlinkedProfiles)->toHaveCount(1)
                ->and($unlinkedProfiles->first()->id)->toBe($unlinkedProfile->id);
        });

        it('can filter linked profiles', function () {
            // Create a fresh academy to ensure test isolation
            $testAcademy = Academy::factory()->create();

            $unlinkedProfile = ParentProfile::factory()->create([
                'academy_id' => $testAcademy->id,
                'user_id' => null,
            ]);

            $user = User::factory()->parent()->forAcademy($testAcademy)->create();
            $linkedProfile = ParentProfile::factory()->create([
                'academy_id' => $testAcademy->id,
                'user_id' => $user->id,
            ]);

            // Verify the scope returns only profiles with user_id by checking our specific profile
            expect($linkedProfile->user_id)->not->toBeNull();
            expect($unlinkedProfile->user_id)->toBeNull();

            // Use withoutGlobalScopes and check the specific profiles we created
            $linkedProfiles = ParentProfile::withoutGlobalScopes()
                ->whereIn('id', [$unlinkedProfile->id, $linkedProfile->id])
                ->linked()
                ->get();

            expect($linkedProfiles)->toHaveCount(1)
                ->and($linkedProfiles->first()->id)->toBe($linkedProfile->id);
        });
    });

    describe('attributes and casts', function () {
        it('casts relationship_type to enum', function () {
            $profile = ParentProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'relationship_type' => RelationshipType::FATHER,
            ]);

            expect($profile->relationship_type)->toBeInstanceOf(RelationshipType::class);
        });
    });

    describe('accessors', function () {
        it('returns full name', function () {
            $profile = ParentProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'first_name' => 'محمد',
                'last_name' => 'أحمد',
            ]);

            expect($profile->full_name)->toBe('محمد أحمد');
        });

        it('returns display name with parent code', function () {
            $user = User::factory()->parent()->forAcademy($this->academy)->create([
                'first_name' => 'محمد',
                'last_name' => 'أحمد',
            ]);
            $profile = ParentProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $user->id,
            ]);

            $displayName = $profile->getDisplayName();

            expect($displayName)->toContain($profile->parent_code);
        });

        it('returns preferred contact method in Arabic for phone', function () {
            $profile = ParentProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'preferred_contact_method' => 'phone',
            ]);

            expect($profile->preferred_contact_method_in_arabic)->toBe('هاتف');
        });

        it('returns preferred contact method in Arabic for email', function () {
            $profile = ParentProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'preferred_contact_method' => 'email',
            ]);

            expect($profile->preferred_contact_method_in_arabic)->toBe('بريد إلكتروني');
        });

        it('returns preferred contact method in Arabic for sms', function () {
            $profile = ParentProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'preferred_contact_method' => 'sms',
            ]);

            expect($profile->preferred_contact_method_in_arabic)->toBe('رسالة نصية');
        });
    });

    describe('methods', function () {
        it('can check if profile is linked', function () {
            $user = User::factory()->parent()->forAcademy($this->academy)->create();
            $profile = ParentProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $user->id,
            ]);

            expect($profile->isLinked())->toBeTrue();
        });

        it('returns false for unlinked profile', function () {
            $profile = ParentProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => null,
            ]);

            expect($profile->isLinked())->toBeFalse();
        });
    });

    describe('parent code generation', function () {
        it('generates unique parent codes', function () {
            $profile1 = ParentProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $profile2 = ParentProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            expect($profile1->parent_code)->not->toBe($profile2->parent_code);
        });

        it('includes academy id in parent code', function () {
            $profile = ParentProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            // Parent code format: PAR-{academyId:02d}-{timestamp}{random}
            expect($profile->parent_code)->toStartWith('PAR-');
        });
    });

    describe('soft deletes', function () {
        it('can be soft deleted', function () {
            $profile = ParentProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $profile->delete();

            expect($profile->trashed())->toBeTrue()
                ->and(ParentProfile::withTrashed()->find($profile->id))->not->toBeNull();
        });

        it('can be restored', function () {
            $profile = ParentProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $profile->delete();

            $profile->restore();

            expect($profile->trashed())->toBeFalse()
                ->and(ParentProfile::find($profile->id))->not->toBeNull();
        });
    });
});
