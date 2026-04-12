<?php

namespace App\Filament\Resources\AcademyGeneralSettingsResource\Pages;

use App\Filament\Pages\BaseEditRecord as EditRecord;
use App\Filament\Resources\AcademyGeneralSettingsResource;
use App\Models\Academy;
use App\Models\AcademySettings;
use Filament\Actions\ViewAction;

/**
 * @property Academy $record
 */
class EditGeneralSettings extends EditRecord
{
    protected static string $resource = AcademyGeneralSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Get or create AcademySettings for this academy
        $academySettings = AcademySettings::firstOrCreate(
            ['academy_id' => $this->record->id],
            []
        );

        // Meeting settings (preparation + buffer minutes)
        $data['meeting_settings'] = [
            'default_preparation_minutes' => $academySettings->default_preparation_minutes ?? 10,
            'default_buffer_minutes' => $academySettings->default_buffer_minutes ?? 5,
        ];

        // Form uses semantic names; DB columns kept their legacy names.
        $data['attendance_settings'] = [
            'student_full_attendance_percent' => $academySettings->default_attendance_threshold_percentage ?? 80,
            'student_partial_attendance_percent' => $academySettings->student_minimum_presence_percent ?? 50,
            'teacher_full_attendance_percent' => $academySettings->teacher_full_attendance_percent ?? 90,
            'teacher_partial_attendance_percent' => $academySettings->teacher_partial_attendance_percent ?? 50,
        ];

        // Add academic settings if they exist in the Academy model
        if (isset($this->record->academic_settings)) {
            $data['academic_settings'] = $this->record->academic_settings;
        }

        // Add quran settings if they exist in the Academy model
        if (isset($this->record->quran_settings)) {
            $data['quran_settings'] = $this->record->quran_settings;
        }

        // Add notification settings with defaults
        $notificationSettings = $this->record->notification_settings ?? [];
        $data['notification_settings'] = [
            'email_enabled' => $notificationSettings['email_enabled'] ?? false,
            'email_from_name' => $notificationSettings['email_from_name'] ?? $this->record->name,
            'email_categories' => $notificationSettings['email_categories'] ?? [],
        ];

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Extract meeting, attendance, and academic settings slots
        $meetingSettings = $data['meeting_settings'] ?? [];
        $attendanceSettings = $data['attendance_settings'] ?? [];
        $academicSettings = $data['academic_settings'] ?? [];

        // Remove the form-only slots so they don't try to save to Academy model
        unset($data['meeting_settings'], $data['attendance_settings']);

        // Keep academic_settings if it's a JSON column on Academy model
        if (is_array($academicSettings)) {
            $data['academic_settings'] = $academicSettings;
        }

        // Keep quran_settings if it's a JSON column on Academy model
        $quranSettings = $data['quran_settings'] ?? [];
        if (is_array($quranSettings)) {
            $data['quran_settings'] = $quranSettings;
        }

        // Build notification_settings from form data
        $notificationSettings = $data['notification_settings'] ?? [];
        $data['notification_settings'] = [
            'email_enabled' => $notificationSettings['email_enabled'] ?? false,
            'email_from_name' => $notificationSettings['email_from_name'] ?? $this->record->name,
            'email_categories' => $notificationSettings['email_categories'] ?? [],
        ];

        // Validate attendance thresholds: partial must be ≤ full for both roles.
        $studentFull = (float) ($attendanceSettings['student_full_attendance_percent'] ?? 80);
        $studentPartial = (float) ($attendanceSettings['student_partial_attendance_percent'] ?? 50);
        $teacherFull = (float) ($attendanceSettings['teacher_full_attendance_percent'] ?? 90);
        $teacherPartial = (float) ($attendanceSettings['teacher_partial_attendance_percent'] ?? 50);

        if ($studentPartial > $studentFull || $teacherPartial > $teacherFull) {
            \Filament\Notifications\Notification::make()
                ->title(__('settings.attendance_partial_lte_full'))
                ->danger()
                ->send();

            $this->halt();
        }

        // Save meeting + attendance settings to AcademySettings model
        $academySettingsModel = AcademySettings::firstOrCreate(
            ['academy_id' => $this->record->id],
            []
        );

        $updates = [];
        if (! empty($meetingSettings)) {
            $updates['default_preparation_minutes'] = $meetingSettings['default_preparation_minutes'] ?? 10;
            $updates['default_buffer_minutes'] = $meetingSettings['default_buffer_minutes'] ?? 5;
        }
        if (! empty($attendanceSettings)) {
            // Semantic names from the form map back to the legacy DB column names.
            $updates['default_attendance_threshold_percentage'] = $studentFull;
            $updates['student_minimum_presence_percent'] = $studentPartial;
            $updates['teacher_full_attendance_percent'] = $teacherFull;
            $updates['teacher_partial_attendance_percent'] = $teacherPartial;
        }

        if (! empty($updates)) {
            $academySettingsModel->update($updates);
        }

        return $data;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم حفظ الإعدادات العامة بنجاح';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
