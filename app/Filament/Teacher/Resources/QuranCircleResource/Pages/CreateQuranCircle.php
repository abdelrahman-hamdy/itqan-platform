<?php

namespace App\Filament\Teacher\Resources\QuranCircleResource\Pages;

use App\Filament\Teacher\Resources\QuranCircleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateQuranCircle extends CreateRecord
{
    protected static string $resource = QuranCircleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        // Set the teacher and academy IDs
        $data['quran_teacher_id'] = $user->id;
        $data['academy_id'] = $user->academy_id;

        // Generate circle code using the model method
        $data['circle_code'] = \App\Models\QuranCircle::generateCircleCode($user->academy_id);

        // Initialize current students count
        $data['current_students'] = 0;

        return $data;
    }
}
