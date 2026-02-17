<?php

namespace App\Filament\Teacher\Resources\QuranCircleResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Teacher\Resources\QuranCircleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuranCircle extends EditRecord
{
    protected static string $resource = QuranCircleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('عرض'),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
