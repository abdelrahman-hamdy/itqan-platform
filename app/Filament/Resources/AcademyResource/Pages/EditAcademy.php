<?php

namespace App\Filament\Resources\AcademyResource\Pages;

use App\Filament\Resources\AcademyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAcademy extends EditRecord
{
    protected static string $resource = AcademyResource::class;
    
    protected static ?string $title = 'تعديل الأكاديمية';

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('عرض')
                ->icon('heroicon-o-eye'),
                
            Actions\DeleteAction::make()
                ->label('حذف'),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم حفظ التغييرات بنجاح';
    }
}
