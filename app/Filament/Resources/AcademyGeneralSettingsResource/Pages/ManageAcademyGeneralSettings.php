<?php

namespace App\Filament\Resources\AcademyGeneralSettingsResource\Pages;

use Exception;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use App\Filament\Resources\AcademyGeneralSettingsResource;
use App\Models\AcademicSettings;
use App\Models\Academy;
use App\Models\AcademySettings;
use App\Services\AcademyContextService;
use Filament\Actions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @property \Filament\Schemas\Schema $form
 */
class ManageAcademyGeneralSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = AcademyGeneralSettingsResource::class;

    protected string $view = 'filament.resources.academy-general-settings-resource.pages.manage-academy-general-settings';

    public ?array $data = [];

    public function getTitle(): string
    {
        return 'الإعدادات العامة للأكاديمية';
    }

    public function getSubheading(): ?string
    {
        $currentAcademy = AcademyContextService::getCurrentAcademy();
        $academyName = $currentAcademy?->name ?? 'الأكاديمية';

        return "إدارة الإعدادات العامة لـ {$academyName}";
    }

    public function mount(): void
    {
        $academyId = AcademyContextService::getCurrentAcademyId();

        if (! $academyId) {
            throw new Exception('لا يوجد سياق أكاديمية متاح. يرجى اختيار أكاديمية أولاً.');
        }

        $academy = Academy::find($academyId);

        if (! $academy) {
            throw new Exception('الأكاديمية المختارة غير موجودة.');
        }

        // Prepare form data
        $formData = $academy->toArray();

        // Load academic settings
        Log::info('Loading academic settings for academy ID', ['academy_id' => $academy->id]);
        $academicSettings = AcademicSettings::getForAcademy($academy->id);
        $formData['academic_settings'] = [
            'available_languages' => $academicSettings->available_languages ?? ['arabic', 'english'],
            'default_package_ids' => $academicSettings->default_package_ids ?? [],
        ];

        // Load meeting settings data
        $academySettings = AcademySettings::getForAcademy($academy);
        $formData['meeting_settings'] = [
            'default_preparation_minutes' => $academySettings->default_preparation_minutes ?? 10,
            'default_late_tolerance_minutes' => $academySettings->default_late_tolerance_minutes ?? 15,
            'default_buffer_minutes' => $academySettings->default_buffer_minutes ?? 5,
        ];

        // Load notification settings with defaults
        $notificationSettings = $academy->notification_settings ?? [];
        $formData['notification_settings'] = [
            'email_enabled' => $notificationSettings['email_enabled'] ?? false,
            'email_from_name' => $notificationSettings['email_from_name'] ?? $academy->name,
            'email_categories' => $notificationSettings['email_categories'] ?? [],
        ];

        // Debug: Log the loaded data
        Log::info('Loading general settings', [
            'academy_id' => $academy->id,
            'meeting_settings' => $formData['meeting_settings'],
        ]);

        $this->form->fill($formData);
    }

    public function form(Schema $schema): Schema
    {
        return AcademyGeneralSettingsResource::form($schema)
            ->statePath('data');
    }

    public function save(): void
    {
        $academy = AcademyContextService::getCurrentAcademy();
        if (! $academy) {
            Notification::make()
                ->title('خطأ')
                ->body('لم يتم العثور على الأكاديمية الحالية')
                ->danger()
                ->send();

            return;
        }

        $data = $this->form->getState();

        try {
            DB::transaction(function () use ($academy, $data) {
                // Update academy basic info, regional settings, and notification settings
                $academy->update([
                    'name' => $data['name'],
                    'name_en' => $data['name_en'] ?? null,
                    'country' => $data['country'],
                    'currency' => $data['currency'],
                    'timezone' => $data['timezone'],
                    'notification_settings' => [
                        'email_enabled' => $data['notification_settings']['email_enabled'] ?? false,
                        'email_from_name' => $data['notification_settings']['email_from_name'] ?? $academy->name,
                        'email_categories' => $data['notification_settings']['email_categories'] ?? [],
                    ],
                ]);

                // Update academic settings
                Log::info('Saving academic settings for academy ID', ['academy_id' => $academy->id]);
                $academicSettings = AcademicSettings::getForAcademy($academy->id);
                $academicSettings->update([
                    'available_languages' => $data['academic_settings']['available_languages'] ?? [],
                    'default_package_ids' => $data['academic_settings']['default_package_ids'] ?? [],
                ]);

                // Update meeting settings
                $academySettings = AcademySettings::getForAcademy($academy);

                // Prepare the update data
                $meetingUpdate = [
                    'default_preparation_minutes' => $data['meeting_settings']['default_preparation_minutes'] ?? 10,
                    'default_late_tolerance_minutes' => $data['meeting_settings']['default_late_tolerance_minutes'] ?? 15,
                    'default_buffer_minutes' => $data['meeting_settings']['default_buffer_minutes'] ?? 5,
                ];

                // Debug: Log the data being saved
                Log::info('Saving meeting settings', [
                    'academy_id' => $academy->id,
                    'data' => $meetingUpdate,
                ]);

                $academySettings->update($meetingUpdate);
            });

            Notification::make()
                ->title('تم الحفظ بنجاح')
                ->body('تم حفظ الإعدادات العامة بنجاح')
                ->success()
                ->send();

        } catch (Exception $e) {
            Log::error('Error saving general settings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data,
            ]);

            Notification::make()
                ->title('خطأ في الحفظ')
                ->body('حدث خطأ أثناء حفظ الإعدادات: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('حفظ الإعدادات')
                ->action('save')
                ->keyBindings(['mod+s'])
                ->color('success')
                ->icon('heroicon-o-check'),
        ];
    }
}
