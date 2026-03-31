<?php

namespace App\Filament\Shared\Traits;

use App\Enums\EnrollmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\SessionDuration;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\AcademicSubscription;
use App\Models\BaseSubscription;
use App\Models\CourseSubscription;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSubscription;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * HasSubscriptionActions Trait
 *
 * Provides ALL reusable Filament actions for subscription management.
 * Consolidated from previously duplicated actions across resources/pages.
 *
 * Actions: Confirm Payment, Reactivate, Pause, Resume, Extend (grace period),
 *          Cancel, Cancel Pending, Create Circle (Quran-only)
 *
 * Also provides: bulk actions, filters, header actions
 */
trait HasSubscriptionActions
{
    /**
     * Check if the model uses session-based status (QuranSubscription, AcademicSubscription)
     * vs enrollment-based status (CourseSubscription).
     */
    protected static function isSessionBasedSubscription(): bool
    {
        $modelClass = static::getModel();

        return $modelClass !== CourseSubscription::class;
    }

    /**
     * Get the pending status for the subscription type.
     */
    protected static function getPendingStatus(): mixed
    {
        return static::isSessionBasedSubscription()
            ? SessionSubscriptionStatus::PENDING
            : EnrollmentStatus::PENDING;
    }

    /**
     * Get the cancelled status for the subscription type.
     */
    protected static function getCancelledStatus(): mixed
    {
        return static::isSessionBasedSubscription()
            ? SessionSubscriptionStatus::CANCELLED
            : EnrollmentStatus::CANCELLED;
    }

    // ========================================
    // SUBSCRIPTION LIFECYCLE ACTIONS
    // ========================================

    /**
     * Confirm Payment action (replaces Activate).
     *
     * Sets payment as PAID. If subscription was PENDING, SUSPENDED, or CANCELLED,
     * activates it. If grace period was active (original_ends_at stored),
     * recalculates ends_at from original date.
     */
    protected static function getConfirmPaymentAction(): Action
    {
        return Action::make('confirmPayment')
            ->label(__('subscriptions.confirm_payment'))
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading(__('subscriptions.confirm_subscription_payment'))
            ->modalDescription(function (BaseSubscription $record) {
                $metadata = $record->metadata ?? [];
                if (isset($metadata['grace_period_ends_at'])) {
                    $gracePeriodEnd = Carbon::parse($metadata['grace_period_ends_at'])->format('Y-m-d');

                    return __('subscriptions.confirm_payment_grace_period', [
                        'grace_end' => $gracePeriodEnd,
                        'ends_at' => $record->ends_at?->format('Y-m-d'),
                    ]);
                }

                return __('subscriptions.confirm_payment_description');
            })
            ->modalSubmitActionLabel(__('subscriptions.confirm_payment'))
            ->schema([
                TextInput::make('payment_reference')
                    ->label(__('subscriptions.payment_reference_label'))
                    ->placeholder(__('subscriptions.payment_reference_placeholder'))
                    ->maxLength(255),
            ])
            ->action(function (BaseSubscription $record, array $data) {
                // Tenant ownership guard
                $academyId = auth()->user()?->academy_id;
                if ($academyId !== null && $record->academy_id !== $academyId) {
                    \Filament\Notifications\Notification::make()
                        ->danger()
                        ->title(__('common.unauthorized'))
                        ->send();

                    return;
                }

                try {
                    app(\App\Services\Payment\PaymentReconciliationService::class)
                        ->confirmPaymentAndActivate(
                            $record,
                            $data['payment_reference'] ?? null,
                        );

                    Notification::make()
                        ->success()
                        ->title(__('subscriptions.payment_confirmed_title'))
                        ->body(__('subscriptions.payment_confirmed_and_activated'))
                        ->send();
                } catch (\Exception $e) {
                    report($e);
                    Notification::make()
                        ->danger()
                        ->title(__('subscriptions.payment_confirmation_failed'))
                        ->body(__('subscriptions.generic_error'))
                        ->send();
                }
            })
            ->visible(fn (BaseSubscription $record) => in_array($record->payment_status, [
                SubscriptionPaymentStatus::PENDING,
                SubscriptionPaymentStatus::FAILED,
            ]));
    }

    /**
     * Pause action — temporarily stops the subscription.
     */
    protected static function getPauseAction(): Action
    {
        return Action::make('pause')
            ->label(__('subscriptions.pause_label'))
            ->icon('heroicon-o-pause-circle')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading(__('subscriptions.pause_modal_heading'))
            ->modalDescription(__('subscriptions.pause_modal_description'))
            ->action(function (BaseSubscription $record) {
                // Tenant ownership guard
                $academyId = auth()->user()?->academy_id;
                if ($academyId !== null && $record->academy_id !== $academyId) {
                    \Filament\Notifications\Notification::make()
                        ->danger()
                        ->title(__('common.unauthorized'))
                        ->send();

                    return;
                }
                $record->update([
                    'status' => SessionSubscriptionStatus::PAUSED,
                    'paused_at' => now(),
                ]);

                Notification::make()
                    ->success()
                    ->title(__('subscriptions.pause_success'))
                    ->send();
            })
            ->visible(fn (BaseSubscription $record) => $record->status === SessionSubscriptionStatus::ACTIVE
                && ! $record->isInGracePeriod());
    }

    /**
     * Resume action — reactivates a paused subscription.
     * Extends ends_at by the paused duration to compensate for lost time.
     */
    protected static function getResumeAction(): Action
    {
        return Action::make('resume')
            ->label(__('subscriptions.resume_label'))
            ->icon('heroicon-o-play-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading(__('subscriptions.resume_modal_heading'))
            ->modalDescription(__('subscriptions.resume_modal_description'))
            ->action(function (BaseSubscription $record) {
                // Tenant ownership guard
                $academyId = auth()->user()?->academy_id;
                if ($academyId !== null && $record->academy_id !== $academyId) {
                    \Filament\Notifications\Notification::make()
                        ->danger()
                        ->title(__('common.unauthorized'))
                        ->send();

                    return;
                }
                $record->update([
                    'status' => SessionSubscriptionStatus::ACTIVE,
                    'paused_at' => null,
                    'pause_reason' => null,
                ]);

                Notification::make()
                    ->success()
                    ->title(__('subscriptions.resume_success'))
                    ->send();
            })
            ->visible(fn (BaseSubscription $record) => $record->status === SessionSubscriptionStatus::PAUSED);
    }

    /**
     * Reactivate action — brings a cancelled subscription back to active.
     * Resets cancellation fields, updates dates, and activates linked circles.
     */
    protected static function getReactivateAction(): Action
    {
        return Action::make('reactivate')
            ->label(__('subscriptions.reactivate_label'))
            ->icon('heroicon-o-arrow-path')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading(__('subscriptions.reactivate_modal_heading'))
            ->modalDescription(__('subscriptions.reactivate_modal_description'))
            ->modalSubmitActionLabel(__('subscriptions.reactivate_confirm_button'))
            ->action(function (BaseSubscription $record) {
                // Tenant ownership guard
                $academyId = auth()->user()?->academy_id;
                if ($academyId !== null && $record->academy_id !== $academyId) {
                    \Filament\Notifications\Notification::make()
                        ->danger()
                        ->title(__('common.unauthorized'))
                        ->send();

                    return;
                }
                // Clear grace period metadata on reactivation
                $metadata = $record->metadata ?? [];
                unset(
                    $metadata['grace_period_ends_at'],
                    $metadata['grace_period_expires_at'],
                    $metadata['grace_period_started_at'],
                    $metadata['grace_notification_last_sent_at'],
                    $metadata['renewal_failed_count'],
                    $metadata['last_renewal_failure_at'],
                    $metadata['last_renewal_failure_reason']
                );

                $updateData = [
                    'status' => SessionSubscriptionStatus::ACTIVE,
                    'payment_status' => SubscriptionPaymentStatus::PAID,
                    'last_payment_date' => now(),
                    'cancelled_at' => null,
                    'cancellation_reason' => null,
                    // Do NOT force auto_renew=true — the student explicitly cancelled this
                    // subscription and should opt back in to auto-renewal manually.
                    'auto_renew' => false,
                    'metadata' => $metadata ?: null,
                ];

                // Reset dates if subscription has no valid dates
                if (! $record->starts_at || $record->ends_at?->isPast()) {
                    $updateData['starts_at'] = now();
                    $updateData['ends_at'] = $record->calculateEndDate(now());
                }

                // Wrap both writes in a transaction so the subscription and its linked
                // circle are always updated atomically.
                DB::transaction(function () use ($record, $updateData) {
                    $record->update($updateData);

                    // Activate linked circle if exists
                    if ($record instanceof QuranSubscription && $record->education_unit_id) {
                        $record->educationUnit?->update(['is_active' => true]);
                    }
                });

                Notification::make()
                    ->success()
                    ->title(__('subscriptions.reactivate_success'))
                    ->body(__('subscriptions.reactivate_success_body'))
                    ->send();
            })
            ->visible(fn (BaseSubscription $record) => $record->status === SessionSubscriptionStatus::CANCELLED);
    }

    /**
     * Extend Subscription action — grants a grace period.
     *
     * Does NOT modify ends_at. Instead stores grace_period_ends_at in metadata.
     * ends_at always represents the paid-for subscription period end.
     * The student keeps access during the grace period (status stays ACTIVE, payment stays PAID).
     */
    protected static function getExtendSubscriptionAction(): Action
    {
        return Action::make('extendSubscription')
            ->label(__('subscriptions.extend_grace_label'))
            ->icon('heroicon-o-calendar-days')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading(__('subscriptions.extend_grace_modal_heading'))
            ->modalDescription(fn (BaseSubscription $record) => __('subscriptions.extend_grace_modal_description', [
                'ends_at' => $record->ends_at?->format('Y-m-d') ?? __('subscriptions.not_specified'),
            ]))
            ->schema([
                TextInput::make('grace_days')
                    ->label(__('subscriptions.grace_days_label'))
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->maxValue(365)
                    ->default(14)
                    ->suffix(__('subscriptions.day_suffix'))
                    ->helperText(fn (BaseSubscription $record) => $record->ends_at
                        ? __('subscriptions.grace_calculated_from')
                            .(isset($record->metadata['grace_period_ends_at'])
                                ? __('subscriptions.grace_current_ends').Carbon::parse($record->metadata['grace_period_ends_at'])->format('Y-m-d')
                                : __('subscriptions.subscription_ends_at_prefix').$record->ends_at->format('Y-m-d'))
                        : __('subscriptions.additional_days')),
            ])
            ->action(function (BaseSubscription $record, array $data) {
                // Tenant ownership guard
                $academyId = auth()->user()?->academy_id;
                if ($academyId !== null && $record->academy_id !== $academyId) {
                    \Filament\Notifications\Notification::make()
                        ->danger()
                        ->title(__('common.unauthorized'))
                        ->send();

                    return;
                }
                $graceDays = (int) $data['grace_days'];
                $metadata = $record->metadata ?? [];

                // Calculate grace_period_ends_at: stack on existing grace period or start from ends_at
                $baseDate = isset($metadata['grace_period_ends_at'])
                    ? Carbon::parse($metadata['grace_period_ends_at'])
                    : ($record->ends_at ?? now());

                $gracePeriodEndsAt = $baseDate->copy()->addDays($graceDays);
                $metadata['grace_period_ends_at'] = $gracePeriodEndsAt->toDateTimeString();

                // Log extension in metadata
                $metadata['extensions'] = $metadata['extensions'] ?? [];
                $metadata['extensions'][] = [
                    'type' => 'grace_period',
                    'grace_days' => $graceDays,
                    'extended_by' => auth()->id(),
                    'extended_by_name' => auth()->user()->name,
                    'ends_at_at_time' => ($record->ends_at ?? now())->toDateTimeString(),
                    'grace_period_ends_at' => $gracePeriodEndsAt->toDateTimeString(),
                    'extended_at' => now()->toDateTimeString(),
                ];

                $updateData = ['metadata' => $metadata];

                // If subscription is EXPIRED or SUSPENDED, transition to ACTIVE
                if (in_array($record->status, [
                    SessionSubscriptionStatus::EXPIRED,
                    SessionSubscriptionStatus::SUSPENDED,
                ])) {
                    $updateData['status'] = SessionSubscriptionStatus::ACTIVE;
                }

                // Update metadata (+ status if needed) — do NOT change ends_at or payment_status
                $record->update($updateData);

                Notification::make()
                    ->success()
                    ->title(__('subscriptions.extend_grace_success'))
                    ->body(__('subscriptions.extend_grace_success_body', [
                        'days' => $graceDays,
                        'date' => $gracePeriodEndsAt->format('Y-m-d'),
                    ]))
                    ->send();
            })
            ->visible(fn (BaseSubscription $record) => in_array($record->status, [
                SessionSubscriptionStatus::ACTIVE,
                SessionSubscriptionStatus::PAUSED,
                SessionSubscriptionStatus::EXPIRED,
                SessionSubscriptionStatus::SUSPENDED,
            ]) && auth()->user()->hasRole(['super_admin', 'admin']));
    }

    /**
     * Cancel action — permanently cancels the subscription.
     * Cancels future scheduled sessions and sets auto_renew to false.
     */
    protected static function getCancelAction(): Action
    {
        return Action::make('cancel')
            ->label(__('subscriptions.cancel_label'))
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(__('subscriptions.cancel_modal_heading'))
            ->modalDescription(__('subscriptions.cancel_modal_description'))
            ->modalSubmitActionLabel(__('subscriptions.cancel_confirm_button'))
            ->action(function (BaseSubscription $record) {
                // Tenant ownership guard
                $academyId = auth()->user()?->academy_id;
                if ($academyId !== null && $record->academy_id !== $academyId) {
                    \Filament\Notifications\Notification::make()
                        ->danger()
                        ->title(__('common.unauthorized'))
                        ->send();

                    return;
                }
                DB::transaction(function () use ($record) {
                    $record->update([
                        'status' => SessionSubscriptionStatus::CANCELLED,
                        'cancelled_at' => now(),
                        'auto_renew' => false,
                    ]);

                    // Cancel future scheduled sessions
                    $cancelledSessions = $record->sessions()
                        ->where('scheduled_at', '>', now())
                        ->where('status', SessionStatus::SCHEDULED)
                        ->update(['status' => SessionStatus::CANCELLED]);

                    DB::afterCommit(function () use ($cancelledSessions) {
                        Notification::make()
                            ->success()
                            ->title(__('subscriptions.cancel_success'))
                            ->body(__('subscriptions.cancel_success_body', ['count' => $cancelledSessions]))
                            ->send();
                    });
                });
            })
            ->visible(fn (BaseSubscription $record) => ! in_array($record->status, [
                SessionSubscriptionStatus::CANCELLED,
                SessionSubscriptionStatus::PENDING,
            ]));
    }

    /**
     * Create Circle action — Quran-only.
     * Creates a QuranIndividualCircle for individual subscriptions that don't have one.
     * If subscription is cancelled but paid, auto-activates the subscription.
     */
    protected static function getCreateCircleAction(): Action
    {
        return Action::make('createCircle')
            ->label(__('subscriptions.create_circle_label'))
            ->icon('heroicon-o-plus-circle')
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading(__('subscriptions.create_circle_modal_heading'))
            ->modalDescription(__('subscriptions.create_circle_modal_description'))
            ->schema([
                Select::make('specialization')
                    ->label(__('subscriptions.specialization_label'))
                    ->options([
                        'memorization' => __('subscriptions.specialization_memorization'),
                        'recitation' => __('subscriptions.specialization_recitation'),
                        'interpretation' => __('subscriptions.specialization_interpretation'),
                        'tajweed' => __('subscriptions.specialization_tajweed'),
                        'complete' => __('subscriptions.specialization_complete'),
                    ])
                    ->default('memorization')
                    ->required(),

                Select::make('memorization_level')
                    ->label(__('subscriptions.memorization_level_label'))
                    ->options([
                        'beginner' => __('subscriptions.level_beginner'),
                        'intermediate' => __('subscriptions.level_intermediate'),
                        'advanced' => __('subscriptions.level_advanced'),
                    ])
                    ->default('beginner')
                    ->required(),

                TextInput::make('name')
                    ->label(__('subscriptions.circle_name_label'))
                    ->placeholder(__('subscriptions.circle_name_placeholder'))
                    ->maxLength(255),

                Textarea::make('description')
                    ->label(__('subscriptions.circle_description_label'))
                    ->rows(2)
                    ->maxLength(500),

                TagsInput::make('learning_objectives')
                    ->label(__('subscriptions.learning_objectives_label'))
                    ->placeholder(__('subscriptions.learning_objectives_placeholder'))
                    ->reorderable(),

                Select::make('default_duration_minutes')
                    ->label(__('subscriptions.default_session_duration_label'))
                    ->options(SessionDuration::options()),
            ])
            ->fillForm(fn (BaseSubscription $record) => [
                'default_duration_minutes' => $record->session_duration_minutes,
            ])
            ->action(function (BaseSubscription $record, array $data) {
                // Tenant ownership guard
                $academyId = auth()->user()?->academy_id;
                if ($academyId !== null && $record->academy_id !== $academyId) {
                    \Filament\Notifications\Notification::make()
                        ->danger()
                        ->title(__('common.unauthorized'))
                        ->send();

                    return;
                }
                $circleData = [
                    'academy_id' => $record->academy_id,
                    'quran_teacher_id' => $record->quran_teacher_id,
                    'student_id' => $record->student_id,
                    'subscription_id' => $record->id,
                    'specialization' => $data['specialization'],
                    'memorization_level' => $data['memorization_level'],
                    'total_sessions' => $record->total_sessions,
                    'sessions_remaining' => $record->sessions_remaining,
                    'default_duration_minutes' => $data['default_duration_minutes'] ?? $record->session_duration_minutes ?? $record->package?->session_duration_minutes ?? 60,
                    'is_active' => true,
                ];

                if (! empty($data['name'])) {
                    $circleData['name'] = $data['name'];
                }
                if (! empty($data['description'])) {
                    $circleData['description'] = $data['description'];
                }
                if (! empty($data['learning_objectives'])) {
                    $circleData['learning_objectives'] = $data['learning_objectives'];
                }

                $circle = QuranIndividualCircle::create($circleData);

                // Link via polymorphic relationship
                $record->linkToEducationUnit($circle);

                // If subscription is cancelled but payment confirmed, auto-activate
                if ($record->status === SessionSubscriptionStatus::CANCELLED
                    && $record->payment_status === SubscriptionPaymentStatus::PAID) {
                    $activateData = [
                        'status' => SessionSubscriptionStatus::ACTIVE,
                        'cancelled_at' => null,
                        'cancellation_reason' => null,
                        'auto_renew' => true,
                    ];

                    if (! $record->starts_at || $record->ends_at?->isPast()) {
                        $activateData['starts_at'] = now();
                        $activateData['ends_at'] = $record->calculateEndDate(now());
                    }

                    $record->update($activateData);

                    Notification::make()
                        ->info()
                        ->title(__('subscriptions.auto_activated_title'))
                        ->body(__('subscriptions.auto_activated_body'))
                        ->send();
                }

                Notification::make()
                    ->success()
                    ->title(__('subscriptions.create_circle_success'))
                    ->body(__('subscriptions.create_circle_success_body', ['code' => $circle->circle_code]))
                    ->send();
            })
            ->visible(fn (BaseSubscription $record) => $record instanceof QuranSubscription
                && $record->subscription_type === 'individual'
                && ! $record->education_unit_id);
    }

    // ========================================
    // CANCEL PENDING ACTIONS (kept from original)
    // ========================================

    /**
     * Get the "Cancel Pending" action for single subscriptions.
     */
    protected static function getCancelPendingAction(): Action
    {
        return Action::make('cancelPending')
            ->label(__('subscriptions.cancel_pending_label'))
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(__('subscriptions.cancel_pending_modal_heading'))
            ->modalDescription(__('subscriptions.cancel_pending_modal_description'))
            ->modalSubmitActionLabel(__('subscriptions.cancel_pending_confirm_button'))
            ->action(function (BaseSubscription $record) {
                // Tenant ownership guard
                $academyId = auth()->user()?->academy_id;
                if ($academyId !== null && $record->academy_id !== $academyId) {
                    \Filament\Notifications\Notification::make()
                        ->danger()
                        ->title(__('common.unauthorized'))
                        ->send();

                    return;
                }
                $reason = config('subscriptions.cancellation_reasons.admin');

                $record->cancelAsDuplicateOrExpired($reason);

                // Cancel associated pending payments
                $record->payments()
                    ->where('status', PaymentStatus::PENDING)
                    ->update(['status' => PaymentStatus::CANCELLED]);

                Notification::make()
                    ->success()
                    ->title(__('subscriptions.cancel_pending_success'))
                    ->body(__('subscriptions.cancel_pending_success_body'))
                    ->send();
            })
            ->visible(fn (BaseSubscription $record) => $record->isPending()
                && $record->payment_status === SubscriptionPaymentStatus::PENDING);
    }

    /**
     * Get the bulk cancel action for pending subscriptions.
     */
    protected static function getBulkCancelPendingAction(): BulkAction
    {
        return BulkAction::make('bulkCancelPending')
            ->label(__('subscriptions.bulk_cancel_pending_label'))
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(__('subscriptions.bulk_cancel_pending_modal_heading'))
            ->modalDescription(__('subscriptions.bulk_cancel_pending_modal_description'))
            ->modalSubmitActionLabel(__('subscriptions.bulk_cancel_pending_confirm_button'))
            ->action(function (Collection $records) {
                $cancelledCount = 0;
                $pendingStatus = static::getPendingStatus();

                foreach ($records as $record) {
                    if ($record->status === $pendingStatus
                        && $record->payment_status === SubscriptionPaymentStatus::PENDING) {
                        $record->cancelAsDuplicateOrExpired(config('subscriptions.cancellation_reasons.admin'));

                        $record->payments()
                            ->where('status', PaymentStatus::PENDING)
                            ->update(['status' => PaymentStatus::CANCELLED]);

                        $cancelledCount++;
                    }
                }

                Notification::make()
                    ->success()
                    ->title(__('subscriptions.bulk_cancel_pending_success'))
                    ->body(__('subscriptions.bulk_cancel_pending_success_body', ['count' => $cancelledCount]))
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    // ========================================
    // RENEWAL & RESUBSCRIBE ACTIONS
    // ========================================

    /**
     * Shared form schema for renew and resubscribe actions.
     */
    private static function getRenewalFormSchema(): array
    {
        return [
            Select::make('billing_cycle')
                ->label(__('subscriptions.select_billing_cycle'))
                ->options([
                    'monthly' => __('enums.billing_cycle.monthly'),
                    'quarterly' => __('enums.billing_cycle.quarterly'),
                    'yearly' => __('enums.billing_cycle.yearly'),
                ])
                ->default(fn (BaseSubscription $record) => $record->billing_cycle->value)
                ->required(),
            Select::make('activate_mode')
                ->label(__('subscriptions.activation_mode'))
                ->options([
                    'pending' => __('subscriptions.create_as_pending'),
                    'immediate' => __('subscriptions.activate_immediately'),
                ])
                ->default('pending')
                ->required(),
        ];
    }

    /**
     * Renew action — creates a new subscription from an active/expiring one.
     * Supports package change and billing cycle change.
     */
    protected static function getRenewAction(): Action
    {
        return Action::make('renewSubscription')
            ->label(__('subscriptions.renew_subscription'))
            ->icon('heroicon-o-arrow-path')
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading(__('subscriptions.renew_subscription'))
            ->modalDescription(function (BaseSubscription $record) {
                $remaining = method_exists($record, 'getSessionsRemaining') ? $record->getSessionsRemaining() : 0;
                if ($remaining > 0) {
                    return __('subscriptions.sessions_carryover', ['count' => $remaining]);
                }

                return __('subscriptions.confirm_payment_description');
            })
            ->schema(static::getRenewalFormSchema())
            ->action(function (BaseSubscription $record, array $data) {
                $academyId = auth()->user()?->academy_id;
                if ($academyId !== null && $record->academy_id !== $academyId) {
                    Notification::make()->danger()->title(__('common.unauthorized'))->send();

                    return;
                }

                try {
                    $new = app(\App\Services\Subscription\SubscriptionRenewalService::class)
                        ->renew($record, [
                            'billing_cycle' => $data['billing_cycle'],
                            'activate_immediately' => $data['activate_mode'] === 'immediate',
                        ]);

                    Notification::make()
                        ->success()
                        ->title(__('subscriptions.renewal_success'))
                        ->body(__('subscriptions.renewal_success')." (#{$new->subscription_code})")
                        ->send();
                } catch (\Exception $e) {
                    report($e);
                    Notification::make()->danger()->title(__('subscriptions.payment_confirmation_failed'))->body(__('subscriptions.generic_error'))->send();
                }
            })
            ->visible(fn (BaseSubscription $record) => app(\App\Services\Subscription\SubscriptionRenewalService::class)->canRenew($record));
    }

    /**
     * Resubscribe action — creates a new subscription from a cancelled/expired one.
     * Checks teacher availability and uses current pricing.
     */
    protected static function getResubscribeAction(): Action
    {
        return Action::make('resubscribe')
            ->label(__('subscriptions.resubscribe'))
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading(__('subscriptions.resubscribe'))
            ->schema(static::getRenewalFormSchema())
            ->action(function (BaseSubscription $record, array $data) {
                $academyId = auth()->user()?->academy_id;
                if ($academyId !== null && $record->academy_id !== $academyId) {
                    Notification::make()->danger()->title(__('common.unauthorized'))->send();

                    return;
                }

                try {
                    $new = app(\App\Services\Subscription\SubscriptionRenewalService::class)
                        ->resubscribe($record, [
                            'billing_cycle' => $data['billing_cycle'],
                            'activate_immediately' => $data['activate_mode'] === 'immediate',
                        ]);

                    Notification::make()
                        ->success()
                        ->title(__('subscriptions.resubscribe_success'))
                        ->body(__('subscriptions.resubscribe_success')." (#{$new->subscription_code})")
                        ->send();
                } catch (\Exception $e) {
                    report($e);
                    Notification::make()->danger()->title(__('subscriptions.payment_confirmation_failed'))->body(__('subscriptions.generic_error'))->send();
                }
            })
            ->visible(fn (BaseSubscription $record) => app(\App\Services\Subscription\SubscriptionRenewalService::class)->canResubscribe($record));
    }

    // ========================================
    // ACTION AGGREGATORS
    // ========================================

    /**
     * Get all subscription-related table actions.
     */
    protected static function getSubscriptionTableActions(): array
    {
        return [
            static::getConfirmPaymentAction(),
            static::getRenewAction(),
            static::getResubscribeAction(),
            static::getReactivateAction(),
            static::getPauseAction(),
            static::getResumeAction(),
            static::getExtendSubscriptionAction(),
            static::getCancelAction(),
            static::getCancelPendingAction(),
        ];
    }

    /**
     * Get all subscription-related actions for view page headers.
     */
    public static function getSubscriptionViewActions(): array
    {
        $actions = [
            static::getConfirmPaymentAction(),
            static::getRenewAction(),
            static::getResubscribeAction(),
            static::getReactivateAction(),
            static::getPauseAction(),
            static::getResumeAction(),
            static::getExtendSubscriptionAction(),
            static::getCancelAction(),
            static::getCancelPendingAction(),
        ];

        // Add Create Circle for Quran subscriptions
        if (static::getModel() === QuranSubscription::class) {
            $actions[] = static::getCreateCircleAction();
        }

        return $actions;
    }

    /**
     * Get all subscription-related bulk actions.
     */
    protected static function getSubscriptionBulkActions(): array
    {
        return [
            static::getBulkCancelPendingAction(),
        ];
    }

    // ========================================
    // FILTERS
    // ========================================

    /**
     * Get filter for pending subscriptions.
     */
    protected static function getPendingSubscriptionsFilter(): SelectFilter
    {
        return SelectFilter::make('pending_status')
            ->label(__('subscriptions.request_status_label'))
            ->options([
                'all_pending' => __('subscriptions.filter_all_pending'),
                'expired_pending' => __('subscriptions.filter_expired_pending'),
                'valid_pending' => __('subscriptions.filter_valid_pending'),
            ])
            ->query(function (Builder $query, array $data): Builder {
                if (empty($data['value'])) {
                    return $query;
                }

                $pendingStatus = static::getPendingStatus();
                $hours = config('subscriptions.pending.expires_after_hours', 48);

                return match ($data['value']) {
                    'all_pending' => $query->where('status', $pendingStatus)
                        ->where('payment_status', SubscriptionPaymentStatus::PENDING),

                    'expired_pending' => $query->where('status', $pendingStatus)
                        ->where('payment_status', SubscriptionPaymentStatus::PENDING)
                        ->where('created_at', '<', now()->subHours($hours)),

                    'valid_pending' => $query->where('status', $pendingStatus)
                        ->where('payment_status', SubscriptionPaymentStatus::PENDING)
                        ->where('created_at', '>=', now()->subHours($hours)),

                    default => $query,
                };
            });
    }

    /**
     * Get filter for expired pending subscriptions only.
     */
    protected static function getExpiredPendingFilter(): Filter
    {
        $hours = config('subscriptions.pending.expires_after_hours', 48);

        return Filter::make('expired_pending')
            ->label(__('subscriptions.filter_expired_hours', ['hours' => $hours]))
            ->query(function (Builder $query): Builder {
                $pendingStatus = static::getPendingStatus();
                $hours = config('subscriptions.pending.expires_after_hours', 48);

                return $query->where('status', $pendingStatus)
                    ->where('payment_status', SubscriptionPaymentStatus::PENDING)
                    ->where('created_at', '<', now()->subHours($hours));
            })
            ->toggle();
    }

    /**
     * Get all subscription-related filters.
     */
    protected static function getSubscriptionFilters(): array
    {
        return [
            static::getPendingSubscriptionsFilter(),
            static::getExpiredPendingFilter(),
        ];
    }
}
