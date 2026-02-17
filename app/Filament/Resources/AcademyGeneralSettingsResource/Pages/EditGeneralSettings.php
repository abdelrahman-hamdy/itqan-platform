<?php

namespace App\Filament\Resources\AcademyGeneralSettingsResource\Pages;

use Filament\Actions\ViewAction;
use App\Filament\Resources\AcademyGeneralSettingsResource;
use App\Models\Academy;
use App\Models\AcademySettings;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;

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

        // Add meeting settings to the form data
        $data['meeting_settings'] = [
            'default_preparation_minutes' => $academySettings->default_preparation_minutes ?? 10,
            'default_late_tolerance_minutes' => $academySettings->default_late_tolerance_minutes ?? 15,
            'default_buffer_minutes' => $academySettings->default_buffer_minutes ?? 5,
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
        // Extract meeting settings and academic settings
        $meetingSettings = $data['meeting_settings'] ?? [];
        $academicSettings = $data['academic_settings'] ?? [];

        // Remove these from the main data array so they don't try to save to Academy model
        unset($data['meeting_settings']);

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

        // Save meeting settings to AcademySettings model
        if (! empty($meetingSettings)) {
            $academySettingsModel = AcademySettings::firstOrCreate(
                ['academy_id' => $this->record->id],
                []
            );

            $academySettingsModel->update([
                'default_preparation_minutes' => $meetingSettings['default_preparation_minutes'] ?? 10,
                'default_late_tolerance_minutes' => $meetingSettings['default_late_tolerance_minutes'] ?? 15,
                'default_buffer_minutes' => $meetingSettings['default_buffer_minutes'] ?? 5,
            ]);
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
