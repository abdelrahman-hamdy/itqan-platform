<?php

namespace App\Filament\Resources\AcademicPackageResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\AcademicPackageResource;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;

class EditAcademicPackage extends EditRecord
{
    protected static string $resource = AcademicPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'تعديل الباقة الأكاديمية';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
