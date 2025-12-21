<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MeetingAttendanceResource\Pages;
use App\Filament\Resources\MeetingAttendanceResource\RelationManagers;
use App\Models\MeetingAttendance;
use App\Enums\AttendanceStatus;
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['session', 'user']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الحضور')
                    ->schema([
                        Forms\Components\TextInput::make('session_id')
                            ->label('معرف الجلسة')
                            ->numeric()
                            ->required()
                            ->disabled(),
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
                        Forms\Components\Select::make('session_type')
                            ->label('نوع الجلسة')
                            ->options([
                                'individual' => 'فردي',
                                'group' => 'مجموعة',
                                'academic' => 'أكاديمي',
                            ])
                            ->required(),
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

                Forms\Components\Section::make('حالة الحضور والحساب')
                    ->schema([
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
                        Forms\Components\DateTimePicker::make('attendance_calculated_at')
                            ->label('تاريخ الحساب'),
                        Forms\Components\Toggle::make('is_calculated')
                            ->label('محسوب تلقائياً')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('دورات الدخول والخروج')
                    ->schema([
                        Forms\Components\Textarea::make('join_leave_cycles')
                            ->label('سجل دورات الدخول والخروج (JSON)')
                            ->rows(5)
                            ->columnSpanFull()
                            ->disabled()
                            ->helperText('عرض فقط - يتم تحديث هذا الحقل تلقائياً من الأحداث'),
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
                Tables\Columns\IconColumn::make('is_calculated')
                    ->label('محسوب تلقائياً')
                    ->boolean()
                    ->toggleable(),
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
                    ->options(AttendanceStatus::options()),
                Tables\Filters\SelectFilter::make('user_type')
                    ->label('نوع المستخدم')
                    ->options([
                        'student' => 'طالب',
                        'teacher' => 'معلم',
                    ]),
                Tables\Filters\SelectFilter::make('session_type')
                    ->label('نوع الجلسة')
                    ->options([
                        'individual' => 'فردي',
                        'group' => 'مجموعة',
                        'academic' => 'أكاديمي',
                    ]),
                Tables\Filters\TernaryFilter::make('is_calculated')
                    ->label('محسوب تلقائياً'),
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
            'view' => Pages\ViewMeetingAttendance::route('/{record}'),
            'edit' => Pages\EditMeetingAttendance::route('/{record}/edit'),
        ];
    }
}
