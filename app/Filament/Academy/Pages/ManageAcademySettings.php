<?php

namespace App\Filament\Academy\Pages;

use App\Enums\Currency;
use App\Enums\UserType;
use App\Models\Academy;
use App\Models\AcademySettings;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

/**
 * @property \Filament\Schemas\Components\Form $form
 */
class ManageAcademySettings extends Page implements HasForms
{
    use InteractsWithForms;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected string $view = 'filament.academy.pages.manage-academy-settings';

    protected static string|\UnitEnum|null $navigationGroup = 'الإعدادات';

    protected static ?string $navigationLabel = 'إعدادات الأكاديمية';

    protected static ?string $title = 'إعدادات الأكاديمية';

    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    public function mount(): void
    {
        /** @var Academy $academy */
        $academy = Filament::getTenant();
        $academySettings = AcademySettings::getForAcademy($academy);

        $data = $academy->toArray();
        // Form uses semantic names; DB columns kept their legacy names.
        $data['attendance_settings'] = [
            'student_full_attendance_percent' => $academySettings->default_attendance_threshold_percentage,
            'student_partial_attendance_percent' => $academySettings->student_minimum_presence_percent,
            'teacher_full_attendance_percent' => $academySettings->teacher_full_attendance_percent,
            'teacher_partial_attendance_percent' => $academySettings->teacher_partial_attendance_percent,
        ];

        $this->form->fill($data);
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
                        FileUpload::make('logo')
                            ->label('الشعار')
                            ->image()
                            ->disk('public')
                            ->directory('academy-logos')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/svg+xml'])
                            ->maxSize(2048),

                        FileUpload::make('favicon')
                            ->label('أيقونة الموقع')
                            ->image()
                            ->disk('public')
                            ->directory('academy-favicons')
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
                            ->options(Currency::toArray())
                            ->required()
                            ->searchable(),

                        Select::make('teacher_earnings_currency')
                            ->label('عملة أرباح المعلمين')
                            ->options(Currency::toArray())
                            ->searchable()
                            ->placeholder('استخدام عملة الأكاديمية')
                            ->helperText('العملة المستخدمة لأرباح المعلمين. اتركه فارغاً لاستخدام عملة الأكاديمية.'),
                    ])
                    ->columns(2),

                Section::make(__('settings.attendance_rules'))
                    ->description(__('settings.attendance_rules_description'))
                    ->schema([
                        Fieldset::make(__('settings.student_attendance'))
                            ->schema([
                                TextInput::make('attendance_settings.student_full_attendance_percent')
                                    ->label(__('settings.student_full_attendance'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->default(80)
                                    ->required()
                                    ->suffix('%')
                                    ->helperText(__('settings.student_full_help')),

                                TextInput::make('attendance_settings.student_partial_attendance_percent')
                                    ->label(__('settings.student_partial_attendance'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->default(50)
                                    ->required()
                                    ->suffix('%')
                                    ->helperText(__('settings.student_partial_help')),
                            ])
                            ->columns(2),

                        Fieldset::make(__('settings.teacher_attendance'))
                            ->schema([
                                TextInput::make('attendance_settings.teacher_full_attendance_percent')
                                    ->label(__('settings.teacher_full_attendance'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->default(90)
                                    ->required()
                                    ->suffix('%')
                                    ->helperText(__('settings.teacher_full_attendance_help')),

                                TextInput::make('attendance_settings.teacher_partial_attendance_percent')
                                    ->label(__('settings.teacher_partial_attendance'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->default(50)
                                    ->required()
                                    ->suffix('%')
                                    ->helperText(__('settings.teacher_partial_attendance_help')),
                            ])
                            ->columns(2),
                    ]),
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

        // Separate attendance settings from academy fields
        $attendanceData = $data['attendance_settings'] ?? [];
        unset($data['attendance_settings']);

        // Cross-field validation: partial must be ≤ full for both student and teacher.
        $studentFull = (float) ($attendanceData['student_full_attendance_percent'] ?? 80);
        $studentPartial = (float) ($attendanceData['student_partial_attendance_percent'] ?? 50);
        $teacherFull = (float) ($attendanceData['teacher_full_attendance_percent'] ?? 90);
        $teacherPartial = (float) ($attendanceData['teacher_partial_attendance_percent'] ?? 50);

        if ($studentPartial > $studentFull || $teacherPartial > $teacherFull) {
            Notification::make()
                ->danger()
                ->title(__('settings.attendance_partial_lte_full'))
                ->send();

            return;
        }

        // Explicit allowlist — only update fields exposed in the form schema
        $allowedFields = [
            'name', 'name_en', 'description',
            'logo', 'favicon',
            'email', 'phone',
            'timezone', 'currency', 'teacher_earnings_currency',
        ];
        $data = array_intersect_key($data, array_flip($allowedFields));

        /** @var Academy $academy */
        $academy = Filament::getTenant();

        $academy->update($data);

        if (! empty($attendanceData)) {
            $academySettings = AcademySettings::getForAcademy($academy);
            $academySettings->update([
                'default_attendance_threshold_percentage' => $studentFull,
                'student_minimum_presence_percent' => $studentPartial,
                'teacher_full_attendance_percent' => $teacherFull,
                'teacher_partial_attendance_percent' => $teacherPartial,
            ]);
        }

        Notification::make()
            ->success()
            ->title('تم حفظ الإعدادات')
            ->body('تم تحديث إعدادات الأكاديمية بنجاح')
            ->send();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null
            && $user->hasRole(UserType::ADMIN->value)
            && $user->academy_id !== null;
    }
}
