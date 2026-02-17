<?php

namespace App\Filament\Resources\AcademicSubscriptionResource\Pages;

use Filament\Actions\CreateAction;
use App\Enums\SessionSubscriptionStatus;
use App\Filament\Resources\AcademicSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListAcademicSubscriptions extends ListRecords
{
    protected static string $resource = AcademicSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('إضافة اشتراك جديد'),
        ];
    }

    public function getTitle(): string
    {
        return 'الاشتراكات الأكاديمية';
    }

}
