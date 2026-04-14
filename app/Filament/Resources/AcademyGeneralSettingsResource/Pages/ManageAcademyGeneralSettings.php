<?php

namespace App\Filament\Resources\AcademyGeneralSettingsResource\Pages;

use App\Filament\Resources\AcademyGeneralSettingsResource;
use App\Models\AcademicSettings;
use App\Models\Academy;
use App\Models\AcademySettings;
use App\Services\AcademyContextService;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @property \Filament\Schemas\Schema $form
 */
class ManageAcademyGeneralSettings extends Page implements HasForms
{
    use InteractsWithForms;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

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
            Notification::make()
                ->title('يرجى اختيار أكاديمية')
                ->body('لا يمكن عرض الإعدادات العامة بدون اختيار أكاديمية محددة. يرجى اختيار أكاديمية من القائمة أولاً.')
                ->warning()
                ->send();

            $this->redirect(url('/admin'));

            return;
        }

        $academy = Academy::find($academyId);

        if (! $academy) {
            Notification::make()
                ->title('خطأ')
                ->body('الأكاديمية المختارة غير موجودة.')
                ->danger()
                ->send();

            $this->redirect(url('/admin'));

            return;
        }

        // Prepare form data
        $formData = $academy->toArray();

        // Load academic settings — merge Spatie model values into the Academy JSON column data
        // so that all keys (default_individual_session_prices, auto_approve_reviews, etc.) are preserved
        Log::info('Loading academic settings for academy ID', ['academy_id' => $academy->id]);
        $academicSettings = AcademicSettings::getForAcademy($academy->id);
        $formData['academic_settings'] = array_merge($formData['academic_settings'] ?? [], [
            'available_languages' => $academicSettings->available_languages ?? ['arabic', 'english'],
            'default_package_ids' => $academicSettings->default_package_ids ?? [],
        ]);

        // Load meeting settings data
        $academySettings = AcademySettings::getForAcademy($academy);
        $formData['meeting_settings'] = [
            'default_preparation_minutes' => $academySettings->default_preparation_minutes ?? 10,
            'default_buffer_minutes' => $academySettings->default_buffer_minutes ?? 5,
            'teacher_reschedule_deadline_hours' => $academySettings->teacher_reschedule_deadline_hours ?? 24,
        ];

        // Form uses semantic names; DB columns keep their legacy names.
        $formData['attendance_settings'] = [
            'student_full_attendance_percent' => $academySettings->default_attendance_threshold_percentage ?? 80,
            'student_partial_attendance_percent' => $academySettings->student_minimum_presence_percent ?? 50,
            'teacher_full_attendance_percent' => $academySettings->teacher_full_attendance_percent ?? 90,
            'teacher_partial_attendance_percent' => $academySettings->teacher_partial_attendance_percent ?? 50,
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
                    'teacher_earnings_currency' => $data['teacher_earnings_currency'] ?? null,
                    'timezone' => $data['timezone'],
                    'notification_settings' => [
                        'email_enabled' => $data['notification_settings']['email_enabled'] ?? false,
                        'email_from_name' => $data['notification_settings']['email_from_name'] ?? $academy->name,
                        'email_categories' => $data['notification_settings']['email_categories'] ?? [],
                    ],
                    'academic_settings' => array_merge($academy->academic_settings ?? [], [
                        'available_languages' => $data['academic_settings']['available_languages'] ?? [],
                        'default_package_ids' => $data['academic_settings']['default_package_ids'] ?? [],
                        'default_individual_session_prices' => $data['academic_settings']['default_individual_session_prices'] ?? [],
                        'auto_approve_reviews' => $data['academic_settings']['auto_approve_reviews'] ?? true,
                    ]),
                    'quran_settings' => array_merge($academy->quran_settings ?? [], [
                        'available_languages' => $data['quran_settings']['available_languages'] ?? [],
                        'default_package_ids' => $data['quran_settings']['default_package_ids'] ?? [],
                        'default_individual_session_prices' => $data['quran_settings']['default_individual_session_prices'] ?? [],
                        'default_group_session_prices' => $data['quran_settings']['default_group_session_prices'] ?? [],
                    ]),
                ]);

                // Update academic settings (Spatie settings model)
                Log::info('Saving academic settings for academy ID', ['academy_id' => $academy->id]);
                $academicSettings = AcademicSettings::getForAcademy($academy->id);
                $academicSettings->update([
                    'available_languages' => $data['academic_settings']['available_languages'] ?? [],
                    'default_package_ids' => $data['academic_settings']['default_package_ids'] ?? [],
                ]);

                $academySettings = AcademySettings::getForAcademy($academy);

                $attendanceForm = $data['attendance_settings'] ?? [];
                $studentFull = (int) ($attendanceForm['student_full_attendance_percent'] ?? 80);
                $studentPartial = (int) ($attendanceForm['student_partial_attendance_percent'] ?? 50);
                $teacherFull = (int) ($attendanceForm['teacher_full_attendance_percent'] ?? 90);
                $teacherPartial = (int) ($attendanceForm['teacher_partial_attendance_percent'] ?? 50);

                if ($studentPartial >= $studentFull || $teacherPartial >= $teacherFull) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'attendance_settings.student_partial_attendance_percent' => __('settings.attendance_partial_lte_full'),
                    ]);
                }

                // DB column names are legacy; form uses semantic names.
                $meetingUpdate = [
                    'default_preparation_minutes' => $data['meeting_settings']['default_preparation_minutes'] ?? 10,
                    'default_buffer_minutes' => $data['meeting_settings']['default_buffer_minutes'] ?? 5,
                    'teacher_reschedule_deadline_hours' => $data['meeting_settings']['teacher_reschedule_deadline_hours'] ?? 24,
                    'default_attendance_threshold_percentage' => $studentFull,
                    'student_minimum_presence_percent' => $studentPartial,
                    'teacher_full_attendance_percent' => $teacherFull,
                    'teacher_partial_attendance_percent' => $teacherPartial,
                ];

                Log::info('Saving meeting + attendance settings', [
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
                ->color('primary'),
        ];
    }
}
