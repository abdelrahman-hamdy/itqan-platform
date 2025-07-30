<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AcademyManagementResource\Pages;
use App\Models\Academy;
use App\Models\User;
use App\Models\RecordedCourse;
use App\Models\InteractiveCourse;
use App\Models\QuranCircle;
use App\Models\AcademicTeacher;
use App\Models\QuranTeacher;
use App\Models\StudentProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\ColorPicker;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Services\AcademyContextService;

class AcademyManagementResource extends Resource
{
    protected static ?string $model = Academy::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'إدارة النظام';

    protected static ?string $navigationLabel = 'إدارة الأكاديميات';

    protected static ?string $modelLabel = 'أكاديمية';

    protected static ?string $pluralModelLabel = 'الأكاديميات';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('المعلومات الأساسية')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('اسم الأكاديمية')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('أدخل اسم الأكاديمية'),

                                TextInput::make('name_en')
                                    ->label('Academy Name (English)')
                                    ->maxLength(255)
                                    ->placeholder('Enter academy name in English'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('subdomain')
                                    ->label('النطاق الفرعي')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(50)
                                    ->placeholder('academy-name')
                                    ->helperText('سيكون الرابط: https://academy-name.itqan.com'),

                                TextInput::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('contact@academy.com'),
                            ]),

                        Textarea::make('description')
                            ->label('وصف الأكاديمية')
                            ->rows(4)
                            ->placeholder('أدخل وصف مختصر للأكاديمية'),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('phone')
                                    ->label('رقم الهاتف')
                                    ->tel()
                                    ->placeholder('+966 50 123 4567'),

                                TextInput::make('website')
                                    ->label('الموقع الإلكتروني')
                                    ->url()
                                    ->placeholder('https://academy.com'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('الهوية البصرية')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                FileUpload::make('logo')
                                    ->label('شعار الأكاديمية')
                                    ->image()
                                    ->directory('academy-logos')
                                    ->visibility('public'),

                                ColorPicker::make('brand_color')
                                    ->label('اللون الأساسي')
                                    ->default('#3B82F6')
                                    ->helperText('لون الواجهة الأساسي'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                ColorPicker::make('secondary_color')
                                    ->label('اللون الثانوي')
                                    ->default('#10B981'),

                                Select::make('theme')
                                    ->label('المظهر')
                                    ->options([
                                        'light' => 'فاتح',
                                        'dark' => 'داكن',
                                        'auto' => 'تلقائي',
                                    ])
                                    ->default('light'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('الإعدادات')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('status')
                                    ->label('حالة الأكاديمية')
                                    ->options([
                                        'active' => 'نشطة',
                                        'suspended' => 'معلقة',
                                        'maintenance' => 'صيانة',
                                    ])
                                    ->default('active')
                                    ->required()
                                    ->helperText('استخدم مفتاح "مفعلة" أدناه لتفعيل/إلغاء تفعيل الأكاديمية'),

                                Select::make('timezone')
                                    ->label('المنطقة الزمنية')
                                    ->options([
                                        'Asia/Riyadh' => 'الرياض (GMT+3)',
                                        'Asia/Dubai' => 'دبي (GMT+4)',
                                        'Africa/Cairo' => 'القاهرة (GMT+2)',
                                    ])
                                    ->default('Asia/Riyadh')
                                    ->required(),

                                Select::make('currency')
                                    ->label('العملة')
                                    ->options([
                                        'SAR' => 'ريال سعودي',
                                        'AED' => 'درهم إماراتي',
                                        'EGP' => 'جنيه مصري',
                                    ])
                                    ->default('SAR')
                                    ->required(),
                            ]),

                        Grid::make(3)
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('مفعلة')
                                    ->default(true),

                                Toggle::make('allow_registration')
                                    ->label('السماح بالتسجيل')
                                    ->default(true)
                                    ->helperText('السماح للمستخدمين بالتسجيل الجديد'),

                                Toggle::make('maintenance_mode')
                                    ->label('وضع الصيانة')
                                    ->default(false)
                                    ->helperText('إيقاف الوصول مؤقتاً للصيانة'),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم الأكاديمية')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('subdomain')
                    ->label('النطاق الفرعي')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('تم نسخ النطاق الفرعي')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'suspended' => 'danger',
                        'maintenance' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'نشطة',
                        'suspended' => 'معلقة',
                        'maintenance' => 'صيانة',
                        default => $state,
                    }),

                TextColumn::make('users_count')
                    ->label('المستخدمين')
                    ->counts('users')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('recorded_courses_count')
                    ->label('الدورات المسجلة')
                    ->getStateUsing(function (Academy $record) {
                        return RecordedCourse::where('academy_id', $record->id)->count();
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                TextColumn::make('interactive_courses_count')
                    ->label('الدورات التفاعلية')
                    ->getStateUsing(function (Academy $record) {
                        return InteractiveCourse::where('academy_id', $record->id)->count();
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('warning'),

                TextColumn::make('quran_circles_count')
                    ->label('حلقات القرآن')
                    ->getStateUsing(function (Academy $record) {
                        return QuranCircle::where('academy_id', $record->id)->count();
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('success'),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'active' => 'نشطة',
                        'suspended' => 'معلقة',
                        'maintenance' => 'صيانة',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('مفعلة')
                    ->placeholder('الكل')
                    ->trueLabel('مفعلة')
                    ->falseLabel('غير مفعلة'),
            ])
            ->actions([
                Action::make('select_academy')
                    ->label('اختيار هذه الأكاديمية')
                    ->icon('heroicon-o-cursor-arrow-rays')
                    ->color('primary')
                    ->action(function (Academy $record) {
                        // Set academy context using our service
                        AcademyContextService::setAcademyContext($record->id);
                    })
                    ->successRedirectUrl(fn () => request()->url())
                    ->requiresConfirmation()
                    ->modalHeading('اختيار الأكاديمية')
                    ->modalDescription(fn (Academy $record) => "هل تريد اختيار أكاديمية '{$record->name}' للإدارة؟ سيتم تحديث جميع الصفحات لعرض بيانات هذه الأكاديمية.")
                    ->modalSubmitActionLabel('اختيار الأكاديمية'),

                Action::make('view_academy')
                    ->label('زيارة الأكاديمية')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('info')
                    ->url(fn (Academy $record): string => "https://{$record->subdomain}.itqan.com")
                    ->openUrlInNewTab(),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAcademyManagements::route('/'),
            'create' => Pages\CreateAcademyManagement::route('/create'),
            'edit' => Pages\EditAcademyManagement::route('/{record}/edit'),
            'view' => Pages\ViewAcademyManagement::route('/{record}'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        // Only show this resource for super admin
        return AcademyContextService::isSuperAdmin();
    }

    public static function canViewAny(): bool
    {
        // Only super admin can access academy management
        return AcademyContextService::isSuperAdmin();
    }
} 