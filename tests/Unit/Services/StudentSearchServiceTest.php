<?php

use App\Models\Academy;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourse;
use App\Models\QuranCircle;
use App\Models\QuranTeacherProfile;
use App\Models\RecordedCourse;
use App\Models\User;
use App\Services\StudentSearchService;

describe('StudentSearchService', function () {
    beforeEach(function () {
        $this->service = new StudentSearchService();
        $this->academy = Academy::factory()->create();
        $this->student = User::factory()
            ->student()
            ->forAcademy($this->academy)
            ->create();
    });

    describe('search()', function () {
        it('returns search results for all entity types', function () {
            $query = 'test';

            $results = $this->service->search($this->student, $query);

            expect($results)->toBeArray()
                ->and($results)->toHaveKeys([
                    'interactive_courses',
                    'recorded_courses',
                    'quran_teachers',
                    'academic_teachers',
                    'quran_circles',
                ])
                ->and($results['interactive_courses'])->toBeInstanceOf(\Illuminate\Support\Collection::class)
                ->and($results['recorded_courses'])->toBeInstanceOf(\Illuminate\Support\Collection::class)
                ->and($results['quran_teachers'])->toBeInstanceOf(\Illuminate\Support\Collection::class)
                ->and($results['academic_teachers'])->toBeInstanceOf(\Illuminate\Support\Collection::class)
                ->and($results['quran_circles'])->toBeInstanceOf(\Illuminate\Support\Collection::class);
        });

        it('returns empty collections when no matches found', function () {
            $query = 'nonexistentquery123456';

            $results = $this->service->search($this->student, $query);

            expect($results['interactive_courses'])->toBeEmpty()
                ->and($results['recorded_courses'])->toBeEmpty()
                ->and($results['quran_teachers'])->toBeEmpty()
                ->and($results['academic_teachers'])->toBeEmpty()
                ->and($results['quran_circles'])->toBeEmpty();
        });

        it('limits results based on limit parameter', function () {
            // Create 15 interactive courses
            InteractiveCourse::factory()
                ->count(15)
                ->for($this->academy)
                ->create([
                    'title' => 'Test Course',
                    'is_published' => true,
                ]);

            $results = $this->service->search($this->student, 'Test', 5);

            expect($results['interactive_courses'])->toHaveCount(5);
        });

        it('searches across multiple entity types simultaneously', function () {
            // Create one of each entity type with matching query
            InteractiveCourse::factory()
                ->for($this->academy)
                ->create([
                    'title' => 'Math Course',
                    'is_published' => true,
                ]);

            RecordedCourse::factory()
                ->for($this->academy)
                ->create([
                    'title' => 'Math Tutorial',
                    'is_published' => true,
                ]);

            QuranTeacherProfile::factory()
                ->for($this->academy)
                ->create([
                    'first_name' => 'Math',
                    'is_active' => true,
                    'approval_status' => 'approved',
                ]);

            AcademicTeacherProfile::factory()
                ->for($this->academy)
                ->create([
                    'first_name' => 'Math',
                    'is_active' => true,
                    'approval_status' => 'approved',
                ]);

            QuranCircle::factory()
                ->for($this->academy)
                ->create([
                    'name_ar' => 'Math Circle',
                    'status' => true,
                    'enrollment_status' => 'open',
                ]);

            $results = $this->service->search($this->student, 'Math');

            expect($results['interactive_courses'])->toHaveCount(1)
                ->and($results['recorded_courses'])->toHaveCount(1)
                ->and($results['quran_teachers'])->toHaveCount(1)
                ->and($results['academic_teachers'])->toHaveCount(1)
                ->and($results['quran_circles'])->toHaveCount(1);
        });
    });

    describe('searchInteractiveCourses()', function () {
        it('returns interactive courses matching title', function () {
            InteractiveCourse::factory()
                ->for($this->academy)
                ->create([
                    'title' => 'Python Programming',
                    'is_published' => true,
                ]);

            $results = $this->service->searchInteractiveCourses($this->academy, 'Python');

            expect($results)->toHaveCount(1)
                ->and($results->first()->title)->toContain('Python');
        });

        it('returns interactive courses matching description', function () {
            InteractiveCourse::factory()
                ->for($this->academy)
                ->create([
                    'title' => 'Programming Course',
                    'description' => 'Learn advanced algorithms',
                    'is_published' => true,
                ]);

            $results = $this->service->searchInteractiveCourses($this->academy, 'algorithms');

            expect($results)->toHaveCount(1)
                ->and($results->first()->description)->toContain('algorithms');
        });

        it('only returns published courses', function () {
            InteractiveCourse::factory()
                ->for($this->academy)
                ->create([
                    'title' => 'Published Course',
                    'is_published' => true,
                ]);

            InteractiveCourse::factory()
                ->for($this->academy)
                ->create([
                    'title' => 'Draft Course',
                    'is_published' => false,
                ]);

            $results = $this->service->searchInteractiveCourses($this->academy, 'Course');

            expect($results)->toHaveCount(1)
                ->and($results->first()->title)->toBe('Published Course');
        });

        it('only returns courses from specified academy', function () {
            $otherAcademy = Academy::factory()->create();

            InteractiveCourse::factory()
                ->for($this->academy)
                ->create([
                    'title' => 'Academy 1 Course',
                    'is_published' => true,
                ]);

            InteractiveCourse::factory()
                ->for($otherAcademy)
                ->create([
                    'title' => 'Academy 2 Course',
                    'is_published' => true,
                ]);

            $results = $this->service->searchInteractiveCourses($this->academy, 'Course');

            expect($results)->toHaveCount(1)
                ->and($results->first()->title)->toBe('Academy 1 Course');
        });

        it('eager loads assigned teacher relationship', function () {
            InteractiveCourse::factory()
                ->for($this->academy)
                ->create([
                    'title' => 'Course with Teacher',
                    'is_published' => true,
                ]);

            $results = $this->service->searchInteractiveCourses($this->academy, 'Course');

            expect($results->first()->relationLoaded('assignedTeacher'))->toBeTrue();
        });

        it('respects limit parameter', function () {
            InteractiveCourse::factory()
                ->count(10)
                ->for($this->academy)
                ->create([
                    'title' => 'Test Course',
                    'is_published' => true,
                ]);

            $results = $this->service->searchInteractiveCourses($this->academy, 'Test', 3);

            expect($results)->toHaveCount(3);
        });

        it('performs case-insensitive search', function () {
            InteractiveCourse::factory()
                ->for($this->academy)
                ->create([
                    'title' => 'Python Programming',
                    'is_published' => true,
                ]);

            $results = $this->service->searchInteractiveCourses($this->academy, 'python');

            expect($results)->toHaveCount(1);
        });
    });

    describe('searchRecordedCourses()', function () {
        it('returns recorded courses matching title', function () {
            RecordedCourse::factory()
                ->for($this->academy)
                ->create([
                    'title' => 'JavaScript Basics',
                    'is_published' => true,
                ]);

            $results = $this->service->searchRecordedCourses($this->academy, 'JavaScript');

            expect($results)->toHaveCount(1)
                ->and($results->first()->title)->toContain('JavaScript');
        });

        it('returns recorded courses matching description', function () {
            RecordedCourse::factory()
                ->for($this->academy)
                ->create([
                    'title' => 'Web Development',
                    'description' => 'Master React framework',
                    'is_published' => true,
                ]);

            $results = $this->service->searchRecordedCourses($this->academy, 'React');

            expect($results)->toHaveCount(1)
                ->and($results->first()->description)->toContain('React');
        });

        it('only returns published courses', function () {
            RecordedCourse::factory()
                ->for($this->academy)
                ->create([
                    'title' => 'Published Course',
                    'is_published' => true,
                ]);

            RecordedCourse::factory()
                ->for($this->academy)
                ->create([
                    'title' => 'Unpublished Course',
                    'is_published' => false,
                ]);

            $results = $this->service->searchRecordedCourses($this->academy, 'Course');

            expect($results)->toHaveCount(1)
                ->and($results->first()->title)->toBe('Published Course');
        });

        it('only returns courses from specified academy', function () {
            $otherAcademy = Academy::factory()->create();

            RecordedCourse::factory()
                ->for($this->academy)
                ->create([
                    'title' => 'My Academy Course',
                    'is_published' => true,
                ]);

            RecordedCourse::factory()
                ->for($otherAcademy)
                ->create([
                    'title' => 'Other Academy Course',
                    'is_published' => true,
                ]);

            $results = $this->service->searchRecordedCourses($this->academy, 'Course');

            expect($results)->toHaveCount(1)
                ->and($results->first()->title)->toBe('My Academy Course');
        });

        it('respects limit parameter', function () {
            RecordedCourse::factory()
                ->count(8)
                ->for($this->academy)
                ->create([
                    'title' => 'Test Course',
                    'is_published' => true,
                ]);

            $results = $this->service->searchRecordedCourses($this->academy, 'Test', 4);

            expect($results)->toHaveCount(4);
        });

        it('performs case-insensitive search', function () {
            RecordedCourse::factory()
                ->for($this->academy)
                ->create([
                    'title' => 'Advanced CSS',
                    'is_published' => true,
                ]);

            $results = $this->service->searchRecordedCourses($this->academy, 'css');

            expect($results)->toHaveCount(1);
        });
    });

    describe('searchQuranTeachers()', function () {
        it('returns teachers matching first name', function () {
            QuranTeacherProfile::factory()
                ->for($this->academy)
                ->create([
                    'first_name' => 'Ahmed',
                    'is_active' => true,
                    'approval_status' => 'approved',
                ]);

            $results = $this->service->searchQuranTeachers($this->academy, 'Ahmed');

            expect($results)->toHaveCount(1)
                ->and($results->first()->first_name)->toBe('Ahmed');
        });

        it('returns teachers matching last name', function () {
            QuranTeacherProfile::factory()
                ->for($this->academy)
                ->create([
                    'first_name' => 'Mohamed',
                    'last_name' => 'Hassan',
                    'is_active' => true,
                    'approval_status' => 'approved',
                ]);

            $results = $this->service->searchQuranTeachers($this->academy, 'Hassan');

            expect($results)->toHaveCount(1)
                ->and($results->first()->last_name)->toBe('Hassan');
        });

        it('returns teachers matching bio in Arabic', function () {
            QuranTeacherProfile::factory()
                ->for($this->academy)
                ->create([
                    'first_name' => 'Ali',
                    'bio_arabic' => 'معلم قرآن محترف',
                    'is_active' => true,
                    'approval_status' => 'approved',
                ]);

            $results = $this->service->searchQuranTeachers($this->academy, 'محترف');

            expect($results)->toHaveCount(1)
                ->and($results->first()->bio_arabic)->toContain('محترف');
        });

        it('returns teachers matching bio in English', function () {
            QuranTeacherProfile::factory()
                ->for($this->academy)
                ->create([
                    'first_name' => 'Omar',
                    'bio_english' => 'Professional Quran teacher',
                    'is_active' => true,
                    'approval_status' => 'approved',
                ]);

            $results = $this->service->searchQuranTeachers($this->academy, 'Professional');

            expect($results)->toHaveCount(1)
                ->and($results->first()->bio_english)->toContain('Professional');
        });

        it('returns teachers matching user name', function () {
            $user = User::factory()
                ->quranTeacher()
                ->forAcademy($this->academy)
                ->create(['name' => 'Abdullah Ibrahim']);

            QuranTeacherProfile::factory()
                ->for($this->academy)
                ->create([
                    'user_id' => $user->id,
                    'first_name' => 'Abdullah',
                    'is_active' => true,
                    'approval_status' => 'approved',
                ]);

            $results = $this->service->searchQuranTeachers($this->academy, 'Ibrahim');

            expect($results)->toHaveCount(1);
        });

        it('only returns active teachers', function () {
            QuranTeacherProfile::factory()
                ->for($this->academy)
                ->create([
                    'first_name' => 'Active Teacher',
                    'is_active' => true,
                    'approval_status' => 'approved',
                ]);

            QuranTeacherProfile::factory()
                ->for($this->academy)
                ->create([
                    'first_name' => 'Inactive Teacher',
                    'is_active' => false,
                    'approval_status' => 'approved',
                ]);

            $results = $this->service->searchQuranTeachers($this->academy, 'Teacher');

            expect($results)->toHaveCount(1)
                ->and($results->first()->first_name)->toBe('Active Teacher');
        });

        it('only returns approved teachers', function () {
            QuranTeacherProfile::factory()
                ->for($this->academy)
                ->create([
                    'first_name' => 'Approved Teacher',
                    'is_active' => true,
                    'approval_status' => 'approved',
                ]);

            QuranTeacherProfile::factory()
                ->for($this->academy)
                ->create([
                    'first_name' => 'Pending Teacher',
                    'is_active' => true,
                    'approval_status' => 'pending',
                ]);

            $results = $this->service->searchQuranTeachers($this->academy, 'Teacher');

            expect($results)->toHaveCount(1)
                ->and($results->first()->first_name)->toBe('Approved Teacher');
        });

        it('only returns teachers from specified academy', function () {
            $otherAcademy = Academy::factory()->create();

            QuranTeacherProfile::factory()
                ->for($this->academy)
                ->create([
                    'first_name' => 'Teacher One',
                    'is_active' => true,
                    'approval_status' => 'approved',
                ]);

            QuranTeacherProfile::factory()
                ->for($otherAcademy)
                ->create([
                    'first_name' => 'Teacher Two',
                    'is_active' => true,
                    'approval_status' => 'approved',
                ]);

            $results = $this->service->searchQuranTeachers($this->academy, 'Teacher');

            expect($results)->toHaveCount(1)
                ->and($results->first()->first_name)->toBe('Teacher One');
        });

        it('eager loads user relationship', function () {
            QuranTeacherProfile::factory()
                ->for($this->academy)
                ->create([
                    'first_name' => 'Teacher',
                    'is_active' => true,
                    'approval_status' => 'approved',
                ]);

            $results = $this->service->searchQuranTeachers($this->academy, 'Teacher');

            expect($results->first()->relationLoaded('user'))->toBeTrue();
        });

        it('respects limit parameter', function () {
            QuranTeacherProfile::factory()
                ->count(7)
                ->for($this->academy)
                ->create([
                    'first_name' => 'Test Teacher',
                    'is_active' => true,
                    'approval_status' => 'approved',
                ]);

            $results = $this->service->searchQuranTeachers($this->academy, 'Test', 2);

            expect($results)->toHaveCount(2);
        });
    });

    describe('searchAcademicTeachers()', function () {
        it('returns teachers matching first name', function () {
            AcademicTeacherProfile::factory()
                ->for($this->academy)
                ->create([
                    'first_name' => 'Sarah',
                    'is_active' => true,
                    'approval_status' => 'approved',
                ]);

            $results = $this->service->searchAcademicTeachers($this->academy, 'Sarah');

            expect($results)->toHaveCount(1)
                ->and($results->first()->first_name)->toBe('Sarah');
        });

        it('returns teachers matching last name', function () {
            AcademicTeacherProfile::factory()
                ->for($this->academy)
                ->create([
                    'first_name' => 'Fatima',
                    'last_name' => 'Ali',
                    'is_active' => true,
                    'approval_status' => 'approved',
                ]);

            $results = $this->service->searchAcademicTeachers($this->academy, 'Ali');

            expect($results)->toHaveCount(1)
                ->and($results->first()->last_name)->toBe('Ali');
        });

        it('returns teachers matching bio in Arabic', function () {
            AcademicTeacherProfile::factory()
                ->for($this->academy)
                ->create([
                    'first_name' => 'Noor',
                    'bio_arabic' => 'معلمة رياضيات متميزة',
                    'is_active' => true,
                    'approval_status' => 'approved',
                ]);

            $results = $this->service->searchAcademicTeachers($this->academy, 'رياضيات');

            expect($results)->toHaveCount(1)
                ->and($results->first()->bio_arabic)->toContain('رياضيات');
        });

        it('returns teachers matching bio in English', function () {
            AcademicTeacherProfile::factory()
                ->for($this->academy)
                ->create([
                    'first_name' => 'Layla',
                    'bio_english' => 'Expert mathematics teacher',
                    'is_active' => true,
                    'approval_status' => 'approved',
                ]);

            $results = $this->service->searchAcademicTeachers($this->academy, 'mathematics');

            expect($results)->toHaveCount(1)
                ->and($results->first()->bio_english)->toContain('mathematics');
        });

        it('returns teachers matching user name', function () {
            $user = User::factory()
                ->academicTeacher()
                ->forAcademy($this->academy)
                ->create(['name' => 'Khaled Mohamed']);

            AcademicTeacherProfile::factory()
                ->for($this->academy)
                ->create([
                    'user_id' => $user->id,
                    'first_name' => 'Khaled',
                    'is_active' => true,
                    'approval_status' => 'approved',
                ]);

            $results = $this->service->searchAcademicTeachers($this->academy, 'Mohamed');

            expect($results)->toHaveCount(1);
        });

        it('only returns active teachers', function () {
            AcademicTeacherProfile::factory()
                ->for($this->academy)
                ->create([
                    'first_name' => 'Active Teacher',
                    'is_active' => true,
                    'approval_status' => 'approved',
                ]);

            AcademicTeacherProfile::factory()
                ->for($this->academy)
                ->create([
                    'first_name' => 'Inactive Teacher',
                    'is_active' => false,
                    'approval_status' => 'approved',
                ]);

            $results = $this->service->searchAcademicTeachers($this->academy, 'Teacher');

            expect($results)->toHaveCount(1)
                ->and($results->first()->first_name)->toBe('Active Teacher');
        });

        it('only returns approved teachers', function () {
            AcademicTeacherProfile::factory()
                ->for($this->academy)
                ->create([
                    'first_name' => 'Approved Teacher',
                    'is_active' => true,
                    'approval_status' => 'approved',
                ]);

            AcademicTeacherProfile::factory()
                ->for($this->academy)
                ->create([
                    'first_name' => 'Rejected Teacher',
                    'is_active' => true,
                    'approval_status' => 'rejected',
                ]);

            $results = $this->service->searchAcademicTeachers($this->academy, 'Teacher');

            expect($results)->toHaveCount(1)
                ->and($results->first()->first_name)->toBe('Approved Teacher');
        });

        it('only returns teachers from specified academy', function () {
            $otherAcademy = Academy::factory()->create();

            AcademicTeacherProfile::factory()
                ->for($this->academy)
                ->create([
                    'first_name' => 'Teacher A',
                    'is_active' => true,
                    'approval_status' => 'approved',
                ]);

            AcademicTeacherProfile::factory()
                ->for($otherAcademy)
                ->create([
                    'first_name' => 'Teacher B',
                    'is_active' => true,
                    'approval_status' => 'approved',
                ]);

            $results = $this->service->searchAcademicTeachers($this->academy, 'Teacher');

            expect($results)->toHaveCount(1)
                ->and($results->first()->first_name)->toBe('Teacher A');
        });

        it('eager loads user relationship', function () {
            AcademicTeacherProfile::factory()
                ->for($this->academy)
                ->create([
                    'first_name' => 'Teacher',
                    'is_active' => true,
                    'approval_status' => 'approved',
                ]);

            $results = $this->service->searchAcademicTeachers($this->academy, 'Teacher');

            expect($results->first()->relationLoaded('user'))->toBeTrue();
        });

        it('respects limit parameter', function () {
            AcademicTeacherProfile::factory()
                ->count(9)
                ->for($this->academy)
                ->create([
                    'first_name' => 'Test Teacher',
                    'is_active' => true,
                    'approval_status' => 'approved',
                ]);

            $results = $this->service->searchAcademicTeachers($this->academy, 'Test', 5);

            expect($results)->toHaveCount(5);
        });
    });

    describe('searchQuranCircles()', function () {
        it('returns circles matching Arabic name', function () {
            QuranCircle::factory()
                ->for($this->academy)
                ->create([
                    'name_ar' => 'حلقة الفجر',
                    'status' => true,
                    'enrollment_status' => 'open',
                ]);

            $results = $this->service->searchQuranCircles($this->academy, 'الفجر');

            expect($results)->toHaveCount(1)
                ->and($results->first()->name_ar)->toContain('الفجر');
        });

        it('returns circles matching English name', function () {
            QuranCircle::factory()
                ->for($this->academy)
                ->create([
                    'name_en' => 'Morning Circle',
                    'status' => true,
                    'enrollment_status' => 'open',
                ]);

            $results = $this->service->searchQuranCircles($this->academy, 'Morning');

            expect($results)->toHaveCount(1)
                ->and($results->first()->name_en)->toContain('Morning');
        });

        it('returns circles matching Arabic description', function () {
            QuranCircle::factory()
                ->for($this->academy)
                ->create([
                    'name_ar' => 'حلقة التجويد',
                    'description_ar' => 'لتعليم أحكام التجويد',
                    'status' => true,
                    'enrollment_status' => 'open',
                ]);

            $results = $this->service->searchQuranCircles($this->academy, 'التجويد');

            expect($results)->toHaveCount(1);
        });

        it('returns circles matching English description', function () {
            QuranCircle::factory()
                ->for($this->academy)
                ->create([
                    'name_en' => 'Tajweed Circle',
                    'description_en' => 'Learn Tajweed rules',
                    'status' => true,
                    'enrollment_status' => 'open',
                ]);

            $results = $this->service->searchQuranCircles($this->academy, 'rules');

            expect($results)->toHaveCount(1);
        });

        it('returns circles matching circle code', function () {
            QuranCircle::factory()
                ->for($this->academy)
                ->create([
                    'name_ar' => 'حلقة الحفظ',
                    'circle_code' => 'QC-001',
                    'status' => true,
                    'enrollment_status' => 'open',
                ]);

            $results = $this->service->searchQuranCircles($this->academy, 'QC-001');

            expect($results)->toHaveCount(1)
                ->and($results->first()->circle_code)->toBe('QC-001');
        });

        it('only returns active circles', function () {
            QuranCircle::factory()
                ->for($this->academy)
                ->create([
                    'name_ar' => 'حلقة نشطة',
                    'status' => true,
                    'enrollment_status' => 'open',
                ]);

            QuranCircle::factory()
                ->for($this->academy)
                ->create([
                    'name_ar' => 'حلقة غير نشطة',
                    'status' => false,
                    'enrollment_status' => 'open',
                ]);

            $results = $this->service->searchQuranCircles($this->academy, 'حلقة');

            expect($results)->toHaveCount(1)
                ->and($results->first()->name_ar)->toBe('حلقة نشطة');
        });

        it('only returns circles with open enrollment', function () {
            QuranCircle::factory()
                ->for($this->academy)
                ->create([
                    'name_ar' => 'حلقة مفتوحة',
                    'status' => true,
                    'enrollment_status' => 'open',
                ]);

            QuranCircle::factory()
                ->for($this->academy)
                ->create([
                    'name_ar' => 'حلقة مغلقة',
                    'status' => true,
                    'enrollment_status' => 'closed',
                ]);

            $results = $this->service->searchQuranCircles($this->academy, 'حلقة');

            expect($results)->toHaveCount(1)
                ->and($results->first()->name_ar)->toBe('حلقة مفتوحة');
        });

        it('only returns circles from specified academy', function () {
            $otherAcademy = Academy::factory()->create();

            QuranCircle::factory()
                ->for($this->academy)
                ->create([
                    'name_ar' => 'حلقة الأكاديمية الأولى',
                    'status' => true,
                    'enrollment_status' => 'open',
                ]);

            QuranCircle::factory()
                ->for($otherAcademy)
                ->create([
                    'name_ar' => 'حلقة الأكاديمية الثانية',
                    'status' => true,
                    'enrollment_status' => 'open',
                ]);

            $results = $this->service->searchQuranCircles($this->academy, 'حلقة');

            expect($results)->toHaveCount(1)
                ->and($results->first()->name_ar)->toBe('حلقة الأكاديمية الأولى');
        });

        it('eager loads teacher relationship', function () {
            QuranCircle::factory()
                ->for($this->academy)
                ->create([
                    'name_ar' => 'حلقة',
                    'status' => true,
                    'enrollment_status' => 'open',
                ]);

            $results = $this->service->searchQuranCircles($this->academy, 'حلقة');

            expect($results->first()->relationLoaded('teacher'))->toBeTrue();
        });

        it('respects limit parameter', function () {
            QuranCircle::factory()
                ->count(12)
                ->for($this->academy)
                ->create([
                    'name_ar' => 'حلقة تجريبية',
                    'status' => true,
                    'enrollment_status' => 'open',
                ]);

            $results = $this->service->searchQuranCircles($this->academy, 'تجريبية', 6);

            expect($results)->toHaveCount(6);
        });
    });

    describe('getTotalCount()', function () {
        it('returns zero for empty results', function () {
            $results = [
                'interactive_courses' => collect(),
                'recorded_courses' => collect(),
                'quran_teachers' => collect(),
                'academic_teachers' => collect(),
                'quran_circles' => collect(),
            ];

            $count = $this->service->getTotalCount($results);

            expect($count)->toBe(0);
        });

        it('returns correct total count across all entity types', function () {
            $results = [
                'interactive_courses' => collect([1, 2, 3]),
                'recorded_courses' => collect([1, 2]),
                'quran_teachers' => collect([1]),
                'academic_teachers' => collect([1, 2, 3, 4]),
                'quran_circles' => collect([1, 2, 3, 4, 5]),
            ];

            $count = $this->service->getTotalCount($results);

            expect($count)->toBe(15);
        });

        it('handles partial results', function () {
            $results = [
                'interactive_courses' => collect([1, 2]),
                'recorded_courses' => collect(),
                'quran_teachers' => collect([1]),
                'academic_teachers' => collect(),
                'quran_circles' => collect([1, 2, 3]),
            ];

            $count = $this->service->getTotalCount($results);

            expect($count)->toBe(6);
        });

        it('works with actual search results', function () {
            // Create entities
            InteractiveCourse::factory()
                ->count(2)
                ->for($this->academy)
                ->create([
                    'title' => 'Search Term',
                    'is_published' => true,
                ]);

            QuranTeacherProfile::factory()
                ->count(3)
                ->for($this->academy)
                ->create([
                    'first_name' => 'Search Term',
                    'is_active' => true,
                    'approval_status' => 'approved',
                ]);

            $results = $this->service->search($this->student, 'Search Term');
            $count = $this->service->getTotalCount($results);

            expect($count)->toBe(5);
        });
    });
});
