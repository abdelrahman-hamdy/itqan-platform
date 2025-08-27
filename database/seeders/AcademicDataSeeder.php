<?php

namespace Database\Seeders;

use App\Models\AcademicGradeLevel;
use App\Models\AcademicSubject;
use App\Models\Academy;
use Illuminate\Database\Seeder;

class AcademicDataSeeder extends Seeder
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

        // Create subjects for the academy
        $subjects = [
            ['name' => 'القرآن الكريم', 'name_en' => 'Holy Quran', 'description' => 'تعليم وتحفيظ القرآن الكريم'],
            ['name' => 'التاريخ الإسلامي', 'name_en' => 'Islamic History', 'description' => 'دراسة التاريخ الإسلامي والحضارة'],
            ['name' => 'اللغة العربية', 'name_en' => 'Arabic Language', 'description' => 'تعليم قواعد اللغة العربية والنحو'],
            ['name' => 'الفقه الإسلامي', 'name_en' => 'Islamic Jurisprudence', 'description' => 'دراسة أحكام الفقه الإسلامي'],
            ['name' => 'العقيدة الإسلامية', 'name_en' => 'Islamic Creed', 'description' => 'تعليم أصول العقيدة الإسلامية'],
        ];

        foreach ($subjects as $subjectData) {
            AcademicSubject::firstOrCreate(
                ['name' => $subjectData['name'], 'academy_id' => $academy->id],
                array_merge($subjectData, ['academy_id' => $academy->id])
            );
        }

        // Create grade levels for the academy
        $gradeLevels = [
            ['name' => 'المستوى المبتدئ', 'name_en' => 'Beginner Level', 'description' => 'للطلاب المبتدئين'],
            ['name' => 'المستوى المتوسط', 'name_en' => 'Intermediate Level', 'description' => 'للطلاب في المستوى المتوسط'],
            ['name' => 'المستوى المتقدم', 'name_en' => 'Advanced Level', 'description' => 'للطلاب المتقدمين'],
            ['name' => 'المستوى التخصصي', 'name_en' => 'Specialized Level', 'description' => 'للطلاب المتخصصين'],
        ];

        foreach ($gradeLevels as $gradeLevelData) {
            AcademicGradeLevel::firstOrCreate(
                ['name' => $gradeLevelData['name'], 'academy_id' => $academy->id],
                array_merge($gradeLevelData, ['academy_id' => $academy->id])
            );
        }

        $this->command->info('Successfully created academic subjects and grade levels!');
        $this->command->info('Subjects: '.count($subjects));
        $this->command->info('Grade Levels: '.count($gradeLevels));
    }
}
