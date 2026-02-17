<?php

namespace App\Filament\Resources\QuranPackageResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Models\QuranPackage;
use App\Filament\Resources\QuranPackageResource;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;
use Illuminate\Support\Facades\Auth;

/**
 * @property QuranPackage $record
 */
class EditQuranPackage extends EditRecord
{
    protected static string $resource = QuranPackageResource::class;

    public function getTitle(): string
    {
        return 'تعديل باقة القرآن: '.$this->record->name;
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
        return 'تم تحديث باقة القرآن بنجاح';
    }
}
