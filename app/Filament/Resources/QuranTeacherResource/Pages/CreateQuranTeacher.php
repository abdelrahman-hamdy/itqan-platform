<?php

namespace App\Filament\Resources\QuranTeacherResource\Pages;

use App\Filament\Resources\QuranTeacherResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateQuranTeacher extends CreateRecord
{
    protected static string $resource = QuranTeacherResource::class;

    public function getTitle(): string
    {
        return 'إضافة معلم قرآن جديد';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Add the academy ID and created_by automatically
        $data['academy_id'] = Auth::user()->academy_id ?? 1; // Default academy or get from user
        $data['created_by'] = Auth::id();
        
        // Generate teacher code
        $academyId = $data['academy_id'];
        $count = \App\Models\QuranTeacher::where('academy_id', $academyId)->count() + 1;
        $data['teacher_code'] = 'QT-' . $academyId . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إضافة معلم القرآن بنجاح';
    }
}
