<?php

namespace App\Filament\Resources;

use App\Enums\CircleEnrollmentStatus;
use App\Enums\WeekDays;
use App\Filament\Concerns\OptimizedSelectOptions;
use App\Filament\Resources\QuranCircleResource\Pages;
use App\Filament\Shared\Resources\BaseQuranCircleResource;
use App\Models\QuranCircle;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Quran Circle Resource for SuperAdmin Panel
 *
 * Full CRUD access with soft delete support, enrollment management,
 * and administrative features. Extends BaseQuranCircleResource for
 * shared form/table definitions.
 */
class QuranCircleResource extends BaseQuranCircleResource
{
    use OptimizedSelectOptions;

    // ========================================
    // Tenant Configuration (SuperAdmin-specific)
    // ========================================

    /**
     * Tenant ownership relationship for Filament multi-tenancy.
     */
    protected static ?string $tenantOwnershipRelationshipName = 'academy';

    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy';
    }

    // ========================================
    // Navigation Configuration
    // ========================================

    protected static ?string $navigationGroup = 'إدارة القرآن';

    protected static ?int $navigationSort = 3;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * SuperAdmin sees all circles, including soft-deleted ones.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        return $query->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /**
     * Get the teacher field - SuperAdmin can select any teacher.
     * Uses optimized searchable AJAX loading to prevent N+1 queries.
     */
    protected static function getTeacherFormField(): Forms\Components\Component
    {
        return Select::make('quran_teacher_id')
            ->label('معلم القرآن')
            ->searchable()
            ->preload()
            ->options(function (): array {
                $academyId = \App\Services\AcademyContextService::getCurrentAcademyId();

                return \App\Models\QuranTeacherProfile::query()
                    ->with('user')
                    ->whereHas('user', fn ($q) => $q->where('active_status', true))
                    ->when($academyId, function ($query) use ($academyId) {
                        $query->where('academy_id', $academyId);
                    })
                    ->orderBy('created_at', 'desc')
                    ->limit(50)
                    ->get()
                    ->mapWithKeys(function ($teacher) {
                        $label = $teacher->user?->name ?? 'معلم غير محدد';

                        if ($teacher->teacher_code) {
                            $label .= " ({$teacher->teacher_code})";
                        }

                        return [$teacher->user_id => $label];
                    })
                    ->toArray();
            })
            ->required();
    }

    /**
     * Get description field - SuperAdmin uses single description field.
     */
    protected static function getDescriptionFormFields(): array
    {
        return [
            Textarea::make('description')
                ->label('وصف الحلقة')
                ->rows(3)
                ->maxLength(500)
                ->columnSpanFull(),
        ];
    }

    /**
     * Full table actions for SuperAdmin with status management and soft deletes.
     */
    protected static function getTableActions(): array
    {
        return [
            ActionGroup::make([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\Action::make('toggle_status')
                    ->label(fn (QuranCircle $record) => $record->status ? 'إلغاء التفعيل' : 'تفعيل')
                    ->icon(fn (QuranCircle $record) => $record->status ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
                    ->color(fn (QuranCircle $record) => $record->status ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (QuranCircle $record) => $record->status ? 'إلغاء تفعيل الحلقة' : 'تفعيل الحلقة')
                    ->modalDescription(fn (QuranCircle $record) => $record->status
                        ? 'هل أنت متأكد من إلغاء تفعيل هذه الحلقة؟ لن يتمكن الطلاب من الانضمام إليها.'
                        : 'هل أنت متأكد من تفعيل هذه الحلقة؟ ستصبح متاحة للطلاب للانضمام.'
                    )
                    ->action(fn (QuranCircle $record) => $record->update([
                        'status' => ! $record->status,
                        'enrollment_status' => $record->status ? CircleEnrollmentStatus::CLOSED : CircleEnrollmentStatus::OPEN,
                    ])),
                Tables\Actions\Action::make('activate')
                    ->label('تفعيل للتسجيل')
                    ->icon('heroicon-o-megaphone')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (QuranCircle $record) => $record->update([
                        'status' => true,
                        'enrollment_status' => CircleEnrollmentStatus::OPEN,
                    ])),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
                Tables\Actions\RestoreAction::make()
                    ->label(__('filament.actions.restore')),
                Tables\Actions\ForceDeleteAction::make()
                    ->label(__('filament.actions.force_delete')),
            ]),
        ];
    }

    /**
     * Full bulk actions for SuperAdmin.
     */
    protected static function getTableBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make()
                    ->label('حذف المحدد'),
                Tables\Actions\RestoreBulkAction::make()
                    ->label(__('filament.actions.restore_selected')),
                Tables\Actions\ForceDeleteBulkAction::make()
                    ->label(__('filament.actions.force_delete_selected')),
            ]),
        ];
    }

    // ========================================
    // Form Sections Override (SuperAdmin-specific)
    // ========================================

    /**
     * Additional form sections for SuperAdmin:
     * - Progress tracking
     * - Status and enrollment management
     * - Administrative notes
     */
    protected static function getAdditionalFormSections(): array
    {
        return [
            Section::make('تتبع التقدم')
                ->description('يتم حسابها تلقائياً من واجبات الجلسات')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextInput::make('total_memorized_pages')
                                ->label('إجمالي الصفحات المحفوظة')
                                ->numeric()
                                ->default(0)
                                ->disabled()
                                ->helperText('يتم تحديثه من واجبات الحفظ الجديد'),

                            TextInput::make('total_reviewed_pages')
                                ->label('إجمالي الصفحات المراجعة')
                                ->numeric()
                                ->default(0)
                                ->disabled()
                                ->helperText('يتم تحديثه من واجبات المراجعة'),

                            TextInput::make('total_reviewed_surahs')
                                ->label('إجمالي السور المراجعة')
                                ->numeric()
                                ->default(0)
                                ->disabled()
                                ->helperText('يتم تحديثه من واجبات المراجعة الشاملة'),
                        ]),
                ])
                ->collapsible()
                ->collapsed(),

            Section::make('الحالة والإعدادات الإدارية')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Toggle::make('status')
                                ->label('حالة الحلقة')
                                ->helperText('تفعيل أو إلغاء تفعيل الحلقة')
                                ->default(true),

                            Toggle::make('enrollment_status')
                                ->label('حالة التسجيل')
                                ->helperText('تفعيل/إلغاء تفعيل التسجيل في الحلقة')
                                ->default(false)
                                ->live()
                                ->afterStateUpdated(function ($state, $get, $set) {
                                    $maxStudents = $get('max_students') ?? 0;
                                    $currentStudents = $get('enrolled_students') ?? 0;

                                    if ($currentStudents >= $maxStudents && $state === true) {
                                        $set('enrollment_status', false);
                                    }
                                })
                                ->dehydrateStateUsing(fn ($state) => $state ? CircleEnrollmentStatus::OPEN : CircleEnrollmentStatus::CLOSED)
                                ->formatStateUsing(function ($state) {
                                    return $state === CircleEnrollmentStatus::OPEN || $state === CircleEnrollmentStatus::FULL;
                                }),
                        ]),
                ]),

            Section::make('ملاحظات')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Textarea::make('admin_notes')
                                ->label('ملاحظات الإدارة')
                                ->rows(3)
                                ->maxLength(1000)
                                ->helperText('ملاحظات داخلية للإدارة'),

                            Textarea::make('supervisor_notes')
                                ->label('ملاحظات المشرف')
                                ->rows(3)
                                ->maxLength(2000)
                                ->helperText('ملاحظات مرئية للمشرف والإدارة فقط'),
                        ]),
                ]),
        ];
    }

    // ========================================
    // Table Columns Override (SuperAdmin-specific)
    // ========================================

    /**
     * Table columns with academy and teacher columns for SuperAdmin.
     */
    protected static function getTableColumns(): array
    {
        return [
            TextColumn::make('circle_code')
                ->label('رمز الدائرة')
                ->searchable()
                ->fontFamily('mono')
                ->weight(\Filament\Support\Enums\FontWeight::Bold),

            TextColumn::make('academy.name')
                ->label('الأكاديمية')
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('name')
                ->label('اسم الدائرة')
                ->searchable()
                ->limit(30),

            TextColumn::make('quranTeacher.full_name')
                ->label('المعلم')
                ->searchable()
                ->sortable(),

            BadgeColumn::make('memorization_level')
                ->label('المستوى')
                ->formatStateUsing(fn (string $state): string => static::formatMemorizationLevel($state)),

            BadgeColumn::make('age_group')
                ->label('الفئة العمرية')
                ->formatStateUsing(fn (string $state): string => static::formatAgeGroup($state)),

            BadgeColumn::make('gender_type')
                ->label('النوع')
                ->formatStateUsing(fn (string $state): string => static::formatGenderType($state))
                ->colors([
                    'info' => 'male',
                    'success' => 'female',
                    'warning' => 'mixed',
                ]),

            TextColumn::make('students_count')
                ->label('المسجلون')
                ->alignCenter()
                ->color('info')
                ->sortable(),

            TextColumn::make('max_students')
                ->label('الحد الأقصى')
                ->alignCenter(),

            TextColumn::make('schedule_days')
                ->label('أيام الانعقاد')
                ->formatStateUsing(function ($state) {
                    if (! is_array($state) || empty($state)) {
                        return 'غير محدد';
                    }

                    return WeekDays::getDisplayNames($state);
                })
                ->wrap()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('schedule_time')
                ->label('الساعة')
                ->placeholder('غير محدد')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('schedule_status')
                ->label('حالة الجدولة')
                ->getStateUsing(fn ($record) => $record->schedule ? 'مُجدولة' : 'غير مُجدولة')
                ->badge()
                ->color(fn ($state) => $state === 'مُجدولة' ? 'success' : 'warning')
                ->toggleable(),

            TextColumn::make('monthly_fee')
                ->label('الرسوم الشهرية')
                ->money(fn ($record) => $record->academy?->currency?->value ?? config('currencies.default', 'SAR'))
                ->toggleable(),

            BadgeColumn::make('status')
                ->label(__('filament.status'))
                ->formatStateUsing(fn (bool $state): string => $state
                    ? __('enums.circle_active_status.active')
                    : __('enums.circle_active_status.inactive'))
                ->colors([
                    'success' => true,
                    'danger' => false,
                ]),

            Tables\Columns\BadgeColumn::make('enrollment_status')
                ->label(__('filament.circle.enrollment_status'))
                ->formatStateUsing(function ($state): string {
                    if ($state instanceof \App\Enums\CircleEnrollmentStatus) {
                        return $state->label();
                    }

                    return __('enums.circle_enrollment_status.'.$state) ?? $state;
                })
                ->color(function ($state): string {
                    if ($state instanceof \App\Enums\CircleEnrollmentStatus) {
                        return $state->color();
                    }

                    return match ($state) {
                        'open' => 'success',
                        'closed' => 'gray',
                        'full' => 'warning',
                        'waitlist' => 'info',
                        default => 'gray',
                    };
                })
                ->alignCenter()
                ->sortable(),
        ];
    }

    // ========================================
    // Table Filters Override (SuperAdmin-specific)
    // ========================================

    /**
     * Extended filters with enrollment status, date range, and trashed.
     */
    protected static function getTableFilters(): array
    {
        return [
            Tables\Filters\TernaryFilter::make('status')
                ->label(__('filament.status'))
                ->trueLabel(__('filament.tabs.active'))
                ->falseLabel(__('filament.tabs.paused'))
                ->placeholder(__('filament.all')),

            SelectFilter::make('memorization_level')
                ->label(__('filament.circle.memorization_level'))
                ->options(QuranCircle::MEMORIZATION_LEVELS),

            SelectFilter::make('enrollment_status')
                ->label(__('filament.circle.enrollment_status'))
                ->options([
                    'open' => __('filament.circle.enrollment_open'),
                    'closed' => __('filament.circle.enrollment_closed'),
                    'full' => __('filament.circle.enrollment_full'),
                ]),

            SelectFilter::make('age_group')
                ->label(__('filament.circle.age_group'))
                ->options([
                    'children' => __('filament.circle.children'),
                    'youth' => __('filament.circle.youth'),
                    'adults' => __('filament.circle.adults'),
                    'all_ages' => __('filament.circle.all_ages'),
                ]),

            SelectFilter::make('gender_type')
                ->label(__('filament.gender_type'))
                ->options([
                    'male' => __('filament.circle.male'),
                    'female' => __('filament.circle.female'),
                    'mixed' => __('filament.circle.mixed'),
                ]),

            Filter::make('available_spots')
                ->label(__('filament.circle.available_spots'))
                ->query(fn (Builder $query): Builder => $query->whereRaw('(SELECT COUNT(*) FROM quran_circle_students WHERE circle_id = quran_circles.id) < max_students')),

            Filter::make('created_at')
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
                        $indicators['from'] = __('filament.filters.from_date').': '.$data['from'];
                    }
                    if ($data['until'] ?? null) {
                        $indicators['until'] = __('filament.filters.to_date').': '.$data['until'];
                    }

                    return $indicators;
                }),

        ];
    }

    // ========================================
    // Authorization Overrides
    // ========================================

    public static function canDelete(Model $record): bool
    {
        return true;
    }

    // ========================================
    // Navigation Badge
    // ========================================

    public static function getNavigationBadge(): ?string
    {
        $query = static::getEloquentQuery()->where('status', false);

        return $query->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'info';
    }

    // ========================================
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuranCircles::route('/'),
            'create' => Pages\CreateQuranCircle::route('/create'),
            'view' => Pages\ViewQuranCircle::route('/{record}'),
            'edit' => Pages\EditQuranCircle::route('/{record}/edit'),
        ];
    }
}
