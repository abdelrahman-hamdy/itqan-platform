<?php

namespace App\Filament\Academy\Pages;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use App\Enums\UserType;
use App\Models\Academy;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ManageAcademySettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected string $view = 'filament.academy.pages.manage-academy-settings';

    protected static string | \UnitEnum | null $navigationGroup = 'الإعدادات';

    protected static ?string $navigationLabel = 'إعدادات الأكاديمية';

    protected static ?string $title = 'إعدادات الأكاديمية';

    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    public function mount(): void
    {
        /** @var Academy $academy */
        $academy = Filament::getTenant();

        $this->form->fill($academy->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات القراءة فقط')
                    ->description('هذه المعلومات لا يمكن تعديلها')
                    ->collapsed()
                    ->schema([
                        TextInput::make('subdomain')
                            ->label('النطاق الفرعي')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('is_active')
                            ->label('الحالة')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($state) => $state ? 'نشط' : 'غير نشط'),
                    ])
                    ->columns(2),

                Section::make('المعلومات العامة')
                    ->schema([
                        TextInput::make('name')
                            ->label('اسم الأكاديمية (عربي)')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('name_en')
                            ->label('اسم الأكاديمية (إنجليزي)')
                            ->maxLength(255),

                        Textarea::make('description')
                            ->label('وصف الأكاديمية')
                            ->rows(3),
                    ])
                    ->columns(2),

                Section::make('العلامة التجارية')
                    ->schema([
                        FileUpload::make('logo_url')
                            ->label('الشعار')
                            ->image()
                            ->directory('academies/logos')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/svg+xml'])
                            ->maxSize(2048),

                        FileUpload::make('favicon')
                            ->label('أيقونة الموقع')
                            ->image()
                            ->directory('academies/favicons')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/png', 'image/x-icon', 'image/vnd.microsoft.icon'])
                            ->maxSize(512),
                    ])
                    ->columns(2),

                Section::make('معلومات الاتصال')
                    ->schema([
                        TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->label('رقم الهاتف')
                            ->tel()
                            ->maxLength(20),
                    ])
                    ->columns(2),

                Section::make('الإعدادات المحلية')
                    ->schema([
                        Select::make('timezone')
                            ->label('المنطقة الزمنية')
                            ->options([
                                'Asia/Riyadh' => 'الرياض (GMT+3)',
                                'Africa/Cairo' => 'القاهرة (GMT+2)',
                                'Asia/Dubai' => 'دبي (GMT+4)',
                                'Asia/Kuwait' => 'الكويت (GMT+3)',
                                'Asia/Qatar' => 'قطر (GMT+3)',
                                'Asia/Bahrain' => 'البحرين (GMT+3)',
                            ])
                            ->required()
                            ->searchable(),

                        Select::make('currency')
                            ->label('العملة')
                            ->options([
                                'SAR' => 'ريال سعودي (SAR)',
                                'EGP' => 'جنيه مصري (EGP)',
                                'AED' => 'درهم إماراتي (AED)',
                                'KWD' => 'دينار كويتي (KWD)',
                                'QAR' => 'ريال قطري (QAR)',
                                'BHD' => 'دينار بحريني (BHD)',
                            ])
                            ->required()
                            ->searchable(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('حفظ التغييرات')
                ->submit('save')
                ->color('primary'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        /** @var Academy $academy */
        $academy = Filament::getTenant();

        $academy->update($data);

        Notification::make()
            ->success()
            ->title('تم حفظ الإعدادات')
            ->body('تم تحديث إعدادات الأكاديمية بنجاح')
            ->send();
    }

    public static function canAccess(): bool
    {
        // Only academy admins can access
        return auth()->user()?->user_type === UserType::ADMIN->value;
    }
}
