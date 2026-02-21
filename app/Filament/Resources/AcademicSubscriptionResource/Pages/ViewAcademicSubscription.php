<?php

namespace App\Filament\Resources\AcademicSubscriptionResource\Pages;

use Filament\Actions\EditAction;
use App\Models\AcademicSubscription;
use App\Filament\Resources\AcademicSubscriptionResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

/**
 * @property AcademicSubscription $record
 */
class ViewAcademicSubscription extends ViewRecord
{
    protected static string $resource = AcademicSubscriptionResource::class;

    public function getTitle(): string
    {
        return 'الاشتراك الأكاديمي: '.$this->record->subscription_code;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('تعديل'),
            ...AcademicSubscriptionResource::getSubscriptionViewActions(),
        ];
    }
}
