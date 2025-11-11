<?php

namespace App\Filament\Teacher\Resources\QuranSubscriptionResource\Pages;

use App\Filament\Teacher\Resources\QuranSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuranSubscription extends EditRecord
{
    protected static string $resource = QuranSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('عرض'),
        ];
    }
}