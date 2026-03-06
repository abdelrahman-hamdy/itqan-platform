<?php

namespace App\Filament\Academy\Resources\SupervisorProfileResource\Pages;

use App\Filament\Academy\Resources\SupervisorProfileResource;
use App\Filament\Pages\BaseEditRecord as EditRecord;
use Filament\Actions\ViewAction;
use Illuminate\Support\Facades\Hash;

class EditSupervisorProfile extends EditRecord
{
    protected static string $resource = SupervisorProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [ViewAction::make()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['password'], $data['password_confirmation']);

        return $data;
    }

    protected function afterSave(): void
    {
        if (! $this->record->user) {
            return;
        }

        $updates = [];

        if (isset($this->data['user_active_status'])) {
            $updates['active_status'] = $this->data['user_active_status'];
        }

        if (filled($this->data['password'] ?? null)) {
            $updates['password'] = Hash::make($this->data['password']);
        }

        if ($updates) {
            $this->record->user->update($updates);
        }
    }
}
