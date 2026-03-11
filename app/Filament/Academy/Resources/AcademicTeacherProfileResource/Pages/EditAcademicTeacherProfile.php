<?php

namespace App\Filament\Academy\Resources\AcademicTeacherProfileResource\Pages;

use Filament\Actions\ViewAction;
use App\Filament\Academy\Resources\AcademicTeacherProfileResource;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;

/**
 * @property \App\Models\AcademicTeacherProfile $record
 */
class EditAcademicTeacherProfile extends EditRecord
{
    protected static string $resource = AcademicTeacherProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [ViewAction::make()];
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
