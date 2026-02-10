<?php

namespace App\Filament\Resources\ParentProfileResource\Pages;

use App\Filament\Resources\ParentProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * @property \App\Models\ParentProfile $record
 */
class EditParentProfile extends EditRecord
{
    protected static string $resource = ParentProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Load user's active_status into the form.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['user_active_status'] = $this->record->user?->active_status ?? true;

        return $data;
    }

    /**
     * Update user's active_status after save.
     */
    protected function afterSave(): void
    {
        if ($this->record->user && isset($this->data['user_active_status'])) {
            $this->record->user->update([
                'active_status' => $this->data['user_active_status'],
            ]);
        }
    }
}
