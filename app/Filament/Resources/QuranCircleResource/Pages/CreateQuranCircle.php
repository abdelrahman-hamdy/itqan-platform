<?php

namespace App\Filament\Resources\QuranCircleResource\Pages;

use App\Filament\Resources\QuranCircleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateQuranCircle extends CreateRecord
{
    protected static string $resource = QuranCircleResource::class;

    public function getTitle(): string
    {
        return 'إضافة دائرة قرآن جديدة';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Add the academy ID and created_by automatically
        $data['academy_id'] = Auth::user()->academy_id ?? 1; // Default academy or get from user
        $data['created_by'] = Auth::id();
        
        // Generate circle code
        $academyId = $data['academy_id'];
        $count = \App\Models\QuranCircle::where('academy_id', $academyId)->count() + 1;
        $data['circle_code'] = 'QC-' . $academyId . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);
        
        // Set initial values
        $data['enrolled_students'] = 0;
        $data['sessions_completed'] = 0;
        $data['status'] = 'draft';
        $data['enrollment_status'] = 'closed';
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إضافة دائرة القرآن بنجاح';
    }
} 