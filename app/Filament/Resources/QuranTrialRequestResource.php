<?php

namespace App\Filament\Resources;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use App\Models\User;
use App\Models\QuranTeacherProfile;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use App\Filament\Resources\QuranTrialRequestResource\Pages\ListQuranTrialRequests;
use App\Filament\Resources\QuranTrialRequestResource\Pages\CreateQuranTrialRequest;
use App\Filament\Resources\QuranTrialRequestResource\Pages\ViewQuranTrialRequest;
use App\Filament\Resources\QuranTrialRequestResource\Pages\EditQuranTrialRequest;
use App\Enums\UserType;
use App\Filament\Resources\QuranTrialRequestResource\Pages;
use App\Filament\Shared\Resources\BaseQuranTrialRequestResource;
use App\Models\QuranTrialRequest;
use App\Services\AcademyContextService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables;
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

    protected static string | \UnitEnum | null $navigationGroup = 'إدارة القرآن';

    protected static ?int $navigationSort = 8;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * SuperAdmin sees all requests, including soft-deleted ones.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        // Include soft-deleted records for admin management
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

                                    return User::where('user_type', UserType::STUDENT->value)
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

                                    $teachers = QuranTeacherProfile::where('academy_id', $academyId)
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
                ViewAction::make(),
                EditAction::make(),

                static::makeScheduleAction(),

                Action::make('cancel')
                    ->label('إلغاء الطلب')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (QuranTrialRequest $record) => $record->isPending())
                    ->requiresConfirmation()
                    ->modalHeading('إلغاء طلب الجلسة التجريبية')
                    ->modalDescription('هل أنت متأكد من إلغاء هذا الطلب؟')
                    ->action(fn (QuranTrialRequest $record) => $record->cancel())
                    ->successNotificationTitle('تم إلغاء الطلب'),

                DeleteAction::make(),
                RestoreAction::make()
                    ->label(__('filament.actions.restore')),
                ForceDeleteAction::make()
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
            BulkActionGroup::make([
                DeleteBulkAction::make(),
                RestoreBulkAction::make()
                    ->label(__('filament.actions.restore_selected')),
                ForceDeleteBulkAction::make()
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
        $essentialNames = ['request_code', 'status'];
        $columns = parent::getTableColumns();

        foreach ($columns as $column) {
            if (! in_array($column->getName(), $essentialNames)) {
                $column->toggleable();
            }
        }

        return [
            ...$columns,

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

                    return QuranTeacherProfile::where('academy_id', $academyId)
                        ->active()
                        ->get()
                        ->mapWithKeys(function ($teacher) {
                            return [$teacher->id => $teacher->display_name];
                        });
                })
                ->searchable()
                ->preload(),

            Filter::make('scheduled_date')
                ->schema([
                    DatePicker::make('scheduled_from')
                        ->label('من تاريخ'),
                    DatePicker::make('scheduled_until')
                        ->label('إلى تاريخ'),
                ])
                ->columns(2)
                ->columnSpanFull()
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

        ];
    }

    // ========================================
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => ListQuranTrialRequests::route('/'),
            'create' => CreateQuranTrialRequest::route('/create'),
            'view' => ViewQuranTrialRequest::route('/{record}'),
            'edit' => EditQuranTrialRequest::route('/{record}/edit'),
        ];
    }
}
