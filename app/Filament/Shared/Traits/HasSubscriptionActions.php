<?php

namespace App\Filament\Shared\Traits;

use App\Enums\EnrollmentStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\BaseSubscription;
use App\Services\SubscriptionService;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * HasSubscriptionActions Trait
 *
 * Provides reusable Filament table actions for subscription management.
 * Can be used by QuranSubscriptionResource, AcademicSubscriptionResource, and CourseSubscriptionResource.
 *
 * Provides:
 * - Cancel pending action (single)
 * - Bulk cancel pending action
 * - Cancel expired pending action
 * - Pending subscriptions filter
 * - Expired pending filter
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

        return $modelClass !== \App\Models\CourseSubscription::class;
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

    /**
     * Get the "Cancel Pending" action for single subscriptions.
     *
     * Shows only for pending subscriptions and allows immediate cancellation
     * without requiring a reason (since it's a pending subscription).
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
            ->form([
                Textarea::make('cancellation_reason')
                    ->label('سبب الإلغاء (اختياري)')
                    ->placeholder('مثال: لم يتم الدفع خلال المهلة المحددة')
                    ->maxLength(500),
            ])
            ->action(function (BaseSubscription $record, array $data) {
                $reason = $data['cancellation_reason'] ?? config('subscriptions.cancellation_reasons.admin');

                $record->cancelAsDuplicateOrExpired($reason);

                // Cancel associated pending payments
                $record->payments()
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                    ]);

                Notification::make()
                    ->success()
                    ->title('تم إلغاء الطلب')
                    ->body("تم إلغاء طلب الاشتراك بنجاح.")
                    ->send();
            })
            ->visible(fn (BaseSubscription $record) => $record->isPending()
                && $record->payment_status === SubscriptionPaymentStatus::PENDING);
    }

    /**
     * Get the bulk cancel action for pending subscriptions.
     *
     * Allows admins to cancel multiple pending subscriptions at once.
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
            ->form([
                Textarea::make('cancellation_reason')
                    ->label('سبب الإلغاء')
                    ->default(config('subscriptions.cancellation_reasons.admin'))
                    ->required(),
            ])
            ->action(function (Collection $records, array $data) {
                $cancelledCount = 0;
                $pendingStatus = static::getPendingStatus();

                foreach ($records as $record) {
                    // Only cancel if pending and payment is pending
                    if ($record->status === $pendingStatus
                        && $record->payment_status === SubscriptionPaymentStatus::PENDING) {
                        $record->cancelAsDuplicateOrExpired($data['cancellation_reason']);

                        // Cancel associated payments
                        $record->payments()
                            ->where('status', 'pending')
                            ->update([
                                'status' => 'cancelled',
                                'cancelled_at' => now(),
                            ]);

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

    /**
     * Get action to cancel all expired pending subscriptions.
     *
     * This is a header action that cancels all pending subscriptions
     * older than the configured expiry time.
     */
    protected static function getCancelExpiredPendingAction(): Action
    {
        return Action::make('cancelAllExpiredPending')
            ->label('إلغاء الطلبات المنتهية')
            ->icon('heroicon-o-clock')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('إلغاء جميع طلبات الاشتراك المنتهية')
            ->modalDescription(function () {
                $hours = config('subscriptions.pending.expires_after_hours', 48);

                return "سيتم إلغاء جميع طلبات الاشتراك المعلقة التي مر عليها أكثر من {$hours} ساعة بدون دفع.";
            })
            ->modalSubmitActionLabel('نعم، إلغاء الطلبات المنتهية')
            ->action(function () {
                $subscriptionService = app(SubscriptionService::class);
                $hours = config('subscriptions.pending.expires_after_hours', 48);

                $result = $subscriptionService->cleanupExpiredPending($hours, false);

                Notification::make()
                    ->success()
                    ->title('تم إلغاء الطلبات المنتهية')
                    ->body("تم إلغاء {$result['cancelled']} طلب اشتراك منتهي.")
                    ->send();
            });
    }

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
     * Get all subscription-related table actions.
     *
     * Returns an array of actions that can be spread into the resource's actions array.
     */
    protected static function getSubscriptionTableActions(): array
    {
        return [
            static::getCancelPendingAction(),
        ];
    }

    /**
     * Get all subscription-related bulk actions.
     *
     * Returns an array of bulk actions that can be spread into the resource's bulk actions array.
     */
    protected static function getSubscriptionBulkActions(): array
    {
        return [
            static::getBulkCancelPendingAction(),
        ];
    }

    /**
     * Get all subscription-related filters.
     *
     * Returns an array of filters that can be spread into the resource's filters array.
     */
    protected static function getSubscriptionFilters(): array
    {
        return [
            static::getPendingSubscriptionsFilter(),
            static::getExpiredPendingFilter(),
        ];
    }

    /**
     * Get all subscription-related header actions.
     *
     * Returns an array of header actions for the table.
     */
    protected static function getSubscriptionHeaderActions(): array
    {
        return [
            static::getCancelExpiredPendingAction(),
        ];
    }
}
