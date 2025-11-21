<?php

namespace App\Filament\Teacher\Resources;

use App\Filament\Teacher\Resources\StudentSessionReportResource\Pages;
use App\Models\StudentSessionReport;
use App\Services\StudentReportService;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StudentSessionReportResource extends Resource
{
    protected static ?string $model = StudentSessionReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'تقارير الطلاب';

    protected static ?string $modelLabel = 'تقرير طالب';

    protected static ?string $pluralModelLabel = 'تقارير الطلاب';

    protected static ?string $navigationGroup = 'جلساتي';

    protected static ?int $navigationSort = 3;

    // Scope to only the current teacher's reports
    public static function getEloquentQuery(): Builder
    {
        $teacher = Auth::user();

        if (! $teacher->isQuranTeacher() && ! $teacher->isAcademicTeacher()) {
            return parent::getEloquentQuery()->whereRaw('1 = 0'); // Return no results
        }

        return parent::getEloquentQuery()
            ->with(['student', 'session.circle', 'session.individualCircle'])
            ->whereHas('session', function (Builder $query) use ($teacher) {
                $query->where('quran_teacher_id', $teacher->id);
            });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('new_memorization_degree')
                            ->label('درجة الحفظ الجديد (0-10)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10)
                            ->step(0.5),

                        TextInput::make('reservation_degree')
                            ->label('درجة المراجعة (0-10)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10)
                            ->step(0.5),
                    ]),

                Select::make('attendance_status')
                    ->label('حالة الحضور')
                    ->options([
                        'attended' => 'حاضر',
                        'late' => 'متأخر',
                        'leaved' => 'غادر مبكراً',
                        'absent' => 'غائب',
                    ])
                    ->helperText('اختياري - اتركه فارغاً للاحتفاظ بالحالة المحسوبة تلقائياً'),

                Textarea::make('notes')
                    ->label('ملاحظات التقييم')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('اسم الطالب')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('session.scheduled_at')
                    ->label('تاريخ الجلسة')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('session.session_type')
                    ->label('نوع الجلسة')
                    ->formatStateUsing(function (string $state): string {
                        return match ($state) {
                            'individual' => 'فردية',
                            'group' => 'جماعية',
                            default => $state,
                        };
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'individual' => 'info',
                        'group' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('attendance_status')
                    ->label('حالة الحضور')
                    ->formatStateUsing(function (string $state): string {
                        return match ($state) {
                            'attended' => 'حاضر',
                            'late' => 'متأخر',
                            'leaved' => 'غادر مبكراً',
                            'absent' => 'غائب',
                            default => $state,
                        };
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'attended' => 'success',
                        'late' => 'warning',
                        'leaved' => 'info',
                        'absent' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('attendance_percentage')
                    ->label('نسبة الحضور')
                    ->suffix('%')
                    ->numeric(2)
                    ->sortable(),

                Tables\Columns\TextColumn::make('new_memorization_degree')
                    ->label('درجة الحفظ')
                    ->suffix('/10')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reservation_degree')
                    ->label('درجة المراجعة')
                    ->suffix('/10')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_calculated')
                    ->label('محسوب تلقائياً')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-pencil'),

                Tables\Columns\TextColumn::make('evaluated_at')
                    ->label('تاريخ التقييم')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('attendance_status')
                    ->label('حالة الحضور')
                    ->options([
                        'attended' => 'حاضر',
                        'late' => 'متأخر',
                        'leaved' => 'غادر مبكراً',
                        'absent' => 'غائب',
                    ]),

                SelectFilter::make('session_type')
                    ->label('نوع الجلسة')
                    ->options([
                        'individual' => 'فردية',
                        'group' => 'جماعية',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! $data['value']) {
                            return $query;
                        }

                        return $query->whereHas('session', function (Builder $subQuery) use ($data) {
                            $subQuery->where('session_type', $data['value']);
                        });
                    }),

                Filter::make('date_range')
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
                                fn (Builder $query, $date): Builder => $query->whereHas('session', function (Builder $subQuery) use ($date) {
                                    $subQuery->whereDate('scheduled_at', '>=', $date);
                                }),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereHas('session', function (Builder $subQuery) use ($date) {
                                    $subQuery->whereDate('scheduled_at', '<=', $date);
                                }),
                            );
                    }),

                SelectFilter::make('is_calculated')
                    ->label('نوع التقييم')
                    ->options([
                        '1' => 'محسوب تلقائياً',
                        '0' => 'مقيم يدوياً',
                    ]),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('عرض'),
                    Tables\Actions\EditAction::make()
                        ->label('تعديل'),
                    Action::make('update_evaluation')
                        ->label('تحديث التقييم')
                        ->icon('heroicon-o-pencil')
                        ->form([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('new_memorization_degree')
                                        ->label('درجة الحفظ الجديد (0-10)')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(10)
                                        ->step(0.5),

                                    TextInput::make('reservation_degree')
                                        ->label('درجة المراجعة (0-10)')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(10)
                                        ->step(0.5),
                                ]),

                            Select::make('attendance_status')
                                ->label('حالة الحضور')
                                ->options([
                                    'present' => 'حاضر',
                                    'late' => 'متأخر',
                                    'partial' => 'جزئي',
                                    'absent' => 'غائب',
                                ])
                                ->helperText('اختياري - اتركه فارغاً للاحتفاظ بالحالة المحسوبة تلقائياً'),

                            Textarea::make('notes')
                                ->label('ملاحظات التقييم')
                                ->rows(3),
                        ])
                        ->action(function (StudentSessionReport $record, array $data): void {
                            $studentReportService = app(StudentReportService::class);

                            $studentReportService->updateTeacherEvaluation(
                                $record,
                                $data['new_memorization_degree'] ?? 0,
                                $data['reservation_degree'] ?? 0,
                                $data['notes'] ?? null
                            );

                            // Update attendance status if provided
                            if (! empty($data['attendance_status'])) {
                                $record->update([
                                    'attendance_status' => $data['attendance_status'],
                                    'manually_evaluated' => true,
                                ]);
                            }

                            Notification::make()
                                ->title('تم تحديث التقييم بنجاح')
                                ->success()
                                ->send();
                        })
                        ->fillForm(fn (StudentSessionReport $record): array => [
                            'new_memorization_degree' => $record->new_memorization_degree,
                            'reservation_degree' => $record->reservation_degree,
                            'attendance_status' => $record->manually_evaluated ? $record->attendance_status : '',
                            'notes' => $record->notes,
                        ]),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Add bulk actions if needed
                ]),
            ])
            ->defaultSort('session.scheduled_at', 'desc')
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentSessionReports::route('/'),
            'view' => Pages\ViewStudentSessionReport::route('/{record}'),
            'edit' => Pages\EditStudentSessionReport::route('/{record}/edit'),
        ];
    }

    public static function getBreadcrumb(): string
    {
        return static::$pluralModelLabel ?? 'تقارير الطلاب';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && (Auth::user()->isQuranTeacher() || Auth::user()->isAcademicTeacher());
    }
}
