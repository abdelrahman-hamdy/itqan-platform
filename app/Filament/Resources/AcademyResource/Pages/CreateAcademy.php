<?php

namespace App\Filament\Resources\AcademyResource\Pages;

use App\Filament\Resources\AcademyResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAcademy extends CreateRecord
{
    protected static string $resource = AcademyResource::class;
    
    protected static ?string $title = 'إضافة أكاديمية جديدة';
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء الأكاديمية بنجاح';
    }
} 