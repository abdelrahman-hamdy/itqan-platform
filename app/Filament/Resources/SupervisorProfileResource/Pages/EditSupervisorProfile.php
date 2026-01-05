<?php

namespace App\Filament\Resources\SupervisorProfileResource\Pages;

use App\Filament\Resources\SupervisorProfileResource;
use App\Models\User;
use App\Models\SupervisorResponsibility;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditSupervisorProfile extends EditRecord
{
    protected static string $resource = SupervisorProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Load existing responsibility IDs into the form.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;

        // Load existing teacher IDs for each type
        $data['quran_teacher_ids'] = $record->getAssignedQuranTeacherIds();
        $data['academic_teacher_ids'] = $record->getAssignedAcademicTeacherIds();
        // Interactive courses are derived from academic teachers, no need to load separately

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
}
