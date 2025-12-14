<?php

namespace App\Filament\Resources\QuranSessionResource\Pages;

use App\Filament\Resources\QuranSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuranSession extends EditRecord
{
    protected static string $resource = QuranSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('عرض'),
            Actions\DeleteAction::make()
                ->label('حذف'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
