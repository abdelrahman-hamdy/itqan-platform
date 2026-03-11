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

        if ($this->record->user_id && isset($data['first_name'])) {
            $this->record->user->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
            ]);
        }
    }
}
