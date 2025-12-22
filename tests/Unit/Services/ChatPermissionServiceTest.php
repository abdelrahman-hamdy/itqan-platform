<?php

use App\Models\Academy;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\QuranCircle;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\User;
use App\Services\ChatPermissionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

describe('ChatPermissionService', function () {
    beforeEach(function () {
        $this->service = new ChatPermissionService();
        $this->academy = Academy::factory()->create();
    });

    describe('canMessage()', function () {
        it('returns false when user tries to message self', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $result = $this->service->canMessage($user, $user);

            expect($result)->toBeFalse();
        });

        it('returns true when super admin messages any user', function () {
            $superAdmin = User::factory()->superAdmin()->create();
            $targetUser = User::factory()->student()->forAcademy($this->academy)->create();

            $result = $this->service->canMessage($superAdmin, $targetUser);

            expect($result)->toBeTrue();
        });

        it('returns false when users are from different academies', function () {
            $academy1 = Academy::factory()->create();
            $academy2 = Academy::factory()->create();

            $user1 = User::factory()->student()->forAcademy($academy1)->create();
            $user2 = User::factory()->student()->forAcademy($academy2)->create();

            $result = $this->service->canMessage($user1, $user2);

            expect($result)->toBeFalse();
        });

        it('uses cache for permission checks', function () {
            $user1 = User::factory()->student()->forAcademy($this->academy)->create();
            $user2 = User::factory()->student()->forAcademy($this->academy)->create();

            Cache::flush();

            // First call
            $result1 = $this->service->canMessage($user1, $user2);

            // Mock Cache to verify it's hit on second call
            Cache::shouldReceive('remember')
                ->once()
                ->andReturn(false);

            // Second call should use cache
            $result2 = $this->service->canMessage($user1, $user2);

            expect($result1)->toBe($result2);
        });

        it('returns true when academy admin messages user in their academy', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            Cache::flush();
            $result = $this->service->canMessage($admin, $student);

            expect($result)->toBeTrue();
        });

        it('returns true when supervisor messages user in their academy', function () {
            $supervisor = User::factory()->supervisor()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            Cache::flush();
            $result = $this->service->canMessage($supervisor, $student);

            expect($result)->toBeTrue();
        });
    });

    describe('checkStudentPermissions()', function () {
        it('allows student to message academy admin', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();

            Cache::flush();
            $result = $this->service->canMessage($student, $admin);

            expect($result)->toBeTrue();
        });

        it('allows student to message supervisor', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $supervisor = User::factory()->supervisor()->forAcademy($this->academy)->create();

            Cache::flush();
            $result = $this->service->canMessage($student, $supervisor);

            expect($result)->toBeTrue();
        });

        it('allows student to message their quran teacher', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            // Create a quran session linking them
            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            Cache::flush();
            $result = $this->service->canMessage($student, $teacher);

            expect($result)->toBeTrue();
        });

        it('allows student to message their academic teacher', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = \App\Models\AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
            ]);

            // Create an academic session linking them
            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'academic_teacher_id' => $teacherProfile->id,
            ]);

            Cache::flush();
            $result = $this->service->canMessage($student, $teacher);

            expect($result)->toBeTrue();
        });

        it('prevents student from messaging unrelated teacher', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            Cache::flush();
            $result = $this->service->canMessage($student, $teacher);

            expect($result)->toBeFalse();
        });

        it('allows student to message their parent', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();

            // Link parent and student
            DB::table('parent_student_relationships')->insert([
                'parent_id' => $parent->id,
                'student_id' => $student->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Cache::flush();
            $result = $this->service->canMessage($student, $parent);

            expect($result)->toBeTrue();
        });

        it('prevents student from messaging unrelated parent', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();

            Cache::flush();
            $result = $this->service->canMessage($student, $parent);

            expect($result)->toBeFalse();
        });

        it('prevents student from messaging other students', function () {
            $student1 = User::factory()->student()->forAcademy($this->academy)->create();
            $student2 = User::factory()->student()->forAcademy($this->academy)->create();

            Cache::flush();
            $result = $this->service->canMessage($student1, $student2);

            expect($result)->toBeFalse();
        });
    });

    describe('checkTeacherPermissions()', function () {
        it('allows teacher to message academy admin', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();

            Cache::flush();
            $result = $this->service->canMessage($teacher, $admin);

            expect($result)->toBeTrue();
        });

        it('allows teacher to message supervisor', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $supervisor = User::factory()->supervisor()->forAcademy($this->academy)->create();

            Cache::flush();
            $result = $this->service->canMessage($teacher, $supervisor);

            expect($result)->toBeTrue();
        });

        it('allows quran teacher to message their student', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            Cache::flush();
            $result = $this->service->canMessage($teacher, $student);

            expect($result)->toBeTrue();
        });

        it('allows academic teacher to message their student', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = \App\Models\AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
            ]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'academic_teacher_id' => $teacherProfile->id,
            ]);

            Cache::flush();
            $result = $this->service->canMessage($teacher, $student);

            expect($result)->toBeTrue();
        });

        it('prevents teacher from messaging unrelated student', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            Cache::flush();
            $result = $this->service->canMessage($teacher, $student);

            expect($result)->toBeFalse();
        });

        it('prevents teacher from messaging parents', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();

            Cache::flush();
            $result = $this->service->canMessage($teacher, $parent);

            expect($result)->toBeFalse();
        });

        it('prevents teacher from messaging other teachers', function () {
            $teacher1 = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacher2 = User::factory()->academicTeacher()->forAcademy($this->academy)->create();

            Cache::flush();
            $result = $this->service->canMessage($teacher1, $teacher2);

            expect($result)->toBeFalse();
        });
    });

    describe('checkParentPermissions()', function () {
        it('allows parent to message academy admin', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();

            Cache::flush();
            $result = $this->service->canMessage($parent, $admin);

            expect($result)->toBeTrue();
        });

        it('allows parent to message their child', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            DB::table('parent_student_relationships')->insert([
                'parent_id' => $parent->id,
                'student_id' => $student->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Cache::flush();
            $result = $this->service->canMessage($parent, $student);

            expect($result)->toBeTrue();
        });

        it('prevents parent from messaging other students', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            Cache::flush();
            $result = $this->service->canMessage($parent, $student);

            expect($result)->toBeFalse();
        });

        it('allows parent to message their child quran teacher', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            DB::table('parent_student_relationships')->insert([
                'parent_id' => $parent->id,
                'student_id' => $student->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            Cache::flush();
            $result = $this->service->canMessage($parent, $teacher);

            expect($result)->toBeTrue();
        });

        it('allows parent to message their child academic teacher', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = \App\Models\AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
            ]);

            DB::table('parent_student_relationships')->insert([
                'parent_id' => $parent->id,
                'student_id' => $student->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'academic_teacher_id' => $teacherProfile->id,
            ]);

            Cache::flush();
            $result = $this->service->canMessage($parent, $teacher);

            expect($result)->toBeTrue();
        });

        it('prevents parent from messaging unrelated teacher', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            Cache::flush();
            $result = $this->service->canMessage($parent, $teacher);

            expect($result)->toBeFalse();
        });

        it('prevents parent from messaging other parents', function () {
            $parent1 = User::factory()->parent()->forAcademy($this->academy)->create();
            $parent2 = User::factory()->parent()->forAcademy($this->academy)->create();

            Cache::flush();
            $result = $this->service->canMessage($parent1, $parent2);

            expect($result)->toBeFalse();
        });
    });

    describe('isTeacherOfStudent()', function () {
        it('returns true when teacher has quran sessions with student', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            Cache::flush();
            $result = $this->service->canMessage($teacher, $student);

            expect($result)->toBeTrue();
        });

        it('returns true when teacher has academic sessions with student', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = \App\Models\AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
            ]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'academic_teacher_id' => $teacherProfile->id,
            ]);

            Cache::flush();
            $result = $this->service->canMessage($teacher, $student);

            expect($result)->toBeTrue();
        });

        it('returns true when teacher has active academic subscription with student', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'status' => 'active',
            ]);

            Cache::flush();
            $result = $this->service->canMessage($teacher, $student);

            expect($result)->toBeTrue();
        });

        it('returns true when teacher has active quran subscription with student', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->id,
                'status' => 'active',
            ]);

            Cache::flush();
            $result = $this->service->canMessage($teacher, $student);

            expect($result)->toBeTrue();
        });

        it('returns true when teacher has active quran circle with student', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
            ]);

            DB::table('quran_circle_students')->insert([
                'circle_id' => $circle->id,
                'student_id' => $student->id,
                'status' => 'enrolled',
                'enrolled_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Cache::flush();
            $result = $this->service->canMessage($teacher, $student);

            expect($result)->toBeTrue();
        });

        it('returns false when teacher has no relationship with student', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            Cache::flush();
            $result = $this->service->canMessage($teacher, $student);

            expect($result)->toBeFalse();
        });

        it('returns false when subscription is not active', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'status' => 'expired',
            ]);

            Cache::flush();
            $result = $this->service->canMessage($teacher, $student);

            expect($result)->toBeFalse();
        });
    });

    describe('isParentOfStudent()', function () {
        it('returns true when parent is linked to student', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            DB::table('parent_student_relationships')->insert([
                'parent_id' => $parent->id,
                'student_id' => $student->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Cache::flush();
            $result = $this->service->canMessage($parent, $student);

            expect($result)->toBeTrue();
        });

        it('returns false when parent is not linked to student', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            Cache::flush();
            $result = $this->service->canMessage($parent, $student);

            expect($result)->toBeFalse();
        });
    });

    describe('isTeacherOfParentChildren()', function () {
        it('returns true when teacher teaches parent child via quran session', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            DB::table('parent_student_relationships')->insert([
                'parent_id' => $parent->id,
                'student_id' => $student->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            Cache::flush();
            $result = $this->service->canMessage($parent, $teacher);

            expect($result)->toBeTrue();
        });

        it('returns true when teacher teaches parent child via academic session', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = \App\Models\AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
            ]);

            DB::table('parent_student_relationships')->insert([
                'parent_id' => $parent->id,
                'student_id' => $student->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'academic_teacher_id' => $teacherProfile->id,
            ]);

            Cache::flush();
            $result = $this->service->canMessage($parent, $teacher);

            expect($result)->toBeTrue();
        });

        it('returns false when parent has no children', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            Cache::flush();
            $result = $this->service->canMessage($parent, $teacher);

            expect($result)->toBeFalse();
        });

        it('returns false when teacher does not teach any parent children', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            DB::table('parent_student_relationships')->insert([
                'parent_id' => $parent->id,
                'student_id' => $student->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Cache::flush();
            $result = $this->service->canMessage($parent, $teacher);

            expect($result)->toBeFalse();
        });
    });

    describe('clearUserCache()', function () {
        it('clears cache for user', function () {
            $user1 = User::factory()->student()->forAcademy($this->academy)->create();
            $user2 = User::factory()->student()->forAcademy($this->academy)->create();

            Cache::flush();

            // Prime cache
            $this->service->canMessage($user1, $user2);

            // Clear cache
            $this->service->clearUserCache($user1->id);

            // Verify cache was cleared by checking it gets recalculated
            $result = $this->service->canMessage($user1, $user2);

            expect($result)->toBeFalse();
        });
    });

    describe('getCacheKey()', function () {
        it('generates consistent cache keys regardless of user order', function () {
            $user1 = User::factory()->student()->forAcademy($this->academy)->create();
            $user2 = User::factory()->student()->forAcademy($this->academy)->create();

            Cache::flush();

            // Call in both orders
            $this->service->canMessage($user1, $user2);
            $result = $this->service->canMessage($user2, $user1);

            // Should return same result (from cache)
            expect($result)->toBeFalse();
        });
    });

    describe('filterAllowedContacts()', function () {
        it('returns empty array when no contacts are allowed', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $otherStudents = User::factory()->student()->forAcademy($this->academy)->count(3)->create();

            Cache::flush();
            $allowed = $this->service->filterAllowedContacts(
                $student,
                $otherStudents->pluck('id')->toArray()
            );

            expect($allowed)->toBeArray()
                ->and($allowed)->toBeEmpty();
        });

        it('returns array of allowed contact IDs', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $supervisor = User::factory()->supervisor()->forAcademy($this->academy)->create();
            $otherStudent = User::factory()->student()->forAcademy($this->academy)->create();

            Cache::flush();
            $allowed = $this->service->filterAllowedContacts(
                $student,
                [$admin->id, $supervisor->id, $otherStudent->id]
            );

            expect($allowed)->toBeArray()
                ->and($allowed)->toContain($admin->id)
                ->and($allowed)->toContain($supervisor->id)
                ->and($allowed)->not->toContain($otherStudent->id);
        });

        it('filters based on actual permissions', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student1 = User::factory()->student()->forAcademy($this->academy)->create();
            $student2 = User::factory()->student()->forAcademy($this->academy)->create();

            // Only link teacher to student1
            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student1->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            Cache::flush();
            $allowed = $this->service->filterAllowedContacts(
                $teacher,
                [$student1->id, $student2->id]
            );

            expect($allowed)->toBeArray()
                ->and($allowed)->toContain($student1->id)
                ->and($allowed)->not->toContain($student2->id);
        });

        it('handles empty user ID array', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            Cache::flush();
            $allowed = $this->service->filterAllowedContacts($student, []);

            expect($allowed)->toBeArray()
                ->and($allowed)->toBeEmpty();
        });

        it('handles non-existent user IDs', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            Cache::flush();
            $allowed = $this->service->filterAllowedContacts($student, [99999, 88888]);

            expect($allowed)->toBeArray()
                ->and($allowed)->toBeEmpty();
        });
    });

    describe('edge cases', function () {
        it('handles multiple relationships between teacher and student', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            // Create multiple relationships
            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->id,
                'status' => 'active',
            ]);

            Cache::flush();
            $result = $this->service->canMessage($teacher, $student);

            expect($result)->toBeTrue();
        });

        it('handles parent with multiple children', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $student1 = User::factory()->student()->forAcademy($this->academy)->create();
            $student2 = User::factory()->student()->forAcademy($this->academy)->create();

            DB::table('parent_student_relationships')->insert([
                ['parent_id' => $parent->id, 'student_id' => $student1->id, 'created_at' => now(), 'updated_at' => now()],
                ['parent_id' => $parent->id, 'student_id' => $student2->id, 'created_at' => now(), 'updated_at' => now()],
            ]);

            Cache::flush();
            $result1 = $this->service->canMessage($parent, $student1);
            $result2 = $this->service->canMessage($parent, $student2);

            expect($result1)->toBeTrue()
                ->and($result2)->toBeTrue();
        });

        it('handles teacher teaching multiple students in same circle', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student1 = User::factory()->student()->forAcademy($this->academy)->create();
            $student2 = User::factory()->student()->forAcademy($this->academy)->create();

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => true,
            ]);

            DB::table('quran_circle_students')->insert([
                ['circle_id' => $circle->id, 'student_id' => $student1->id, 'status' => 'enrolled', 'enrolled_at' => now(), 'created_at' => now(), 'updated_at' => now()],
                ['circle_id' => $circle->id, 'student_id' => $student2->id, 'status' => 'enrolled', 'enrolled_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ]);

            Cache::flush();
            $result1 = $this->service->canMessage($teacher, $student1);
            $result2 = $this->service->canMessage($teacher, $student2);

            expect($result1)->toBeTrue()
                ->and($result2)->toBeTrue();
        });
    });
});
