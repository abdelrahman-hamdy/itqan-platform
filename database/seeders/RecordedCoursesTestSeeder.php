<?php

namespace Database\Seeders;

use App\Models\AcademicGradeLevel;
use App\Models\AcademicSubject;
use App\Models\Academy;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\RecordedCourse;
use Illuminate\Database\Seeder;

class RecordedCoursesTestSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Get first academy for testing
        $academy = Academy::first();
        if (! $academy) {
            $this->command->error('No academy found. Please create an academy first.');

            return;
        }

        // Get subjects and grade levels for the academy
        $subjects = AcademicSubject::where('academy_id', $academy->id)->get();
        $gradeLevels = AcademicGradeLevel::where('academy_id', $academy->id)->get();

        if ($subjects->isEmpty() || $gradeLevels->isEmpty()) {
            $this->command->error('No subjects or grade levels found for academy. Please create them first.');

            return;
        }

        // Course 1: Quran Recitation for Beginners
        $course1 = RecordedCourse::firstOrCreate(
            ['course_code' => 'QRC-001'],
            [
                'academy_id' => $academy->id,
                'subject_id' => $subjects->first()->id,
                'grade_level_id' => $gradeLevels->first()->id,
                'title' => 'تلاوة القرآن الكريم للمبتدئين',
                'title_en' => 'Quran Recitation for Beginners',
                'description' => 'دورة شاملة لتعلم أساسيات تلاوة القرآن الكريم بالطريقة الصحيحة مع التركيز على مخارج الحروف والأحكام الأساسية',
                'description_en' => 'Comprehensive course for learning the basics of Quran recitation with proper pronunciation and fundamental rules',
                'course_code' => 'QRC-001',
                'level' => 'beginner',
                'duration_hours' => 20,
                'language' => 'ar',
                'price' => 299.00,
                'currency' => 'SAR',
                'is_free' => false,
                'is_published' => true,
                'is_featured' => true,
                'completion_certificate' => true,
                'prerequisites' => 'معرفة أساسية بالحروف العربية',
                'learning_outcomes' => json_encode([
                    'إتقان مخارج الحروف',
                    'تطبيق أحكام التجويد الأساسية',
                    'تلاوة صحيحة للسور القصيرة',
                ]),
                'difficulty_level' => 'easy',
                'category' => 'تلاوة',
                'tags' => json_encode(['تجويد', 'تلاوة', 'مبتدئين']),

                'published_at' => now(),
            ]
        );

        // Create section for Course 1
        $section1 = CourseSection::create([
            'recorded_course_id' => $course1->id,
            'title' => 'أساسيات التجويد',
            'title_en' => 'Tajweed Basics',
            'description' => 'تعلم الأساسيات الضرورية لتلاوة القرآن الكريم',
            'order' => 1,
            'is_published' => true,
        ]);

        // Add lessons for Course 1
        $lessons1 = [
            [
                'title' => 'مقدمة في علم التجويد',
                'description' => 'تعريف بعلم التجويد وأهميته في تلاوة القرآن الكريم',
                'lesson_code' => 'QRC-001-L01',
                'video_duration_seconds' => 900, // 15 minutes
                'is_published' => true,
                'is_free_preview' => true,
            ],
            [
                'title' => 'مخارج الحروف - الجزء الأول',
                'description' => 'تعلم مخارج الحروف من الحلق والفم',
                'lesson_code' => 'QRC-001-L02',
                'video_duration_seconds' => 1200, // 20 minutes
                'is_published' => true,
                'is_free_preview' => false,
            ],
            [
                'title' => 'مخارج الحروف - الجزء الثاني',
                'description' => 'تعلم مخارج الحروف من اللسان والشفتين',
                'lesson_code' => 'QRC-001-L03',
                'video_duration_seconds' => 1200, // 20 minutes
                'is_published' => true,
                'is_free_preview' => false,
            ],
            [
                'title' => 'أحكام النون الساكنة والتنوين',
                'description' => 'شرح مفصل لأحكام النون الساكنة والتنوين الأربعة',
                'lesson_code' => 'QRC-001-L04',
                'video_duration_seconds' => 1800, // 30 minutes
                'is_published' => true,
                'is_free_preview' => false,
            ],
        ];

        foreach ($lessons1 as $lessonData) {
            Lesson::firstOrCreate(
                ['lesson_code' => $lessonData['lesson_code']],
                array_merge($lessonData, [
                    'recorded_course_id' => $course1->id,
                    'course_section_id' => $section1->id,
                    'lesson_type' => 'video',
                    'video_url' => 'https://example.com/videos/'.$lessonData['lesson_code'].'.mp4',
                ])
            );
        }

        // Course 2: Islamic History - Advanced
        $course2 = RecordedCourse::firstOrCreate(
            ['course_code' => 'IH-002'],
            [
                'academy_id' => $academy->id,
                'subject_id' => $subjects->count() > 1 ? $subjects->skip(1)->first()->id : $subjects->first()->id,
                'grade_level_id' => $gradeLevels->count() > 1 ? $gradeLevels->skip(1)->first()->id : $gradeLevels->first()->id,
                'title' => 'التاريخ الإسلامي المتقدم',
                'title_en' => 'Advanced Islamic History',
                'description' => 'دراسة معمقة للتاريخ الإسلامي من عهد الخلفاء الراشدين حتى العصر العثماني مع التركيز على الأحداث المهمة والشخصيات المؤثرة',
                'description_en' => 'In-depth study of Islamic history from the Rightly-Guided Caliphs to the Ottoman era',
                'course_code' => 'IH-002',
                'level' => 'advanced',
                'duration_hours' => 35,
                'language' => 'ar',
                'price' => 499.00,
                'currency' => 'SAR',
                'is_free' => false,
                'is_published' => true,
                'is_featured' => false,
                'completion_certificate' => true,
                'prerequisites' => 'معرفة أساسية بالتاريخ الإسلامي',
                'learning_outcomes' => json_encode([
                    'فهم عميق للأحداث التاريخية الإسلامية',
                    'تحليل الشخصيات التاريخية المؤثرة',
                    'ربط الأحداث التاريخية بالواقع المعاصر',
                ]),
                'difficulty_level' => 'hard',
                'category' => 'تاريخ',
                'tags' => json_encode(['تاريخ إسلامي', 'خلافة', 'عثماني']),

                'published_at' => now(),
            ]
        );

        // Create section for Course 2
        $section2 = CourseSection::create([
            'recorded_course_id' => $course2->id,
            'title' => 'العصور الإسلامية',
            'title_en' => 'Islamic Eras',
            'description' => 'رحلة عبر التاريخ الإسلامي من الخلافة الراشدة إلى العثمانية',
            'order' => 1,
            'is_published' => true,
        ]);

        // Add lessons for Course 2
        $lessons2 = [
            [
                'title' => 'عهد الخلفاء الراشدين',
                'description' => 'دراسة شاملة لفترة الخلفاء الراشدين وإنجازاتهم',
                'lesson_code' => 'IH-002-L01',
                'video_duration_seconds' => 2400, // 40 minutes
                'is_published' => true,
                'is_free_preview' => true,
            ],
            [
                'title' => 'الدولة الأموية',
                'description' => 'تاريخ الدولة الأموية وتوسعاتها',
                'lesson_code' => 'IH-002-L02',
                'video_duration_seconds' => 2100, // 35 minutes
                'is_published' => true,
                'is_free_preview' => false,
            ],
            [
                'title' => 'الدولة العباسية',
                'description' => 'العصر الذهبي للحضارة الإسلامية في العهد العباسي',
                'lesson_code' => 'IH-002-L03',
                'video_duration_seconds' => 2700, // 45 minutes
                'is_published' => true,
                'is_free_preview' => false,
            ],
            [
                'title' => 'الحروب الصليبية',
                'description' => 'الحروب الصليبية والمقاومة الإسلامية',
                'lesson_code' => 'IH-002-L04',
                'video_duration_seconds' => 3000, // 50 minutes
                'is_published' => true,
                'is_free_preview' => false,
            ],
            [
                'title' => 'الدولة العثمانية',
                'description' => 'نشأة وازدهار الدولة العثمانية',
                'lesson_code' => 'IH-002-L05',
                'video_duration_seconds' => 2400, // 40 minutes
                'is_published' => true,
                'is_free_preview' => false,
            ],
        ];

        foreach ($lessons2 as $lessonData) {
            Lesson::firstOrCreate(
                ['lesson_code' => $lessonData['lesson_code']],
                array_merge($lessonData, [
                    'recorded_course_id' => $course2->id,
                    'course_section_id' => $section2->id,
                    'lesson_type' => 'video',
                    'video_url' => 'https://example.com/videos/'.$lessonData['lesson_code'].'.mp4',
                ])
            );
        }

        // Course 3: Arabic Grammar - Intermediate
        $course3 = RecordedCourse::firstOrCreate(
            ['course_code' => 'AG-003'],
            [
                'academy_id' => $academy->id,
                'subject_id' => $subjects->first()->id,
                'grade_level_id' => $gradeLevels->count() > 2 ? $gradeLevels->skip(2)->first()->id : $gradeLevels->first()->id,
                'title' => 'النحو العربي المتوسط',
                'title_en' => 'Intermediate Arabic Grammar',
                'description' => 'دورة متوسطة في النحو العربي تغطي القواعد الأساسية والمتقدمة مع التطبيق العملي على النصوص القرآنية والأدبية',
                'description_en' => 'Intermediate course in Arabic grammar covering basic and advanced rules with practical application',
                'course_code' => 'AG-003',
                'level' => 'intermediate',
                'duration_hours' => 28,
                'language' => 'ar',
                'price' => 399.00,
                'currency' => 'SAR',
                'is_free' => false,
                'is_published' => true,
                'is_featured' => true,
                'completion_certificate' => true,
                'prerequisites' => 'معرفة أساسية بقواعد النحو',
                'learning_outcomes' => json_encode([
                    'إتقان قواعد النحو المتوسطة',
                    'تطبيق القواعد على النصوص',
                    'فهم الإعراب والتحليل النحوي',
                ]),
                'difficulty_level' => 'medium',
                'category' => 'نحو',
                'tags' => json_encode(['نحو', 'قواعد', 'إعراب']),

                'published_at' => now(),
            ]
        );

        // Create section for Course 3
        $section3 = CourseSection::create([
            'recorded_course_id' => $course3->id,
            'title' => 'قواعد النحو العربي',
            'title_en' => 'Arabic Grammar Rules',
            'description' => 'دراسة شاملة لقواعد النحو العربي وتطبيقاتها',
            'order' => 1,
            'is_published' => true,
        ]);

        // Add lessons for Course 3
        $lessons3 = [
            [
                'title' => 'المرفوعات من الأسماء',
                'description' => 'دراسة المبتدأ والخبر والفاعل ونائب الفاعل',
                'lesson_code' => 'AG-003-L01',
                'video_duration_seconds' => 1800, // 30 minutes
                'is_published' => true,
                'is_free_preview' => true,
            ],
            [
                'title' => 'المنصوبات من الأسماء',
                'description' => 'المفعول به والمفعول المطلق والمفعول لأجله',
                'lesson_code' => 'AG-003-L02',
                'video_duration_seconds' => 2100, // 35 minutes
                'is_published' => true,
                'is_free_preview' => false,
            ],
            [
                'title' => 'المجرورات من الأسماء',
                'description' => 'حروف الجر ومعانيها والإضافة',
                'lesson_code' => 'AG-003-L03',
                'video_duration_seconds' => 1500, // 25 minutes
                'is_published' => true,
                'is_free_preview' => false,
            ],
            [
                'title' => 'الأفعال وأحكامها',
                'description' => 'أنواع الأفعال وإعرابها وعلامات الإعراب',
                'lesson_code' => 'AG-003-L04',
                'video_duration_seconds' => 2400, // 40 minutes
                'is_published' => true,
                'is_free_preview' => false,
            ],
            [
                'title' => 'الجملة الاسمية والفعلية',
                'description' => 'تركيب الجمل وأنواعها وخصائصها',
                'lesson_code' => 'AG-003-L05',
                'video_duration_seconds' => 1800, // 30 minutes
                'is_published' => true,
                'is_free_preview' => false,
            ],
            [
                'title' => 'التطبيق العملي على النصوص',
                'description' => 'إعراب نصوص من القرآن الكريم والأدب العربي',
                'lesson_code' => 'AG-003-L06',
                'video_duration_seconds' => 2700, // 45 minutes
                'is_published' => true,
                'is_free_preview' => false,
            ],
        ];

        foreach ($lessons3 as $lessonData) {
            Lesson::firstOrCreate(
                ['lesson_code' => $lessonData['lesson_code']],
                array_merge($lessonData, [
                    'recorded_course_id' => $course3->id,
                    'course_section_id' => $section3->id,
                    'lesson_type' => 'video',
                    'video_url' => 'https://example.com/videos/'.$lessonData['lesson_code'].'.mp4',
                ])
            );
        }

        // Update course statistics
        $course1->updateStats();
        $course2->updateStats();
        $course3->updateStats();

        $this->command->info('Successfully created 3 test recorded courses with lessons!');
        $this->command->info('Course 1: '.$course1->title.' ('.$course1->lessons->count().' lessons)');
        $this->command->info('Course 2: '.$course2->title.' ('.$course2->lessons->count().' lessons)');
        $this->command->info('Course 3: '.$course3->title.' ('.$course3->lessons->count().' lessons)');
    }
}
