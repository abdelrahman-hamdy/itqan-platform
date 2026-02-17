<?php

namespace App\Filament\Academy\Resources\QuranSubscriptionResource\Pages;

use Filament\Actions\ViewAction;
use App\Filament\Academy\Resources\QuranSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuranSubscription extends EditRecord
{
    protected static string $resource = QuranSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
