<?php

namespace App\Filament\Academy\Resources\RecordedCourseResource\Pages;

use App\Filament\Academy\Resources\RecordedCourseResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CreateRecordedCourse extends CreateRecord
{
    protected static string $resource = RecordedCourseResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set the academy_id to the current user's academy
        $data['academy_id'] = Auth::user()->academy_id;
        
        // Set the created_by to the current user
        $data['created_by'] = Auth::user()->id;
        
        // Generate course code if not provided
        if (empty($data['course_code'])) {
            $data['course_code'] = $this->generateCourseCode();
        }
        
        // Set default values
        $data['total_duration_minutes'] = ($data['duration_hours'] ?? 1) * 60;
        $data['avg_rating'] = 0;
        $data['total_reviews'] = 0;
        $data['total_enrollments'] = 0;
        
        // Set published_at if course is published
        if ($data['is_published'] ?? false) {
            $data['published_at'] = now();
        }
        
        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء الدورة بنجاح';
    }

    private function generateCourseCode(): string
    {
        $academy = Auth::user()->academy;
        $prefix = strtoupper(substr($academy->name, 0, 3));
        $timestamp = now()->format('ymd');
        $random = strtoupper(Str::random(3));
        
        return "{$prefix}{$timestamp}{$random}";
    }
} 