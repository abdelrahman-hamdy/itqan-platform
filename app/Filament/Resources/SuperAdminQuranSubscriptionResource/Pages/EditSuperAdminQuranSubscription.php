<?php

namespace App\Filament\Resources\SuperAdminQuranSubscriptionResource\Pages;

use App\Filament\Resources\SuperAdminQuranSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSuperAdminQuranSubscription extends EditRecord
{
    protected static string $resource = SuperAdminQuranSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}