<?php

namespace App\Filament\Resources\QuranTeacherProfileResource\Pages;

use App\Filament\Resources\QuranTeacherProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuranTeacherProfile extends EditRecord
{
    protected static string $resource = QuranTeacherProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Hydrate user data fields from the linked User record
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->user) {
            $data['user_first_name'] = $this->record->user->first_name;
            $data['user_last_name'] = $this->record->user->last_name;
            $data['user_email'] = $this->record->user->email;
            $data['user_phone'] = $this->record->user->phone;
        }

        return $data;
    }

    /**
     * Save user data fields to the linked User record
     */
    protected function afterSave(): void
    {
        $data = $this->form->getRawState();

        if ($this->record->user_id && isset($data['user_first_name'])) {
            $this->record->user->update([
                'first_name' => $data['user_first_name'],
                'last_name' => $data['user_last_name'],
                'email' => $data['user_email'],
                'phone' => $data['user_phone'] ?? null,
            ]);
        }
    }
}
