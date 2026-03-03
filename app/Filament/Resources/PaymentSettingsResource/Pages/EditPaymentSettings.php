<?php

namespace App\Filament\Resources\PaymentSettingsResource\Pages;

use App\Filament\Resources\PaymentSettingsResource;
use App\Filament\Pages\BaseEditRecord as EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPaymentSettings extends EditRecord
{
    protected static string $resource = PaymentSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Override to handle payment_settings which is excluded from $fillable for security.
     * We need to set it directly on the model attribute to bypass mass assignment protection.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Extract payment_settings from form data (Filament builds nested array from dot notation)
        if (isset($data['payment_settings'])) {
            $existingSettings = $record->payment_settings ?? [];
            $newSettings = $data['payment_settings'];

            // Deep merge: new settings override existing, but preserve keys not in form
            $mergedSettings = array_replace_recursive($existingSettings, $newSettings);

            // Set directly on the model attribute (bypasses $fillable)
            $record->payment_settings = $mergedSettings;

            // Remove from $data so fill() doesn't try to handle it
            unset($data['payment_settings']);
        }

        // Let Filament handle all other fillable fields normally
        $record->fill($data);
        $record->save();

        return $record;
    }
}
