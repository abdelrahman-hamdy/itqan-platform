<?php

namespace App\Filament\Resources;

use App\Enums\Country;
use App\Enums\Gender;
use App\Filament\Concerns\TenantAwareFileUpload;
use App\Filament\Resources\StudentProfileResource\Pages;
use App\Models\StudentProfile;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StudentProfileResource extends BaseResource
{
    use TenantAwareFileUpload;

    protected static ?string $model = StudentProfile::class;

    protected static ?string $tenantOwnershipRelationshipName = 'gradeLevel';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'الطلاب';

    protected static ?string $navigationGroup = 'إدارة المستخدمين';

    protected static ?string $modelLabel = 'طالب';

    protected static ?string $pluralModelLabel = 'الطلاب';

    protected static function getAcademyRelationshipPath(): string
    {
        return 'gradeLevel.academy'; // StudentProfile -> GradeLevel -> Academy
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['parent', 'gradeLevel.academy'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    /**
     * Determine if the user can view any records
     * SuperAdmin and Admin can view, Teachers can view their students
     */
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // SuperAdmin and Admin can always view
        if ($user->isSuperAdmin() || $user->hasRole('admin')) {
            return true;
        }

        // Teachers can view (will be filtered to their students)
        if ($user->isQuranTeacher() || $user->isAcademicTeacher()) {
            return true;
        }

        // Supervisors can view
        if ($user->hasRole('supervisor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can view a record
     * SuperAdmin: full access
     * Admin: only students in their academy
     * Teacher: only students they teach
     */
    public static function canView($record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // SuperAdmin has full access
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admin can view students in their academy context
        if ($user->hasRole('admin')) {
            $currentAcademyId = AcademyContextService::getCurrentAcademyId();
            if (! $currentAcademyId) {
                return false; // No academy context, deny
            }

            // Check if student's grade level belongs to current academy
            // Note: Must bypass academy scope to get the actual academy_id
            return static::getRecordAcademyId($record) === $currentAcademyId;
        }

        // Supervisors can view students in academies they supervise
        if ($user->hasRole('supervisor')) {
            $currentAcademyId = AcademyContextService::getCurrentAcademyId();

            return static::getRecordAcademyId($record) === $currentAcademyId;
        }

        // Teachers can view their own students (students enrolled in their sessions/circles)
        if ($user->isQuranTeacher() || $user->isAcademicTeacher()) {
            return static::isTeacherOfStudent($user, $record);
        }

        return false;
    }

    /**
     * Determine if the user can edit a record
     * SuperAdmin: full access
     * Admin: only students in their academy
     * Teachers: cannot edit
     */
    public static function canEdit($record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // SuperAdmin has full access
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admin can edit students in their academy
        if ($user->hasRole('admin')) {
            $currentAcademyId = AcademyContextService::getCurrentAcademyId();
            if (! $currentAcademyId) {
                return false;
            }

            // Note: Must bypass academy scope to get the actual academy_id
            return static::getRecordAcademyId($record) === $currentAcademyId;
        }

        // Teachers and others cannot edit
        return false;
    }

    /**
     * Determine if the user can delete a record
     * Only SuperAdmin can delete student profiles
     */
    public static function canDelete($record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // Only SuperAdmin can delete student profiles
        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can create records
     * SuperAdmin and Admin can create student profiles
     */
    public static function canCreate(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // SuperAdmin and Admin can create
        if ($user->isSuperAdmin() || $user->hasRole('admin')) {
            return true;
        }

        return false;
    }

    /**
     * Check if a teacher teaches this student
     */
    protected static function isTeacherOfStudent($user, $studentProfile): bool
    {
        $studentUserId = $studentProfile->user_id;

        // Check Quran subscriptions
        if ($user->isQuranTeacher()) {
            $hasQuranStudent = \App\Models\QuranSubscription::query()
                ->where('student_id', $studentUserId)
                ->whereHas('circle', function ($q) use ($user) {
                    $q->where('quran_teacher_id', $user->id);
                })
                ->orWhereHas('individualCircle', function ($q) use ($user) {
                    $q->where('quran_teacher_id', $user->id);
                })
                ->exists();

            if ($hasQuranStudent) {
                return true;
            }
        }

        // Check Academic subscriptions
        if ($user->isAcademicTeacher()) {
            $hasAcademicStudent = \App\Models\AcademicSubscription::query()
                ->where('student_id', $studentUserId)
                ->whereHas('teacher', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->exists();

            if ($hasAcademicStudent) {
                return true;
            }

            // Check interactive courses
            $hasInteractiveStudent = \App\Models\InteractiveCourseEnrollment::query()
                ->where('student_id', $studentUserId)
                ->whereHas('course', function ($q) use ($user) {
                    $q->where('assigned_teacher_id', $user->id);
                })
                ->exists();

            if ($hasInteractiveStudent) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the academy ID for a student profile record
     * Bypasses global academy scope to get the actual academy_id
     */
    protected static function getRecordAcademyId($record): ?int
    {
        if (! $record->grade_level_id) {
            return null;
        }

        // Query grade level directly, bypassing the academy scope
        $gradeLevel = \App\Models\AcademicGradeLevel::withoutGlobalScope('academy')
            ->find($record->grade_level_id);

        return $gradeLevel?->academy_id;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('المعلومات الشخصية')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('first_name')
                                    ->label('الاسم الأول')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('last_name')
                                    ->label('الاسم الأخير')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->helperText('سيستخدم الطالب هذا البريد للدخول إلى المنصة'),
                                static::getPhoneInput('phone', 'رقم الهاتف')
                                    ->helperText('رقم الهاتف مع رمز الدولة'),
                            ]),
                        Forms\Components\FileUpload::make('avatar')
                            ->label('الصورة الشخصية')
                            ->image()
                            ->imageEditor()
                            ->circleCropper()
                            ->directory(static::getTenantDirectoryLazy('avatars/students'))
                            ->maxSize(2048),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('birth_date')
                                    ->label('تاريخ الميلاد'),
                                Forms\Components\Select::make('nationality')
                                    ->label('الجنسية')
                                    ->options(Country::toArray())
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->enum(Country::class),
                                Forms\Components\Select::make('gender')
                                    ->label('الجنس')
                                    ->options(Gender::options()),
                            ]),
                    ]),

                Forms\Components\Section::make('المعلومات الأكاديمية')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('grade_level_id')
                                    ->label('المرحلة الدراسية')
                                    ->relationship('gradeLevel', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\DatePicker::make('enrollment_date')
                                    ->label('تاريخ التسجيل')
                                    ->default(now()),
                            ]),
                    ]),

                Forms\Components\Section::make('معلومات الاتصال والطوارئ')
                    ->schema([
                        Forms\Components\Textarea::make('address')
                            ->label('العنوان')
                            ->maxLength(500)
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                static::getPhoneInput('parent_phone', 'رقم هاتف ولي الأمر')
                                    ->required()
                                    ->helperText('رقم الهاتف مع رمز الدولة (مطلوب للربط مع حساب ولي الأمر)'),
                                Forms\Components\TextInput::make('emergency_contact')
                                    ->label('رقم الطوارئ (اختياري)')
                                    ->tel()
                                    ->maxLength(20),
                            ]),
                        Forms\Components\Select::make('parent_id')
                            ->label('ولي الأمر')
                            ->relationship('parent', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name.' ('.$record->parent_code.')')
                            ->searchable(['first_name', 'last_name', 'parent_code', 'email'])
                            ->preload()
                            ->nullable()
                            ->helperText('اختر ولي الأمر المسؤول عن هذا الطالب (أو سيتم الربط تلقائياً عند تسجيل ولي الأمر)'),
                    ]),

                Forms\Components\Section::make('ملاحظات إضافية')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->maxLength(1000)
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
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
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name='.urlencode($record->full_name ?? 'N/A').'&background=4169E1&color=fff'),
                Tables\Columns\TextColumn::make('student_code')
                    ->label('رمز الطالب')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('الاسم')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->weight(FontWeight::Bold),
                Tables\Columns\TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('gradeLevel.name')
                    ->label('المرحلة الدراسية')
                    ->sortable(),
                Tables\Columns\TextColumn::make('parent.full_name')
                    ->label('ولي الأمر')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->default('—')
                    ->description(fn ($record) => $record->parent?->parent_code),
                Tables\Columns\TextColumn::make('nationality')
                    ->label('الجنسية')
                    ->formatStateUsing(function (?string $state): string {
                        if (! $state) {
                            return '';
                        }

                        try {
                            return \App\Enums\Country::from($state)->label();
                        } catch (\ValueError $e) {
                            return $state;
                        }
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('gradeLevel.academy.name')
                    ->label('الأكاديمية')
                    ->badge()
                    ->color('info')
                    ->visible(fn () => AcademyContextService::isSuperAdmin() && AcademyContextService::isGlobalViewMode()),
                Tables\Columns\TextColumn::make('enrollment_date')
                    ->label('تاريخ التسجيل')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('filament.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('grade_level_id')
                    ->label('المرحلة الدراسية')
                    ->relationship('gradeLevel', 'name')
                    ->preload(),
                Tables\Filters\SelectFilter::make('parent_id')
                    ->label('ولي الأمر')
                    ->relationship('parent', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name.' ('.$record->parent_code.')')
                    ->searchable(['first_name', 'last_name', 'parent_code'])
                    ->preload(),
                Tables\Filters\SelectFilter::make('nationality')
                    ->label('الجنسية')
                    ->options(\App\Enums\Country::toArray())
                    ->preload(),
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
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = __('filament.filters.from_date').': '.$data['from'];
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = __('filament.filters.to_date').': '.$data['until'];
                        }

                        return $indicators;
                    }),
                Tables\Filters\TrashedFilter::make()
                    ->label(__('filament.filters.trashed')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListStudentProfiles::route('/'),
            'create' => Pages\CreateStudentProfile::route('/create'),
            'view' => Pages\ViewStudentProfile::route('/{record}'),
            'edit' => Pages\EditStudentProfile::route('/{record}/edit'),
        ];
    }
}
