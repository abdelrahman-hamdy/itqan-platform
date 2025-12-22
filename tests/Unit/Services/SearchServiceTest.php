<?php

use App\Models\Academy;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Models\GradeLevel;
use App\Models\InteractiveCourse;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranTeacherProfile;
use App\Models\RecordedCourse;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\User;
use App\Services\SearchService;

describe('SearchService', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
        $this->service = new SearchService();
        $this->student = StudentProfile::factory()->create([
            'academy_id' => $this->academy->id,
        ]);
    });

    describe('searchAll()', function () {
        it('returns empty collections when query is empty', function () {
            $results = $this->service->searchAll('');

            expect($results)->toBeInstanceOf(\Illuminate\Support\Collection::class)
                ->and($results->get('quran_circles'))->toBeEmpty()
                ->and($results->get('individual_circles'))->toBeEmpty()
                ->and($results->get('academic_sessions'))->toBeEmpty()
                ->and($results->get('interactive_courses'))->toBeEmpty()
                ->and($results->get('recorded_courses'))->toBeEmpty()
                ->and($results->get('quran_teachers'))->toBeEmpty()
                ->and($results->get('academic_teachers'))->toBeEmpty();
        });

        it('returns empty collections when query is whitespace only', function () {
            $results = $this->service->searchAll('   ');

            expect($results)->toBeInstanceOf(\Illuminate\Support\Collection::class)
                ->and($results->get('quran_circles'))->toBeEmpty();
        });

        it('searches across all resources with valid query', function () {
            QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'name_ar' => 'حلقة القرآن',
                'name_en' => 'Quran Circle',
            ]);

            $results = $this->service->searchAll('قرآن', $this->student);

            expect($results)->toBeInstanceOf(\Illuminate\Support\Collection::class)
                ->and($results)->toHaveKeys([
                    'quran_circles',
                    'individual_circles',
                    'academic_sessions',
                    'interactive_courses',
                    'recorded_courses',
                    'quran_teachers',
                    'academic_teachers',
                ]);
        });

        it('calls all search methods when query is provided', function () {
            $results = $this->service->searchAll('test', $this->student);

            expect($results->keys()->toArray())->toEqual([
                'quran_circles',
                'individual_circles',
                'academic_sessions',
                'interactive_courses',
                'recorded_courses',
                'quran_teachers',
                'academic_teachers',
            ]);
        });
    });

    describe('searchQuranCircles()', function () {
        it('finds circles by Arabic name', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'name_ar' => 'حلقة التحفيظ',
                'name_en' => 'Memorization Circle',
            ]);

            $results = $this->service->searchAll('تحفيظ', $this->student);

            expect($results->get('quran_circles'))->toHaveCount(1)
                ->and($results->get('quran_circles')->first()['id'])->toBe($circle->id)
                ->and($results->get('quran_circles')->first()['type'])->toBe('quran_circle');
        });

        it('finds circles by English name', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'name_ar' => 'حلقة القرآن',
                'name_en' => 'Advanced Circle',
            ]);

            $results = $this->service->searchAll('Advanced', $this->student);

            expect($results->get('quran_circles'))->toHaveCount(1)
                ->and($results->get('quran_circles')->first()['id'])->toBe($circle->id);
        });

        it('finds circles by description', function () {
            QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'description_ar' => 'حلقة متخصصة في التجويد',
                'description_en' => 'Tajweed specialist',
            ]);

            $results = $this->service->searchAll('تجويد', $this->student);

            expect($results->get('quran_circles'))->toHaveCount(1);
        });

        it('finds circles by teacher name', function () {
            $teacher = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'first_name' => 'محمد',
                'last_name' => 'أحمد',
            ]);

            QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'teacher_id' => $teacher->id,
            ]);

            $results = $this->service->searchAll('محمد', $this->student);

            expect($results->get('quran_circles'))->toHaveCount(1);
        });

        it('filters by level when provided', function () {
            QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'name_ar' => 'حلقة مبتدئين',
                'circle_level' => 'beginner',
            ]);

            QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'name_ar' => 'حلقة متقدمين',
                'circle_level' => 'advanced',
            ]);

            $results = $this->service->searchAll('حلقة', $this->student, ['level' => 'beginner']);

            expect($results->get('quran_circles'))->toHaveCount(1)
                ->and($results->get('quran_circles')->first()['meta']['schedule'])->toBeString();
        });

        it('filters by enrollment status when provided', function () {
            QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'name_ar' => 'حلقة مفتوحة',
                'enrollment_status' => 'open',
            ]);

            QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'name_ar' => 'حلقة مغلقة',
                'enrollment_status' => 'closed',
            ]);

            $results = $this->service->searchAll('حلقة', $this->student, ['enrollment_status' => 'open']);

            expect($results->get('quran_circles'))->toHaveCount(1);
        });

        it('limits results to 10', function () {
            QuranCircle::factory()->count(15)->create([
                'academy_id' => $this->academy->id,
                'name_ar' => 'حلقة القرآن',
            ]);

            $results = $this->service->searchAll('حلقة', $this->student);

            expect($results->get('quran_circles'))->toHaveCount(10);
        });

        it('returns correct result structure', function () {
            $teacher = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'first_name' => 'أحمد',
                'last_name' => 'محمد',
            ]);

            QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'name_ar' => 'حلقة القرآن',
                'teacher_id' => $teacher->id,
                'max_students' => 10,
                'monthly_fee' => 200,
            ]);

            $results = $this->service->searchAll('حلقة', $this->student);
            $result = $results->get('quran_circles')->first();

            expect($result)->toHaveKeys([
                'type',
                'id',
                'title',
                'description',
                'icon',
                'icon_bg',
                'icon_color',
                'teacher_name',
                'meta',
                'status',
                'is_enrolled',
                'route',
            ])
                ->and($result['type'])->toBe('quran_circle')
                ->and($result['icon'])->toBe('ri-group-line')
                ->and($result['meta'])->toHaveKeys([
                    'students_count',
                    'max_students',
                    'schedule',
                    'monthly_fee',
                ]);
        });
    });

    describe('searchIndividualCircles()', function () {
        it('returns empty collection when student is not provided', function () {
            $results = $this->service->searchAll('test', null);

            expect($results->get('individual_circles'))->toBeEmpty();
        });

        it('finds individual circles for student by name', function () {
            $circle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->user_id,
                'name' => 'حلقة فردية',
            ]);

            $results = $this->service->searchAll('فردية', $this->student);

            expect($results->get('individual_circles'))->toHaveCount(1)
                ->and($results->get('individual_circles')->first()['id'])->toBe($circle->id)
                ->and($results->get('individual_circles')->first()['type'])->toBe('individual_circle');
        });

        it('finds individual circles by teacher name', function () {
            $teacher = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'first_name' => 'خالد',
                'last_name' => 'سعيد',
            ]);

            QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->user_id,
                'teacher_id' => $teacher->id,
            ]);

            $results = $this->service->searchAll('خالد', $this->student);

            expect($results->get('individual_circles'))->toHaveCount(1);
        });

        it('only returns circles for the specified student', function () {
            $otherStudent = StudentProfile::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->user_id,
                'name' => 'حلقة الطالب الأول',
            ]);

            QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $otherStudent->user_id,
                'name' => 'حلقة الطالب الثاني',
            ]);

            $results = $this->service->searchAll('حلقة', $this->student);

            expect($results->get('individual_circles'))->toHaveCount(1);
        });

        it('limits results to 10', function () {
            QuranIndividualCircle::factory()->count(15)->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->user_id,
                'name' => 'حلقة فردية',
            ]);

            $results = $this->service->searchAll('حلقة', $this->student);

            expect($results->get('individual_circles'))->toHaveCount(10);
        });

        it('returns correct result structure', function () {
            QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->user_id,
                'name' => 'حلقة فردية',
            ]);

            $results = $this->service->searchAll('حلقة', $this->student);
            $result = $results->get('individual_circles')->first();

            expect($result)->toHaveKeys([
                'type',
                'id',
                'title',
                'description',
                'icon',
                'icon_bg',
                'icon_color',
                'teacher_name',
                'meta',
                'status',
                'is_enrolled',
                'route',
            ])
                ->and($result['type'])->toBe('individual_circle')
                ->and($result['icon'])->toBe('ri-user-line')
                ->and($result['is_enrolled'])->toBeTrue();
        });
    });

    describe('searchAcademicSessions()', function () {
        it('returns empty collection when student is not provided', function () {
            $results = $this->service->searchAll('test', null);

            expect($results->get('academic_sessions'))->toBeEmpty();
        });

        it('finds academic subscriptions by subject name', function () {
            $subject = Subject::factory()->create([
                'academy_id' => $this->academy->id,
                'name' => 'الرياضيات',
            ]);

            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->user_id,
                'subject_id' => $subject->id,
                'subject_name' => 'الرياضيات',
                'status' => 'active',
            ]);

            $results = $this->service->searchAll('رياضيات', $this->student);

            expect($results->get('academic_sessions'))->toHaveCount(1)
                ->and($results->get('academic_sessions')->first()['id'])->toBe($subscription->id)
                ->and($results->get('academic_sessions')->first()['type'])->toBe('academic_session');
        });

        it('finds subscriptions by grade level', function () {
            AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->user_id,
                'grade_level_name' => 'الصف الأول',
                'status' => 'active',
            ]);

            $results = $this->service->searchAll('الصف الأول', $this->student);

            expect($results->get('academic_sessions'))->toHaveCount(1);
        });

        it('finds subscriptions by teacher name', function () {
            $teacher = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'first_name' => 'فاطمة',
                'last_name' => 'علي',
            ]);

            AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->user_id,
                'teacher_id' => $teacher->id,
                'status' => 'active',
            ]);

            $results = $this->service->searchAll('فاطمة', $this->student);

            expect($results->get('academic_sessions'))->toHaveCount(1);
        });

        it('only returns active subscriptions', function () {
            AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->user_id,
                'subject_name' => 'الرياضيات',
                'status' => 'active',
            ]);

            AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->user_id,
                'subject_name' => 'الرياضيات',
                'status' => 'expired',
            ]);

            $results = $this->service->searchAll('رياضيات', $this->student);

            expect($results->get('academic_sessions'))->toHaveCount(1);
        });

        it('filters by subject_id when provided', function () {
            $subject1 = Subject::factory()->create(['academy_id' => $this->academy->id]);
            $subject2 = Subject::factory()->create(['academy_id' => $this->academy->id]);

            AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->user_id,
                'subject_id' => $subject1->id,
                'subject_name' => 'الرياضيات',
                'status' => 'active',
            ]);

            AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->user_id,
                'subject_id' => $subject2->id,
                'subject_name' => 'الرياضيات',
                'status' => 'active',
            ]);

            $results = $this->service->searchAll('رياضيات', $this->student, ['subject_id' => $subject1->id]);

            expect($results->get('academic_sessions'))->toHaveCount(1);
        });

        it('limits results to 10', function () {
            AcademicSubscription::factory()->count(15)->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->user_id,
                'subject_name' => 'الرياضيات',
                'status' => 'active',
            ]);

            $results = $this->service->searchAll('رياضيات', $this->student);

            expect($results->get('academic_sessions'))->toHaveCount(10);
        });

        it('returns correct result structure', function () {
            AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->user_id,
                'subject_name' => 'الرياضيات',
                'grade_level_name' => 'الصف الأول',
                'sessions_per_month' => 8,
                'final_monthly_amount' => 500,
                'status' => 'active',
            ]);

            $results = $this->service->searchAll('رياضيات', $this->student);
            $result = $results->get('academic_sessions')->first();

            expect($result)->toHaveKeys([
                'type',
                'id',
                'title',
                'description',
                'icon',
                'icon_bg',
                'icon_color',
                'teacher_name',
                'meta',
                'status',
                'is_enrolled',
                'route',
            ])
                ->and($result['type'])->toBe('academic_session')
                ->and($result['icon'])->toBe('ri-book-open-line')
                ->and($result['is_enrolled'])->toBeTrue()
                ->and($result['meta'])->toHaveKeys([
                    'subject',
                    'grade_level',
                    'sessions_per_month',
                    'student_price',
                ]);
        });
    });

    describe('searchInteractiveCourses()', function () {
        it('finds published courses by title', function () {
            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'title' => 'دورة الفيزياء التفاعلية',
                'is_published' => true,
            ]);

            $results = $this->service->searchAll('فيزياء', $this->student);

            expect($results->get('interactive_courses'))->toHaveCount(1)
                ->and($results->get('interactive_courses')->first()['id'])->toBe($course->id)
                ->and($results->get('interactive_courses')->first()['type'])->toBe('interactive_course');
        });

        it('finds courses by description', function () {
            InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'title' => 'دورة الكيمياء',
                'description' => 'دورة متقدمة في الكيمياء العضوية',
                'is_published' => true,
            ]);

            $results = $this->service->searchAll('عضوية', $this->student);

            expect($results->get('interactive_courses'))->toHaveCount(1);
        });

        it('finds courses by teacher name', function () {
            $teacher = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'first_name' => 'سارة',
                'last_name' => 'محمود',
            ]);

            InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'teacher_id' => $teacher->id,
                'is_published' => true,
            ]);

            $results = $this->service->searchAll('سارة', $this->student);

            expect($results->get('interactive_courses'))->toHaveCount(1);
        });

        it('finds courses by subject', function () {
            $subject = Subject::factory()->create([
                'academy_id' => $this->academy->id,
                'name' => 'الأحياء',
            ]);

            InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'subject_id' => $subject->id,
                'is_published' => true,
            ]);

            $results = $this->service->searchAll('أحياء', $this->student);

            expect($results->get('interactive_courses'))->toHaveCount(1);
        });

        it('only returns published courses', function () {
            InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'title' => 'دورة الفيزياء',
                'is_published' => true,
            ]);

            InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'title' => 'دورة الفيزياء المتقدمة',
                'is_published' => false,
            ]);

            $results = $this->service->searchAll('فيزياء', $this->student);

            expect($results->get('interactive_courses'))->toHaveCount(1);
        });

        it('filters by subject_id when provided', function () {
            $subject1 = Subject::factory()->create(['academy_id' => $this->academy->id]);
            $subject2 = Subject::factory()->create(['academy_id' => $this->academy->id]);

            InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'title' => 'دورة 1',
                'subject_id' => $subject1->id,
                'is_published' => true,
            ]);

            InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'title' => 'دورة 2',
                'subject_id' => $subject2->id,
                'is_published' => true,
            ]);

            $results = $this->service->searchAll('دورة', $this->student, ['subject_id' => $subject1->id]);

            expect($results->get('interactive_courses'))->toHaveCount(1);
        });

        it('filters by grade_level_id when provided', function () {
            $grade1 = GradeLevel::factory()->create(['academy_id' => $this->academy->id]);
            $grade2 = GradeLevel::factory()->create(['academy_id' => $this->academy->id]);

            InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'title' => 'دورة 1',
                'grade_level_id' => $grade1->id,
                'is_published' => true,
            ]);

            InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'title' => 'دورة 2',
                'grade_level_id' => $grade2->id,
                'is_published' => true,
            ]);

            $results = $this->service->searchAll('دورة', $this->student, ['grade_level_id' => $grade1->id]);

            expect($results->get('interactive_courses'))->toHaveCount(1);
        });

        it('limits results to 10', function () {
            InteractiveCourse::factory()->count(15)->create([
                'academy_id' => $this->academy->id,
                'title' => 'دورة تفاعلية',
                'is_published' => true,
            ]);

            $results = $this->service->searchAll('دورة', $this->student);

            expect($results->get('interactive_courses'))->toHaveCount(10);
        });

        it('returns correct result structure', function () {
            InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'title' => 'دورة الفيزياء',
                'description' => 'دورة متقدمة',
                'total_sessions' => 10,
                'duration_weeks' => 5,
                'student_price' => 1000,
                'is_published' => true,
            ]);

            $results = $this->service->searchAll('فيزياء', $this->student);
            $result = $results->get('interactive_courses')->first();

            expect($result)->toHaveKeys([
                'type',
                'id',
                'title',
                'description',
                'icon',
                'icon_bg',
                'icon_color',
                'teacher_name',
                'meta',
                'status',
                'is_enrolled',
                'route',
            ])
                ->and($result['type'])->toBe('interactive_course')
                ->and($result['icon'])->toBe('ri-book-open-line')
                ->and($result['status'])->toBe('published')
                ->and($result['meta'])->toHaveKeys([
                    'subject',
                    'grade_level',
                    'total_sessions',
                    'duration_weeks',
                    'student_price',
                    'progress_percentage',
                ]);
        });
    });

    describe('searchRecordedCourses()', function () {
        it('finds published courses by title', function () {
            $course = RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'title' => 'دورة البرمجة المسجلة',
                'is_published' => true,
            ]);

            $results = $this->service->searchAll('برمجة', $this->student);

            expect($results->get('recorded_courses'))->toHaveCount(1)
                ->and($results->get('recorded_courses')->first()['id'])->toBe($course->id)
                ->and($results->get('recorded_courses')->first()['type'])->toBe('recorded_course');
        });

        it('finds courses by description', function () {
            RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'title' => 'دورة Python',
                'description' => 'تعلم البرمجة من الصفر',
                'is_published' => true,
            ]);

            $results = $this->service->searchAll('من الصفر', $this->student);

            expect($results->get('recorded_courses'))->toHaveCount(1);
        });

        it('finds courses by subject', function () {
            $subject = Subject::factory()->create([
                'academy_id' => $this->academy->id,
                'name' => 'علوم الحاسب',
            ]);

            RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'subject_id' => $subject->id,
                'is_published' => true,
            ]);

            $results = $this->service->searchAll('حاسب', $this->student);

            expect($results->get('recorded_courses'))->toHaveCount(1);
        });

        it('only returns published courses', function () {
            RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'title' => 'دورة JavaScript',
                'is_published' => true,
            ]);

            RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'title' => 'دورة JavaScript المتقدمة',
                'is_published' => false,
            ]);

            $results = $this->service->searchAll('JavaScript', $this->student);

            expect($results->get('recorded_courses'))->toHaveCount(1);
        });

        it('filters by subject_id when provided', function () {
            $subject1 = Subject::factory()->create(['academy_id' => $this->academy->id]);
            $subject2 = Subject::factory()->create(['academy_id' => $this->academy->id]);

            RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'title' => 'دورة 1',
                'subject_id' => $subject1->id,
                'is_published' => true,
            ]);

            RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'title' => 'دورة 2',
                'subject_id' => $subject2->id,
                'is_published' => true,
            ]);

            $results = $this->service->searchAll('دورة', $this->student, ['subject_id' => $subject1->id]);

            expect($results->get('recorded_courses'))->toHaveCount(1);
        });

        it('filters by grade_level_id when provided', function () {
            $grade1 = GradeLevel::factory()->create(['academy_id' => $this->academy->id]);
            $grade2 = GradeLevel::factory()->create(['academy_id' => $this->academy->id]);

            RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'title' => 'دورة 1',
                'grade_level_id' => $grade1->id,
                'is_published' => true,
            ]);

            RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'title' => 'دورة 2',
                'grade_level_id' => $grade2->id,
                'is_published' => true,
            ]);

            $results = $this->service->searchAll('دورة', $this->student, ['grade_level_id' => $grade1->id]);

            expect($results->get('recorded_courses'))->toHaveCount(1);
        });

        it('limits results to 10', function () {
            RecordedCourse::factory()->count(15)->create([
                'academy_id' => $this->academy->id,
                'title' => 'دورة مسجلة',
                'is_published' => true,
            ]);

            $results = $this->service->searchAll('دورة', $this->student);

            expect($results->get('recorded_courses'))->toHaveCount(10);
        });

        it('returns correct result structure', function () {
            RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'title' => 'دورة البرمجة',
                'description' => 'دورة شاملة',
                'duration_hours' => 20,
                'total_lessons' => 15,
                'price' => 500,
                'is_published' => true,
            ]);

            $results = $this->service->searchAll('برمجة', $this->student);
            $result = $results->get('recorded_courses')->first();

            expect($result)->toHaveKeys([
                'type',
                'id',
                'title',
                'description',
                'icon',
                'icon_bg',
                'icon_color',
                'teacher_name',
                'meta',
                'status',
                'is_enrolled',
                'route',
            ])
                ->and($result['type'])->toBe('recorded_course')
                ->and($result['icon'])->toBe('ri-video-line')
                ->and($result['status'])->toBe('published')
                ->and($result['teacher_name'])->toBeNull()
                ->and($result['meta'])->toHaveKeys([
                    'subject',
                    'grade_level',
                    'duration_hours',
                    'lessons_count',
                    'price',
                    'progress_percentage',
                ]);
        });
    });

    describe('searchQuranTeachers()', function () {
        it('finds teachers by first name', function () {
            $teacher = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'first_name' => 'عبدالرحمن',
                'last_name' => 'خالد',
                'is_active' => true,
            ]);

            $results = $this->service->searchAll('عبدالرحمن', $this->student);

            expect($results->get('quran_teachers'))->toHaveCount(1)
                ->and($results->get('quran_teachers')->first()['id'])->toBe($teacher->id)
                ->and($results->get('quran_teachers')->first()['type'])->toBe('quran_teacher');
        });

        it('finds teachers by last name', function () {
            QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'first_name' => 'محمد',
                'last_name' => 'الأحمدي',
                'is_active' => true,
            ]);

            $results = $this->service->searchAll('الأحمدي', $this->student);

            expect($results->get('quran_teachers'))->toHaveCount(1);
        });

        it('finds teachers by Arabic bio', function () {
            QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'bio_arabic' => 'متخصص في التجويد وعلوم القرآن',
                'is_active' => true,
            ]);

            $results = $this->service->searchAll('التجويد', $this->student);

            expect($results->get('quran_teachers'))->toHaveCount(1);
        });

        it('finds teachers by English bio', function () {
            QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'bio_english' => 'Specialized in Tajweed',
                'is_active' => true,
            ]);

            $results = $this->service->searchAll('Tajweed', $this->student);

            expect($results->get('quran_teachers'))->toHaveCount(1);
        });

        it('only returns active teachers', function () {
            QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'first_name' => 'أحمد',
                'is_active' => true,
            ]);

            QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'first_name' => 'أحمد',
                'is_active' => false,
            ]);

            $results = $this->service->searchAll('أحمد', $this->student);

            expect($results->get('quran_teachers'))->toHaveCount(1);
        });

        it('limits results to 10', function () {
            QuranTeacherProfile::factory()->count(15)->create([
                'academy_id' => $this->academy->id,
                'first_name' => 'محمد',
                'is_active' => true,
            ]);

            $results = $this->service->searchAll('محمد', $this->student);

            expect($results->get('quran_teachers'))->toHaveCount(10);
        });

        it('returns correct result structure', function () {
            QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'first_name' => 'أحمد',
                'last_name' => 'محمد',
                'bio_arabic' => 'معلم قرآن متميز',
                'teaching_experience_years' => 10,
                'is_active' => true,
            ]);

            $results = $this->service->searchAll('أحمد', $this->student);
            $result = $results->get('quran_teachers')->first();

            expect($result)->toHaveKeys([
                'type',
                'id',
                'title',
                'description',
                'icon',
                'icon_bg',
                'icon_color',
                'teacher_name',
                'meta',
                'status',
                'is_enrolled',
                'route',
            ])
                ->and($result['type'])->toBe('quran_teacher')
                ->and($result['icon'])->toBe('ri-user-star-line')
                ->and($result['status'])->toBe('active')
                ->and($result['teacher_name'])->toBeNull()
                ->and($result['is_enrolled'])->toBeFalse()
                ->and($result['meta'])->toHaveKeys([
                    'experience_years',
                    'circles_count',
                ]);
        });
    });

    describe('searchAcademicTeachers()', function () {
        it('finds teachers by first name', function () {
            $teacher = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'first_name' => 'نورة',
                'last_name' => 'السعيد',
                'is_active' => true,
            ]);

            $results = $this->service->searchAll('نورة', $this->student);

            expect($results->get('academic_teachers'))->toHaveCount(1)
                ->and($results->get('academic_teachers')->first()['id'])->toBe($teacher->id)
                ->and($results->get('academic_teachers')->first()['type'])->toBe('academic_teacher');
        });

        it('finds teachers by last name', function () {
            AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'first_name' => 'فاطمة',
                'last_name' => 'القحطاني',
                'is_active' => true,
            ]);

            $results = $this->service->searchAll('القحطاني', $this->student);

            expect($results->get('academic_teachers'))->toHaveCount(1);
        });

        it('finds teachers by subject name', function () {
            $subject = Subject::factory()->create([
                'academy_id' => $this->academy->id,
                'name' => 'الكيمياء',
            ]);

            $teacher = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'is_active' => true,
            ]);

            $teacher->subjects()->attach($subject->id);

            $results = $this->service->searchAll('كيمياء', $this->student);

            expect($results->get('academic_teachers'))->toHaveCount(1);
        });

        it('only returns active teachers', function () {
            AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'first_name' => 'سعيد',
                'is_active' => true,
            ]);

            AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'first_name' => 'سعيد',
                'is_active' => false,
            ]);

            $results = $this->service->searchAll('سعيد', $this->student);

            expect($results->get('academic_teachers'))->toHaveCount(1);
        });

        it('limits results to 10', function () {
            AcademicTeacherProfile::factory()->count(15)->create([
                'academy_id' => $this->academy->id,
                'first_name' => 'خالد',
                'is_active' => true,
            ]);

            $results = $this->service->searchAll('خالد', $this->student);

            expect($results->get('academic_teachers'))->toHaveCount(10);
        });

        it('returns correct result structure', function () {
            $teacher = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'first_name' => 'منى',
                'last_name' => 'العتيبي',
                'teaching_experience_years' => 8,
                'is_active' => true,
            ]);

            $results = $this->service->searchAll('منى', $this->student);
            $result = $results->get('academic_teachers')->first();

            expect($result)->toHaveKeys([
                'type',
                'id',
                'title',
                'description',
                'icon',
                'icon_bg',
                'icon_color',
                'teacher_name',
                'meta',
                'status',
                'is_enrolled',
                'route',
            ])
                ->and($result['type'])->toBe('academic_teacher')
                ->and($result['icon'])->toBe('ri-graduation-cap-line')
                ->and($result['status'])->toBe('active')
                ->and($result['teacher_name'])->toBeNull()
                ->and($result['is_enrolled'])->toBeFalse()
                ->and($result['meta'])->toHaveKeys([
                    'subjects',
                    'experience_years',
                ]);
        });
    });

    describe('getTotalResultsCount()', function () {
        it('returns zero for empty results', function () {
            $results = collect([
                'quran_circles' => collect(),
                'individual_circles' => collect(),
                'academic_sessions' => collect(),
            ]);

            $count = $this->service->getTotalResultsCount($results);

            expect($count)->toBe(0);
        });

        it('returns correct count for single category', function () {
            QuranCircle::factory()->count(3)->create([
                'academy_id' => $this->academy->id,
                'name_ar' => 'حلقة القرآن',
            ]);

            $results = $this->service->searchAll('قرآن', $this->student);
            $count = $this->service->getTotalResultsCount($results);

            expect($count)->toBe(3);
        });

        it('returns correct total count across multiple categories', function () {
            QuranCircle::factory()->count(2)->create([
                'academy_id' => $this->academy->id,
                'name_ar' => 'حلقة القرآن',
            ]);

            QuranTeacherProfile::factory()->count(3)->create([
                'academy_id' => $this->academy->id,
                'first_name' => 'قرآن',
                'is_active' => true,
            ]);

            $results = $this->service->searchAll('قرآن', $this->student);
            $count = $this->service->getTotalResultsCount($results);

            expect($count)->toBe(5);
        });
    });
});
