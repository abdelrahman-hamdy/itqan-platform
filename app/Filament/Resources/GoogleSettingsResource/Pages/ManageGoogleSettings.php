<?php

namespace App\Filament\Resources\GoogleSettingsResource\Pages;

use App\Filament\Resources\GoogleSettingsResource;
use App\Models\AcademyGoogleSettings;
use App\Services\AcademyContextService;
use Filament\Actions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ManageGoogleSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = GoogleSettingsResource::class;

    protected static string $view = 'filament.resources.google-settings-resource.pages.manage-google-settings';

    public ?array $data = [];

    public function getTitle(): string
    {
        return 'إعدادات Google Meet';
    }

    public function getSubheading(): ?string
    {
        $currentAcademy = AcademyContextService::getCurrentAcademy();
        $academyName = $currentAcademy?->name ?? 'الأكاديمية';
        return "إدارة إعدادات Google Meet لـ {$academyName} - هذه الإعدادات تُستخدم كحساب احتياطي عند عدم ربط المعلمين حساباتهم الشخصية";
    }

    public function mount(): void
    {
        $academy = AcademyContextService::getCurrentAcademy();
        
        if (!$academy) {
            throw new \Exception('No academy context available. Please select an academy first.');
        }
        
        // Get or create settings for current academy
        $settings = AcademyGoogleSettings::forAcademy($academy);
        
        // Prepare data with proper array handling
        $data = $settings->toArray();
        
        // Ensure array fields are properly formatted
        if (isset($data['oauth_scopes'])) {
            if (is_string($data['oauth_scopes'])) {
                $data['oauth_scopes'] = explode(',', $data['oauth_scopes']);
            } elseif (!is_array($data['oauth_scopes'])) {
                $data['oauth_scopes'] = $settings->getDefaultOAuthScopes();
            }
        } else {
            $data['oauth_scopes'] = $settings->getDefaultOAuthScopes();
        }
        
        if (isset($data['reminder_times'])) {
            if (is_string($data['reminder_times'])) {
                $data['reminder_times'] = explode(',', $data['reminder_times']);
            } elseif (!is_array($data['reminder_times'])) {
                $data['reminder_times'] = $settings->getDefaultReminderTimes();
            }
        } else {
            $data['reminder_times'] = $settings->getDefaultReminderTimes();
        }
        
        // Remove service account key from form data - it will be handled by the Placeholder component
        unset($data['google_service_account_key']);
        
        // Fill the form with properly formatted data
        $this->form->fill($data);
    }

    public function form(Form $form): Form
    {
        return GoogleSettingsResource::form($form)
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();
            
            // Debug logging
            Log::info('Attempting to save Google settings', [
                'academy_context' => AcademyContextService::getCurrentAcademy()?->id,
                'data_keys' => array_keys($data),
                'has_service_account_key' => isset($data['google_service_account_key']),
                'service_account_key_type' => isset($data['google_service_account_key']) ? gettype($data['google_service_account_key']) : null,
            ]);
            
            $academy = AcademyContextService::getCurrentAcademy();
            
            if (!$academy) {
                throw new \Exception('No academy context available. Please select an academy first.');
            }
            
            $settings = AcademyGoogleSettings::forAcademy($academy);
            
            // Handle service account key file upload
            if (isset($data['google_service_account_key']) && !empty($data['google_service_account_key'])) {
                $uploadedFile = $data['google_service_account_key'];
                
                // Handle UploadedFile object (new upload)
                if ($uploadedFile instanceof \Illuminate\Http\UploadedFile) {
                    $fileContent = file_get_contents($uploadedFile->getRealPath());
                    
                    // Validate JSON format
                    $jsonData = json_decode($fileContent, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new \Exception('Service account key file must be valid JSON');
                    }
                    
                    // Store the JSON content (not the file)
                    $data['google_service_account_key'] = $fileContent;
                } else {
                    // Remove any non-UploadedFile data
                    unset($data['google_service_account_key']);
                }
            } else {
                // Don't update the field if no new file was uploaded
                unset($data['google_service_account_key']);
            }
            
            // Encrypt sensitive fields before saving
            if (!empty($data['google_client_secret'])) {
                $data['google_client_secret'] = encrypt($data['google_client_secret']);
            }
            
            if (!empty($data['fallback_account_credentials'])) {
                $data['fallback_account_credentials'] = encrypt($data['fallback_account_credentials']);
            }
            
            // Note: google_service_account_key will be encrypted by the model mutator
            
            // Mark as configured if basic fields are provided
            $basicFieldsProvided = !empty($data['google_project_id']) 
                && !empty($data['google_client_id']) 
                && !empty($data['google_client_secret']);
                
            if ($basicFieldsProvided) {
                $data['is_configured'] = true;
                $data['configured_at'] = now();
                $data['configured_by'] = Auth::id();
            }

            $settings->update($data);

            Log::info('Google settings saved successfully', [
                'academy_id' => $academy->id,
                'settings_id' => $settings->id,
                'has_service_account_key' => !empty($settings->google_service_account_key),
            ]);

            Notification::make()
                ->title('تم حفظ الإعدادات بنجاح')
                ->body('تم حفظ إعدادات Google Meet بنجاح')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error('Failed to save Google settings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'academy_id' => AcademyContextService::getCurrentAcademy()?->id,
            ]);

            Notification::make()
                ->title('حدث خطأ أثناء حفظ الإعدادات')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('حفظ الإعدادات')
                ->submit('save'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test_connection')
                ->label('اختبار الاتصال')
                ->icon('heroicon-o-signal')
                ->color('info')
                ->action(function () {
                    $academy = AcademyContextService::getCurrentAcademy();
                    
                    if (!$academy) {
                        Notification::make()
                            ->title('خطأ')
                            ->body('لم يتم تحديد الأكاديمية')
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    $settings = AcademyGoogleSettings::forAcademy($academy);
                    
                    if (!$settings->is_configured) {
                        Notification::make()
                            ->title('إعدادات غير مكتملة')
                            ->body('يرجى إكمال الإعدادات أولاً قبل اختبار الاتصال')
                            ->warning()
                            ->send();
                        return;
                    }
                    
                    $result = $settings->testConnection();
                    
                    if ($result['success']) {
                        Notification::make()
                            ->title('نجح الاختبار')
                            ->body($result['message'])
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('فشل الاختبار')
                            ->body($result['message'])
                            ->danger()
                            ->send();
                    }
                }),
                
            Action::make('setup_guide')
                ->label('دليل الإعداد')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->action(function () {
                    Notification::make()
                        ->title('دليل الإعداد')
                        ->body('يمكنك العثور على دليل الإعداد التفصيلي في المستندات')
                        ->info()
                        ->send();
                }),
                
            Action::make('reset_settings')
                ->label('إعادة تعيين')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('إعادة تعيين الإعدادات')
                ->modalDescription('هذا سيحذف جميع إعدادات Google وإعادتها للحالة الافتراضية. هذا الإجراء لا يمكن التراجع عنه.')
                ->modalSubmitActionLabel('نعم، إعادة تعيين')
                ->action(function () {
                    $academy = AcademyContextService::getCurrentAcademy();
                    
                    if (!$academy) {
                        Notification::make()
                            ->title('خطأ')
                            ->body('لم يتم تحديد الأكاديمية')
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    $settings = AcademyGoogleSettings::forAcademy($academy);
                    
                    // Reset to defaults
                    $settings->update([
                        'google_project_id' => null,
                        'google_client_id' => null,
                        'google_client_secret' => null,
                        'google_service_account_key' => null,
                        'oauth_redirect_uri' => null,
                        'oauth_scopes' => $settings->getDefaultOAuthScopes(),
                        'fallback_account_email' => null,
                        'fallback_account_credentials' => null,
                        'fallback_account_enabled' => false,
                        'fallback_daily_limit' => 100,
                        'auto_create_meetings' => true,
                        'meeting_prep_minutes' => 60,
                        'auto_record_sessions' => false,
                        'default_session_duration' => 60,
                        'notify_on_teacher_disconnect' => true,
                        'send_meeting_reminders' => true,
                        'reminder_times' => $settings->getDefaultReminderTimes(),
                        'is_configured' => false,
                        'last_tested_at' => null,
                        'last_test_result' => null,
                    ]);
                    
                    Notification::make()
                        ->title('تم إعادة التعيين')
                        ->body('تم إعادة تعيين جميع إعدادات Google بنجاح')
                        ->success()
                        ->send();
                }),
        ];
    }
} 