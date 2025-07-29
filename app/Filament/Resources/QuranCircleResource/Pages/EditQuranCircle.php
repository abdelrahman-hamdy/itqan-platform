<?php

namespace App\Filament\Resources\QuranCircleResource\Pages;

use App\Filament\Resources\QuranCircleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditQuranCircle extends EditRecord
{
    protected static string $resource = QuranCircleResource::class;

    public function getTitle(): string
    {
        return 'تعديل دائرة القرآن: ' . $this->record->name_ar;
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
        return 'تم تحديث بيانات دائرة القرآن بنجاح';
    }
} 