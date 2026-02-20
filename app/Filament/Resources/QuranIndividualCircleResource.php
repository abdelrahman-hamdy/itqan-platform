<?php

namespace App\Filament\Resources;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use App\Models\QuranTeacherProfile;
use Exception;
use Log;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\DatePicker;
use App\Filament\Resources\QuranIndividualCircleResource\Pages\ListQuranIndividualCircles;
use App\Filament\Resources\QuranIndividualCircleResource\Pages\CreateQuranIndividualCircle;
use App\Filament\Resources\QuranIndividualCircleResource\Pages\ViewQuranIndividualCircle;
use App\Filament\Resources\QuranIndividualCircleResource\Pages\EditQuranIndividualCircle;
use App\Enums\UserType;
use App\Filament\Resources\QuranIndividualCircleResource\Pages;
use App\Filament\Shared\Resources\BaseQuranIndividualCircleResource;
use App\Models\QuranIndividualCircle;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Quran Individual Circle Resource for SuperAdmin Panel
 *
 * Full CRUD access with soft delete support.
 * Extends BaseQuranIndividualCircleResource for shared form/table definitions.
 */
class QuranIndividualCircleResource extends BaseQuranIndividualCircleResource
{
    // ========================================
    // Tenant Configuration
    // ========================================

    protected static ?string $tenantOwnershipRelationshipName = 'academy';

    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy';
    }

    // ========================================
    // Navigation Configuration
    // ========================================

    protected static ?string $navigationLabel = 'الحلقات الفردية';

    protected static string | \UnitEnum | null $navigationGroup = 'إدارة القرآن';

    protected static ?int $navigationSort = 4;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * SuperAdmin sees all circles, including soft-deleted ones.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        // Include soft-deleted records for admin management
        return $query->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /**
     * Get basic info section with teacher/student selection.
     */
    protected static function getBasicInfoFormSection(): Section
    {
        return Section::make('معلومات الحلقة الأساسية')
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('circle_code')
                            ->label('رمز الحلقة')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('يتم إنشاؤه تلقائياً'),

                        TextInput::make('name')
                            ->label('اسم الحلقة')
                            ->maxLength(255)
                            ->helperText('يتم إنشاؤه تلقائياً إذا تُرك فارغاً'),

                        Select::make('quran_teacher_id')
                            ->label('معلم القرآن')
                            ->options(function () {
                                try {
                                    $academyId = AcademyContextService::getCurrentAcademyId();

                                    $teachers = QuranTeacherProfile::when($academyId, function ($query) use ($academyId) {
                                        return $query->where('academy_id', $academyId);
                                    })
                                        ->whereHas('user', fn ($q) => $q->where('active_status', true))
                                        ->get();

                                    if ($teachers->isEmpty()) {
                                        return ['0' => 'لا توجد معلمين نشطين'];
                                    }

                                    return $teachers->mapWithKeys(function ($teacher) {
                                        $userId = $teacher->user_id;
                                        $fullName = $teacher->display_name ?? $teacher->full_name ?? 'معلم غير محدد';

                                        return [$userId => $fullName];
                                    })->toArray();
                                } catch (Exception $e) {
                                    Log::error('Error loading teachers: '.$e->getMessage());

                                    return ['0' => 'خطأ في تحميل المعلمين'];
                                }
                            })
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('student_id')
                            ->label('الطالب')
                            ->options(function () {
                                $academyId = AcademyContextService::getCurrentAcademyId();

                                return User::where('user_type', UserType::STUDENT->value)
                                    ->when($academyId, fn ($q) => $q->where('academy_id', $academyId))
                                    ->with('studentProfile')
                                    ->limit(100)
                                    ->get()
                                    ->mapWithKeys(function ($user) {
                                        $displayName = $user->studentProfile?->display_name ?? $user->name;

                                        return [$user->id => $displayName];
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),

                Textarea::make('description')
                    ->label('وصف الحلقة')
                    ->rows(2)
                    ->maxLength(500)
                    ->columnSpanFull(),

                TagsInput::make('learning_objectives')
                    ->label('أهداف التعلم')
                    ->placeholder('أضف هدفاً تعليمياً')
                    ->helperText('أهداف محددة لهذه الحلقة الفردية')
                    ->reorderable()
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label('الحلقة نشطة')
                    ->default(true)
                    ->helperText('يتم تعطيلها تلقائياً عند إيقاف الاشتراك')
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Full table actions for SuperAdmin with soft deletes.
     */
    protected static function getTableActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->label('عرض'),
                EditAction::make()
                    ->label('تعديل'),
                Action::make('toggle_status')
                    ->label(fn (QuranIndividualCircle $record) => $record->is_active ? 'إيقاف' : 'تفعيل')
                    ->icon(fn (QuranIndividualCircle $record) => $record->is_active ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
                    ->color(fn (QuranIndividualCircle $record) => $record->is_active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (QuranIndividualCircle $record) => $record->is_active ? 'إيقاف الحلقة' : 'تفعيل الحلقة')
                    ->modalDescription(fn (QuranIndividualCircle $record) => $record->is_active
                        ? 'هل أنت متأكد من إيقاف هذه الحلقة الفردية؟'
                        : 'هل أنت متأكد من تفعيل هذه الحلقة الفردية؟'
                    )
                    ->action(fn (QuranIndividualCircle $record) => $record->update([
                        'is_active' => ! $record->is_active,
                    ])),
                Action::make('view_sessions')
                    ->label('الجلسات')
                    ->icon('heroicon-o-calendar-days')
                    ->url(fn (QuranIndividualCircle $record): string => QuranSessionResource::getUrl('index', [
                        'tableFilters[individual_circle_id][value]' => $record->id,
                    ])),
                DeleteAction::make()
                    ->label('حذف'),
                RestoreAction::make()
                    ->label('استعادة'),
                ForceDeleteAction::make()
                    ->label('حذف نهائي'),
            ]),
        ];
    }

    /**
     * Full bulk actions for SuperAdmin.
     */
    protected static function getTableBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make()
                    ->label('حذف المحدد'),
                RestoreBulkAction::make()
                    ->label('استعادة المحدد'),
                ForceDeleteBulkAction::make()
                    ->label('حذف نهائي للمحدد'),
            ]),
        ];
    }

    // ========================================
    // Form Sections Override (SuperAdmin-specific)
    // ========================================

    /**
     * Notes section for SuperAdmin.
     */
    protected static function getAdditionalFormSections(): array
    {
        return [
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
     * Table columns with circle code, teacher, and academy.
     */
    protected static function getTableColumns(): array
    {
        return [
            TextColumn::make('circle_code')
                ->label('رمز الحلقة')
                ->searchable()
                ->sortable()
                ->fontFamily('mono')
                ->weight(FontWeight::Bold),

            TextColumn::make('name')
                ->label('اسم الحلقة')
                ->searchable()
                ->sortable()
                ->limit(25)
                ->tooltip(fn ($record) => $record->name)
                ->toggleable(),

            TextColumn::make('quranTeacher.name')
                ->label('المعلم')
                ->searchable()
                ->sortable()
                ->toggleable(),

            TextColumn::make('student.name')
                ->label('الطالب')
                ->searchable()
                ->sortable()
                ->toggleable(),

            TextColumn::make('specialization')
                ->badge()
                ->label('التخصص')
                ->formatStateUsing(fn (string $state): string => QuranIndividualCircle::SPECIALIZATIONS[$state] ?? $state)
                ->colors([
                    'success' => 'memorization',
                    'info' => 'recitation',
                    'warning' => 'interpretation',
                    'danger' => 'tajweed',
                    'primary' => 'complete',
                ])
                ->toggleable(),

            TextColumn::make('memorization_level')
                ->badge()
                ->label('المستوى')
                ->formatStateUsing(fn (string $state): string => QuranIndividualCircle::MEMORIZATION_LEVELS[$state] ?? $state)
                ->color('gray')
                ->toggleable(),

            TextColumn::make('sessions_completed')
                ->label('الجلسات')
                ->formatStateUsing(fn ($record): string => "{$record->sessions_completed} / {$record->total_sessions}")
                ->alignCenter()
                ->sortable()
                ->toggleable(),

            IconColumn::make('is_active')
                ->label('الحالة')
                ->boolean()
                ->trueIcon('heroicon-o-check-circle')
                ->falseIcon('heroicon-o-x-circle')
                ->trueColor('success')
                ->falseColor('danger'),

            TextColumn::make('created_at')
                ->label('تاريخ الإنشاء')
                ->dateTime('Y-m-d')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    // ========================================
    // Table Filters Override (SuperAdmin-specific)
    // ========================================

    /**
     * Extended filters with teacher, academy, and trashed.
     */
    protected static function getTableFilters(): array
    {
        return [
            TernaryFilter::make('is_active')
                ->label('الحالة')
                ->trueLabel('نشطة')
                ->falseLabel('غير نشطة')
                ->placeholder('الكل'),

            SelectFilter::make('quran_teacher_id')
                ->label('المعلم')
                ->relationship('quranTeacher', 'name')
                ->searchable()
                ->preload(),

            Filter::make('created_at')
                ->columnSpan(2)
                ->columns(2)
                ->schema([
                    DatePicker::make('from')
                        ->label('من تاريخ'),
                    DatePicker::make('until')
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
        ];
    }

    // ========================================
    // Navigation Badge
    // ========================================

    public static function getNavigationBadge(): ?string
    {
        $query = static::getEloquentQuery()->where('is_active', false);

        return $query->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    // ========================================
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => ListQuranIndividualCircles::route('/'),
            'create' => CreateQuranIndividualCircle::route('/create'),
            'view' => ViewQuranIndividualCircle::route('/{record}'),
            'edit' => EditQuranIndividualCircle::route('/{record}/edit'),
        ];
    }
}
