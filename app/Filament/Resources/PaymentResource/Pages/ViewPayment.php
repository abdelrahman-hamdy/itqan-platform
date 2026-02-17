<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\PaymentResource;
use Filament\Actions;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
