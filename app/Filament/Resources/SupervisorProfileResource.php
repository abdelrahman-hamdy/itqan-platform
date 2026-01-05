<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\TenantAwareFileUpload;
use App\Enums\Gender;
use App\Filament\Resources\SupervisorProfileResource\Pages;
use App\Filament\Widgets\SupervisorResponsibilitiesWidget;
use App\Models\SupervisorProfile;
use App\Models\User;
use App\Models\InteractiveCourse;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Services\AcademyContextService;
use App\Models\Academy;

class SupervisorProfileResource extends BaseResource
{
    use TenantAwareFileUpload;

    protected static ?string $model = SupervisorProfile::class;
    
    protected static ?string $tenantOwnershipRelationshipName = 'academy';

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'إدارة المستخدمين';

    protected static ?string $navigationLabel = 'المشرفين';

    protected static ?string $modelLabel = 'مشرف';

    protected static ?string $pluralModelLabel = 'المشرفين';

    protected static ?int $navigationSort = 4;

    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy'; // SupervisorProfile -> Academy (direct relationship)
    }

    // Note: getEloquentQuery() is now handled by ScopedToAcademyViaRelationship trait

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('المعلومات الأساسية')
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
                                return $user && $user->isSuperAdmin() && !AcademyContextService::getCurrentAcademy();
                            })
                            ->dehydrated(true) // CRITICAL: Always include in form data even when hidden
                            ->helperText('حدد الأكاديمية التي سينتمي إليها هذا المشرف'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->email()
                                    ->required()
                                    ->unique(table: SupervisorProfile::class, ignoreRecord: true)
                                    ->rules([
                                        function (?SupervisorProfile $record) {
                                            return function (string $attribute, $value, \Closure $fail) use ($record) {
                                                // Check if email exists in users table (excluding the linked user)
                                                $query = \App\Models\User::where('email', $value);
                                                if ($record && $record->user_id) {
                                                    $query->where('id', '!=', $record->user_id);
                                                }
                                                if ($query->exists()) {
                                                    $fail('البريد الإلكتروني مستخدم بالفعل في حساب مستخدم آخر.');
                                                }
                                            };
                                        },
                                    ])
                                    ->maxLength(255)
                                    ->helperText('سيستخدم المشرف هذا البريد للدخول إلى المنصة'),
                                Forms\Components\TextInput::make('first_name')
                                    ->label('الاسم الأول')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('last_name')
                                    ->label('اسم العائلة')
                                    ->required()
                                    ->maxLength(255),
                                static::getPhoneInput()
                                    ->required(),
                                Forms\Components\Select::make('gender')
                                    ->label(__('common.gender'))
                                    ->options(Gender::options())
                                    ->default(Gender::MALE->value)
                                    ->required(),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('password')
                                    ->label('كلمة المرور')
                                    ->password()
                                    ->revealable()
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->required(fn (string $context): bool => $context === 'create')
                                    ->minLength(8)
                                    ->maxLength(255)
                                    ->helperText('سيتم إنشاء حساب تلقائياً للمشرف باستخدام هذه الكلمة. الحد الأدنى 8 أحرف.')
                                    ->visible(fn ($record) => !$record || !$record->user_id),
                                Forms\Components\TextInput::make('password_confirmation')
                                    ->label('تأكيد كلمة المرور')
                                    ->password()
                                    ->revealable()
                                    ->dehydrated(false)
                                    ->required(fn (string $context, $get): bool => $context === 'create' && filled($get('password')))
                                    ->same('password')
                                    ->maxLength(255)
                                    ->visible(fn ($record) => !$record || !$record->user_id),
                            ]),

                        Forms\Components\FileUpload::make('avatar')
                            ->label('الصورة الشخصية')
                            ->image()
                            ->imageEditor()
                            ->circleCropper()
                            ->directory(static::getTenantDirectoryLazy('avatars/supervisors'))
                            ->maxSize(2048),
                    ]),
                // Supervisor code - only shown on edit page as read-only
                Forms\Components\Section::make('معلومات المشرف')
                    ->schema([
                        Forms\Components\TextInput::make('supervisor_code')
                            ->label('رمز المشرف')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('يتم إنشاء هذا الرمز تلقائياً'),
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->visible(fn (string $operation): bool => $operation !== 'create'),

                // Responsibilities section - only on edit page
                Forms\Components\Section::make('المسؤوليات')
                    ->description('حدد المعلمين والدورات التي يتولى الإشراف عليها')
                    ->schema([
                        Forms\Components\Toggle::make('can_manage_teachers')
                            ->label('إدارة المعلمين')
                            ->helperText('تمكين هذا الخيار يسمح للمشرف بإدارة ملفات المعلمين الموكلين إليه وأرباحهم ومدفوعاتهم')
                            ->default(false)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('quran_teacher_ids')
                            ->label('معلمو القرآن')
                            ->multiple()
                            ->options(function () {
                                $academyId = AcademyContextService::getCurrentAcademy()?->id;
                                $query = User::where('user_type', 'quran_teacher');
                                if ($academyId) {
                                    $query->where('academy_id', $academyId);
                                }
                                return $query->orderBy('first_name')
                                    ->get()
                                    ->mapWithKeys(fn ($user) => [$user->id => $user->full_name ?? $user->name ?? $user->email]);
                            })
                            ->searchable()
                            ->preload()
                            ->dehydrated(false)
                            ->helperText('جميع حلقات وجلسات هؤلاء المعلمين ستكون مرئية للمشرف'),

                        Forms\Components\Select::make('academic_teacher_ids')
                            ->label('المعلمون الأكاديميون')
                            ->multiple()
                            ->options(function () {
                                $academyId = AcademyContextService::getCurrentAcademy()?->id;
                                $query = User::where('user_type', 'academic_teacher');
                                if ($academyId) {
                                    $query->where('academy_id', $academyId);
                                }
                                return $query->orderBy('first_name')
                                    ->get()
                                    ->mapWithKeys(fn ($user) => [$user->id => $user->full_name ?? $user->name ?? $user->email]);
                            })
                            ->searchable()
                            ->preload()
                            ->dehydrated(false)
                            ->helperText('جميع دروس وجلسات ودورات هؤلاء المعلمين ستكون مرئية للمشرف'),
                    ])
                    ->columns(2)
                    ->visible(fn (string $operation): bool => $operation === 'edit'),

                // Notes section for create page
                Forms\Components\Section::make('ملاحظات')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->visible(fn (string $operation): bool => $operation === 'create'),
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
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->full_name ?? 'N/A') . '&background=9333ea&color=fff'),
                Tables\Columns\TextColumn::make('supervisor_code')
                    ->label('رمز المشرف')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('الاسم الكامل')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->weight(FontWeight::Bold),
                Tables\Columns\TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('gender')
                    ->label(__('common.gender'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? (Gender::tryFrom($state)?->label() ?? $state) : '-')
                    ->color(fn (?string $state): string => $state === 'male' ? 'info' : 'pink'),
                Tables\Columns\IconColumn::make('can_manage_teachers')
                    ->label('إدارة المعلمين')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
                Tables\Columns\TextColumn::make('assigned_teachers_count')
                    ->label('المعلمون')
                    ->state(function ($record) {
                        $quranCount = count($record->getAssignedQuranTeacherIds());
                        $academicCount = count($record->getAssignedAcademicTeacherIds());
                        return $quranCount + $academicCount;
                    })
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('assigned_courses_count')
                    ->label('الدورات')
                    ->state(fn ($record) => $record->getDerivedInteractiveCoursesCount())
                    ->badge()
                    ->color('info')
                    ->description('من المعلمين'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('can_manage_teachers')
                    ->label('إدارة المعلمين')
                    ->trueLabel('مُمكّنة')
                    ->falseLabel('معطّلة'),
                Tables\Filters\Filter::make('has_assignments')
                    ->label('لديه مسؤوليات')
                    ->query(fn (Builder $query): Builder => $query->whereHas('responsibilities')),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('من تاريخ'),
                        Forms\Components\DatePicker::make('until')
                            ->label('إلى تاريخ'),
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
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getWidgets(): array
    {
        return [
            SupervisorResponsibilitiesWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupervisorProfiles::route('/'),
            'create' => Pages\CreateSupervisorProfile::route('/create'),
            'view' => Pages\ViewSupervisorProfile::route('/{record}'),
            'edit' => Pages\EditSupervisorProfile::route('/{record}/edit'),
        ];
    }
}
