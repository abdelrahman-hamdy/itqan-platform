<?php

namespace App\Filament\Resources;

use App\Enums\ApprovalStatus;
use App\Enums\EducationalQualification;
use App\Enums\Gender;
use App\Enums\TeachingLanguage;
use App\Enums\WeekDays;
use App\Filament\Actions\ApprovalActions;
use App\Filament\Concerns\HasInlineUserCreation;
use App\Filament\Concerns\HasPendingBadge;
use App\Filament\Concerns\TenantAwareFileUpload;
use App\Filament\Resources\QuranTeacherProfileResource\Pages;
use App\Models\QuranTeacherProfile;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class QuranTeacherProfileResource extends BaseResource
{
    use HasInlineUserCreation;
    use HasPendingBadge;
    use TenantAwareFileUpload;

    protected static ?string $model = QuranTeacherProfile::class;

    protected static ?string $tenantOwnershipRelationshipName = 'user';

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'معلمو القرآن';

    protected static ?string $navigationGroup = 'إدارة القرآن';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'معلم قرآن';

    protected static ?string $pluralModelLabel = 'معلمو القرآن';

    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy'; // QuranTeacherProfile -> Academy (direct relationship)
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'academy'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('المعلومات الشخصية')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('حساب المستخدم')
                            ->relationship(
                                name: 'user',
                                titleAttribute: 'email',
                                modifyQueryUsing: fn ($query) => $query
                                    ->where('user_type', 'quran_teacher')
                                    ->whereDoesntHave('quranTeacherProfile')
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->full_name} ({$record->email})")
                            ->searchable(['first_name', 'last_name', 'email'])
                            ->preload()
                            ->required()
                            ->createOptionForm(static::getUserCreationFormSchema())
                            ->createOptionUsing(fn (array $data) => static::createUserFromFormData($data, 'quran_teacher', false))
                            ->helperText('اختر حساب مستخدم موجود أو أنشئ حساباً جديداً'),

                        Forms\Components\Select::make('gender')
                            ->label('الجنس')
                            ->options(Gender::teacherOptions())
                            ->required()
                            ->helperText('يستخدم لتصنيف المعلم للطلاب حسب الجنس'),

                        Forms\Components\FileUpload::make('avatar')
                            ->label('الصورة الشخصية')
                            ->image()
                            ->imageEditor()
                            ->circleCropper()
                            ->directory(static::getTenantDirectoryLazy('avatars/quran-teachers'))
                            ->maxSize(2048),
                    ]),

                Forms\Components\Section::make('المؤهلات والخبرة')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('educational_qualification')
                                    ->label('المؤهل التعليمي')
                                    ->options(EducationalQualification::options())
                                    ->default(EducationalQualification::BACHELOR->value)
                                    ->required(),
                                Forms\Components\TextInput::make('teaching_experience_years')
                                    ->label('سنوات الخبرة في تدريس القرآن')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(50)
                                    ->default(0),
                            ]),
                        Forms\Components\TagsInput::make('certifications')
                            ->label('الشهادات والإجازات')
                            ->placeholder('أضف شهادة أو إجازة')
                            ->helperText('مثل: إجازة في القراءات، شهادة تجويد، إلخ'),
                        Forms\Components\CheckboxList::make('languages')
                            ->label('اللغات التي يجيدها')
                            ->options(function () {
                                return \App\Enums\TeachingLanguage::toArray();
                            })
                            ->default(function () {
                                $academyId = AcademyContextService::getCurrentAcademy()?->id;
                                $academy = $academyId ? \App\Models\Academy::find($academyId) : null;

                                // Get default languages from academy quran_settings or use TeachingLanguage defaults
                                return $academy?->quran_settings['available_languages']
                                    ?? \App\Enums\TeachingLanguage::defaults();
                            })
                            ->columns(4),
                        Forms\Components\CheckboxList::make('package_ids')
                            ->label('الباقات التي يمكن تدريسها')
                            ->options(function (?QuranTeacherProfile $record) {
                                $academyId = $record?->academy_id ?? AcademyContextService::getCurrentAcademy()?->id;

                                if (! $academyId) {
                                    return [];
                                }

                                return \App\Models\QuranPackage::where('academy_id', $academyId)
                                    ->where('is_active', true)
                                    ->whereNotNull('name')
                                    ->where('name', '!=', '')
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->default(function (?QuranTeacherProfile $record) {
                                // Cascade: 1) Teacher's own packages, 2) Academy default packages, 3) All packages
                                $academyId = $record?->academy_id ?? AcademyContextService::getCurrentAcademy()?->id;

                                if (! $academyId) {
                                    return [];
                                }

                                // Step 1: If editing and teacher has packages defined, use them
                                if ($record && ! empty($record->package_ids)) {
                                    return $record->package_ids;
                                }

                                // Step 2: Get default packages from academy general settings (quran_settings)
                                $academy = \App\Models\Academy::find($academyId);
                                $defaultPackageIds = $academy?->quran_settings['default_package_ids'] ?? [];

                                if (! empty($defaultPackageIds)) {
                                    // Validate that these packages still exist and are active
                                    return \App\Models\QuranPackage::where('academy_id', $academyId)
                                        ->where('is_active', true)
                                        ->whereIn('id', $defaultPackageIds)
                                        ->pluck('id')
                                        ->toArray();
                                }

                                // Step 3: If no defaults, select all available packages
                                return \App\Models\QuranPackage::where('academy_id', $academyId)
                                    ->where('is_active', true)
                                    ->whereNotNull('name')
                                    ->where('name', '!=', '')
                                    ->pluck('id')
                                    ->toArray();
                            })
                            ->helperText(function (?QuranTeacherProfile $record) {
                                $academyId = $record?->academy_id ?? AcademyContextService::getCurrentAcademy()?->id;

                                if (! $academyId) {
                                    return 'لا يمكن تحديد الأكاديمية. يرجى تحديد الأكاديمية أولاً.';
                                }

                                $count = \App\Models\QuranPackage::where('academy_id', $academyId)
                                    ->where('is_active', true)
                                    ->whereNotNull('name')
                                    ->where('name', '!=', '')
                                    ->count();

                                if ($count === 0) {
                                    return 'لا توجد باقات قرآن متاحة في هذه الأكاديمية. يرجى إضافة الباقات أولاً من قسم إدارة باقات القرآن.';
                                }

                                // Check if using defaults from settings
                                $academy = \App\Models\Academy::find($academyId);
                                $hasDefaults = ! empty($academy?->quran_settings['default_package_ids'] ?? []);

                                if ($hasDefaults && ! $record) {
                                    return "يوجد {$count} باقة متاحة. تم تحديد الباقات الافتراضية من الإعدادات العامة.";
                                }

                                return "يوجد {$count} باقة قرآن متاحة للاختيار";
                            })
                            ->columns(2),
                    ]),

                Forms\Components\Section::make('الأوقات المتاحة')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TimePicker::make('available_time_start')
                                    ->label('وقت البدء')
                                    ->default('08:00')
                                    ->required(),
                                Forms\Components\TimePicker::make('available_time_end')
                                    ->label('وقت الانتهاء')
                                    ->default('18:00')
                                    ->required(),
                            ]),
                        Forms\Components\CheckboxList::make('available_days')
                            ->label('الأيام المتاحة')
                            ->options(WeekDays::options())
                            ->columns(2)
                            ->required(),
                    ]),

                Forms\Components\Section::make('الأسعار والرسوم')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('session_price_individual')
                                    ->label('سعر الحصة الفردية')
                                    ->numeric()
                                    ->prefix('ر.س')
                                    ->minValue(0)
                                    ->step(5)
                                    ->default(50)
                                    ->helperText('سعر الحصة الواحدة للطالب الواحد'),
                                Forms\Components\TextInput::make('session_price_group')
                                    ->label('سعر الحصة الجماعية')
                                    ->numeric()
                                    ->prefix('ر.س')
                                    ->minValue(0)
                                    ->step(5)
                                    ->default(30)
                                    ->helperText('سعر الحصة الجماعية كاملة بغض النظر عن عدد الطلاب في الحلقة'),
                            ]),
                    ]),

                Forms\Components\Section::make('السيرة الذاتية')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Textarea::make('bio_arabic')
                                    ->label('السيرة الذاتية (عربي)')
                                    ->maxLength(1000)
                                    ->rows(4)
                                    ->helperText('اكتب نبذة عن خبرتك في تدريس القرآن الكريم'),
                                Forms\Components\Textarea::make('bio_english')
                                    ->label('السيرة الذاتية (إنجليزي)')
                                    ->maxLength(1000)
                                    ->rows(4),
                            ]),
                    ]),

                Forms\Components\Section::make('الحالة')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true),
                        Forms\Components\Select::make('approval_status')
                            ->label('حالة الموافقة')
                            ->options(ApprovalStatus::options())
                            ->default(ApprovalStatus::PENDING->value)
                            ->required()
                            ->helperText('يجب أن يكون المعلم موافق عليه ونشط ليظهر للطلاب'),
                        Forms\Components\Toggle::make('offers_trial_sessions')
                            ->label('يقدم جلسات تجريبية')
                            ->default(true)
                            ->helperText('عند تفعيل هذا الخيار، سيتمكن الطلاب من طلب جلسات تجريبية مع هذا المعلم'),
                        Forms\Components\DateTimePicker::make('approved_at')
                            ->label('تاريخ الموافقة')
                            ->disabled()
                            ->visible(fn ($record) => $record && $record->approval_status === 'approved'),
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات إدارية')
                            ->maxLength(1000)
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('ملاحظات إدارية حول المدرس'),
                    ])
                    ->columns(2)
                    ->visible(fn () => Auth::check() && Auth::user()->isAdmin()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                static::getAcademyColumn(), // Add academy column when viewing all academies
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('الصورة')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name='.urlencode($record->full_name).'&background=4169E1&color=fff'),

                Tables\Columns\TextColumn::make('teacher_code')
                    ->label('رمز المعلم')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('اسم المعلم')
                    ->searchable(['users.first_name', 'users.last_name'])
                    ->sortable()
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\BadgeColumn::make('is_active')
                    ->label('نشط')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'نشط' : 'غير نشط')
                    ->colors([
                        'success' => true,
                        'gray' => false,
                    ]),

                Tables\Columns\BadgeColumn::make('approval_status')
                    ->label('حالة الموافقة')
                    ->formatStateUsing(fn (?string $state): string => ApprovalStatus::tryFrom($state)?->label() ?? $state ?? '-')
                    ->colors([
                        'success' => ApprovalStatus::APPROVED->value,
                        'warning' => ApprovalStatus::PENDING->value,
                        'danger' => ApprovalStatus::REJECTED->value,
                    ])
                    ->icon(fn (?string $state): ?string => ApprovalStatus::tryFrom($state)?->icon()),

                Tables\Columns\BadgeColumn::make('offers_trial_sessions')
                    ->label('الجلسات التجريبية')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'متاحة' : 'غير متاحة')
                    ->colors([
                        'success' => true,
                        'gray' => false,
                    ]),

                Tables\Columns\TextColumn::make('total_students')
                    ->label('عدد الطلاب')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_sessions')
                    ->label('عدد الجلسات')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('rating')
                    ->label('التقييم')
                    ->formatStateUsing(function ($state) {
                        if (! $state) {
                            return '-';
                        }

                        return number_format($state, 1).'/5';
                    }),

                Tables\Columns\TextColumn::make('languages')
                    ->label('اللغات')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        if (! is_array($state)) {
                            return '-';
                        }

                        return collect($state)
                            ->map(fn ($lang) => TeachingLanguage::tryFrom($lang)?->label() ?? $lang)
                            ->implode(', ');
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('certifications')
                    ->label('الشهادات')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        if (! is_array($state)) {
                            return '-';
                        }

                        return collect($state)->take(2)->implode(', ').(count($state) > 2 ? '...' : '');
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('educational_qualification')
                    ->label('المؤهل التعليمي')
                    ->formatStateUsing(fn (?string $state): string => EducationalQualification::tryFrom($state)?->label() ?? $state ?? '-')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('teaching_experience_years')
                    ->label('سنوات الخبرة')
                    ->numeric()
                    ->suffix(' سنة')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('approval_status')
                    ->label('حالة الموافقة')
                    ->options(ApprovalStatus::options()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('نشط'),
                Tables\Filters\TernaryFilter::make('offers_trial_sessions')
                    ->label('الجلسات التجريبية')
                    ->trueLabel('متاحة')
                    ->falseLabel('غير متاحة'),
                Tables\Filters\SelectFilter::make('educational_qualification')
                    ->label('المؤهل التعليمي')
                    ->options(EducationalQualification::options()),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label(__('filament.filters.from_date')),
                        Forms\Components\DatePicker::make('until')
                            ->label(__('filament.filters.to_date')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                Tables\Filters\TrashedFilter::make()
                    ->label(__('filament.filters.trashed')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                ...ApprovalActions::make('معلم'),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make()
                    ->label(__('filament.actions.restore')),
                Tables\Actions\ForceDeleteAction::make()
                    ->label(__('filament.actions.force_delete')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make()
                        ->label(__('filament.actions.restore_selected')),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->label(__('filament.actions.force_delete_selected')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuranTeacherProfiles::route('/'),
            'create' => Pages\CreateQuranTeacherProfile::route('/create'),
            'view' => Pages\ViewQuranTeacherProfile::route('/{record}'),
            'edit' => Pages\EditQuranTeacherProfile::route('/{record}/edit'),
        ];
    }
}
