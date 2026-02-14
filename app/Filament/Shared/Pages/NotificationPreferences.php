<?php

namespace App\Filament\Shared\Pages;

use App\Enums\NotificationCategory;
use App\Models\UserNotificationPreference;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

/**
 * Notification Preferences Page
 *
 * Allows users to control which notification categories they receive
 * via email, dashboard (database), and browser channels.
 *
 * Shared across all Filament panels.
 */
class NotificationPreferences extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-bell';

    protected static string $view = 'filament.shared.pages.notification-preferences';

    protected static ?string $navigationLabel = null;

    protected static ?string $title = null;

    protected static ?int $navigationSort = 99;

    protected static ?string $navigationGroup = null;

    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('notifications.preferences.page_title');
    }

    public function getTitle(): string
    {
        return __('notifications.preferences.page_title');
    }

    public static function getNavigationGroup(): ?string
    {
        return null;
    }

    public function mount(): void
    {
        $user = Auth::user();
        $preferences = UserNotificationPreference::getForUser($user->id);

        $formData = [];

        foreach (NotificationCategory::cases() as $category) {
            $pref = $preferences[$category->value] ?? null;

            $formData["{$category->value}_email"] = $pref?->email_enabled ?? true;
            $formData["{$category->value}_database"] = $pref?->database_enabled ?? true;
            $formData["{$category->value}_browser"] = $pref?->browser_enabled ?? true;
        }

        $this->form->fill($formData);
    }

    public function form(Form $form): Form
    {
        $sections = [];

        foreach (NotificationCategory::cases() as $category) {
            $sections[] = Section::make($category->label())
                ->icon($category->getIcon())
                ->iconColor($category->getFilamentColor())
                ->compact()
                ->columns(3)
                ->schema([
                    Toggle::make("{$category->value}_email")
                        ->label(__('notifications.preferences.channels.email'))
                        ->helperText(__('notifications.preferences.channels.email_help'))
                        ->default(true),

                    Toggle::make("{$category->value}_database")
                        ->label(__('notifications.preferences.channels.dashboard'))
                        ->helperText(__('notifications.preferences.channels.dashboard_help'))
                        ->default(true),

                    Toggle::make("{$category->value}_browser")
                        ->label(__('notifications.preferences.channels.browser'))
                        ->helperText(__('notifications.preferences.channels.browser_help'))
                        ->default(true),
                ]);
        }

        return $form
            ->schema($sections)
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $user = Auth::user();

        foreach (NotificationCategory::cases() as $category) {
            UserNotificationPreference::setForUser(
                userId: $user->id,
                category: $category,
                emailEnabled: $data["{$category->value}_email"] ?? true,
                databaseEnabled: $data["{$category->value}_database"] ?? true,
                browserEnabled: $data["{$category->value}_browser"] ?? true,
            );
        }

        Notification::make()
            ->title(__('notifications.preferences.saved'))
            ->success()
            ->send();
    }
}
