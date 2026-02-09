<?php

namespace App\Filament\Resources;

use App\Enums\UserType;
use App\Filament\Resources\QuranTrialRequestResource\Pages;
use App\Filament\Shared\Resources\BaseQuranTrialRequestResource;
use App\Models\QuranTrialRequest;
use App\Services\AcademyContextService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Quran Trial Request Resource for SuperAdmin Panel
 *
 * Full CRUD access with soft delete support.
 * Extends BaseQuranTrialRequestResource for shared form/table definitions.
 */
class QuranTrialRequestResource extends BaseQuranTrialRequestResource
{
    // ========================================
    // Tenant Configuration
    // ========================================

    protected static ?string $tenantOwnershipRelationshipName = 'academy';

    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy';
    }

    // ========================================
    // Navigation Configuration
    // ========================================

    protected static ?string $navigationLabel = 'طلبات الجلسات التجريبية';

    protected static ?string $navigationGroup = 'إدارة القرآن';

    protected static ?int $navigationSort = 8;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * SuperAdmin sees all requests, including soft-deleted ones.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        return $query->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /**
     * Get form schema for SuperAdmin.
     */
    protected static function getFormSchema(): array
    {
        return [
            static::getRequestInfoFormSection(),

            Section::make('تفاصيل الطلب')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('student_id')
                                ->label('الطالب')
                                ->options(function () {
                                    $academyId = AcademyContextService::getCurrentAcademyId();

                                    return \App\Models\User::where('user_type', UserType::STUDENT->value)
                                        ->where('academy_id', $academyId)
                                        ->get()
                                        ->pluck('name', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->required(),

                            Select::make('teacher_id')
                                ->label('المعلم')
                                ->options(function () {
                                    $academyId = AcademyContextService::getCurrentAcademyId();

                                    $teachers = \App\Models\QuranTeacherProfile::where('academy_id', $academyId)
                                        ->active()
                                        ->get();

                                    if ($teachers->isEmpty()) {
                                        return [];
                                    }

                                    return $teachers->mapWithKeys(function ($teacher) {
                                        $displayName = $teacher->display_name
                                            ?? ($teacher->full_name ?? 'معلم غير محدد').' ('.($teacher->teacher_code ?? 'N/A').')';

                                        return [$teacher->id => $displayName];
                                    })->toArray();
                                })
                                ->searchable()
                                ->preload()
                                ->required(),
                        ]),
                ]),

            Section::make('تفاصيل التعلم')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('current_level')
                                ->label('المستوى الحالي')
                                ->options(QuranTrialRequest::LEVELS)
                                ->required()
                                ->native(false),

                            Select::make('preferred_time')
                                ->label('الوقت المفضل')
                                ->options(QuranTrialRequest::TIMES)
                                ->native(false),
                        ]),

                    Textarea::make('notes')
                        ->label('ملاحظات الطالب')
                        ->rows(3),
                ]),

            static::getSessionEvaluationFormSection(),
        ];
    }

    /**
     * Full table actions for SuperAdmin with soft deletes.
     */
    protected static function getTableActions(): array
    {
        return [
            ActionGroup::make([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                static::makeScheduleAction(),

                Tables\Actions\Action::make('cancel')
                    ->label('إلغاء الطلب')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (QuranTrialRequest $record) => $record->isPending())
                    ->requiresConfirmation()
                    ->modalHeading('إلغاء طلب الجلسة التجريبية')
                    ->modalDescription('هل أنت متأكد من إلغاء هذا الطلب؟')
                    ->action(fn (QuranTrialRequest $record) => $record->cancel())
                    ->successNotificationTitle('تم إلغاء الطلب'),

                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make()
                    ->label(__('filament.actions.restore')),
                Tables\Actions\ForceDeleteAction::make()
                    ->label(__('filament.actions.force_delete')),
            ]),
        ];
    }

    /**
     * Full bulk actions for SuperAdmin.
     */
    protected static function getTableBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make()
                    ->label(__('filament.actions.restore_selected')),
                Tables\Actions\ForceDeleteBulkAction::make()
                    ->label(__('filament.actions.force_delete_selected')),
            ]),
        ];
    }

    // ========================================
    // Table Columns Override (SuperAdmin-specific)
    // ========================================

    /**
     * Table columns with teacher column.
     */
    protected static function getTableColumns(): array
    {
        return [
            ...parent::getTableColumns(),

            TextColumn::make('teacher.full_name')
                ->label('المعلم')
                ->searchable()
                ->sortable()
                ->toggleable(),
        ];
    }

    // ========================================
    // Table Filters Override (SuperAdmin-specific)
    // ========================================

    /**
     * Extended filters with teacher, date range, and trashed.
     */
    protected static function getTableFilters(): array
    {
        return [
            ...parent::getTableFilters(),

            SelectFilter::make('teacher_id')
                ->label('المعلم')
                ->options(function () {
                    $academyId = AcademyContextService::getCurrentAcademyId();

                    return \App\Models\QuranTeacherProfile::where('academy_id', $academyId)
                        ->active()
                        ->get()
                        ->mapWithKeys(function ($teacher) {
                            return [$teacher->id => $teacher->display_name];
                        });
                })
                ->searchable()
                ->preload(),

            Filter::make('scheduled_date')
                ->form([
                    DatePicker::make('scheduled_from')
                        ->label('من تاريخ'),
                    DatePicker::make('scheduled_until')
                        ->label('إلى تاريخ'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['scheduled_from'],
                            fn (Builder $query, $date): Builder => $query->whereHas('trialSession', fn ($q) => $q->whereDate('scheduled_at', '>=', $date)
                            ),
                        )
                        ->when(
                            $data['scheduled_until'],
                            fn (Builder $query, $date): Builder => $query->whereHas('trialSession', fn ($q) => $q->whereDate('scheduled_at', '<=', $date)
                            ),
                        );
                }),

            Tables\Filters\TrashedFilter::make()
                ->label(__('filament.filters.trashed')),
        ];
    }

    // ========================================
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuranTrialRequests::route('/'),
            'create' => Pages\CreateQuranTrialRequest::route('/create'),
            'view' => Pages\ViewQuranTrialRequest::route('/{record}'),
            'edit' => Pages\EditQuranTrialRequest::route('/{record}/edit'),
        ];
    }
}
