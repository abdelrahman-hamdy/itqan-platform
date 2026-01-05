<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AcademicSessionReportResource\Pages;
use App\Filament\Resources\AcademicSessionReportResource\RelationManagers;
use App\Models\AcademicSessionReport;
use App\Enums\AttendanceStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AcademicSessionReportResource extends Resource
{
    protected static ?string $model = AcademicSessionReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'تقارير الجلسات الأكاديمية';

    protected static ?string $modelLabel = 'تقرير جلسة أكاديمية';

    protected static ?string $pluralModelLabel = 'تقارير الجلسات الأكاديمية';

    protected static ?string $navigationGroup = 'التقارير والحضور';

    protected static ?int $navigationSort = 2;

    /**
     * Eager load relationships to prevent N+1 queries
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'session',
                'student',
                'teacher',
                'academy',
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الجلسة')
                    ->schema([
                        Forms\Components\Select::make('session_id')
                            ->relationship('session', 'title')
                            ->label('الجلسة')
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
                                        $user->id => $user->display_name ?? $user->name ?? 'طالب #' . $user->id
                                    ])
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(fn ($value) =>
                                \App\Models\User::find($value)?->display_name
                                ?? \App\Models\User::find($value)?->name
                                ?? 'طالب #' . $value
                            ),
                        Forms\Components\Select::make('teacher_id')
                            ->label('المعلم')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->options(function () {
                                return \App\Models\User::whereHas('quranTeacherProfile')
                                    ->orWhereHas('academicTeacherProfile')
                                    ->get()
                                    ->mapWithKeys(fn ($user) => [
                                        $user->id => $user->display_name ?? $user->name ?? 'معلم #' . $user->id
                                    ])
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(fn ($value) =>
                                \App\Models\User::find($value)?->display_name
                                ?? \App\Models\User::find($value)?->name
                                ?? 'معلم #' . $value
                            ),
                        Forms\Components\Select::make('academy_id')
                            ->relationship('academy', 'name')
                            ->label('الأكاديمية')
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])->columns(2),

                Forms\Components\Section::make('الأداء الأكاديمي')
                    ->schema([
                        Forms\Components\TextInput::make('homework_degree')
                            ->label('درجة الواجب (0-10)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10)
                            ->step(0.5),
                    ]),

                Forms\Components\Section::make('ملاحظات المعلم')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات المعلم على الأداء')
                            ->placeholder('أضف ملاحظات المعلم حول أداء الطالب في الجلسة...')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('تفاصيل الحضور')
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
                        Forms\Components\Select::make('attendance_status')
                            ->label('حالة الحضور')
                            ->options(AttendanceStatus::options())
                            ->required(),
                        Forms\Components\TextInput::make('attendance_percentage')
                            ->label('نسبة الحضور')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->default(0),
                    ])->columns(3),

                Forms\Components\Section::make('معلومات النظام')
                    ->schema([
                        Forms\Components\DateTimePicker::make('evaluated_at')
                            ->label('تاريخ التقييم'),
                        Forms\Components\Toggle::make('is_calculated')
                            ->label('محسوب تلقائياً')
                            ->default(true),
                        Forms\Components\Toggle::make('manually_evaluated')
                            ->label('معدل يدوياً'),
                        Forms\Components\Textarea::make('override_reason')
                            ->label('سبب التعديل اليدوي')
                            ->visible(fn (Forms\Get $get) => $get('manually_evaluated'))
                            ->columnSpanFull(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('session.title')
                    ->label('الجلسة')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('المعلم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('academy.name')
                    ->label('الأكاديمية')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    }),
                Tables\Columns\TextColumn::make('attendance_status')
                    ->label('الحضور')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        AttendanceStatus::ATTENDED->value => 'success',
                        AttendanceStatus::LATE->value => 'warning',
                        AttendanceStatus::LEFT->value => 'info',
                        AttendanceStatus::ABSENT->value => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(function (?string $state): string {
                        if (!$state) return '-';
                        try {
                            return AttendanceStatus::from($state)->label();
                        } catch (\ValueError $e) {
                            return $state;
                        }
                    }),
                Tables\Columns\TextColumn::make('attendance_percentage')
                    ->label('نسبة الحضور')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => $state . '%'),
                Tables\Columns\TextColumn::make('actual_attendance_minutes')
                    ->label('مدة الحضور (دقيقة)')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_late')
                    ->label('متأخر')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('meeting_enter_time')
                    ->label('وقت الدخول')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('meeting_leave_time')
                    ->label('وقت الخروج')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_calculated')
                    ->label('محسوب تلقائياً')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('manually_evaluated')
                    ->label('معدل يدوياً')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('evaluated_at')
                    ->label('تاريخ التقييم')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('attendance_status')
                    ->label('حالة الحضور')
                    ->options(AttendanceStatus::options()),
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
                Tables\Filters\Filter::make('has_homework_grade')
                    ->label('تم تقييم الواجب')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('homework_degree')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListAcademicSessionReports::route('/'),
            'create' => Pages\CreateAcademicSessionReport::route('/create'),
            'view' => Pages\ViewAcademicSessionReport::route('/{record}'),
            'edit' => Pages\EditAcademicSessionReport::route('/{record}/edit'),
        ];
    }
}
