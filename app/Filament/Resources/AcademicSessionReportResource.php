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
                            ->relationship('student', 'name')
                            ->label('الطالب')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('teacher_id')
                            ->relationship('teacher', 'name')
                            ->label('المعلم')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('academy_id')
                            ->relationship('academy', 'name')
                            ->label('الأكاديمية')
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])->columns(2),

                Forms\Components\Section::make('الأداء الأكاديمي')
                    ->schema([
                        Forms\Components\TextInput::make('academic_performance_score')
                            ->label('الأداء الأكاديمي (0-10)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10)
                            ->step(0.1),
                        Forms\Components\TextInput::make('engagement_score')
                            ->label('درجة التفاعل (0-10)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10)
                            ->step(0.1),
                        Forms\Components\TagsInput::make('learning_objectives_achieved')
                            ->label('الأهداف التعليمية المحققة')
                            ->placeholder('أضف الأهداف التعليمية')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('إدارة الواجبات')
                    ->schema([
                        Forms\Components\Textarea::make('homework_description')
                            ->label('وصف الواجب')
                            ->placeholder('اكتب وصف الواجب المنزلي...')
                            ->rows(4)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('homework_file_path')
                            ->label('ملف الواجب')
                            ->directory('academic-homework')
                            ->acceptedFileTypes(['pdf', 'doc', 'docx', 'txt', 'jpg', 'png'])
                            ->maxSize(5120) // 5MB
                            ->downloadable()
                            ->openable()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('الملاحظات والتغذية الراجعة')
                    ->schema([
                        Forms\Components\Textarea::make('teacher_notes')
                            ->label('ملاحظات المعلم')
                            ->placeholder('ملاحظات ومشاهدات المعلم...')
                            ->rows(3),
                        Forms\Components\Textarea::make('student_notes')
                            ->label('ملاحظات الطالب')
                            ->placeholder('ملاحظات الطالب والتأملات الذاتية...')
                            ->rows(3),
                    ])->columns(2),

                Forms\Components\Section::make('تفاصيل الحضور')
                    ->schema([
                        Forms\Components\DateTimePicker::make('meeting_enter_time')
                            ->label('وقت الدخول للجلسة'),
                        Forms\Components\DateTimePicker::make('meeting_leave_time')
                            ->label('وقت الخروج من الجلسة'),
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
                        Forms\Components\Toggle::make('is_auto_calculated')
                            ->label('محسوب تلقائياً')
                            ->default(true),
                        Forms\Components\Toggle::make('manually_overridden')
                            ->label('معدل يدوياً'),
                        Forms\Components\Textarea::make('override_reason')
                            ->label('سبب التعديل اليدوي')
                            ->visible(fn (Forms\Get $get) => $get('manually_overridden'))
                            ->columnSpanFull(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('academicSession.title')
                    ->label('Session')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Teacher')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('academy.name')
                    ->label('Academy')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('academic_performance_score')
                    ->label('Performance')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        $state >= 8 => 'success',
                        $state >= 6 => 'warning',
                        default => 'danger',
                    }),
                Tables\Columns\TextColumn::make('engagement_score')
                    ->label('Engagement')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        $state >= 8 => 'success',
                        $state >= 6 => 'warning',
                        default => 'danger',
                    }),
                Tables\Columns\TextColumn::make('attendance_status')
                    ->label('Attendance')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'attended' => 'success',
                        'late' => 'warning',
                        'leaved' => 'info',
                        'absent' => 'danger',
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
                    ->label('Attendance %')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => $state . '%'),
                Tables\Columns\TextColumn::make('actual_attendance_minutes')
                    ->label('Duration (min)')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_late')
                    ->label('Late')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('homework_description')
                    ->label('Has Homework')
                    ->formatStateUsing(fn (?string $state): string => $state ? 'Yes' : 'No')
                    ->badge()
                    ->color(fn (?string $state): string => $state ? 'success' : 'gray')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('homework_file_path')
                    ->label('Homework File')
                    ->formatStateUsing(fn (?string $state): string => $state ? 'Uploaded' : 'None')
                    ->badge()
                    ->color(fn (?string $state): string => $state ? 'success' : 'gray')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('meeting_enter_time')
                    ->label('Join Time')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('meeting_leave_time')
                    ->label('Leave Time')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('manually_overridden')
                    ->label('Manual Override')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('evaluated_at')
                    ->label('Evaluated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('attendance_status')
                    ->label('Attendance Status')
                    ->options(AttendanceStatus::options()),
                Tables\Filters\SelectFilter::make('academy_id')
                    ->label('Academy')
                    ->relationship('academy', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('has_homework')
                    ->label('Has Homework')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('homework_description')),
                Tables\Filters\Filter::make('has_homework_file')
                    ->label('Has Homework File')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('homework_file_path')),
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
            'edit' => Pages\EditAcademicSessionReport::route('/{record}/edit'),
        ];
    }
}
