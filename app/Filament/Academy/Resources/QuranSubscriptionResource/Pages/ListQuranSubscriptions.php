<?php

namespace App\Filament\Academy\Resources\QuranSubscriptionResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Academy\Resources\QuranSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuranSubscriptions extends ListRecords
{
    protected static string $resource = QuranSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
