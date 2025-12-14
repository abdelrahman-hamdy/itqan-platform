<?php

namespace App\Filament\AcademicTeacher\Resources;

use App\Filament\AcademicTeacher\Resources\InteractiveSessionReportResource\Pages;
use App\Models\InteractiveSessionReport;
use App\Enums\AttendanceStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Interactive Session Report Resource for AcademicTeacher Panel
 *
 * Allows academic teachers to view and manage session reports for interactive courses.
 */
class InteractiveSessionReportResource extends Resource
{
    protected static ?string $model = InteractiveSessionReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'تقارير الدورات التفاعلية';

    protected static ?string $modelLabel = 'تقرير جلسة تفاعلية';

    protected static ?string $pluralModelLabel = 'تقارير الجلسات التفاعلية';

    protected static ?string $navigationGroup = 'التقييمات';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الجلسة')
                    ->schema([
                        Forms\Components\Select::make('session_id')
                            ->relationship('session', 'id', fn (Builder $query) =>
                                $query->whereHas('course', fn ($q) =>
                                    $q->where('assigned_teacher_id', Auth::user()->academicTeacherProfile?->id)
                                )
                            )
                            ->label('الجلسة')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn ($record) =>
                                $record->course?->name . ' - ' . $record->scheduled_date?->format('Y-m-d')
                            ),
                        Forms\Components\Select::make('student_id')
                            ->relationship('student', 'name')
                            ->label('الطالب')
                            ->required()
                            ->searchable()
                            ->disabled(fn (?InteractiveSessionReport $record) => $record !== null),
                    ])->columns(2),

                Forms\Components\Section::make('تقييم الواجب')
                    ->schema([
                        Forms\Components\TextInput::make('homework_degree')
                            ->label('درجة الواجب (0-10)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10)
                            ->step(0.5)
                            ->helperText('تقييم جودة وإنجاز الواجب المنزلي'),
                    ]),

                Forms\Components\Section::make('الملاحظات')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات المعلم')
                            ->placeholder('أضف ملاحظات حول أداء الطالب...')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('تعديل الحضور (إذا لزم الأمر)')
                    ->schema([
                        Forms\Components\Select::make('attendance_status')
                            ->label('حالة الحضور')
                            ->options(AttendanceStatus::options())
                            ->helperText('قم بالتغيير فقط إذا كان الحساب التلقائي غير صحيح'),
                        Forms\Components\Toggle::make('manually_evaluated')
                            ->label('تم التقييم يدوياً')
                            ->helperText('حدد هذا إذا كنت تقوم بتعديل الحضور التلقائي'),
                        Forms\Components\Textarea::make('override_reason')
                            ->label('سبب التعديل')
                            ->placeholder('اشرح سبب تعديل الحضور التلقائي...')
                            ->visible(fn (Forms\Get $get) => $get('manually_evaluated'))
                            ->columnSpanFull(),
                    ])->columns(2)
                    ->collapsed()
                    ->description('افتح هذا القسم فقط إذا كنت بحاجة إلى تصحيح الحضور يدوياً'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('session.course.name')
                    ->label('الدورة')
                    ->searchable()
                    ->sortable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('session.scheduled_date')
                    ->label('تاريخ الجلسة')
                    ->date('Y-m-d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('homework_degree')
                    ->label('درجة الواجب')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (?string $state): string => match (true) {
                        $state === null => 'gray',
                        (float) $state >= 8 => 'success',
                        (float) $state >= 6 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (?string $state): string => $state ? $state . '/10' : 'لم يقيم'),

                Tables\Columns\TextColumn::make('attendance_status')
                    ->label('الحضور')
                    ->badge()
                    ->formatStateUsing(function (?string $state): string {
                        if (!$state) return '-';
                        try {
                            return AttendanceStatus::from($state)->label();
                        } catch (\ValueError $e) {
                            return $state;
                        }
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'attended' => 'success',
                        'late' => 'warning',
                        'leaved' => 'info',
                        'absent' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('actual_attendance_minutes')
                    ->label('مدة الحضور')
                    ->formatStateUsing(fn (?string $state): string => $state ? $state . ' دقيقة' : '-')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('manually_evaluated')
                    ->label('تعديل يدوي')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('evaluated_at')
                    ->label('تاريخ التقييم')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('attendance_status')
                    ->label('حالة الحضور')
                    ->options(AttendanceStatus::options()),

                Tables\Filters\Filter::make('has_homework_grade')
                    ->label('تم تقييم الواجب')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('homework_degree')),

                Tables\Filters\Filter::make('not_graded')
                    ->label('بدون تقييم')
                    ->query(fn (Builder $query): Builder => $query->whereNull('homework_degree')),

                Tables\Filters\SelectFilter::make('session_id')
                    ->label('الجلسة')
                    ->relationship('session', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) =>
                        $record->course?->name . ' - ' . $record->scheduled_date?->format('Y-m-d')
                    )
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تقييم'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) =>
                $query->whereHas('session.course', fn ($q) =>
                    $q->where('assigned_teacher_id', Auth::user()->academicTeacherProfile?->id)
                )
            );
    }

    public static function getRelations(): array
    {
        return [];
    }

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
