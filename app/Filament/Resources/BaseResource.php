<?php

namespace App\Filament\Resources;

use Filament\Facades\Filament;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use App\Enums\SessionSubscriptionStatus;
use App\Models\Academy;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

abstract class BaseResource extends Resource
{
    /**
     * Determine if this resource should be visible in navigation
     */
    public static function shouldRegisterNavigation(): bool
    {
        // If this is a settings resource, only show when specific academy is selected
        if (static::isSettingsResource()) {
            return static::hasSpecificAcademySelected();
        }

        // Data resources are always visible
        return true;
    }

    /**
     * Check if specific academy is selected (not "All Academies")
     */
    protected static function hasSpecificAcademySelected(): bool
    {
        $academyContextService = app(AcademyContextService::class);

        return $academyContextService->getCurrentAcademyId() !== null;
    }

    /**
     * Check if currently viewing all academies
     */
    protected static function isViewingAllAcademies(): bool
    {
        // If we're in a tenant panel (Academy panel), we always have an academy context
        if (Filament::getTenant() !== null) {
            return false;
        }

        // For admin panel, check via AcademyContextService
        $academyContextService = app(AcademyContextService::class);

        return $academyContextService->getCurrentAcademyId() === null;
    }

    /**
     * Determine if this resource is a settings resource
     * Override in child classes to return true for settings resources
     */
    protected static function isSettingsResource(): bool
    {
        return false;
    }

    /**
     * Get academy column for tables when viewing all academies
     */
    protected static function getAcademyColumn(): TextColumn
    {
        // Get the academy relationship path for this resource
        $academyPath = static::getAcademyRelationshipPath();

        return TextColumn::make($academyPath.'.name')
            ->label('الأكاديمية')
            ->sortable()
            ->searchable()
            ->visible(static::isViewingAllAcademies())
            ->placeholder('غير محدد');
    }

    /**
     * Get the Eloquent query with academy relationship eager loaded when needed
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // When viewing all academies, eager load the academy relationship to prevent N+1 queries
        if (static::isViewingAllAcademies()) {
            $academyPath = static::getAcademyRelationshipPath();
            // Only eager load if academy path is not empty
            if (! empty($academyPath)) {
                $query->with($academyPath);
            }
        }

        return $query;
    }

    /**
     * Get the relationship path to academy
     * Override in child classes if academy is not directly related
     */
    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy';
    }

    /**
     * Check if resource can be created when viewing all academies
     */
    public static function canCreate(): bool
    {
        // Don't allow creation when viewing all academies
        if (static::isViewingAllAcademies()) {
            return false;
        }

        return parent::canCreate();
    }

    /**
     * Get the Academy options for forms
     */
    protected static function getAcademyOptions(): array
    {
        if (static::isViewingAllAcademies()) {
            return Academy::pluck('name', 'id')->toArray();
        }

        $academyContextService = app(AcademyContextService::class);
        $currentAcademyId = $academyContextService->getCurrentAcademyId();

        if ($currentAcademyId) {
            $academy = Academy::find($currentAcademyId);

            return $academy ? [$academy->id => $academy->name] : [];
        }

        return [];
    }

    /**
     * Get a reusable date range filter for tables
     *
     * @param  string  $column  The column name to filter on (default: 'created_at')
     */
    protected static function getDateRangeFilter(string $column = 'created_at'): Filter
    {
        return Filter::make($column)
            ->schema([
                DatePicker::make('from')
                    ->label(__('filament.filters.from_date')),
                DatePicker::make('until')
                    ->label(__('filament.filters.to_date')),
            ])
            ->query(function (Builder $query, array $data) use ($column): Builder {
                return $query
                    ->when(
                        $data['from'],
                        fn (Builder $query, $date): Builder => $query->whereDate($column, '>=', $date),
                    )
                    ->when(
                        $data['until'],
                        fn (Builder $query, $date): Builder => $query->whereDate($column, '<=', $date),
                    );
            })
            ->indicateUsing(function (array $data): array {
                $indicators = [];
                if ($data['from'] ?? null) {
                    $indicators['from'] = __('filament.filters.from_date').': '.$data['from'];
                }
                if ($data['until'] ?? null) {
                    $indicators['until'] = __('filament.filters.to_date').': '.$data['until'];
                }

                return $indicators;
            });
    }

    /**
     * Get a subscription status filter for tables
     */
    protected static function getSubscriptionStatusFilter(): SelectFilter
    {
        return SelectFilter::make('status')
            ->label(__('filament.status'))
            ->options(SessionSubscriptionStatus::options());
    }

    /**
     * Get the standard subscription status badge column configuration (uses TextColumn with badge)
     *
     * @param  string  $column  The column name (default: 'status')
     */
    protected static function getStatusBadgeColumn(string $column = 'status'): TextColumn
    {
        return TextColumn::make($column)
            ->label(__('filament.status'))
            ->badge()
            ->formatStateUsing(fn ($state) => $state instanceof SessionSubscriptionStatus ? $state->label() : $state)
            ->color(fn ($state) => $state instanceof SessionSubscriptionStatus ? $state->color() : 'secondary');
    }

    /**
     * Preferred countries shown at top of phone input dropdown.
     */
    public const PHONE_PREFERRED_COUNTRIES = [
        'sa', 'eg', 'ae', 'kw', 'qa', 'om', 'bh', 'jo',
    ];

    /**
     * Get a standardized phone input field.
     *
     * Uses the FilamentPhoneInput package with consistent configuration:
     * - Default country: Saudi Arabia (SA)
     * - All countries available, Israel excluded
     * - Preferred Arab countries shown at top
     * - Dial code separated for proper storage
     * - Country flags shown
     * - Format as you type with strict mode (enforces country digit format)
     * - Arabic locale with Palestine name corrected to 'فلسطين'
     *
     * @param  string  $name  The field name (default: 'phone')
     * @param  string  $label  The field label (default: 'رقم الهاتف')
     */
    protected static function getPhoneInput(
        string $name = 'phone',
        string $label = 'رقم الهاتف'
    ): PhoneInput {
        return PhoneInput::make($name)
            ->label($label)
            ->defaultCountry('SA')
            ->initialCountry('sa')
            ->excludeCountries(['il'])
            ->countryOrder(static::PHONE_PREFERRED_COUNTRIES)
            ->separateDialCode(true)
            ->formatAsYouType(true)
            ->showFlags(true)
            ->strictMode(true)
            ->locale('ar')
            ->i18n([
                'ps' => 'فلسطين',
            ]);
    }
}
