<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MeetingAttendanceResource\Pages;
use App\Filament\Resources\MeetingAttendanceResource\RelationManagers;
use App\Models\MeetingAttendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MeetingAttendanceResource extends Resource
{
    protected static ?string $model = MeetingAttendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'سجل الحضور';

    protected static ?string $modelLabel = 'سجل حضور';

    protected static ?string $pluralModelLabel = 'سجل الحضور';

    protected static ?string $navigationGroup = 'التقارير والحضور';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الحضور')
                    ->schema([
                        Forms\Components\Select::make('session_id')
                            ->relationship('session', 'title')
                            ->label('الجلسة')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->label('المستخدم')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('user_type')
                            ->label('نوع المستخدم')
                            ->options([
                                'student' => 'طالب',
                                'teacher' => 'معلم',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('session_type')
                            ->label('نوع الجلسة')
                            ->disabled(),
                        Forms\Components\TextInput::make('attendable_type')
                            ->label('نوع السجل')
                            ->maxLength(255)
                            ->disabled(),
                        Forms\Components\TextInput::make('attendable_id')
                            ->label('معرف السجل')
                            ->numeric()
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('تفاصيل الحضور والوقت')
                    ->schema([
                        Forms\Components\DateTimePicker::make('first_join_time')
                            ->label('أول وقت دخول'),
                        Forms\Components\DateTimePicker::make('last_leave_time')
                            ->label('آخر وقت خروج'),
                        Forms\Components\TextInput::make('total_duration_minutes')
                            ->label('إجمالي المدة (دقيقة)')
                            ->numeric()
                            ->default(0)
                            ->suffix('دقيقة'),
                        Forms\Components\TextInput::make('join_count')
                            ->label('عدد مرات الدخول')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('leave_count')
                            ->label('عدد مرات الخروج')
                            ->numeric()
                            ->default(0),
                        Forms\Components\DateTimePicker::make('session_start_time')
                            ->label('وقت بدء الجلسة'),
                        Forms\Components\DateTimePicker::make('session_end_time')
                            ->label('وقت انتهاء الجلسة'),
                        Forms\Components\TextInput::make('session_duration_minutes')
                            ->label('مدة الجلسة (دقيقة)')
                            ->numeric()
                            ->suffix('دقيقة'),
                    ])->columns(3),

                Forms\Components\Section::make('حالة الحضور والتقييم')
                    ->schema([
                        Forms\Components\Select::make('attendance_status')
                            ->label('حالة الحضور')
                            ->options([
                                'present' => 'حاضر',
                                'late' => 'متأخر',
                                'partial' => 'حضور جزئي',
                                'absent' => 'غائب',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('attendance_percentage')
                            ->label('نسبة الحضور')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->default(0),
                        Forms\Components\TextInput::make('participation_score')
                            ->label('درجة المشاركة')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100),
                        Forms\Components\DateTimePicker::make('attendance_calculated_at')
                            ->label('تاريخ الحساب'),
                        Forms\Components\Toggle::make('is_calculated')
                            ->label('محسوب تلقائياً')
                            ->default(true),
                    ])->columns(3),

                Forms\Components\Section::make('التعديل اليدوي')
                    ->schema([
                        Forms\Components\Toggle::make('manually_overridden')
                            ->label('معدل يدوياً')
                            ->default(false)
                            ->reactive(),
                        Forms\Components\Select::make('overridden_by')
                            ->relationship('overriddenBy', 'name')
                            ->label('المعدّل')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => $get('manually_overridden')),
                        Forms\Components\DateTimePicker::make('overridden_at')
                            ->label('تاريخ التعديل')
                            ->visible(fn (Forms\Get $get) => $get('manually_overridden')),
                        Forms\Components\Textarea::make('override_reason')
                            ->label('سبب التعديل')
                            ->rows(3)
                            ->visible(fn (Forms\Get $get) => $get('manually_overridden'))
                            ->columnSpanFull(),
                    ])->columns(3),

                Forms\Components\Section::make('ملاحظات إضافية')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
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
                Tables\Columns\TextColumn::make('user.name')
                    ->label('المستخدم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'student' => 'طالب',
                        'teacher' => 'معلم',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'student' => 'info',
                        'teacher' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('session_type')
                    ->label('نوع الجلسة')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'quran' => 'قرآن',
                        'academic' => 'أكاديمي',
                        'interactive' => 'تفاعلي',
                        default => $state ?? '-',
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('attendance_status')
                    ->label('الحضور')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'present' => 'success',
                        'late' => 'warning',
                        'partial' => 'info',
                        'absent' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'present' => 'حاضر',
                        'late' => 'متأخر',
                        'partial' => 'جزئي',
                        'absent' => 'غائب',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('attendance_percentage')
                    ->label('نسبة الحضور')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => $state . '%'),
                Tables\Columns\TextColumn::make('total_duration_minutes')
                    ->label('المدة (دقيقة)')
                    ->numeric()
                    ->sortable()
                    ->suffix(' د'),
                Tables\Columns\TextColumn::make('first_join_time')
                    ->label('أول دخول')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('last_leave_time')
                    ->label('آخر خروج')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('join_count')
                    ->label('مرات الدخول')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('leave_count')
                    ->label('مرات الخروج')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('participation_score')
                    ->label('درجة المشاركة')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (?string $state): string => match (true) {
                        $state === null => 'gray',
                        (float) $state >= 80 => 'success',
                        (float) $state >= 60 => 'warning',
                        default => 'danger',
                    })
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_calculated')
                    ->label('محسوب تلقائياً')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('manually_overridden')
                    ->label('معدل يدوياً')
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('overriddenBy.name')
                    ->label('المعدّل')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('attendance_calculated_at')
                    ->label('تاريخ الحساب')
                    ->dateTime()
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
                    ->options([
                        'present' => 'حاضر',
                        'late' => 'متأخر',
                        'partial' => 'حضور جزئي',
                        'absent' => 'غائب',
                    ]),
                Tables\Filters\SelectFilter::make('user_type')
                    ->label('نوع المستخدم')
                    ->options([
                        'student' => 'طالب',
                        'teacher' => 'معلم',
                    ]),
                Tables\Filters\SelectFilter::make('session_type')
                    ->label('نوع الجلسة')
                    ->options([
                        'quran' => 'قرآن',
                        'academic' => 'أكاديمي',
                        'interactive' => 'تفاعلي',
                    ]),
                Tables\Filters\TernaryFilter::make('manually_overridden')
                    ->label('معدل يدوياً'),
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
            'index' => Pages\ListMeetingAttendances::route('/'),
            'create' => Pages\CreateMeetingAttendance::route('/create'),
            'edit' => Pages\EditMeetingAttendance::route('/{record}/edit'),
        ];
    }
}
