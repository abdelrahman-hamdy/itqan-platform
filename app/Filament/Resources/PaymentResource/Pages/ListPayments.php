<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use Filament\Resources\Pages\Page;
use Filament\Actions;

class ListPayments extends Page
{
    protected static string $resource = PaymentResource::class;

    protected static string $view = 'filament.pages.coming-soon';

    protected static ?string $title = 'المدفوعات';

    public function getViewData(): array
    {
        return [
            'title' => 'نظام المدفوعات',
            'description' => 'نعمل حالياً على تطوير نظام متكامل لإدارة المدفوعات يتضمن:',
            'features' => [
                'ربط بوابات الدفع الإلكتروني (مدى، فيزا، ماستركارد)',
                'إدارة الفواتير والإيصالات',
                'تتبع المدفوعات والمستحقات',
                'تقارير مالية شاملة',
                'نظام تذكيرات للمدفوعات المتأخرة',
            ],
            'icon' => 'heroicon-o-banknotes',
        ];
    }
}
