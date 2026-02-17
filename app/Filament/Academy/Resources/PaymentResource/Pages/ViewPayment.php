<?php

namespace App\Filament\Academy\Resources\PaymentResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Academy\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
        ];
    }
}
