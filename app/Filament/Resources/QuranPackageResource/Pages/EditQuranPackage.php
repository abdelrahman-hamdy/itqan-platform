<?php

namespace App\Filament\Resources\QuranPackageResource\Pages;

use App\Filament\Resources\QuranPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditQuranPackage extends EditRecord
{
    protected static string $resource = QuranPackageResource::class;

    public function getTitle(): string
    {
        return 'تعديل باقة القرآن: ' . $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('عرض'),
            Actions\DeleteAction::make()
                ->label('حذف'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم تحديث باقة القرآن بنجاح';
    }
} 