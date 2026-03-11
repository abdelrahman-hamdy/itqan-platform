<?php

namespace App\Filament\Resources\QuranTeacherProfileResource\Pages;

use Filament\Actions\DeleteAction;
use App\Models\QuranTeacherProfile;
use App\Filament\Resources\QuranTeacherProfileResource;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;

/**
 * @property QuranTeacherProfile $record
 */
class EditQuranTeacherProfile extends EditRecord
{
    protected static string $resource = QuranTeacherProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->user) {
            $data['first_name'] = $this->record->user->first_name;
            $data['last_name'] = $this->record->user->last_name;
            $data['email'] = $this->record->user->email;
            $data['phone'] = $this->record->user->phone;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $data = $this->form->getRawState();

        if ($this->record->user_id && $this->record->user) {
            $userData = [
                'first_name' => $data['first_name'] ?? $this->record->user->first_name,
                'last_name' => $data['last_name'] ?? $this->record->user->last_name,
                'email' => $data['email'] ?? $this->record->user->email,
                'phone' => $data['phone'] ?? null,
            ];

            if (! empty($data['password'])) {
                $userData['password'] = $data['password'];
            }

            if (array_key_exists('user_active_status', $data)) {
                $userData['active_status'] = (bool) $data['user_active_status'];
            }

            $this->record->user->update($userData);
        }
    }
}
