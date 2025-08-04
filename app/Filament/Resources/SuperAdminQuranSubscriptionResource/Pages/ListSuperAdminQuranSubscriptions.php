<?php

namespace App\Filament\Resources\SuperAdminQuranSubscriptionResource\Pages;

use App\Filament\Resources\SuperAdminQuranSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSuperAdminQuranSubscriptions extends ListRecords
{
    protected static string $resource = SuperAdminQuranSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}