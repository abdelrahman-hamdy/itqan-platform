<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InteractiveSessionReportResource\Pages;
use App\Filament\Shared\Resources\BaseInteractiveSessionReportResource;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

/**
 * Interactive Session Report Resource for SuperAdmin Panel
 *
 * Full CRUD access with all filters and options.
 * Extends BaseInteractiveSessionReportResource for shared form/table definitions.
 */
class InteractiveSessionReportResource extends BaseInteractiveSessionReportResource
{
    // ========================================
    // Navigation Configuration
    // ========================================

    protected static ?string $navigationGroup = 'التقارير والحضور';

    protected static ?int $navigationSort = 3;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * SuperAdmin sees all reports.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        return $query->with(['teacher', 'academy']);
    }

    /**
     * Full session info section with teacher and academy selection.
     */
    protected static function getSessionInfoFormSection(): Section
    {
        return Section::make('معلومات الجلسة')
            ->schema([
                Forms\Components\Select::make('session_id')
                    ->relationship('session', 'title')
                    ->label('الجلسة التفاعلية')
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\Select::make('student_id')
                    ->label('الطالب')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->options(function () {
                        return \App\Models\User::whereHas('studentProfile')
                            ->get()
                            ->mapWithKeys(fn ($user) => [
                                $user->id => $user->display_name ?? $user->name ?? 'طالب #'.$user->id,
                            ])
                            ->toArray();
                    })
                    ->getOptionLabelUsing(fn ($value) => \App\Models\User::find($value)?->display_name
                        ?? \App\Models\User::find($value)?->name
                        ?? 'طالب #'.$value
                    ),

                Forms\Components\Select::make('teacher_id')
                    ->label('المعلم')
                    ->nullable()
                    ->searchable()
                    ->preload()
                    ->options(function () {
                        return \App\Models\User::whereHas('quranTeacherProfile')
                            ->orWhereHas('academicTeacherProfile')
                            ->get()
                            ->mapWithKeys(fn ($user) => [
                                $user->id => $user->display_name ?? $user->name ?? 'معلم #'.$user->id,
                            ])
                            ->toArray();
                    })
                    ->getOptionLabelUsing(fn ($value) => \App\Models\User::find($value)?->display_name
                        ?? \App\Models\User::find($value)?->name
                        ?? 'معلم #'.$value
                    ),

                Forms\Components\Select::make('academy_id')
                    ->relationship('academy', 'name')
                    ->label('الأكاديمية')
                    ->nullable()
                    ->searchable()
                    ->preload(),
            ])->columns(2);
    }

    /**
     * Full table actions for SuperAdmin.
     */
    protected static function getTableActions(): array
    {
        return [
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
        ];
    }

    /**
     * Full bulk actions for SuperAdmin.
     */
    protected static function getTableBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ];
    }

    // ========================================
    // Additional Form Sections (SuperAdmin-specific)
    // ========================================

    /**
     * Add detailed attendance section for SuperAdmin.
     */
    protected static function getAdditionalFormSections(): array
    {
        return [
            static::getDetailedAttendanceFormSection(),
            static::getSystemInfoFormSection(),
        ];
    }

    /**
     * Detailed attendance section - SuperAdmin only.
     */
    protected static function getDetailedAttendanceFormSection(): Section
    {
        return Section::make('تفاصيل الحضور')
            ->schema([
                Forms\Components\DateTimePicker::make('meeting_enter_time')
                    ->label('وقت الدخول للجلسة')
                    ->live(),

                Forms\Components\DateTimePicker::make('meeting_leave_time')
                    ->label('وقت الخروج من الجلسة')
                    ->after('meeting_enter_time'),

                Forms\Components\TextInput::make('actual_attendance_minutes')
                    ->label('دقائق الحضور الفعلي')
                    ->numeric()
                    ->default(0)
                    ->suffix('دقيقة'),

                Forms\Components\Toggle::make('is_late')
                    ->label('الطالب متأخر'),

                Forms\Components\TextInput::make('late_minutes')
                    ->label('دقائق التأخير')
                    ->numeric()
                    ->default(0)
                    ->suffix('دقيقة')
                    ->visible(fn (Forms\Get $get) => $get('is_late')),

                Forms\Components\TextInput::make('attendance_percentage')
                    ->label('نسبة الحضور')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%')
                    ->default(0),
            ])->columns(3);
    }

    /**
     * System info section - SuperAdmin only.
     */
    protected static function getSystemInfoFormSection(): Section
    {
        return Section::make('معلومات النظام')
            ->schema([
                Forms\Components\DateTimePicker::make('evaluated_at')
                    ->label('تاريخ التقييم'),

                Forms\Components\Toggle::make('is_calculated')
                    ->label('محسوب تلقائياً')
                    ->default(true),

                Forms\Components\Toggle::make('manually_evaluated')
                    ->label('معدل يدوياً')
                    ->default(false),

                Forms\Components\Textarea::make('override_reason')
                    ->label('سبب التعديل اليدوي')
                    ->visible(fn (Forms\Get $get) => $get('manually_evaluated'))
                    ->columnSpanFull(),
            ])->columns(3);
    }

    // ========================================
    // Table Columns Override (SuperAdmin-specific)
    // ========================================

    /**
     * Add teacher, academy, and attendance percentage columns for SuperAdmin.
     */
    protected static function getTableColumns(): array
    {
        $columns = parent::getTableColumns();

        // Add teacher column
        $teacherColumn = TextColumn::make('teacher.name')
            ->label('المعلم')
            ->searchable()
            ->sortable()
            ->toggleable();

        // Add academy column
        $academyColumn = TextColumn::make('academy.name')
            ->label('الأكاديمية')
            ->searchable()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);

        // Add attendance percentage column
        $attendancePercentageColumn = TextColumn::make('attendance_percentage')
            ->label('نسبة الحضور')
            ->numeric()
            ->sortable()
            ->formatStateUsing(fn (string $state): string => $state.'%')
            ->toggleable();

        // Insert columns at appropriate positions
        $result = [];
        foreach ($columns as $column) {
            $result[] = $column;

            // Add teacher after student
            if ($column->getName() === 'student.name') {
                $result[] = $teacherColumn;
            }

            // Add attendance percentage after attendance_status
            if ($column->getName() === 'attendance_status') {
                $result[] = $attendancePercentageColumn;
            }
        }

        // Add academy at the end before toggleable columns
        array_splice($result, -3, 0, [$academyColumn]);

        return $result;
    }

    // ========================================
    // Table Filters Override (SuperAdmin-specific)
    // ========================================

    /**
     * Extended filters with teacher, student, and academy.
     */
    protected static function getTableFilters(): array
    {
        return [
            ...parent::getTableFilters(),

            Tables\Filters\SelectFilter::make('teacher_id')
                ->label('المعلم')
                ->relationship('teacher', 'name')
                ->searchable()
                ->preload(),

            Tables\Filters\SelectFilter::make('student_id')
                ->label('الطالب')
                ->relationship('student', 'name')
                ->searchable()
                ->preload(),

            Tables\Filters\SelectFilter::make('academy_id')
                ->label('الأكاديمية')
                ->relationship('academy', 'name')
                ->searchable()
                ->preload(),
        ];
    }

    // ========================================
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInteractiveSessionReports::route('/'),
            'create' => Pages\CreateInteractiveSessionReport::route('/create'),
            'view' => Pages\ViewInteractiveSessionReport::route('/{record}'),
            'edit' => Pages\EditInteractiveSessionReport::route('/{record}/edit'),
        ];
    }
}
