<?php

namespace App\Filament\Resources\RecordedCourseResource\Pages;

use App\Filament\Resources\RecordedCourseResource;
use App\Helpers\AcademyHelper;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateRecordedCourse extends CreateRecord
{
    protected static string $resource = RecordedCourseResource::class;

    private $lessonsData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $currentAcademy = AcademyHelper::getCurrentAcademy();
        
        if ($currentAcademy) {
            $data['academy_id'] = $currentAcademy->id;
        }
        
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        
        // Store lessons data for later processing and remove from main data
        if (isset($data['lessons'])) {
            $this->lessonsData = $data['lessons'];
            unset($data['lessons']);
        }
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Create lessons after course is created
        if (!empty($this->lessonsData)) {
            foreach ($this->lessonsData as $index => $lessonData) {
                $lessonData['recorded_course_id'] = $this->record->id;
                $lessonData['created_by'] = auth()->id();
                
                // Generate lesson code if not provided
                if (empty($lessonData['lesson_code'])) {
                    $lessonData['lesson_code'] = $this->generateLessonCode($index + 1);
                }
                
                // Set published_at if lesson is published
                if ($lessonData['is_published'] ?? false) {
                    $lessonData['published_at'] = now();
                }
                
                $this->record->lessons()->create($lessonData);
            }
            
            // Update course statistics
            $this->record->updateStats();
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء الدورة بنجاح';
    }

    private function generateLessonCode(int $lessonNumber): string
    {
        $courseCode = $this->record->course_code ?? 'COURSE';
        return "{$courseCode}_LESSON" . str_pad($lessonNumber, 2, '0', STR_PAD_LEFT);
    }
} 