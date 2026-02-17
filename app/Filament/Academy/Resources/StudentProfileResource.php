<?php

namespace App\Filament\Academy\Resources;

use App\Enums\UserType;
use App\Filament\Academy\Resources\StudentProfileResource\Pages;
use App\Filament\Shared\Resources\Profiles\BaseStudentProfileResource;
use App\Helpers\CountryList;
use App\Models\AcademicGradeLevel;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StudentProfileResource extends BaseStudentProfileResource
{
    // ========================================
    // Panel-Specific Implementations
    // ========================================

    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        // Academy admins see only students in their academy
        $academyId = Auth::user()->academy_id;

        return $query->whereHas('gradeLevel', function ($q) use ($academyId) {
            $q->where('academy_id', $academyId);
        });
    }

    protected static function getTableActions(): array
    {
        return [
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
            // Academy admins cannot delete students
        ];
    }

    protected static function getTableBulkActions(): array
    {
        return [
            // No bulk actions for academy admins
        ];
    }

    protected static function getGradeLevelOptions(): array
    {
        // Only grade levels from current user's academy
        $academyId = Auth::user()->academy_id;

        return AcademicGradeLevel::query()
            ->where('academy_id', $academyId)
            ->where('is_active', true)
            ->pluck('name', 'id')
            ->toArray();
    }

    // ========================================
    // Academy-Specific Table Customization
    // ========================================

    protected static function getTableColumns(): array
    {
        $columns = [];

        // Add avatar column (Academy-specific)
        $columns[] = Tables\Columns\ImageColumn::make('avatar')
            ->label('الصورة')
            ->circular()
            ->defaultImageUrl(fn ($record) => config('services.ui_avatars.base_url', 'https://ui-avatars.com/api/').'?name='.urlencode($record->full_name ?? 'N/A').'&background=4169E1&color=fff');

        // Add shared columns from base
        $columns[] = Tables\Columns\TextColumn::make('student_code')
            ->label('رمز الطالب')
            ->searchable()
            ->sortable()
            ->copyable();

        $columns[] = Tables\Columns\TextColumn::make('full_name')
            ->label('الاسم')
            ->searchable(['first_name', 'last_name'])
            ->sortable()
            ->weight(FontWeight::Bold);

        $columns[] = Tables\Columns\TextColumn::make('email')
            ->label('البريد الإلكتروني')
            ->searchable()
            ->sortable()
            ->copyable();

        $columns[] = Tables\Columns\TextColumn::make('gradeLevel.name')
            ->label('المرحلة الدراسية')
            ->sortable();

        $columns[] = Tables\Columns\TextColumn::make('parent.full_name')
            ->label('ولي الأمر')
            ->searchable(['first_name', 'last_name'])
            ->sortable()
            ->default('—')
            ->description(fn ($record) => $record->parent?->parent_code);

        $columns[] = Tables\Columns\TextColumn::make('nationality')
            ->label('الجنسية')
            ->formatStateUsing(function (?string $state): string {
                if (! $state) {
                    return '';
                }

                return CountryList::getLabel($state);
            })
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);

        $columns[] = Tables\Columns\TextColumn::make('enrollment_date')
            ->label('تاريخ التسجيل')
            ->date()
            ->sortable()
            ->toggleable();

        $columns[] = Tables\Columns\TextColumn::make('created_at')
            ->label(__('filament.created_at'))
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);

        $columns[] = Tables\Columns\TextColumn::make('updated_at')
            ->label(__('filament.updated_at'))
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);

        return $columns;
    }

    // ========================================
    // Academy-Specific Authorization
    // ========================================

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // Only academy admins can view
        return $user->hasRole(UserType::ADMIN->value) && $user->academy_id !== null;
    }

    public static function canView($record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // Verify student belongs to admin's academy
        $academyId = $user->academy_id;
        $studentAcademyId = static::getRecordAcademyId($record);

        return $studentAcademyId === $academyId;
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // Verify student belongs to admin's academy
        $academyId = $user->academy_id;
        $studentAcademyId = static::getRecordAcademyId($record);

        return $studentAcademyId === $academyId;
    }

    public static function canDelete($record): bool
    {
        // Academy admins cannot delete students
        return false;
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // Academy admins can create students
        return $user->hasRole(UserType::ADMIN->value) && $user->academy_id !== null;
    }

    // ========================================
    // Helper Methods
    // ========================================

    protected static function getRecordAcademyId($record): ?int
    {
        if (! $record->grade_level_id) {
            return null;
        }

        // Query grade level directly
        $gradeLevel = \App\Models\AcademicGradeLevel::withoutGlobalScope('academy')
            ->find($record->grade_level_id);

        return $gradeLevel?->academy_id;
    }

    // ========================================
    // Resource Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentProfiles::route('/'),
            'create' => Pages\CreateStudentProfile::route('/create'),
            'view' => Pages\ViewStudentProfile::route('/{record}'),
            'edit' => Pages\EditStudentProfile::route('/{record}/edit'),
        ];
    }
}
