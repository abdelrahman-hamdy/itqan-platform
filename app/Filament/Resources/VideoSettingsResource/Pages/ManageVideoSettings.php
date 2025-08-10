<?php

namespace App\Filament\Resources\VideoSettingsResource\Pages;

use App\Filament\Resources\VideoSettingsResource;
use App\Models\VideoSettings;
use App\Models\Academy;
use App\Services\AcademyContextService;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class ManageVideoSettings extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static string $resource = VideoSettingsResource::class;
    protected static string $view = 'filament.resources.pages.manage-video-settings';

    public function getTitle(): string
    {
        return 'إعدادات الفيديو والاجتماعات';
    }

    public function getSubheading(): ?string
    {
        return 'إدارة الإعدادات الافتراضية للاجتماعات المرئية وميزات الفيديو في الأكاديمية';
    }

    public function form(Form $form): Form
    {
        return VideoSettingsResource::form($form);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('test_settings')
                ->label('اختبار الإعدادات')
                ->icon('heroicon-o-wrench-screwdriver')
                ->color('info')
                ->action(function () {
                    try {
                        $academy = app(AcademyContextService::class)->getCurrentAcademy();
                        if (!$academy) {
                            throw new \Exception('لم يتم العثور على الأكاديمية');
                        }

                        $settings = VideoSettings::forAcademy($academy);
                        $testResult = $settings->testConfiguration();
                        
                        if ($testResult['status'] === 'success') {
                            Notification::make()
                                ->title('نجح الاختبار!')
                                ->body('جميع الإعدادات تعمل بشكل صحيح')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('تحذير في الاختبار')
                                ->body($testResult['message'] ?? 'بعض الإعدادات قد تحتاج إلى مراجعة')
                                ->warning()
                                ->send();
                        }
                        
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('خطأ في الاختبار')
                            ->body('فشل اختبار الإعدادات: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    /**
     * Get the single record for the current academy
     */
    public function getRecord(): Model
    {
        $academy = app(AcademyContextService::class)->getCurrentAcademy();
        
        if (!$academy) {
            throw new \Exception('لم يتم العثور على الأكاديمية');
        }

        return VideoSettings::forAcademy($academy);
    }

    /**
     * Handle form submission
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        DB::beginTransaction();
        
        try {
            // Handle JSON fields properly
            if (isset($data['notification_channels']) && is_array($data['notification_channels'])) {
                $data['notification_channels'] = json_encode($data['notification_channels']);
            }
            
            if (isset($data['blocked_days']) && is_array($data['blocked_days'])) {
                $data['blocked_days'] = json_encode($data['blocked_days']);
            }

            // Update the record
            $record->fill($data);
            $record->save();

            DB::commit();

            Notification::make()
                ->title('تم الحفظ بنجاح')
                ->body('تم حفظ إعدادات الفيديو والاجتماعات')
                ->success()
                ->send();

            return $record;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Notification::make()
                ->title('خطأ في الحفظ')
                ->body('فشل حفظ الإعدادات: ' . $e->getMessage())
                ->danger()
                ->send();
                
            throw $e;
        }
    }

    /**
     * Mount the page
     */
    public function mount(): void
    {
        // Ensure we have a record for the current academy
        $record = $this->getRecord();
        
        // Fill the form with current academy settings
        $data = $record->toArray();
        
        // Convert JSON fields back to arrays for the form
        if (isset($data['notification_channels']) && is_string($data['notification_channels'])) {
            $data['notification_channels'] = json_decode($data['notification_channels'], true) ?? ['email'];
        }
        
        if (isset($data['blocked_days']) && is_string($data['blocked_days'])) {
            $data['blocked_days'] = json_decode($data['blocked_days'], true) ?? [];
        }

        $this->form->fill($data);
    }

    /**
     * Save the form
     */
    public function save(): void
    {
        try {
            $data = $this->form->getState();
            $record = $this->getRecord();
            
            $this->handleRecordUpdate($record, $data);
            
            // Refresh the form with updated data
            $this->mount();
            
        } catch (\Exception $e) {
            // Error already handled in handleRecordUpdate
            throw $e;
        }
    }
}
