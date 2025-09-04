<?php

namespace App\Filament\Resources\AcademicSubscriptionResource\Pages;

use App\Filament\Resources\AcademicSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAcademicSubscriptions extends ListRecords
{
    protected static string $resource = AcademicSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
