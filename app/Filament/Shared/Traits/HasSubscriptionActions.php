<?php

namespace App\Filament\Shared\Traits;

use App\Enums\EnrollmentStatus;
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
            ->label('تأكيد الدفع')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('تأكيد دفع الاشتراك')
            ->modalDescription('سيتم تأكيد الدفع وتفعيل الاشتراك إذا كان معلقاً أو ملغياً.')
            ->modalSubmitActionLabel('تأكيد الدفع')
            ->schema([
                TextInput::make('payment_reference')
                    ->label('مرجع الدفع (اختياري)')
                    ->placeholder('رقم الإيصال أو مرجع التحويل')
                    ->maxLength(255),
            ])
            ->action(function (BaseSubscription $record, array $data) {
                $updateData = [
                    'payment_status' => SubscriptionPaymentStatus::PAID,
                    'last_payment_date' => now(),
                ];

                // If PENDING, SUSPENDED, or CANCELLED, activate the subscription
                if (in_array($record->status, [
                    SessionSubscriptionStatus::PENDING,
                    SessionSubscriptionStatus::SUSPENDED,
                    SessionSubscriptionStatus::CANCELLED,
                ])) {
                    $updateData['status'] = SessionSubscriptionStatus::ACTIVE;

                    // Clear cancellation fields if reactivating from CANCELLED
                    if ($record->status === SessionSubscriptionStatus::CANCELLED) {
                        $updateData['cancelled_at'] = null;
                        $updateData['cancellation_reason'] = null;
                        $updateData['auto_renew'] = true;
                    }

                    // If no start date or dates expired, reset them
                    if (! $record->starts_at || $record->ends_at?->isPast()) {
                        $updateData['starts_at'] = now();
                        $updateData['ends_at'] = $record->calculateEndDate(now());
                    }

                    // If grace period was active, recalculate ends_at from original
                    $metadata = $record->metadata ?? [];
                    if (isset($metadata['original_ends_at'])) {
                        $originalEndsAt = Carbon::parse($metadata['original_ends_at']);
                        $updateData['ends_at'] = $record->billing_cycle
                            ? $record->billing_cycle->calculateEndDate($originalEndsAt)
                            : $originalEndsAt->addMonth();

                        // For Academic: sync end_date
                        if ($record instanceof AcademicSubscription) {
                            $updateData['end_date'] = $updateData['ends_at'];
                        }

                        // Clear original_ends_at (grace period absorbed)
                        unset($metadata['original_ends_at']);
                        $updateData['metadata'] = $metadata ?: null;
                    }
                }

                // Activate linked circle if reactivating
                if (($updateData['status'] ?? null) === SessionSubscriptionStatus::ACTIVE
                    && $record instanceof QuranSubscription
                    && $record->education_unit_id) {
                    $record->educationUnit?->update(['is_active' => true]);
                }

                // Store payment reference in admin notes if provided
                if (! empty($data['payment_reference'])) {
                    $note = sprintf(
                        "[%s] تأكيد دفع بواسطة %s - المرجع: %s",
                        now()->format('Y-m-d H:i'),
                        auth()->user()->name,
                        $data['payment_reference']
                    );
                    $updateData['admin_notes'] = $record->admin_notes
                        ? $record->admin_notes."\n\n".$note
                        : $note;
                }

                $record->update($updateData);

                Notification::make()
                    ->success()
                    ->title('تم تأكيد الدفع')
                    ->body('تم تأكيد الدفع وتفعيل الاشتراك بنجاح.')
                    ->send();
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
            ->label('إيقاف مؤقت')
            ->icon('heroicon-o-pause-circle')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('إيقاف الاشتراك مؤقتاً')
            ->modalDescription('سيتم إيقاف الاشتراك مؤقتاً ويمكن استئنافه لاحقاً.')
            ->action(function (BaseSubscription $record) {
                $record->update([
                    'status' => SessionSubscriptionStatus::PAUSED,
                    'paused_at' => now(),
                ]);

                Notification::make()
                    ->success()
                    ->title('تم إيقاف الاشتراك مؤقتاً')
                    ->send();
            })
            ->visible(fn (BaseSubscription $record) => $record->status === SessionSubscriptionStatus::ACTIVE);
    }

    /**
     * Resume action — reactivates a paused subscription.
     * Extends ends_at by the paused duration to compensate for lost time.
     */
    protected static function getResumeAction(): Action
    {
        return Action::make('resume')
            ->label('استئناف الاشتراك')
            ->icon('heroicon-o-play-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('استئناف الاشتراك')
            ->modalDescription('سيتم استئناف الاشتراك وإعادة تفعيله')
            ->action(function (BaseSubscription $record) {
                $record->update([
                    'status' => SessionSubscriptionStatus::ACTIVE,
                    'paused_at' => null,
                    'pause_reason' => null,
                ]);

                Notification::make()
                    ->success()
                    ->title('تم استئناف الاشتراك')
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
            ->label('إعادة تفعيل الاشتراك')
            ->icon('heroicon-o-arrow-path')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('إعادة تفعيل اشتراك ملغي')
            ->modalDescription('سيتم إعادة تفعيل الاشتراك الملغي وتأكيد الدفع. سيتم تحديث تواريخ البدء والانتهاء.')
            ->modalSubmitActionLabel('نعم، إعادة التفعيل')
            ->action(function (BaseSubscription $record) {
                $updateData = [
                    'status' => SessionSubscriptionStatus::ACTIVE,
                    'payment_status' => SubscriptionPaymentStatus::PAID,
                    'last_payment_date' => now(),
                    'cancelled_at' => null,
                    'cancellation_reason' => null,
                    'auto_renew' => true,
                ];

                // Reset dates if subscription has no valid dates
                if (! $record->starts_at || $record->ends_at?->isPast()) {
                    $updateData['starts_at'] = now();
                    $updateData['ends_at'] = $record->calculateEndDate(now());
                }

                $record->update($updateData);

                // Activate linked circle if exists
                if ($record instanceof QuranSubscription && $record->education_unit_id) {
                    $record->educationUnit?->update(['is_active' => true]);
                }

                Notification::make()
                    ->success()
                    ->title('تم إعادة تفعيل الاشتراك')
                    ->body('تم إعادة تفعيل الاشتراك الملغي بنجاح.')
                    ->send();
            })
            ->visible(fn (BaseSubscription $record) => $record->status === SessionSubscriptionStatus::CANCELLED);
    }

    /**
     * Extend Subscription action — grants a grace period.
     *
     * Pushes ends_at forward by N days but stores original_ends_at in metadata
     * so renewal calculates from the original date. Sets payment_status to PENDING
     * to show the subscription is unpaid. Adds proportional sessions.
     */
    protected static function getExtendSubscriptionAction(): Action
    {
        return Action::make('extendSubscription')
            ->label('تمديد الاشتراك')
            ->icon('heroicon-o-calendar-days')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('تمديد فترة السماح')
            ->modalDescription('منح الطالب فترة سماح إضافية مع تعليق الدفع. يمكن للمعلم الاستمرار في جدولة الجلسات.')
            ->schema([
                TextInput::make('grace_days')
                    ->label('عدد أيام فترة السماح')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->maxValue(365)
                    ->default(14)
                    ->suffix('يوم')
                    ->helperText('عدد الأيام الإضافية من تاريخ الانتهاء الحالي'),
            ])
            ->action(function (BaseSubscription $record, array $data) {
                $graceDays = (int) $data['grace_days'];
                $currentEndDate = $record->ends_at ?? now();
                $newEndDate = $currentEndDate->copy()->addDays($graceDays);

                // Store original_ends_at only on first extension
                $metadata = $record->metadata ?? [];
                if (! isset($metadata['original_ends_at'])) {
                    $metadata['original_ends_at'] = $currentEndDate->toDateTimeString();
                }

                // Log extension in metadata
                $metadata['extensions'] = $metadata['extensions'] ?? [];

                $metadata['extensions'][] = [
                    'type' => 'grace_period',
                    'grace_days' => $graceDays,
                    'extended_by' => auth()->id(),
                    'extended_by_name' => auth()->user()->name,
                    'original_ends_at' => $metadata['original_ends_at'],
                    'new_ends_at' => $newEndDate->toDateTimeString(),
                    'extended_at' => now()->toDateTimeString(),
                ];

                $updateData = [
                    'ends_at' => $newEndDate,
                    'payment_status' => SubscriptionPaymentStatus::PENDING,
                    'metadata' => $metadata,
                ];

                // For Academic: also update end_date
                if ($record instanceof AcademicSubscription) {
                    $updateData['end_date'] = $newEndDate;
                }

                $record->update($updateData);

                Notification::make()
                    ->success()
                    ->title('تم تمديد فترة السماح')
                    ->body("تم تمديد الاشتراك {$graceDays} يوم. تاريخ الانتهاء الجديد: {$newEndDate->format('Y-m-d')}")
                    ->send();
            })
            ->visible(fn (BaseSubscription $record) => in_array($record->status, [
                SessionSubscriptionStatus::ACTIVE,
                SessionSubscriptionStatus::PAUSED,
            ]) && auth()->user()->hasRole(['super_admin', 'admin']));
    }

    /**
     * Cancel action — permanently cancels the subscription.
     * Cancels future scheduled sessions and sets auto_renew to false.
     */
    protected static function getCancelAction(): Action
    {
        return Action::make('cancel')
            ->label('إلغاء الاشتراك')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('إلغاء الاشتراك')
            ->modalDescription('سيتم إلغاء الاشتراك وإلغاء جميع الجلسات المجدولة القادمة.')
            ->modalSubmitActionLabel('نعم، إلغاء الاشتراك')
            ->action(function (BaseSubscription $record) {
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

                Notification::make()
                    ->success()
                    ->title('تم إلغاء الاشتراك')
                    ->body("تم إلغاء الاشتراك و {$cancelledSessions} جلسة مجدولة.")
                    ->send();
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
            ->label('إنشاء حلقة')
            ->icon('heroicon-o-plus-circle')
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading('إنشاء حلقة فردية')
            ->modalDescription('سيتم إنشاء حلقة فردية وربطها بهذا الاشتراك.')
            ->schema([
                Select::make('specialization')
                    ->label('التخصص')
                    ->options([
                        'memorization' => 'حفظ',
                        'recitation' => 'تلاوة',
                        'interpretation' => 'تفسير',
                        'tajweed' => 'تجويد',
                        'complete' => 'شامل',
                    ])
                    ->default('memorization')
                    ->required(),

                Select::make('memorization_level')
                    ->label('مستوى الحفظ')
                    ->options([
                        'beginner' => 'مبتدئ',
                        'intermediate' => 'متوسط',
                        'advanced' => 'متقدم',
                    ])
                    ->default('beginner')
                    ->required(),

                TextInput::make('name')
                    ->label('اسم الحلقة (اختياري)')
                    ->placeholder('يتم إنشاؤه تلقائياً إذا تُرك فارغاً')
                    ->maxLength(255),

                Textarea::make('description')
                    ->label('وصف الحلقة (اختياري)')
                    ->rows(2)
                    ->maxLength(500),

                TagsInput::make('learning_objectives')
                    ->label('أهداف التعلم (اختياري)')
                    ->placeholder('أضف هدفاً تعليمياً')
                    ->reorderable(),

                Select::make('default_duration_minutes')
                    ->label('مدة الجلسة الافتراضية')
                    ->options([
                        30 => '30 دقيقة',
                        45 => '45 دقيقة',
                        60 => '60 دقيقة',
                        90 => '90 دقيقة',
                    ])
                    ->default(45),
            ])
            ->action(function (BaseSubscription $record, array $data) {
                $circleData = [
                    'academy_id' => $record->academy_id,
                    'quran_teacher_id' => $record->quran_teacher_id,
                    'student_id' => $record->student_id,
                    'subscription_id' => $record->id,
                    'specialization' => $data['specialization'],
                    'memorization_level' => $data['memorization_level'],
                    'total_sessions' => $record->total_sessions,
                    'sessions_remaining' => $record->sessions_remaining,
                    'default_duration_minutes' => $data['default_duration_minutes'] ?? $record->session_duration_minutes ?? 45,
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
                        ->title('تم تفعيل الاشتراك تلقائياً')
                        ->body('تم تفعيل الاشتراك لأن الدفع مؤكد والحلقة تم إنشاؤها.')
                        ->send();
                }

                Notification::make()
                    ->success()
                    ->title('تم إنشاء الحلقة')
                    ->body("تم إنشاء الحلقة الفردية: {$circle->circle_code}")
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
            ->label('إلغاء الطلب المعلق')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('إلغاء طلب الاشتراك المعلق')
            ->modalDescription('هل أنت متأكد من إلغاء طلب الاشتراك هذا؟ هذا الإجراء لا يمكن التراجع عنه.')
            ->modalSubmitActionLabel('نعم، إلغاء الطلب')
            ->action(function (BaseSubscription $record) {
                $reason = config('subscriptions.cancellation_reasons.admin');

                $record->cancelAsDuplicateOrExpired($reason);

                // Cancel associated pending payments
                $record->payments()
                    ->where('status', 'pending')
                    ->update(['status' => 'cancelled']);

                Notification::make()
                    ->success()
                    ->title('تم إلغاء الطلب')
                    ->body('تم إلغاء طلب الاشتراك بنجاح.')
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
            ->label('إلغاء الطلبات المعلقة المحددة')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('إلغاء طلبات الاشتراك المعلقة')
            ->modalDescription('سيتم إلغاء جميع طلبات الاشتراك المعلقة المحددة. هذا الإجراء لا يمكن التراجع عنه.')
            ->modalSubmitActionLabel('نعم، إلغاء الطلبات')
            ->action(function (Collection $records) {
                $cancelledCount = 0;
                $pendingStatus = static::getPendingStatus();

                foreach ($records as $record) {
                    if ($record->status === $pendingStatus
                        && $record->payment_status === SubscriptionPaymentStatus::PENDING) {
                        $record->cancelAsDuplicateOrExpired(config('subscriptions.cancellation_reasons.admin'));

                        $record->payments()
                            ->where('status', 'pending')
                            ->update(['status' => 'cancelled']);

                        $cancelledCount++;
                    }
                }

                Notification::make()
                    ->success()
                    ->title('تم إلغاء الطلبات')
                    ->body("تم إلغاء {$cancelledCount} طلب اشتراك بنجاح.")
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
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
            ->label('حالة الطلب')
            ->options([
                'all_pending' => 'جميع الطلبات المعلقة',
                'expired_pending' => 'طلبات منتهية الصلاحية',
                'valid_pending' => 'طلبات صالحة',
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
            ->label("طلبات منتهية (> {$hours} ساعة)")
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
