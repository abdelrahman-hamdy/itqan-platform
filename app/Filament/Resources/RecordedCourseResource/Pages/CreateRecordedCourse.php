<?php

namespace App\Filament\Resources\RecordedCourseResource\Pages;

use App\Filament\Resources\RecordedCourseResource;
use App\Services\AcademyContextService;
use App\Models\RecordedCourse;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateRecordedCourse extends CreateRecord
{
    protected static string $resource = RecordedCourseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $currentAcademy = AcademyContextService::getCurrentAcademy();

        if ($currentAcademy) {
            $data['academy_id'] = $currentAcademy->id;
        }

        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        // Generate course code if not provided
        if (empty($data['course_code'])) {
            $data['course_code'] = $this->generateCourseCode($data['title'] ?? 'COURSE');
        }

        // Set default values for required fields
        $data['duration_hours'] = $data['duration_hours'] ?? 0;
        $data['language'] = $data['language'] ?? 'ar';
        $data['price'] = $data['price'] ?? 0;
        $data['is_published'] = $data['is_published'] ?? false;
        $data['difficulty_level'] = $data['difficulty_level'] ?? 'medium';

        // Set default values for description fields
        $data['description'] = $data['description'] ?? 'وصف الدورة';
        $data['description_en'] = $data['description_en'] ?? 'Course Description';

        // Set calculated fields
        $data['total_duration_minutes'] = ($data['duration_hours'] ?? 0) * 60;
        $data['total_sections'] = 0;
        $data['avg_rating'] = 0;
        $data['total_reviews'] = 0;
        $data['total_enrollments'] = 0;

        return $data;
    }

    protected function afterCreate(): void
    {
        // Get or create the default section
        $defaultSection = $this->record->sections()->first();
        if (! $defaultSection) {
            $defaultSection = $this->record->sections()->create([
                'title' => 'دروس الكورس',
                'title_en' => 'Course Lessons',
                'description' => 'دروس الكورس الرئيسية',
                'description_en' => 'Main course lessons',
                'is_published' => true,
                'order' => 1,
                'created_by' => auth()->id() ?? 1,
            ]);
        }

        // Update any lessons that were created with null course_section_id
        $this->record->lessons()->whereNull('course_section_id')->update([
            'course_section_id' => $defaultSection->id,
        ]);

        // Update course statistics after creation
        $this->record->updateStats();
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء الدورة بنجاح';
    }

    private function generateCourseCode(string $title): string
    {
        $baseCode = Str::upper(Str::slug($title, ''));
        $baseCode = preg_replace('/[^A-Z0-9]/', '', $baseCode);

        if (empty($baseCode)) {
            $baseCode = 'COURSE';
        }

        // Ensure uniqueness by adding a number if needed
        $counter = 1;
        $courseCode = $baseCode;

        while (RecordedCourse::where('course_code', $courseCode)->exists()) {
            $courseCode = $baseCode.$counter;
            $counter++;
        }

        return $courseCode;
    }
}
