<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AcademyGeneralSettingsResource\Pages;
use App\Models\Academy;
use App\Services\AcademyContextService;
use App\Enums\Country;
use App\Enums\Currency;
use App\Enums\Timezone;
use App\Models\AcademicPackage;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AcademyGeneralSettingsResource extends BaseResource
{
    protected static ?string $model = Academy::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationLabel = 'الإعدادات العامة';

    protected static ?string $modelLabel = 'إعدادات عامة';

    protected static ?string $pluralModelLabel = 'الإعدادات العامة';

    protected static ?string $navigationGroup = 'إدارة الأكاديميات';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->user_type === 'super_admin';
    }

    public static function canAccess(): bool
    {
        return static::hasSpecificAcademySelected();
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->user_type === 'super_admin';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    protected static function hasSpecificAcademySelected(): bool
    {
        $academyContextService = app(AcademyContextService::class);
        return $academyContextService->getCurrentAcademyId() !== null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Panel 1: Regional Settings
                Section::make('الإعدادات الإقليمية')
                    ->description('إعدادات البلد والعملة والمنطقة الزمنية')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('country')
                                    ->label('الدولة')
                                    ->options(Country::toArray())
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->default(Country::SAUDI_ARABIA->value)
                                    ->enum(Country::class),

                                Select::make('currency')
                                    ->label('العملة')
                                    ->options(Currency::toArray())
                                    ->default(Currency::SAR->value)
                                    ->required()
                                    ->searchable()
                                    ->helperText('العملة المستخدمة في جميع المعاملات المالية')
                                    ->enum(Currency::class),

                                Select::make('timezone')
                                    ->label('المنطقة الزمنية')
                                    ->options(Timezone::toArray())
                                    ->default(Timezone::RIYADH->value)
                                    ->required()
                                    ->searchable()
                                    ->helperText('المنطقة الزمنية لجميع العمليات والجداول')
                                    ->enum(Timezone::class),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('الإعدادات الأكاديمية')
                    ->description('تخصيص الإعدادات الأكاديمية الخاصة بالمعلمين الجدد')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        CheckboxList::make('academic_settings.available_languages')
                            ->label('اللغات المتاحة للمعلمين')
                            ->options([
                                'arabic' => 'العربية',
                                'english' => 'الإنجليزية',
                                'french' => 'الفرنسية',
                                'german' => 'الألمانية',
                                'turkish' => 'التركية',
                                'spanish' => 'الإسبانية',
                                'chinese' => 'الصينية',
                                'japanese' => 'اليابانية',
                                'korean' => 'الكورية',
                                'italian' => 'الإيطالية',
                                'portuguese' => 'البرتغالية',
                                'russian' => 'الروسية',
                                'hindi' => 'الهندية',
                                'urdu' => 'الأردية',
                                'persian' => 'الفارسية',
                            ])
                            ->columns(3)
                            ->default(['arabic', 'english'])
                            ->helperText('اختر اللغات التي يمكن للمعلمين التدريس بها')
                            ->required(),

                        CheckboxList::make('academic_settings.default_package_ids')
                            ->label('الباقات الافتراضية للمعلمين الجدد')
                            ->options(function () {
                                $academyId = AcademyContextService::getCurrentAcademyId();

                                if (! $academyId) {
                                    return [];
                                }

                                return AcademicPackage::where('academy_id', $academyId)
                                    ->where('is_active', true)
                                    ->whereNotNull('name_ar')
                                    ->where('name_ar', '!=', '')
                                    ->orderBy('sort_order')
                                    ->orderBy('name_ar')
                                    ->pluck('name_ar', 'id')
                                    ->toArray();
                            })
                            ->default([])
                            ->helperText(function () {
                                $academyId = AcademyContextService::getCurrentAcademyId();

                                if (! $academyId) {
                                    return 'لا يمكن تحديد الأكاديمية.';
                                }

                                $count = AcademicPackage::where('academy_id', $academyId)
                                    ->where('is_active', true)
                                    ->whereNotNull('name_ar')
                                    ->where('name_ar', '!=', '')
                                    ->count();

                                if ($count === 0) {
                                    return 'لا توجد باقات أكاديمية متاحة. يرجى إضافة الباقات أولاً من قسم إدارة الباقات الأكاديمية.';
                                }

                                return "الباقات المختارة ستظهر تلقائياً في ملف المعلم الجديد. يوجد {$count} باقة متاحة للاختيار.";
                            })
                            ->columns(2),
                    ]),

                Section::make('إعدادات الاجتماعات')
                    ->description('تحديد القيم الافتراضية لتوقيت فتح وإغلاق الاجتماعات وإدارة الانضمام المتأخر')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('meeting_settings.default_preparation_minutes')
                                    ->label('وقت تحضير الاجتماع (دقيقة)')
                                    ->helperText('الوقت قبل بداية الجلسة لإنشاء أو فتح الاجتماع بشكل افتراضي')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(60)
                                    ->default(10)
                                    ->required()
                                    ->suffix('دقيقة'),

                                TextInput::make('meeting_settings.default_late_tolerance_minutes')
                                    ->label('فترة السماح للانضمام المتأخر (دقيقة)')
                                    ->helperText('الوقت المسموح للطلاب للانضمام بعد بداية الجلسة دون اعتبارهم متأخرين')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(60)
                                    ->default(15)
                                    ->required()
                                    ->suffix('دقيقة'),

                                TextInput::make('meeting_settings.default_buffer_minutes')
                                    ->label('وقت إضافي بعد انتهاء الجلسة (دقيقة)')
                                    ->helperText('الوقت الإضافي لبقاء الاجتماع مفتوحاً بعد انتهاء الجلسة بشكل افتراضي')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(60)
                                    ->default(5)
                                    ->required()
                                    ->suffix('دقيقة'),
                            ]),
                    ]),

                // Future panels can be added here
                Section::make('إعدادات إضافية')
                    ->description('إعدادات إضافية سيتم إضافتها لاحقاً')
                    ->schema([
                        TextInput::make('temp_note')
                            ->label('ملاحظة')
                            ->disabled()
                            ->default('سيتم إضافة المزيد من الإعدادات في المستقبل')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(true),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('الإعدادات الإقليمية')
                    ->description('إعدادات البلد والعملة والمنطقة الزمنية للأكاديمية')
                    ->schema([
                        TextEntry::make('name')
                            ->label('اسم الأكاديمية')
                            ->weight('bold'),

                        TextEntry::make('country')
                            ->label('الدولة')
                            ->formatStateUsing(function (?string $state): string {
                                if (! $state) {
                                    return '';
                                }

                                try {
                                    return Country::from($state)->getLabel();
                                } catch (\ValueError $e) {
                                    return $state;
                                }
                            }),

                        TextEntry::make('currency')
                            ->label('العملة')
                            ->formatStateUsing(function (?string $state): string {
                                if (! $state) {
                                    return '';
                                }

                                try {
                                    return Currency::from($state)->getLabel();
                                } catch (\ValueError $e) {
                                    return $state;
                                }
                            }),

                        TextEntry::make('timezone')
                            ->label('المنطقة الزمنية')
                            ->formatStateUsing(function (?string $state): string {
                                if (! $state) {
                                    return '';
                                }

                                try {
                                    return Timezone::from($state)->getLabel();
                                } catch (\ValueError $e) {
                                    return $state;
                                }
                            }),
                    ])
                    ->columns(2),

                InfolistSection::make('معلومات إضافية')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('تاريخ الإنشاء')
                            ->dateTime('Y-m-d H:i'),

                        TextEntry::make('updated_at')
                            ->label('آخر تحديث')
                            ->dateTime('Y-m-d H:i'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                Academy::query()
                    ->when(
                        app(AcademyContextService::class)->getCurrentAcademyId(),
                        fn ($query, $academyId) => $query->where('id', $academyId)
                    )
            )
            ->columns([
                TextColumn::make('name')
                    ->label('اسم الأكاديمية')
                    ->weight('bold'),

                TextColumn::make('country')
                    ->label('الدولة')
                    ->formatStateUsing(function (?string $state): string {
                        if (! $state) {
                            return '';
                        }

                        try {
                            return Country::from($state)->getLabel();
                        } catch (\ValueError $e) {
                            return $state;
                        }
                    }),

                TextColumn::make('currency')
                    ->label('العملة')
                    ->formatStateUsing(function (?string $state): string {
                        if (! $state) {
                            return '';
                        }

                        try {
                            return Currency::from($state)->getLabel();
                        } catch (\ValueError $e) {
                            return $state;
                        }
                    }),

                TextColumn::make('timezone')
                    ->label('المنطقة الزمنية')
                    ->formatStateUsing(function (?string $state): string {
                        if (! $state) {
                            return '';
                        }

                        try {
                            return Timezone::from($state)->getLabel();
                        } catch (\ValueError $e) {
                            return $state;
                        }
                    }),

                TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime('Y-m-d H:i'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->paginated(false)
            ->poll('30s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAcademyGeneralSettings::route('/'),
            'edit' => Pages\EditGeneralSettings::route('/{record}/edit'),
        ];
    }

    /**
     * Override to prevent trying to load 'academy' relationship on Academy model
     */
    protected static function getAcademyRelationshipPath(): string
    {
        return ''; // Academy model doesn't have a relationship to itself
    }
}
