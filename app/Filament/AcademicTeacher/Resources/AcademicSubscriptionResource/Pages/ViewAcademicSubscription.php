<?php

namespace App\Filament\AcademicTeacher\Resources\AcademicSubscriptionResource\Pages;

use App\Filament\AcademicTeacher\Resources\AcademicSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAcademicSubscription extends ViewRecord
{
    protected static string $resource = AcademicSubscriptionResource::class;

    public function getTitle(): string
    {
        return 'الاشتراك الأكاديمي: ' . $this->record->subscription_code;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
        ];
    }
}
