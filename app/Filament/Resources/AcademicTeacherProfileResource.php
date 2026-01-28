<?php

namespace App\Filament\Resources;

use App\Enums\EducationalQualification;
use App\Enums\Gender;
use App\Enums\TeachingLanguage;
use App\Filament\Concerns\HasInlineUserCreation;
use App\Filament\Concerns\HasPendingBadge;
use App\Filament\Concerns\HasUserDataFields;
use App\Filament\Concerns\TenantAwareFileUpload;
use App\Filament\Resources\AcademicTeacherProfileResource\Pages;
use App\Models\AcademicTeacherProfile;
use App\Models\Academy;
use App\Models\User;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AcademicTeacherProfileResource extends BaseResource
{
    use HasInlineUserCreation;
    use HasPendingBadge;
    use HasUserDataFields;
    use TenantAwareFileUpload;

    protected static ?string $model = AcademicTeacherProfile::class;

    protected static ?string $tenantOwnershipRelationshipName = 'user';

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'المدرسين الأكاديميين';

    protected static ?string $navigationGroup = 'إدارة التعليم الأكاديمي';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'مدرس أكاديمي';

    protected static ?string $pluralModelLabel = 'المدرسين الأكاديميين';

    protected static function getAcademyRelationshipPath(): string
    {
        return 'user.academy'; // AcademicTeacherProfile -> User -> Academy
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
                // Personal information section
                Forms\Components\Section::make('المعلومات الشخصية')
                    ->schema([
                        // Academy selection field for super admin when in global view or creating new records
                        Forms\Components\Select::make('academy_id')
                            ->label('الأكاديمية')
                            ->options(Academy::active()->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->default(fn () => AcademyContextService::getCurrentAcademy()?->id)
                            ->visible(function () {
                                $user = auth()->user();

                                return $user && $user->isSuperAdmin() && ! AcademyContextService::getCurrentAcademy();
                            })
                            ->dehydrated(true)
                            ->helperText('حدد الأكاديمية التي سينتمي إليها هذا المدرس')
                            ->live(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('user_first_name')
                                    ->label('الاسم الأول')
                                    ->required()
                                    ->maxLength(255)
                                    ->dehydrated(false),
                                Forms\Components\TextInput::make('user_last_name')
                                    ->label('اسم العائلة')
                                    ->required()
                                    ->maxLength(255)
                                    ->dehydrated(false),
                                Forms\Components\TextInput::make('user_email')
                                    ->label('البريد الإلكتروني')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->dehydrated(false)
                                    ->rules([
                                        fn (?AcademicTeacherProfile $record): \Closure => function (string $attribute, $value, \Closure $fail) use ($record) {
                                            if (! $record?->user_id) {
                                                return;
                                            }
                                            $exists = \App\Models\User::where('email', $value)
                                                ->where('id', '!=', $record->user_id)
                                                ->exists();
                                            if ($exists) {
                                                $fail('هذا البريد الإلكتروني مستخدم بالفعل.');
                                            }
                                        },
                                    ]),
                                static::getPhoneInput('user_phone', 'رقم الهاتف')
                                    ->dehydrated(false),
                            ])
                            ->visible(fn (?AcademicTeacherProfile $record): bool => $record?->user_id !== null),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('gender')
                                    ->label('الجنس')
                                    ->options(Gender::teacherOptions())
                                    ->required(),

                                Forms\Components\FileUpload::make('avatar')
                                    ->label('الصورة الشخصية')
                                    ->image()
                                    ->imageEditor()
                                    ->circleCropper()
                                    ->directory(static::getTenantDirectoryLazy('avatars/academic-teachers'))
                                    ->maxSize(2048),
                            ]),
                    ]),

                Forms\Components\Section::make('المؤهلات والخبرة')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('education_level')
                                    ->label('المؤهل التعليمي')
                                    ->options(EducationalQualification::options())
                                    ->default(EducationalQualification::BACHELOR->value)
                                    ->required(),
                                Forms\Components\TextInput::make('university')
                                    ->label('الجامعة')
                                    ->maxLength(255),
                            ]),
                        Forms\Components\TextInput::make('teaching_experience_years')
                            ->label('سنوات الخبرة التدريسية')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(50)
                            ->default(0),
                        Forms\Components\TagsInput::make('certifications')
                            ->label('الشهادات والدورات')
                            ->placeholder('أضف شهادة')
                            ->helperText('اضغط Enter لإضافة شهادة جديدة'),
                        Forms\Components\CheckboxList::make('languages')
                            ->label('اللغات')
                            ->options(function () {
                                $academyId = AcademyContextService::getCurrentAcademy()?->id;
                                $academy = $academyId ? \App\Models\Academy::find($academyId) : null;
                                $availableLanguages = $academy?->academic_settings['available_languages'] ?? ['arabic', 'english'];

                                return \App\Enums\TeachingLanguage::toArray();
                            })
                            ->default(function () {
                                $academyId = AcademyContextService::getCurrentAcademy()?->id;
                                $academy = $academyId ? \App\Models\Academy::find($academyId) : null;

                                // Get default languages from academy settings or use TeachingLanguage defaults
                                return $academy?->academic_settings['available_languages']
                                    ?? \App\Enums\TeachingLanguage::defaults();
                            })
                            ->columns(4),
                    ]),

                Forms\Components\Section::make('التخصص')
                    ->schema([
                        Forms\Components\Select::make('subject_ids')
                            ->label('المواد التي يقوم بتدريسها')
                            ->options(function (Forms\Get $get, ?AcademicTeacherProfile $record) {
                                // Get academy_id from the record being edited, or from the form data for new records
                                $academyId = $record?->academy_id ?? $get('academy_id') ?? AcademyContextService::getCurrentAcademy()?->id;

                                if (! $academyId) {
                                    return [];
                                }

                                return \App\Models\AcademicSubject::where('academy_id', $academyId)
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText(function (Forms\Get $get, ?AcademicTeacherProfile $record) {
                                // Get academy_id from the record being edited, or from the form data for new records
                                $academyId = $record?->academy_id ?? $get('academy_id') ?? AcademyContextService::getCurrentAcademy()?->id;

                                if (! $academyId) {
                                    return 'لا يمكن تحديد الأكاديمية. يرجى تحديد الأكاديمية أولاً.';
                                }

                                $count = \App\Models\AcademicSubject::where('academy_id', $academyId)->where('is_active', true)->count();
                                if ($count === 0) {
                                    return 'لا توجد مواد دراسية متاحة في هذه الأكاديمية. يرجى إضافة المواد الدراسية أولاً.';
                                }

                                return "يوجد {$count} مادة دراسية متاحة للاختيار";
                            })
                            ->columns(2),
                        Forms\Components\Select::make('grade_level_ids')
                            ->label('الصفوف الدراسية')
                            ->options(function (Forms\Get $get, ?AcademicTeacherProfile $record) {
                                // Get academy_id from the record being edited, or from the form data for new records
                                $academyId = $record?->academy_id ?? $get('academy_id') ?? AcademyContextService::getCurrentAcademy()?->id;

                                if (! $academyId) {
                                    return [];
                                }

                                return \App\Models\AcademicGradeLevel::where('academy_id', $academyId)
                                    ->where('is_active', true)
                                    ->whereNotNull('name')
                                    ->where('name', '!=', '')
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText(function (Forms\Get $get, ?AcademicTeacherProfile $record) {
                                // Get academy_id from the record being edited, or from the form data for new records
                                $academyId = $record?->academy_id ?? $get('academy_id') ?? AcademyContextService::getCurrentAcademy()?->id;

                                if (! $academyId) {
                                    return 'لا يمكن تحديد الأكاديمية. يرجى تحديد الأكاديمية أولاً.';
                                }

                                $count = \App\Models\AcademicGradeLevel::where('academy_id', $academyId)
                                    ->where('is_active', true)
                                    ->whereNotNull('name')
                                    ->where('name', '!=', '')
                                    ->count();
                                if ($count === 0) {
                                    return 'لا توجد صفوف دراسية متاحة في هذه الأكاديمية. يرجى إضافة الصفوف الدراسية أولاً.';
                                }

                                return "يوجد {$count} صف دراسي متاح للاختيار";
                            })
                            ->columns(2),
                        Forms\Components\CheckboxList::make('package_ids')
                            ->label('الباقات التي يمكن تدريسها')
                            ->options(function (Forms\Get $get, ?AcademicTeacherProfile $record) {
                                // Get academy_id from the record being edited, or from the form data for new records
                                $academyId = $record?->academy_id ?? $get('academy_id') ?? AcademyContextService::getCurrentAcademy()?->id;

                                if (! $academyId) {
                                    return [];
                                }

                                return \App\Models\AcademicPackage::where('academy_id', $academyId)
                                    ->where('is_active', true)
                                    ->orderBy('sort_order')
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->default(function (Forms\Get $get, ?AcademicTeacherProfile $record) {
                                // Cascade: 1) Teacher's own packages, 2) Academy default packages, 3) All packages
                                $academyId = $record?->academy_id ?? $get('academy_id') ?? AcademyContextService::getCurrentAcademy()?->id;

                                if (! $academyId) {
                                    return [];
                                }

                                // Step 1: If editing and teacher has packages defined, use them
                                if ($record && ! empty($record->package_ids)) {
                                    return $record->package_ids;
                                }

                                // Step 2: Get default packages from academy general settings
                                $academy = \App\Models\Academy::find($academyId);
                                $defaultPackageIds = $academy?->academic_settings['default_package_ids'] ?? [];

                                if (! empty($defaultPackageIds)) {
                                    // Validate that these packages still exist and are active
                                    return \App\Models\AcademicPackage::where('academy_id', $academyId)
                                        ->where('is_active', true)
                                        ->whereIn('id', $defaultPackageIds)
                                        ->pluck('id')
                                        ->toArray();
                                }

                                // Step 3: If no defaults, select all available packages
                                return \App\Models\AcademicPackage::where('academy_id', $academyId)
                                    ->where('is_active', true)
                                    ->pluck('id')
                                    ->toArray();
                            })
                            ->helperText(function (Forms\Get $get, ?AcademicTeacherProfile $record) {
                                $academyId = $record?->academy_id ?? $get('academy_id') ?? AcademyContextService::getCurrentAcademy()?->id;

                                if (! $academyId) {
                                    return 'لا يمكن تحديد الأكاديمية. يرجى تحديد الأكاديمية أولاً.';
                                }

                                $count = \App\Models\AcademicPackage::where('academy_id', $academyId)->where('is_active', true)->count();
                                if ($count === 0) {
                                    return 'لا توجد باقات أكاديمية متاحة في هذه الأكاديمية. يرجى إضافة الباقات أولاً من قسم إدارة الباقات الأكاديمية.';
                                }

                                // Check if using defaults from settings
                                $academy = \App\Models\Academy::find($academyId);
                                $hasDefaults = ! empty($academy?->academic_settings['default_package_ids'] ?? []);

                                if ($hasDefaults && ! $record) {
                                    return "يوجد {$count} باقة متاحة. تم تحديد الباقات الافتراضية من الإعدادات العامة.";
                                }

                                return "يوجد {$count} باقة أكاديمية متاحة للاختيار";
                            })
                            ->columns(2),
                    ]),

                Forms\Components\Section::make('الأوقات المتاحة والأسعار')
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
                            ->options(\App\Enums\WeekDays::options())
                            ->columns(3)
                            ->required(),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('session_price_individual')
                                    ->label('سعر الحصة الفردية')
                                    ->numeric()
                                    ->prefix('ر.س')
                                    ->minValue(0)
                                    ->step(5)
                                    ->default(100),
                            ]),
                    ]),

                Forms\Components\Section::make('السيرة الذاتية')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Textarea::make('bio_arabic')
                                    ->label('السيرة الذاتية (عربي)')
                                    ->maxLength(1000)
                                    ->rows(4),
                                Forms\Components\Textarea::make('bio_english')
                                    ->label('السيرة الذاتية (إنجليزي)')
                                    ->maxLength(1000)
                                    ->rows(4),
                            ]),
                        Forms\Components\FileUpload::make('preview_video')
                            ->label('فيديو تعريفي')
                            ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/quicktime'])
                            ->directory(static::getTenantDirectoryLazy('videos/academic-teachers'))
                            ->maxSize(51200)
                            ->helperText('فيديو تعريفي قصير للمعلم (اختياري) - الحد الأقصى 50 ميجابايت'),
                    ]),

                Forms\Components\Section::make('ملاحظات')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات إدارية')
                            ->maxLength(1000)
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('ملاحظات إدارية حول المدرس'),
                    ])
                    ->visible(fn () => auth()->check() && auth()->user()->isAdmin()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                static::getAcademyColumn(),
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('الصورة')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name='.urlencode($record->user?->name ?? 'N/A').'&background=4169E1&color=fff'),
                Tables\Columns\TextColumn::make('teacher_code')
                    ->label('رمز المدرس')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('اسم المدرس')
                    ->searchable(['users.first_name', 'users.last_name'])
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::Bold),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('gender')
                    ->label('الجنس')
                    ->formatStateUsing(fn (?string $state): string => $state ? Gender::tryFrom($state)?->label() ?? '-' : '-')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'male' => 'info',
                        'female' => 'pink',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('user.active_status')
                    ->label('نشط')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
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

                Tables\Columns\TextColumn::make('education_level')
                    ->label('المؤهل التعليمي')
                    ->formatStateUsing(fn (?string $state): string => EducationalQualification::tryFrom($state)?->label() ?? $state ?? '-')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('teaching_experience_years')
                    ->label('سنوات الخبرة')
                    ->numeric()
                    ->suffix(' سنة')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('session_price_individual')
                    ->label('سعر الحصة الفردية')
                    ->money('SAR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('academy_id')
                    ->label('الأكاديمية')
                    ->options(Academy::where('is_active', true)->pluck('name', 'id'))
                    ->searchable(),
                Tables\Filters\TernaryFilter::make('active')
                    ->label('نشط')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('user', fn ($q) => $q->where('active_status', true)),
                        false: fn (Builder $query) => $query->whereHas('user', fn ($q) => $q->where('active_status', false)),
                    ),
                Tables\Filters\SelectFilter::make('education_level')
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('activate')
                    ->label('تفعيل')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (AcademicTeacherProfile $record) => $record->user?->update(['active_status' => true]))
                    ->visible(fn (AcademicTeacherProfile $record) => $record->user && ! $record->user->active_status),
                Tables\Actions\Action::make('deactivate')
                    ->label('إيقاف')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (AcademicTeacherProfile $record) => $record->user?->update(['active_status' => false]))
                    ->visible(fn (AcademicTeacherProfile $record) => $record->user && $record->user->active_status),
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
            'index' => Pages\ListAcademicTeacherProfiles::route('/'),
            'create' => Pages\CreateAcademicTeacherProfile::route('/create'),
            'view' => Pages\ViewAcademicTeacherProfile::route('/{record}'),
            'edit' => Pages\EditAcademicTeacherProfile::route('/{record}/edit'),
        ];
    }
}
