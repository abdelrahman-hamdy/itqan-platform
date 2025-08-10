<?php

namespace App\Filament\Teacher\Resources\QuranCircleResource\Pages;

use App\Filament\Teacher\Resources\QuranCircleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateQuranCircle extends CreateRecord
{
    protected static string $resource = QuranCircleResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        
        // Set the teacher and academy IDs
        $data['quran_teacher_id'] = $user->quranTeacherProfile->id;
        $data['academy_id'] = $user->academy_id;
        
        // Generate circle code
        $data['circle_code'] = 'QC-' . $user->academy_id . '-' . now()->format('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        // Initialize current students count
        $data['current_students'] = 0;
        
        return $data;
    }
}