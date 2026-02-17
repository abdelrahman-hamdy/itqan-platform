<?php

namespace App\Filament\Academy\Resources\PaymentResource\Pages;

use App\Filament\Academy\Resources\PaymentResource;
use App\Filament\Pages\BaseCreateRecord as CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-assign current academy
        $data['academy_id'] = Auth::user()->academy_id;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
