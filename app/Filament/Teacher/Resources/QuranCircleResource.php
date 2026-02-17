<?php

namespace App\Filament\Teacher\Resources;

use Filament\Schemas\Components\Component;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use App\Filament\Teacher\Resources\QuranCircleResource\Pages\ListQuranCircles;
use App\Filament\Teacher\Resources\QuranCircleResource\Pages\CreateQuranCircle;
use App\Filament\Teacher\Resources\QuranCircleResource\Pages\ViewQuranCircle;
use App\Filament\Teacher\Resources\QuranCircleResource\Pages\EditQuranCircle;
use App\Filament\Shared\Resources\BaseQuranCircleResource;
use App\Filament\Teacher\Resources\QuranCircleResource\Pages;
use App\Models\QuranCircle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Quran Circle Resource for Teacher Panel
 *
 * Teachers can view and manage their own circles only.
 * Limited permissions compared to SuperAdmin.
 * Extends BaseQuranCircleResource for shared form/table definitions.
 */
class QuranCircleResource extends BaseQuranCircleResource
{
    // ========================================
    // Navigation Configuration
    // ========================================

    protected static string | \UnitEnum | null $navigationGroup = 'جلساتي';

    protected static ?int $navigationSort = 2;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * Filter circles to current teacher only.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        $user = Auth::user();

        if (! $user->isQuranTeacher() || ! $user->quranTeacherProfile) {
            return $query->whereRaw('1 = 0'); // Return no results
        }

        // quran_teacher_id stores user_id directly
        return $query->where('quran_teacher_id', $user->id);
    }

    /**
     * Get the teacher field - hidden, auto-assigned to current teacher.
     */
    protected static function getTeacherFormField(): Component
    {
        return TextInput::make('quran_teacher_id')
            ->hidden()
            ->dehydrated()
            ->default(function () {
                $user = Auth::user();

                return $user ? $user->id : null;
            });
    }

    /**
     * Get description fields - bilingual for teachers.
     */
    protected static function getDescriptionFormFields(): array
    {
        return [
            Textarea::make('description_ar')
                ->label('وصف الحلقة (عربي)')
                ->rows(3)
                ->maxLength(500),

            Textarea::make('description_en')
                ->label('وصف الحلقة (إنجليزي)')
                ->rows(3)
                ->maxLength(500),
        ];
    }

    /**
     * Limited table actions for teachers.
     */
    protected static function getTableActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->label('عرض'),

                EditAction::make()
                    ->label('تعديل'),

                Action::make('view_circle')
                    ->label('عرض تفاصيل الحلقة')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn (QuranCircle $record): string => route('teacher.group-circles.show', [
                        'subdomain' => Auth::user()->academy->subdomain,
                        'circle' => $record->id,
                    ])
                    )
                    ->openUrlInNewTab(),
            ]),
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
     * Simple status section with read-only admin notes.
     */
    protected static function getAdditionalFormSections(): array
    {
        return [
            Section::make('الحالة والإعدادات الإدارية')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Toggle::make('status')
                                ->label('حالة الحلقة')
                                ->helperText('تفعيل أو إلغاء تفعيل الحلقة')
                                ->default(true),

                            Textarea::make('admin_notes')
                                ->label('ملاحظات الإدارة')
                                ->rows(3)
                                ->maxLength(1000)
                                ->helperText('ملاحظات مرئية للمعلم والإدارة والمشرف فقط')
                                ->columnSpanFull()
                                ->disabled()
                                ->dehydrated(false),
                        ]),
                ]),
        ];
    }

    // ========================================
    // Authorization Overrides
    // ========================================

    /**
     * Teachers can only view circles assigned to them.
     */
    public static function canView(Model $record): bool
    {
        $user = Auth::user();

        if (! $user->isQuranTeacher() || ! $user->quranTeacherProfile) {
            return false;
        }

        return $record->quran_teacher_id === $user->id;
    }

    /**
     * Teachers can edit circles assigned to them.
     */
    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();

        if (! $user->isQuranTeacher() || ! $user->quranTeacherProfile) {
            return false;
        }

        return $record->quran_teacher_id === $user->id;
    }

    /**
     * Teachers can create new circles.
     */
    public static function canCreate(): bool
    {
        return true;
    }

    // ========================================
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => ListQuranCircles::route('/'),
            'create' => CreateQuranCircle::route('/create'),
            'view' => ViewQuranCircle::route('/{record}'),
            'edit' => EditQuranCircle::route('/{record}/edit'),
        ];
    }
}
