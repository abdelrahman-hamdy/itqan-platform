<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AcademicSessionReportResource\Pages;
use App\Filament\Resources\AcademicSessionReportResource\Pages\CreateAcademicSessionReport;
use App\Filament\Resources\AcademicSessionReportResource\Pages\EditAcademicSessionReport;
use App\Filament\Resources\AcademicSessionReportResource\Pages\ListAcademicSessionReports;
use App\Filament\Resources\AcademicSessionReportResource\Pages\ViewAcademicSessionReport;
use App\Filament\Shared\Resources\BaseAcademicSessionReportResource;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

/**
 * Academic Session Report Resource for SuperAdmin Panel
 *
 * Full CRUD access with all filters and options.
 * Extends BaseAcademicSessionReportResource for shared form/table definitions.
 */
class AcademicSessionReportResource extends BaseAcademicSessionReportResource
{
    // ========================================
    // Navigation Configuration
    // ========================================

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|\UnitEnum|null $navigationGroup = 'التقارير والحضور';

    protected static ?int $navigationSort = 2;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * SuperAdmin sees all reports.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        return $query->with(['teacher']);
    }

    /**
     * Full session info section with teacher and academy selection.
     */
    protected static function getSessionInfoFormSection(): Section
    {
        return Section::make('معلومات الجلسة')
            ->schema([
                Select::make('session_id')
                    ->relationship('session', 'title')
                    ->label('الجلسة')
                    ->required()
                    ->searchable()
                    ->preload(),

                Select::make('student_id')
                    ->label('الطالب')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->options(function () {
                        return User::whereHas('studentProfile')
                            ->get()
                            ->mapWithKeys(fn ($user) => [
                                $user->id => $user->display_name ?? $user->name ?? 'طالب #'.$user->id,
                            ])
                            ->toArray();
                    })
                    ->getOptionLabelUsing(fn ($value) => User::find($value)?->display_name
                        ?? User::find($value)?->name
                        ?? 'طالب #'.$value
                    ),

                Select::make('teacher_id')
                    ->label('المعلم')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->options(function () {
                        return User::whereHas('quranTeacherProfile')
                            ->orWhereHas('academicTeacherProfile')
                            ->get()
                            ->mapWithKeys(fn ($user) => [
                                $user->id => $user->display_name ?? $user->name ?? 'معلم #'.$user->id,
                            ])
                            ->toArray();
                    })
                    ->getOptionLabelUsing(fn ($value) => User::find($value)?->display_name
                        ?? User::find($value)?->name
                        ?? 'معلم #'.$value
                    ),

                Select::make('academy_id')
                    ->relationship('academy', 'name')
                    ->label('الأكاديمية')
                    ->required()
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
            ActionGroup::make([
                ViewAction::make()->label('عرض'),
                EditAction::make()->label('تعديل'),
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
                DeleteBulkAction::make(),
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
                DateTimePicker::make('meeting_enter_time')
                    ->label('وقت الدخول للجلسة')
                    ->live(),

                DateTimePicker::make('meeting_leave_time')
                    ->label('وقت الخروج من الجلسة')
                    ->after('meeting_enter_time'),

                TextInput::make('actual_attendance_minutes')
                    ->label('دقائق الحضور الفعلي')
                    ->numeric()
                    ->default(0)
                    ->suffix('دقيقة'),

                Toggle::make('is_late')
                    ->label('الطالب متأخر'),

                TextInput::make('late_minutes')
                    ->label('دقائق التأخير')
                    ->numeric()
                    ->default(0)
                    ->suffix('دقيقة')
                    ->visible(fn (Get $get) => $get('is_late')),

                TextInput::make('attendance_percentage')
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
                DateTimePicker::make('evaluated_at')
                    ->label('تاريخ التقييم'),

                Toggle::make('is_calculated')
                    ->label('محسوب تلقائياً')
                    ->default(true),
            ])->columns(3);
    }

    // ========================================
    // Table Columns Override (SuperAdmin-specific)
    // ========================================

    /**
     * Add teacher and academy columns for SuperAdmin.
     */
    protected static function getTableColumns(): array
    {
        $columns = parent::getTableColumns();

        // Add teacher column after student
        $teacherColumn = TextColumn::make('teacher.name')
            ->label('المعلم')
            ->searchable()
            ->sortable();

        // Add academy column
        $academyColumn = static::getAcademyColumn();

        // Add attendance percentage column
        $attendancePercentageColumn = TextColumn::make('attendance_percentage')
            ->label('نسبة الحضور')
            ->numeric()
            ->sortable()
            ->formatStateUsing(fn (string $state): string => $state.'%');

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
     * Extended filters with teacher.
     */
    protected static function getTableFilters(): array
    {
        return [
            ...parent::getTableFilters(),

            SelectFilter::make('teacher_id')
                ->label('المعلم')
                ->relationship('teacher', 'first_name', fn (Builder $query) => $query->whereIn('user_type', ['quran_teacher', 'academic_teacher']))
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->name ?? $record->first_name ?? 'معلم #'.$record->id)
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
            'index' => ListAcademicSessionReports::route('/'),
            'create' => CreateAcademicSessionReport::route('/create'),
            'view' => ViewAcademicSessionReport::route('/{record}'),
            'edit' => EditAcademicSessionReport::route('/{record}/edit'),
        ];
    }
}
