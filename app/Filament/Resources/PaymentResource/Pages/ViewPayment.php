<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Pages\BaseViewRecord as ViewRecord;
use App\Filament\Resources\PaymentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
