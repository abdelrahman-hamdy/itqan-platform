<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use Filament\Resources\Pages\Page;

class ListPayments extends Page
{
    protected static string $resource = PaymentResource::class;

    protected static string $view = 'filament.pages.coming-soon';

    protected static ?string $title = 'المدفوعات';
}
