<?php

namespace Database\Seeders;

use App\Enums\CertificateTemplateStyle;
use App\Enums\CertificateType;
use App\Enums\RelationshipType;
use App\Enums\UserType;
use App\Models\Academy;
use App\Models\Certificate;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicSubject;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourse;
use App\Models\ParentProfile;
use App\Models\Quiz;
use App\Models\QuizAssignment;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuranIndividualCircle;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Extends E2ETestDataSeeder with additional data for mobile screen coverage.
 *
 * Adds: Parent account, notifications, quiz assignments/attempts,
 * certificates, and chat conversations so every mobile screen renders data.
 *
 * Usage: php artisan db:seed --class=MobileE2ESeeder --force
 *
 * Idempotent: Cleans own [E2E-Mobile] data before re-creating.
 * Run AFTER E2ETestDataSeeder (which creates base users + sessions).
 */
class MobileE2ESeeder extends Seeder
{
    private const PREFIX = '[E2E-Mobile]';

    private Academy $academy;

    private User $student;

    private User $quranTeacher;

    private User $academicTeacher;

    private User $parent;

    private ?StudentProfile $studentProfile;

    public function run(): void
    {
        $this->command->info('Starting Mobile E2E data seeding...');

        // 1. Run the base E2E seeder first (may partially fail due to
        //    pre-existing escapeLikeString framework bug — that's OK,
        //    the core data it creates before the error is sufficient)
        try {
            $this->call(E2ETestDataSeeder::class);
        } catch (\BadMethodCallException $e) {
            if (str_contains($e->getMessage(), 'escapeLikeString')) {
                $this->command->warn('Base seeder hit known escapeLikeString bug — continuing with partial data.');
            } else {
                throw $e;
            }
        }

        // 2. Find academy and existing users
        $this->academy = Academy::where('subdomain', 'e2e-test')->firstOrFail();
        $this->student = User::where('email', 'e2e-student@itqan.com')->firstOrFail();
        $this->quranTeacher = User::where('email', 'e2e-teacher@itqan.com')->firstOrFail();
        $this->academicTeacher = User::where('email', 'e2e-academic@itqan.com')->firstOrFail();
        $this->studentProfile = StudentProfile::withoutGlobalScopes()
            ->where('user_id', $this->student->id)
            ->firstOrFail();

        // 3. Clean old mobile-specific E2E data
        $this->cleanMobileData();

        // 4. Create parent account
        $this->createParentAccount();

        // 5. Create notifications for all users
        $this->createNotifications();

        // 6. Create quiz data
        $this->createQuizData();

        // 7. Create certificates
        $this->createCertificates();

        // 8. Create chat conversations
        $this->createChatConversations();

        $this->command->info('Mobile E2E data seeding completed!');
    }

    /**
     * Clean all Mobile E2E data from production.
     *
     * Public so it can be called from CleanMobileE2EData command.
     * Deletes in strict reverse-dependency order to avoid FK violations.
     */
    public function cleanMobileData(): void
    {
        $prefix = self::PREFIX;
        $academyId = $this->academy->id;

        // 1. Clean chat conversations, participants, messages
        $this->cleanChatData();

        // 2. Clean certificates
        Certificate::withoutGlobalScopes()
            ->where('academy_id', $academyId)
            ->where('certificate_text', 'like', "{$prefix}%")
            ->forceDelete();

        // 3. Clean quiz attempts → assignments → questions → quizzes
        $quizIds = Quiz::withoutGlobalScopes()
            ->where('academy_id', $academyId)
            ->where('title', 'like', "{$prefix}%")
            ->pluck('id');

        if ($quizIds->isNotEmpty()) {
            $assignmentIds = QuizAssignment::whereIn('quiz_id', $quizIds)->pluck('id');
            if ($assignmentIds->isNotEmpty()) {
                QuizAttempt::whereIn('quiz_assignment_id', $assignmentIds)->forceDelete();
            }
            QuizAssignment::whereIn('quiz_id', $quizIds)->forceDelete();
            QuizQuestion::whereIn('quiz_id', $quizIds)->forceDelete();
            Quiz::withoutGlobalScopes()->whereIn('id', $quizIds)->forceDelete();
        }

        // 4. Clean notifications for all e2e users
        $userIds = User::whereIn('email', [
            'e2e-student@itqan.com',
            'e2e-teacher@itqan.com',
            'e2e-academic@itqan.com',
            'e2e-parent@itqan.com',
        ])->pluck('id');

        if ($userIds->isNotEmpty()) {
            DB::table('notifications')
                ->where('notifiable_type', User::class)
                ->whereIn('notifiable_id', $userIds)
                ->where('data->title', 'like', "{$prefix}%")
                ->delete();
        }

        // 5. Clean parent-student pivot → parent profile → parent user
        $parentProfileIds = ParentProfile::withoutGlobalScopes()
            ->where('academy_id', $academyId)
            ->whereHas('user', fn ($q) => $q->where('email', 'e2e-parent@itqan.com'))
            ->pluck('id');

        if ($parentProfileIds->isNotEmpty()) {
            DB::table('parent_student_relationships')
                ->whereIn('parent_id', $parentProfileIds)
                ->delete();

            ParentProfile::withoutGlobalScopes()
                ->whereIn('id', $parentProfileIds)
                ->forceDelete();
        }

        // 6. Delete the parent user itself (safe — all FKs cleaned above)
        User::where('email', 'e2e-parent@itqan.com')->forceDelete();

        $this->command->info('Cleaned old Mobile E2E data.');
    }

    /**
     * Clean WireChat conversations involving E2E users.
     */
    private function cleanChatData(): void
    {
        if (! class_exists(\Namu\WireChat\Models\Conversation::class)) {
            return;
        }

        try {
            $e2eUserIds = User::whereIn('email', [
                'e2e-student@itqan.com',
                'e2e-teacher@itqan.com',
                'e2e-academic@itqan.com',
                'e2e-parent@itqan.com',
            ])->pluck('id');

            if ($e2eUserIds->isEmpty()) {
                return;
            }

            // Find conversations where ALL participants are E2E users
            $conversationIds = DB::table('wire_participants')
                ->where('participantable_type', User::class)
                ->whereIn('participantable_id', $e2eUserIds)
                ->pluck('conversation_id')
                ->unique();

            if ($conversationIds->isEmpty()) {
                return;
            }

            // Only delete conversations where every participant is an E2E user
            $safeToDelete = $conversationIds->filter(function ($convId) use ($e2eUserIds) {
                $nonE2eCount = DB::table('wire_participants')
                    ->where('conversation_id', $convId)
                    ->where(function ($q) use ($e2eUserIds) {
                        $q->where('participantable_type', '!=', User::class)
                            ->orWhereNotIn('participantable_id', $e2eUserIds->toArray());
                    })
                    ->count();

                return $nonE2eCount === 0;
            });

            if ($safeToDelete->isNotEmpty()) {
                $ids = $safeToDelete->values()->toArray();
                DB::table('wire_messages')->whereIn('conversation_id', $ids)->delete();
                DB::table('wire_participants')->whereIn('conversation_id', $ids)->delete();
                DB::table('wire_conversations')->whereIn('id', $ids)->delete();
                $this->command->info('Cleaned '.count($ids).' E2E chat conversation(s).');
            }
        } catch (\Exception $e) {
            $this->command->warn("Chat cleanup failed (non-fatal): {$e->getMessage()}");
        }
    }

    private function createParentAccount(): void
    {
        // Create or find parent user
        $this->parent = User::firstOrCreate(
            ['email' => 'e2e-parent@itqan.com'],
            [
                'name' => 'ولي أمر اختباري',
                'first_name' => 'ولي',
                'last_name' => 'أمر اختباري',
                'password' => Hash::make('E2eTest@2025'),
                'user_type' => UserType::PARENT,
                'academy_id' => $this->academy->id,
                'email_verified_at' => now(),
                'status' => 'active',
            ]
        );

        // Ensure user_type and active status
        if ($this->parent->user_type !== UserType::PARENT) {
            $this->parent->update([
                'user_type' => UserType::PARENT,
                'status' => 'active',
            ]);
        }

        // Create parent profile (use firstOrCreate to be idempotent)
        $parentProfile = ParentProfile::withoutEvents(function () {
            return ParentProfile::withoutGlobalScopes()->firstOrCreate(
                [
                    'academy_id' => $this->academy->id,
                    'user_id' => $this->parent->id,
                ],
                [
                    'first_name' => 'ولي',
                    'last_name' => 'أمر اختباري',
                    'email' => 'e2e-parent@itqan.com',
                    'phone' => '0501234567',
                    'relationship_type' => RelationshipType::FATHER,
                    'parent_code' => 'PAR-E2E-'.Str::random(4),
                ]
            );
        });

        // Link parent to student
        if ($this->studentProfile) {
            $parentProfile->students()->syncWithoutDetaching([
                $this->studentProfile->id => [
                    'relationship_type' => RelationshipType::FATHER->value,
                ],
            ]);
        }

        $this->command->info("Created parent account: {$this->parent->email} (Profile: {$parentProfile->id})");
    }

    private function createNotifications(): void
    {
        $users = [
            $this->student,
            $this->quranTeacher,
            $this->academicTeacher,
            $this->parent,
        ];

        $notificationTemplates = [
            [
                'title' => self::PREFIX.' تذكير بالجلسة القادمة',
                'body' => 'لديك جلسة قرآن بعد 30 دقيقة',
                'type' => 'session_reminder',
            ],
            [
                'title' => self::PREFIX.' واجب جديد',
                'body' => 'تم تعيين واجب جديد في مادة الرياضيات',
                'type' => 'homework_assigned',
            ],
            [
                'title' => self::PREFIX.' تقرير جلسة جديد',
                'body' => 'تم إضافة تقرير لجلسة القرآن المكتملة',
                'type' => 'session_report',
            ],
            [
                'title' => self::PREFIX.' رسالة جديدة',
                'body' => 'لديك رسالة جديدة من المشرف',
                'type' => 'new_message',
            ],
        ];

        foreach ($users as $user) {
            foreach ($notificationTemplates as $i => $template) {
                DB::table('notifications')->insert([
                    'id' => Str::uuid()->toString(),
                    'notifiable_type' => User::class,
                    'notifiable_id' => $user->id,
                    'type' => 'App\\Notifications\\GeneralNotification',
                    'data' => json_encode([
                        'title' => $template['title'],
                        'body' => $template['body'],
                        'type' => $template['type'],
                        'academy_id' => $this->academy->id,
                    ]),
                    'read_at' => $i === 0 ? now()->subHours(2) : null, // First one is read
                    'created_at' => now()->subHours($i * 3),
                    'updated_at' => now()->subHours($i * 3),
                ]);
            }
        }

        $this->command->info('Created 4 notifications per user (4 users = 16 total).');
    }

    private function createQuizData(): void
    {
        // Find interactive course from base seeder, or create one
        $course = InteractiveCourse::withoutGlobalScopes()
            ->where('academy_id', $this->academy->id)
            ->where('title', 'like', '[E2E]%')
            ->first();

        if (! $course) {
            $course = $this->createInteractiveCourse();
        }

        if (! $course) {
            $this->command->warn('Could not find or create InteractiveCourse. Skipping quiz data.');

            return;
        }

        // Create quiz
        $quiz = Quiz::withoutEvents(function () use ($course) {
            return Quiz::create([
                'academy_id' => $this->academy->id,
                'title' => self::PREFIX.' اختبار الرياضيات - الوحدة الأولى',
                'description' => 'اختبار شامل للوحدة الأولى في الرياضيات',
                'duration_minutes' => 30,
                'passing_score' => 60,
                'is_active' => true,
                'randomize_questions' => false,
            ]);
        });

        // Create questions
        $questions = [
            [
                'question_text' => 'ما ناتج 5 + 3 ؟',
                'options' => ['6', '7', '8', '9'],
                'correct_option' => 2,
                'order' => 1,
            ],
            [
                'question_text' => 'ما ناتج 10 × 2 ؟',
                'options' => ['12', '20', '22', '30'],
                'correct_option' => 1,
                'order' => 2,
            ],
            [
                'question_text' => 'ما هو الجذر التربيعي لـ 16 ؟',
                'options' => ['2', '4', '6', '8'],
                'correct_option' => 1,
                'order' => 3,
            ],
            [
                'question_text' => 'أي الأعداد التالية عدد أولي ؟',
                'options' => ['4', '6', '7', '9'],
                'correct_option' => 2,
                'order' => 4,
            ],
            [
                'question_text' => 'ما ناتج 15 - 7 ؟',
                'options' => ['6', '7', '8', '9'],
                'correct_option' => 2,
                'order' => 5,
            ],
        ];

        foreach ($questions as $q) {
            QuizQuestion::create(array_merge(['quiz_id' => $quiz->id], $q));
        }

        // Create quiz assignment linked to the interactive course
        $assignment = QuizAssignment::withoutEvents(function () use ($quiz, $course) {
            return QuizAssignment::create([
                'quiz_id' => $quiz->id,
                'assignable_type' => InteractiveCourse::class,
                'assignable_id' => $course->id,
                'is_visible' => true,
                'available_from' => now()->subDays(5),
                'available_until' => now()->addDays(10),
                'max_attempts' => 3,
            ]);
        });

        // Create a submitted (passed) attempt
        QuizAttempt::withoutEvents(function () use ($assignment) {
            return QuizAttempt::create([
                'quiz_assignment_id' => $assignment->id,
                'student_id' => $this->studentProfile->id,
                'answers' => [
                    ['question_index' => 0, 'selected_option' => 2],
                    ['question_index' => 1, 'selected_option' => 1],
                    ['question_index' => 2, 'selected_option' => 1],
                    ['question_index' => 3, 'selected_option' => 2],
                    ['question_index' => 4, 'selected_option' => 2],
                ],
                'score' => 100,
                'passed' => true,
                'started_at' => now()->subDays(2),
                'submitted_at' => now()->subDays(2)->addMinutes(15),
            ]);
        });

        // Create a second quiz with a failed attempt
        $quiz2 = Quiz::withoutEvents(function () {
            return Quiz::create([
                'academy_id' => $this->academy->id,
                'title' => self::PREFIX.' اختبار العلوم - الفصل الثاني',
                'description' => 'اختبار قصير عن الفصل الثاني',
                'duration_minutes' => 20,
                'passing_score' => 70,
                'is_active' => true,
                'randomize_questions' => false,
            ]);
        });

        $scienceQuestions = [
            [
                'question_text' => 'ما هو الغاز الأكثر وفرة في الغلاف الجوي ؟',
                'options' => ['الأكسجين', 'النيتروجين', 'ثاني أكسيد الكربون', 'الهيدروجين'],
                'correct_option' => 1,
                'order' => 1,
            ],
            [
                'question_text' => 'ما هي وحدة قياس القوة ؟',
                'options' => ['الجول', 'النيوتن', 'الواط', 'الأمبير'],
                'correct_option' => 1,
                'order' => 2,
            ],
            [
                'question_text' => 'أي الكواكب أقرب إلى الشمس ؟',
                'options' => ['الأرض', 'الزهرة', 'عطارد', 'المريخ'],
                'correct_option' => 2,
                'order' => 3,
            ],
        ];

        foreach ($scienceQuestions as $q) {
            QuizQuestion::create(array_merge(['quiz_id' => $quiz2->id], $q));
        }

        $assignment2 = QuizAssignment::withoutEvents(function () use ($quiz2, $course) {
            return QuizAssignment::create([
                'quiz_id' => $quiz2->id,
                'assignable_type' => InteractiveCourse::class,
                'assignable_id' => $course->id,
                'is_visible' => true,
                'available_from' => now()->subDays(3),
                'available_until' => now()->addDays(7),
                'max_attempts' => 2,
            ]);
        });

        // Failed attempt
        QuizAttempt::withoutEvents(function () use ($assignment2) {
            return QuizAttempt::create([
                'quiz_assignment_id' => $assignment2->id,
                'student_id' => $this->studentProfile->id,
                'answers' => [
                    ['question_index' => 0, 'selected_option' => 0],
                    ['question_index' => 1, 'selected_option' => 2],
                    ['question_index' => 2, 'selected_option' => 0],
                ],
                'score' => 33,
                'passed' => false,
                'started_at' => now()->subDays(1),
                'submitted_at' => now()->subDays(1)->addMinutes(10),
            ]);
        });

        $this->command->info('Created 2 quizzes with 8 questions, 2 assignments, 2 attempts.');
    }

    private function createCertificates(): void
    {
        // Find quran circle from base seeder
        $circle = QuranIndividualCircle::withoutGlobalScopes()
            ->where('academy_id', $this->academy->id)
            ->where('name', 'like', '[E2E]%')
            ->first();

        $course = InteractiveCourse::withoutGlobalScopes()
            ->where('academy_id', $this->academy->id)
            ->where('title', 'like', '[E2E]%')
            ->first();

        Certificate::withoutEvents(function () use ($circle, $course) {
            // Certificate for Quran circle completion
            if ($circle) {
                Certificate::create([
                    'academy_id' => $this->academy->id,
                    'student_id' => $this->student->id,
                    'teacher_id' => $this->quranTeacher->id,
                    'issued_by' => $this->quranTeacher->id,
                    'certificate_type' => CertificateType::QURAN_SUBSCRIPTION,
                    'template_style' => CertificateTemplateStyle::TEMPLATE_1,
                    'certificate_number' => 'CERT-E2E-Q-'.Str::random(6),
                    'certificate_text' => self::PREFIX.' شهادة إتمام حفظ جزء عم - حلقة قرآن اختبارية',
                    'certificateable_type' => QuranIndividualCircle::class,
                    'certificateable_id' => $circle->id,
                    'issued_at' => now()->subDays(7),
                    'is_manual' => true,
                    'metadata' => ['e2e_test' => true, 'juz' => 'عم'],
                ]);
            }

            // Certificate for interactive course
            if ($course) {
                Certificate::create([
                    'academy_id' => $this->academy->id,
                    'student_id' => $this->student->id,
                    'teacher_id' => $this->academicTeacher->id,
                    'issued_by' => $this->academicTeacher->id,
                    'certificate_type' => CertificateType::INTERACTIVE_COURSE,
                    'template_style' => CertificateTemplateStyle::TEMPLATE_3,
                    'certificate_number' => 'CERT-E2E-IC-'.Str::random(6),
                    'certificate_text' => self::PREFIX.' شهادة إتمام الدورة التفاعلية الاختبارية بنجاح',
                    'certificateable_type' => InteractiveCourse::class,
                    'certificateable_id' => $course->id,
                    'issued_at' => now()->subDays(3),
                    'is_manual' => true,
                    'metadata' => ['e2e_test' => true, 'grade' => 'A'],
                ]);
            }
        });

        $this->command->info('Created 2 certificates (Quran + Interactive Course).');
    }

    private function createChatConversations(): void
    {
        // Check if WireChat models are available
        if (! class_exists(\Namu\WireChat\Models\Conversation::class)) {
            $this->command->warn('WireChat models not available. Skipping chat data.');

            return;
        }

        try {
            $conversationModel = \Namu\WireChat\Models\Conversation::class;

            // Create a private conversation between student and parent
            $conversation1 = $conversationModel::create(['type' => 'private']);
            // Add participants
            $this->addChatParticipant($conversation1, $this->student);
            $this->addChatParticipant($conversation1, $this->parent);

            // Add test messages
            $this->addChatMessage($conversation1, $this->parent, self::PREFIX.' السلام عليكم، كيف حالك يا بني؟');
            $this->addChatMessage($conversation1, $this->student, self::PREFIX.' وعليكم السلام، الحمد لله بخير');
            $this->addChatMessage($conversation1, $this->parent, self::PREFIX.' هل أنهيت واجبك اليوم؟');

            $this->command->info('Created 1 chat conversation with 3 messages.');
        } catch (\Exception $e) {
            $this->command->warn("Chat conversation creation failed: {$e->getMessage()}");
        }
    }

    private function addChatParticipant($conversation, User $user): void
    {
        if (class_exists(\Namu\WireChat\Models\Participant::class)) {
            \Namu\WireChat\Models\Participant::create([
                'conversation_id' => $conversation->id,
                'participantable_type' => User::class,
                'participantable_id' => $user->id,
            ]);
        }
    }

    private function addChatMessage($conversation, User $user, string $body): void
    {
        if (! class_exists(\Namu\WireChat\Models\Message::class)) {
            return;
        }

        $participant = \Namu\WireChat\Models\Participant::where('conversation_id', $conversation->id)
            ->where('participantable_type', User::class)
            ->where('participantable_id', $user->id)
            ->first();

        if ($participant) {
            \Namu\WireChat\Models\Message::create([
                'conversation_id' => $conversation->id,
                'participant_id' => $participant->id,
                'body' => $body,
            ]);
        }
    }

    /**
     * Create an InteractiveCourse if the base seeder failed before creating one.
     * Uses Model::unguarded() to bypass mass assignment for academy_id.
     */
    private function createInteractiveCourse(): ?InteractiveCourse
    {
        try {
            $subject = AcademicSubject::withoutGlobalScopes()
                ->where('academy_id', $this->academy->id)
                ->first();

            $gradeLevel = AcademicGradeLevel::withoutGlobalScopes()
                ->where('academy_id', $this->academy->id)
                ->first();

            $teacherProfile = AcademicTeacherProfile::withoutGlobalScopes()
                ->where('user_id', $this->academicTeacher->id)
                ->where('academy_id', $this->academy->id)
                ->first();

            if (! $subject || ! $gradeLevel || ! $teacherProfile) {
                $this->command->warn('Missing subject/grade/teacher profile for InteractiveCourse.');

                return null;
            }

            // Use unguarded to set academy_id (excluded from $fillable for security)
            $course = null;
            InteractiveCourse::unguarded(function () use (&$course, $subject, $gradeLevel, $teacherProfile) {
                $course = InteractiveCourse::create([
                    'academy_id' => $this->academy->id,
                    'assigned_teacher_id' => $teacherProfile->id,
                    'subject_id' => $subject->id,
                    'grade_level_id' => $gradeLevel->id,
                    'course_code' => 'IC-E2E-'.Str::random(6),
                    'title' => '[E2E] دورة تفاعلية اختبارية',
                    'description' => 'دورة اختبارية لفحص الاختبارات والشهادات',
                    'difficulty_level' => 'intermediate',
                    'max_students' => 30,
                    'duration_weeks' => 8,
                    'sessions_per_week' => 2,
                    'session_duration_minutes' => 60,
                    'total_sessions' => 10,
                    'student_price' => 200.00,
                    'teacher_payment' => 100.00,
                    'payment_type' => 'fixed_amount',
                    'start_date' => now()->subDays(14),
                    'enrollment_deadline' => now()->subDays(16),
                    'status' => 'published',
                    'is_published' => true,
                    'certificate_enabled' => false,
                    'recording_enabled' => false,
                    'schedule' => json_encode([
                        ['day' => 'sunday', 'start_time' => '10:00', 'end_time' => '11:00'],
                        ['day' => 'tuesday', 'start_time' => '10:00', 'end_time' => '11:00'],
                    ]),
                ]);
            });

            if ($course) {
                $this->command->info("Created fallback InteractiveCourse: {$course->id}");
            }

            return $course;
        } catch (\Exception $e) {
            $this->command->warn("InteractiveCourse creation failed: {$e->getMessage()}");

            return null;
        }
    }
}
