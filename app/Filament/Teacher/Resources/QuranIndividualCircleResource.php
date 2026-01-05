<?php

namespace App\Filament\Teacher\Resources;

use App\Filament\Teacher\Resources\QuranIndividualCircleResource\Pages;
use App\Models\QuranIndividualCircle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

/**
 * Quran Individual Circle Resource for Teacher Panel
 *
 * Extends BaseTeacherResource for proper authorization.
 */
class QuranIndividualCircleResource extends BaseTeacherResource
{
    protected static ?string $model = QuranIndividualCircle::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'حلقاتي الفردية';

    protected static ?string $modelLabel = 'حلقة فردية';

    protected static ?string $pluralModelLabel = 'حلقاتي الفردية';

    protected static ?string $navigationGroup = 'جلساتي';

    protected static ?int $navigationSort = 3;

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
                            ->options(QuranIndividualCircle::SPECIALIZATIONS)
                            ->required(),
                        Forms\Components\Select::make('memorization_level')
                            ->label('مستوى الحفظ')
                            ->options(QuranIndividualCircle::MEMORIZATION_LEVELS)
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('تتبع التقدم')
                    ->description('يتم حسابها تلقائياً من واجبات الجلسات')
                    ->schema([
                        Forms\Components\TextInput::make('total_memorized_pages')
                            ->label('إجمالي الصفحات المحفوظة')
                            ->numeric()
                            ->disabled()
                            ->helperText('من واجبات الحفظ الجديد'),
                        Forms\Components\TextInput::make('total_reviewed_pages')
                            ->label('إجمالي الصفحات المراجعة')
                            ->numeric()
                            ->disabled()
                            ->helperText('من واجبات المراجعة'),
                        Forms\Components\TextInput::make('total_reviewed_surahs')
                            ->label('إجمالي السور المراجعة')
                            ->numeric()
                            ->disabled()
                            ->helperText('من واجبات المراجعة الشاملة'),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('إعدادات الجلسة')
                    ->schema([
                        Forms\Components\TextInput::make('default_duration_minutes')
                            ->label('مدة الجلسة الافتراضية (بالدقائق)')
                            ->numeric()
                            ->minValue(15)
                            ->maxValue(240)
                            ->default(45)
                            ->disabled()
                            ->helperText('يتم تحديدها من الباقة المشترك بها'),
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
                    ->formatStateUsing(fn (string $state): string => QuranIndividualCircle::SPECIALIZATIONS[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'memorization' => 'success',
                        'recitation' => 'info',
                        'interpretation' => 'warning',
                        'tajweed' => 'danger',
                        'complete' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('memorization_level')
                    ->label('المستوى')
                    ->formatStateUsing(fn (string $state): string => QuranIndividualCircle::MEMORIZATION_LEVELS[$state] ?? $state),

                Tables\Columns\TextColumn::make('sessions_completed')
                    ->label('الجلسات المكتملة')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sessions_remaining')
                    ->label('الجلسات المتبقية')
                    ->numeric()
                    ->sortable()
                    ->color(fn (int $state): string => $state <= 5 ? 'danger' : ($state <= 10 ? 'warning' : 'success')),

                Tables\Columns\TextColumn::make('total_memorized_pages')
                    ->label('صفحات الحفظ')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('total_reviewed_pages')
                    ->label('صفحات المراجعة')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('last_session_at')
                    ->label('آخر جلسة')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->trueLabel('نشطة')
                    ->falseLabel('غير نشطة')
                    ->placeholder('الكل'),
                Tables\Filters\SelectFilter::make('specialization')
                    ->label('التخصص')
                    ->options(QuranIndividualCircle::SPECIALIZATIONS),
                Tables\Filters\SelectFilter::make('memorization_level')
                    ->label('مستوى الحفظ')
                    ->options(QuranIndividualCircle::MEMORIZATION_LEVELS),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\Action::make('calendar')
                    ->label('فتح التقويم')
                    ->icon('heroicon-o-calendar-days')
                    ->url(fn (QuranIndividualCircle $record): string => \App\Filament\Shared\Pages\UnifiedTeacherCalendar::getUrl(['circle' => $record->id]))
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
            \App\Filament\Teacher\Resources\QuranIndividualCircleResource\RelationManagers\SessionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuranIndividualCircles::route('/'),
            'create' => Pages\CreateQuranIndividualCircle::route('/create'),
            'view' => Pages\ViewQuranIndividualCircle::route('/{record}'),
            'edit' => Pages\EditQuranIndividualCircle::route('/{record}/edit'),
        ];
    }

    public static function getBreadcrumb(): string
    {
        return static::$pluralModelLabel ?? 'الحلقات الفردية';
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
