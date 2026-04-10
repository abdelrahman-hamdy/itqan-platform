<?php

namespace App\Filament\Resources\PaymentSettingsResource\Pages;

use App\Filament\Pages\BaseEditRecord as EditRecord;
use App\Filament\Resources\PaymentSettingsResource;
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
        if (array_key_exists('payment_settings', $data)) {
            $existingSettings = $record->payment_settings ?? [];
            $newSettings = is_array($data['payment_settings']) ? $data['payment_settings'] : [];

            // Custom deep merge: associative arrays recurse (to preserve hidden credential
            // fields like paymob.* that are undehydrated when use_global=true), but list
            // arrays like enabled_gateways are replaced wholesale so unchecked items are
            // actually removed. array_replace_recursive is unsafe here because it iterates
            // list arrays by numeric index and leaves trailing items untouched.
            $record->payment_settings = $this->mergePaymentSettings($existingSettings, $newSettings);

            unset($data['payment_settings']);
        }

        $record->fill($data);
        $record->save();

        return $record;
    }

    /**
     * Deep-merge payment settings while replacing list arrays entirely.
     *
     * Associative arrays are merged recursively so hidden-field values survive
     * across saves. List arrays (numeric, sequential keys) are replaced whole.
     */
    private function mergePaymentSettings(array $existing, array $incoming): array
    {
        foreach ($incoming as $key => $value) {
            if (
                is_array($value)
                && isset($existing[$key])
                && is_array($existing[$key])
                && ! array_is_list($value)
                && ! array_is_list($existing[$key])
            ) {
                $existing[$key] = $this->mergePaymentSettings($existing[$key], $value);
            } else {
                $existing[$key] = $value;
            }
        }

        return $existing;
    }
}
