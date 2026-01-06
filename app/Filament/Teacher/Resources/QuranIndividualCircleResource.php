<?php

namespace App\Filament\Teacher\Resources;

use App\Filament\Shared\Resources\BaseQuranIndividualCircleResource;
use App\Filament\Teacher\Resources\QuranIndividualCircleResource\Pages;
use App\Models\QuranIndividualCircle;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

/**
 * Quran Individual Circle Resource for Teacher Panel
 *
 * Teachers can view and manage their own circles only.
 * Limited permissions compared to SuperAdmin.
 * Extends BaseQuranIndividualCircleResource for shared form/table definitions.
 */
class QuranIndividualCircleResource extends BaseQuranIndividualCircleResource
{
    // ========================================
    // Navigation Configuration
    // ========================================

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'حلقاتي الفردية';

    protected static ?string $pluralModelLabel = 'حلقاتي الفردية';

    protected static ?string $navigationGroup = 'جلساتي';

    protected static ?int $navigationSort = 3;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * Filter circles to current teacher only.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        return $query
            ->where('quran_teacher_id', Auth::id())
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /**
     * Get basic info section - student is read-only for teachers.
     */
    protected static function getBasicInfoFormSection(): Section
    {
        return Section::make('معلومات الطالب')
            ->schema([
                Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('student_id')
                            ->relationship('student', 'name')
                            ->label('الطالب')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('name')
                            ->label('اسم الحلقة')
                            ->disabled(),
                    ]),
            ]);
    }

    /**
     * Limited table actions for teachers.
     */
    protected static function getTableActions(): array
    {
        return [
            Tables\Actions\ViewAction::make()
                ->label('عرض'),
            Tables\Actions\EditAction::make()
                ->label('تعديل'),
            Tables\Actions\Action::make('calendar')
                ->label('فتح التقويم')
                ->icon('heroicon-o-calendar-days')
                ->url(fn (QuranIndividualCircle $record): string => \App\Filament\Shared\Pages\UnifiedTeacherCalendar::getUrl(['circle' => $record->id]))
                ->openUrlInNewTab(),
        ];
    }

    /**
     * No bulk actions for teachers.
     */
    protected static function getTableBulkActions(): array
    {
        return [];
    }

    // ========================================
    // Form Sections Override (Teacher-specific)
    // ========================================

    /**
     * Session settings and teacher notes for teachers.
     */
    protected static function getAdditionalFormSections(): array
    {
        return [
            Section::make('إعدادات الجلسة')
                ->schema([
                    Grid::make(2)
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
                        ]),
                ]),

            Section::make('ملاحظات')
                ->schema([
                    Forms\Components\Textarea::make('teacher_notes')
                        ->label('ملاحظات المعلم')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
        ];
    }

    // ========================================
    // Table Columns Override (Teacher-specific)
    // ========================================

    /**
     * Table columns with sessions remaining color indicator.
     */
    protected static function getTableColumns(): array
    {
        return [
            TextColumn::make('student.name')
                ->label('الطالب')
                ->searchable()
                ->sortable()
                ->weight(FontWeight::SemiBold),

            TextColumn::make('specialization')
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

            TextColumn::make('memorization_level')
                ->label('المستوى')
                ->formatStateUsing(fn (string $state): string => QuranIndividualCircle::MEMORIZATION_LEVELS[$state] ?? $state),

            TextColumn::make('sessions_completed')
                ->label('الجلسات المكتملة')
                ->numeric()
                ->sortable(),

            TextColumn::make('sessions_remaining')
                ->label('الجلسات المتبقية')
                ->numeric()
                ->sortable()
                ->color(fn (int $state): string => $state <= 5 ? 'danger' : ($state <= 10 ? 'warning' : 'success')),

            TextColumn::make('total_memorized_pages')
                ->label('صفحات الحفظ')
                ->numeric()
                ->sortable()
                ->alignCenter(),

            TextColumn::make('total_reviewed_pages')
                ->label('صفحات المراجعة')
                ->numeric()
                ->sortable()
                ->alignCenter()
                ->toggleable(),

            TextColumn::make('last_session_at')
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
        ];
    }

    // ========================================
    // Eloquent Query Override
    // ========================================

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['subscription']);
    }

    // ========================================
    // Relations
    // ========================================

    public static function getRelations(): array
    {
        return [
            \App\Filament\Teacher\Resources\QuranIndividualCircleResource\RelationManagers\SessionsRelationManager::class,
        ];
    }

    // ========================================
    // Authorization Overrides
    // ========================================

    /**
     * Individual circles are created automatically with subscriptions.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return $record->quran_teacher_id === Auth::id();
    }

    public static function canView(Model $record): bool
    {
        return $record->quran_teacher_id === Auth::id();
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    // ========================================
    // Breadcrumb
    // ========================================

    public static function getBreadcrumb(): string
    {
        return static::$pluralModelLabel ?? 'الحلقات الفردية';
    }

    // ========================================
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuranIndividualCircles::route('/'),
            'create' => Pages\CreateQuranIndividualCircle::route('/create'),
            'view' => Pages\ViewQuranIndividualCircle::route('/{record}'),
            'edit' => Pages\EditQuranIndividualCircle::route('/{record}/edit'),
        ];
    }
}
