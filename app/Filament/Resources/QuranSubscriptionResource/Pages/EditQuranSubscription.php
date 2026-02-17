<?php

namespace App\Filament\Resources\QuranSubscriptionResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Models\QuranSubscription;
use App\Filament\Resources\QuranSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

/**
 * @property QuranSubscription $record
 */
class EditQuranSubscription extends EditRecord
{
    protected static string $resource = QuranSubscriptionResource::class;

    public function getTitle(): string
    {
        return 'تعديل الاشتراك: '.$this->record->subscription_code;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('عرض'),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();

        // Recalculate total price if sessions or price changed
        if (isset($data['price_per_session']) || isset($data['total_sessions'])) {
            $data['total_price'] = ($data['price_per_session'] ?? $this->record->price_per_session) *
                                  ($data['total_sessions'] ?? $this->record->total_sessions);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم تحديث الاشتراك بنجاح';
    }
}
