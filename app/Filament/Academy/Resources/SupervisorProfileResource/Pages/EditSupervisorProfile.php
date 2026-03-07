<?php

namespace App\Filament\Academy\Resources\SupervisorProfileResource\Pages;

use App\Filament\Academy\Resources\SupervisorProfileResource;
use App\Filament\Pages\BaseEditRecord as EditRecord;
use App\Models\SupervisorResponsibility;
use App\Models\User;
use Filament\Actions\ViewAction;

/**
 * @property \App\Models\SupervisorProfile $record
 */
class EditSupervisorProfile extends EditRecord
{
    protected static string $resource = SupervisorProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [ViewAction::make()];
    }

    /**
     * Load existing responsibility IDs and user active status into the form.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;

        $data['quran_teacher_ids'] = $record->getAssignedQuranTeacherIds();
        $data['academic_teacher_ids'] = $record->getAssignedAcademicTeacherIds();
        $data['user_active_status'] = $record->user?->active_status ?? true;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['password'], $data['password_confirmation']);

        return $data;
    }

    /**
     * Override save to sync responsibilities after saving.
     */
    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        parent::save($shouldRedirect, $shouldSendSavedNotification);

        $this->syncResponsibilities();
    }

    /**
     * Sync all responsibility types with the pivot table.
     */
    protected function syncResponsibilities(): void
    {
        $formData = $this->data;
        $record = $this->record;

        $quranTeacherIds = array_filter(is_array($formData['quran_teacher_ids'] ?? []) ? $formData['quran_teacher_ids'] : []);
        $academicTeacherIds = array_filter(is_array($formData['academic_teacher_ids'] ?? []) ? $formData['academic_teacher_ids'] : []);

        // Delete all existing User-type responsibilities for this supervisor
        SupervisorResponsibility::where('supervisor_profile_id', $record->id)
            ->where('responsable_type', User::class)
            ->delete();

        foreach ($quranTeacherIds as $teacherId) {
            SupervisorResponsibility::create([
                'supervisor_profile_id' => $record->id,
                'responsable_type' => User::class,
                'responsable_id' => $teacherId,
            ]);
        }

        foreach ($academicTeacherIds as $teacherId) {
            SupervisorResponsibility::create([
                'supervisor_profile_id' => $record->id,
                'responsable_type' => User::class,
                'responsable_id' => $teacherId,
            ]);
        }
    }

    /**
     * Update user's active_status and password after save.
     * Uses direct assignment because active_status is guarded against mass-assignment.
     */
    protected function afterSave(): void
    {
        if (! $this->record->user) {
            return;
        }

        $user = $this->record->user;
        $dirty = false;

        if (isset($this->data['user_active_status'])) {
            $user->active_status = $this->data['user_active_status'];
            $dirty = true;
        }

        if (filled($this->data['password'] ?? null)) {
            $user->password = $this->data['password'];
            $dirty = true;
        }

        if ($dirty) {
            $user->save();
        }
    }
}
