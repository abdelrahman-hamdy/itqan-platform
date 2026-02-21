<?php

namespace App\Filament\Resources\QuranSubscriptionResource\Pages;

use Filament\Actions\EditAction;
use App\Models\QuranSubscription;
use App\Filament\Resources\QuranSubscriptionResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

/**
 * @property QuranSubscription $record
 */
class ViewQuranSubscription extends ViewRecord
{
    protected static string $resource = QuranSubscriptionResource::class;

    public function getTitle(): string
    {
        return 'اشتراك القرآن: '.$this->record->subscription_code;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('تعديل'),
            ...QuranSubscriptionResource::getSubscriptionViewActions(),
        ];
    }
}
