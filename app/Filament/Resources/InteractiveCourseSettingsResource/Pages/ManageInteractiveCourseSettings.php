<?php

namespace App\Filament\Resources\InteractiveCourseSettingsResource\Pages;

use App\Filament\Resources\InteractiveCourseSettingsResource;
use App\Models\InteractiveCourseSettings;
use Filament\Actions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class ManageInteractiveCourseSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = InteractiveCourseSettingsResource::class;
    protected static string $view = 'filament.resources.interactive-course-settings-resource.pages.manage-interactive-course-settings';
    
    public ?array $data = [];

    public function getTitle(): string
    {
        return 'إعدادات الدورات التفاعلية';
    }

    public function getSubheading(): ?string
    {
        return 'إدارة الإعدادات العامة للدورات التفاعلية في الأكاديمية';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('حفظ الإعدادات')
                ->color('primary')
                ->action('save'),
        ];
    }

    public function form(Form $form): Form
    {
        return InteractiveCourseSettingsResource::form($form)
            ->statePath('data');
    }

    public function mount(): void
    {
        $academyId = auth()->user()->academy_id ?? 1;
        $settings = InteractiveCourseSettings::getForAcademy($academyId);
        $this->form->fill($settings->toArray());
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $academyId = auth()->user()->academy_id ?? 1;
        
        $settings = InteractiveCourseSettings::getForAcademy($academyId);
        $data['updated_by'] = auth()->id();
        $settings->update($data);

        Notification::make()
            ->title('تم حفظ الإعدادات بنجاح')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [];
    }
} 