<?php

namespace App\Filament\Resources\QuranCircleResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Models\QuranCircle;
use App\Filament\Resources\QuranCircleResource;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;
use Illuminate\Support\Facades\Auth;

/**
 * @property QuranCircle $record
 */
class EditQuranCircle extends EditRecord
{
    protected static string $resource = QuranCircleResource::class;

    public function getTitle(): string
    {
        return 'تعديل دائرة القرآن: '.$this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('عرض'),
            DeleteAction::make()
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
