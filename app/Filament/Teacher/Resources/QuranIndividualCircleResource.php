<?php

namespace App\Filament\Teacher\Resources;

use App\Filament\Teacher\Resources\QuranIndividualCircleResource\Pages;
use App\Filament\Teacher\Resources\QuranIndividualCircleResource\RelationManagers;
use App\Models\QuranIndividualCircle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Enums\FontWeight;

class QuranIndividualCircleResource extends Resource
{
    protected static ?string $model = QuranIndividualCircle::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    
    protected static ?string $navigationLabel = 'الحلقات الفردية';
    
    protected static ?string $modelLabel = 'حلقة فردية';
    
    protected static ?string $pluralModelLabel = 'الحلقات الفردية';
    
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الطالب')
                    ->schema([
                        Forms\Components\Select::make('student_id')
                            ->relationship('student', 'name')
                            ->label('الطالب')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الحلقة')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('التقدم الأكاديمي')
                    ->schema([
                        Forms\Components\Select::make('specialization')
                            ->label('التخصص')
                            ->options([
                                'memorization' => 'الحفظ',
                                'recitation' => 'التلاوة',
                                'interpretation' => 'التفسير',
                                'arabic_language' => 'اللغة العربية',
                                'complete' => 'متكامل',
                            ])
                            ->required(),
                        Forms\Components\Select::make('memorization_level')
                            ->label('مستوى الحفظ')
                            ->options([
                                'beginner' => 'مبتدئ',
                                'elementary' => 'ابتدائي',
                                'intermediate' => 'متوسط',
                                'advanced' => 'متقدم',
                                'expert' => 'خبير',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('current_surah')
                            ->label('السورة الحالية')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(114),
                        Forms\Components\TextInput::make('current_verse')
                            ->label('الآية الحالية')
                            ->numeric()
                            ->minValue(1),
                        Forms\Components\TextInput::make('verses_memorized')
                            ->label('عدد الآيات المحفوظة')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('إعدادات الجلسة')
                    ->schema([
                        Forms\Components\TextInput::make('default_duration_minutes')
                            ->label('مدة الجلسة الافتراضية (بالدقائق)')
                            ->numeric()
                            ->minValue(15)
                            ->maxValue(240)
                            ->default(45),
                        Forms\Components\TextInput::make('meeting_link')
                            ->label('رابط الاجتماع')
                            ->url()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('meeting_id')
                            ->label('معرف الاجتماع')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('meeting_password')
                            ->label('كلمة مرور الاجتماع')
                            ->password()
                            ->maxLength(255),
                        Forms\Components\Toggle::make('recording_enabled')
                            ->label('تفعيل التسجيل'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('ملاحظات')
                    ->schema([
                        Forms\Components\Textarea::make('teacher_notes')
                            ->label('ملاحظات المعلم')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold),
                    
                Tables\Columns\TextColumn::make('specialization')
                    ->label('التخصص')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'memorization' => 'الحفظ',
                        'recitation' => 'التلاوة',
                        'interpretation' => 'التفسير',
                        'arabic_language' => 'اللغة العربية',
                        'complete' => 'متكامل',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'memorization' => 'success',
                        'recitation' => 'info',
                        'interpretation' => 'warning',
                        'arabic_language' => 'danger',
                        'complete' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('memorization_level')
                    ->label('المستوى')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'beginner' => 'مبتدئ',
                        'elementary' => 'ابتدائي',
                        'intermediate' => 'متوسط',
                        'advanced' => 'متقدم',
                        'expert' => 'خبير',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('sessions_completed')
                    ->label('الجلسات المكتملة')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sessions_remaining')
                    ->label('الجلسات المتبقية')
                    ->numeric()
                    ->sortable()
                    ->color(fn (int $state): string => $state <= 5 ? 'danger' : ($state <= 10 ? 'warning' : 'success')),

                Tables\Columns\TextColumn::make('progress_percentage')
                    ->label('نسبة التقدم')
                    ->formatStateUsing(fn (float $state): string => number_format($state, 1) . '%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('current_surah')
                    ->label('السورة الحالية')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('last_session_at')
                    ->label('آخر جلسة')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'في الانتظار',
                        'active' => 'نشط',
                        'completed' => 'مكتمل',
                        'suspended' => 'معلق',
                        'cancelled' => 'ملغي',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'active' => 'success',
                        'completed' => 'info',
                        'suspended' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'في الانتظار',
                        'active' => 'نشط',
                        'completed' => 'مكتمل',
                        'suspended' => 'معلق',
                        'cancelled' => 'ملغي',
                    ]),
                Tables\Filters\SelectFilter::make('specialization')
                    ->label('التخصص')
                    ->options([
                        'memorization' => 'الحفظ',
                        'recitation' => 'التلاوة',
                        'interpretation' => 'التفسير',
                        'arabic_language' => 'اللغة العربية',
                        'complete' => 'متكامل',
                    ]),
                Tables\Filters\SelectFilter::make('memorization_level')
                    ->label('مستوى الحفظ')
                    ->options([
                        'beginner' => 'مبتدئ',
                        'elementary' => 'ابتدائي',
                        'intermediate' => 'متوسط',
                        'advanced' => 'متقدم',
                        'expert' => 'خبير',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\Action::make('calendar')
                    ->label('فتح التقويم')
                    ->icon('heroicon-o-calendar-days')
                    ->url(fn (QuranIndividualCircle $record): string => route('teacher.calendar', ['subdomain' => Auth::user()->academy->subdomain ?? 'itqan-academy']) . '?circle=' . $record->id)
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                // Remove bulk delete to prevent accidental deletion
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('quran_teacher_id', Auth::id())
            ->with(['student', 'subscription'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\SessionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuranIndividualCircles::route('/'),
            // 'view' => Pages\ViewQuranIndividualCircle::route('/{record}'),
            'edit' => Pages\EditQuranIndividualCircle::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        // Individual circles are created automatically with subscriptions
        return false;
    }

    public static function canDelete($record): bool
    {
        // Prevent deletion of individual circles
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
