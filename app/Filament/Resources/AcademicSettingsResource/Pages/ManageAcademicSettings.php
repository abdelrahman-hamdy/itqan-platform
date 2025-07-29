<?php

namespace App\Filament\Resources\AcademicSettingsResource\Pages;

use App\Filament\Resources\AcademicSettingsResource;
use App\Models\AcademicSettings;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Forms\Form;
use Filament\Notifications\Notification;

class ManageAcademicSettings extends ManageRecords
{
    protected static string $resource = AcademicSettingsResource::class;

    public function getTitle(): string
    {
        return 'الإعدادات الأكاديمية';
    }

    public function getSubheading(): ?string
    {
        $academyName = auth()->user()->academy->name ?? 'الأكاديمية';
        return "إدارة إعدادات القسم الأكاديمي لـ {$academyName}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('حفظ الإعدادات')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action('save'),
        ];
    }

    public function form(Form $form): Form
    {
        return static::getResource()::form($form);
    }

    public function mount(): void
    {
        parent::mount();

        $academyId = auth()->user()->academy_id ?? 1;
        
        // Get or create settings for current academy
        $settings = AcademicSettings::getForAcademy($academyId);
        
        // Fill the form with current settings
        $this->form->fill($settings->toArray());
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();
            
            $academyId = auth()->user()->academy_id ?? 1;
            $settings = AcademicSettings::getForAcademy($academyId);
            
            // Add updated_by field
            $data['updated_by'] = auth()->id();
            
            $settings->update($data);

            Notification::make()
                ->title('تم حفظ الإعدادات بنجاح')
                ->success()
                ->send();

        } catch (\Exception $e) {
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
            Actions\Action::make('save')
                ->label('حفظ الإعدادات')
                ->submit('save')
                ->keyBindings(['mod+s'])
                ->color('success'),
        ];
    }
} 