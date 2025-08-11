<?php

namespace App\Filament\Teacher\Resources\QuranSessionResource\Pages;

use App\Filament\Teacher\Resources\QuranSessionResource;
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
                ->label('حذف')
                ->after(function () {
                    // Update session counts for individual circles if needed
                    $record = $this->getRecord();
                    if ($record->individualCircle) {
                        $record->individualCircle->updateSessionCounts();
                    }
                }),
        ];
    }
}