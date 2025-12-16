<?php

namespace App\Filament\Pages;

use App\Models\PlatformSettings;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use App\Services\AcademyContextService;

class PlatformSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.pages.platform-settings';

    protected static ?string $navigationLabel = 'إعدادات المنصة';

    protected static ?string $title = 'إعدادات المنصة';

    protected static ?string $navigationGroup = 'إدارة النظام';

    protected static ?int $navigationSort = 0;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return AcademyContextService::isSuperAdmin();
    }

    public function mount(): void
    {
        $settings = PlatformSettings::instance();

        $this->form->fill([
            'logo' => $settings->logo,
            'favicon' => $settings->favicon,
            'email' => $settings->email,
            'phone' => $settings->phone,
            'address' => $settings->address,
            'working_hours' => $settings->working_hours,
            'social_links' => $settings->social_links ?? [],
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Identity Settings Section
                Section::make('إعدادات الهوية')
                    ->description('شعار المنصة وأيقونة الموقع')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                FileUpload::make('logo')
                                    ->label('شعار المنصة')
                                    ->helperText('يظهر في رأس الصفحة وفي جميع صفحات المنصة')
                                    ->image()
                                    ->directory('platform')
                                    ->visibility('public')
                                    ->imageResizeMode('contain')
                                    ->imageResizeTargetWidth('400')
                                    ->imageResizeTargetHeight('100'),

                                FileUpload::make('favicon')
                                    ->label('أيقونة الموقع (Favicon)')
                                    ->helperText('الأيقونة التي تظهر في تبويب المتصفح')
                                    ->image()
                                    ->directory('platform')
                                    ->visibility('public')
                                    ->imageResizeMode('contain')
                                    ->imageResizeTargetWidth('64')
                                    ->imageResizeTargetHeight('64'),
                            ]),
                    ])
                    ->collapsible(),

                // Contact Settings Section
                Section::make('إعدادات التواصل')
                    ->description('معلومات الاتصال بالمنصة')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->email()
                                    ->placeholder('info@itqan-platform.com'),

                                TextInput::make('phone')
                                    ->label('رقم الهاتف')
                                    ->tel()
                                    ->placeholder('+966 XX XXX XXXX'),
                            ]),

                        TextInput::make('address')
                            ->label('العنوان')
                            ->placeholder('المملكة العربية السعودية - الرياض')
                            ->columnSpanFull(),

                        TextInput::make('working_hours')
                            ->label('ساعات العمل')
                            ->placeholder('السبت - الخميس: 9 صباحاً - 6 مساءً')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                // Social Links Section
                Section::make('روابط التواصل الاجتماعي')
                    ->description('روابط حسابات المنصة على مواقع التواصل الاجتماعي')
                    ->icon('heroicon-o-share')
                    ->schema([
                        Repeater::make('social_links')
                            ->label('')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('icon')
                                            ->label('الأيقونة')
                                            ->placeholder('ri-facebook-fill')
                                            ->helperText('استخدم أيقونات Remix Icon مثل: ri-facebook-fill, ri-twitter-x-fill, ri-instagram-fill, ri-whatsapp-fill, ri-youtube-fill, ri-linkedin-fill, ri-tiktok-fill')
                                            ->required(),

                                        TextInput::make('name')
                                            ->label('الاسم')
                                            ->placeholder('فيسبوك')
                                            ->required(),

                                        TextInput::make('url')
                                            ->label('الرابط')
                                            ->url()
                                            ->placeholder('https://facebook.com/itqan')
                                            ->required(),
                                    ]),
                            ])
                            ->addActionLabel('إضافة رابط جديد')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'رابط جديد')
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $settings = PlatformSettings::instance();
        $settings->update($data);

        Notification::make()
            ->title('تم الحفظ')
            ->body('تم حفظ إعدادات المنصة بنجاح')
            ->success()
            ->send();
    }
}
