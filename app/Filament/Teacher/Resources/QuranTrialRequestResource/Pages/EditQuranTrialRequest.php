<?php

namespace App\Filament\Teacher\Resources\QuranTrialRequestResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Teacher\Resources\QuranTrialRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuranTrialRequest extends EditRecord
{
    protected static string $resource = QuranTrialRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('عرض'),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
