<?php

namespace App\Filament\Resources\SupervisorProfileResource\Pages;

use Filament\Actions\DeleteAction;
use App\Models\SupervisorProfile;
use App\Filament\Resources\SupervisorProfileResource;
use App\Models\SupervisorResponsibility;
use App\Models\User;
use App\Filament\Pages\BaseEditRecord as EditRecord;

/**
 * @property SupervisorProfile $record
 */
class EditSupervisorProfile extends EditRecord
{
    protected static string $resource = SupervisorProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Load existing responsibility IDs and user active status into the form.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;

        // Load existing teacher IDs for each type
        $data['quran_teacher_ids'] = $record->getAssignedQuranTeacherIds();
        $data['academic_teacher_ids'] = $record->getAssignedAcademicTeacherIds();
        // Interactive courses are derived from academic teachers, no need to load separately

        // Load user's active_status
        $data['user_active_status'] = $record->user?->active_status ?? true;

        return $data;
    }

    /**
     * Override the save method to ensure responsibilities are synced.
     * This is called when the form is submitted.
     */
    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        // Call parent save first
        parent::save($shouldRedirect, $shouldSendSavedNotification);

        // Then sync responsibilities
        $this->syncResponsibilities();
    }

    /**
     * Sync all responsibility types with the pivot table.
     * Uses manual sync to avoid issues with constrained relationships.
     */
    protected function syncResponsibilities(): void
    {
        // Get form data directly from Livewire component data
        $formData = $this->data;
        $record = $this->record;

        $quranTeacherIds = $formData['quran_teacher_ids'] ?? [];
        $academicTeacherIds = $formData['academic_teacher_ids'] ?? [];

        // Ensure arrays are properly formatted (filter out empty values)
        $quranTeacherIds = array_filter(is_array($quranTeacherIds) ? $quranTeacherIds : []);
        $academicTeacherIds = array_filter(is_array($academicTeacherIds) ? $academicTeacherIds : []);

        // Delete all existing User-type responsibilities for this supervisor
        SupervisorResponsibility::where('supervisor_profile_id', $record->id)
            ->where('responsable_type', User::class)
            ->delete();

        // Add Quran teachers
        foreach ($quranTeacherIds as $teacherId) {
            SupervisorResponsibility::create([
                'supervisor_profile_id' => $record->id,
                'responsable_type' => User::class,
                'responsable_id' => $teacherId,
            ]);
        }

        // Add Academic teachers
        foreach ($academicTeacherIds as $teacherId) {
            SupervisorResponsibility::create([
                'supervisor_profile_id' => $record->id,
                'responsable_type' => User::class,
                'responsable_id' => $teacherId,
            ]);
        }

        // Note: Interactive courses are derived from academic teachers, no separate syncing needed
    }

    /**
     * Strip password fields before saving to SupervisorProfile model.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['password'], $data['password_confirmation']);

        return $data;
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
            $user->password = $this->data['password']; // 'hashed' cast auto-hashes
            $dirty = true;
        }

        if ($dirty) {
            $user->save();
        }
    }
}
