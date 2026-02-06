<?php

namespace App\Services\Payment;

use App\Contracts\Payment\SupportsRecurringPayments;
use App\Contracts\Payment\SupportsTokenization;
use App\Models\Academy;
use App\Models\SavedPaymentMethod;
use App\Models\User;
use App\Services\Payment\DTOs\PaymentResult;
use App\Services\Payment\DTOs\TokenizationResult;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing saved payment methods.
 *
 * Handles CRUD operations for saved cards/payment methods,
 * and provides methods for charging saved payment methods.
 */
class PaymentMethodService
{
    public function __construct(
        private PaymentGatewayManager $gatewayManager,
        private AcademyPaymentGatewayFactory $gatewayFactory,
    ) {}

    /**
     * Save a new payment method for a user.
     *
     * @param  User  $user  The user who owns the payment method
     * @param  Academy  $academy  The academy context
     * @param  string  $gateway  The payment gateway name
     * @param  TokenizationResult  $tokenResult  The tokenization result from gateway
     * @param  bool  $setAsDefault  Whether to set this as the default payment method
     */
    public function saveFromTokenizationResult(
        User $user,
        Academy $academy,
        string $gateway,
        TokenizationResult $tokenResult,
        bool $setAsDefault = false
    ): ?SavedPaymentMethod {
        if (! $tokenResult->isSuccessful()) {
            Log::warning('Attempted to save failed tokenization result', [
                'user_id' => $user->id,
                'gateway' => $gateway,
                'error_code' => $tokenResult->errorCode,
            ]);

            return null;
        }

        return DB::transaction(function () use ($user, $academy, $gateway, $tokenResult, $setAsDefault) {
            // Check if this token already exists for this user
            $existing = SavedPaymentMethod::where('user_id', $user->id)
                ->where('gateway', $gateway)
                ->where('token', $tokenResult->token)
                ->first();

            if ($existing) {
                // Update existing record
                $existing->update([
                    'brand' => $tokenResult->cardBrand,
                    'last_four' => $tokenResult->lastFour,
                    'expiry_month' => $tokenResult->expiryMonth,
                    'expiry_year' => $tokenResult->expiryYear,
                    'holder_name' => $tokenResult->holderName,
                    'is_active' => true,
                    'metadata' => $tokenResult->metadata,
                ]);

                if ($setAsDefault) {
                    $existing->markAsDefault();
                }

                return $existing->fresh();
            }

            // Create new saved payment method
            $paymentMethod = SavedPaymentMethod::create([
                'academy_id' => $academy->id,
                'user_id' => $user->id,
                'gateway' => $gateway,
                'token' => $tokenResult->token,
                'gateway_customer_id' => $tokenResult->gatewayCustomerId,
                'type' => SavedPaymentMethod::TYPE_CARD,
                'brand' => $tokenResult->cardBrand,
                'last_four' => $tokenResult->lastFour,
                'expiry_month' => $tokenResult->expiryMonth,
                'expiry_year' => $tokenResult->expiryYear,
                'holder_name' => $tokenResult->holderName,
                'is_default' => $setAsDefault,
                'is_active' => true,
                'metadata' => $tokenResult->metadata,
            ]);

            // If setting as default, unset other defaults
            if ($setAsDefault) {
                $paymentMethod->markAsDefault();
            }

            Log::info('Saved payment method created', [
                'id' => $paymentMethod->id,
                'user_id' => $user->id,
                'gateway' => $gateway,
                'brand' => $tokenResult->cardBrand,
                'last_four' => $tokenResult->lastFour,
            ]);

            return $paymentMethod;
        });
    }

    /**
     * Save a payment method from raw token data.
     */
    public function savePaymentMethod(
        User $user,
        Academy $academy,
        string $gateway,
        array $tokenData,
        bool $setAsDefault = false
    ): SavedPaymentMethod {
        return DB::transaction(function () use ($user, $academy, $gateway, $tokenData, $setAsDefault) {
            $paymentMethod = SavedPaymentMethod::create([
                'academy_id' => $academy->id,
                'user_id' => $user->id,
                'gateway' => $gateway,
                'token' => $tokenData['token'],
                'gateway_customer_id' => $tokenData['gateway_customer_id'] ?? null,
                'type' => $tokenData['type'] ?? SavedPaymentMethod::TYPE_CARD,
                'brand' => $tokenData['brand'] ?? null,
                'last_four' => $tokenData['last_four'] ?? null,
                'expiry_month' => $tokenData['expiry_month'] ?? null,
                'expiry_year' => $tokenData['expiry_year'] ?? null,
                'holder_name' => $tokenData['holder_name'] ?? null,
                'display_name' => $tokenData['display_name'] ?? null,
                'is_default' => $setAsDefault,
                'is_active' => true,
                'metadata' => $tokenData['metadata'] ?? null,
                'billing_address' => $tokenData['billing_address'] ?? null,
            ]);

            if ($setAsDefault) {
                $paymentMethod->markAsDefault();
            }

            return $paymentMethod;
        });
    }

    /**
     * Get all payment methods for a user.
     */
    public function getUserPaymentMethods(User $user, ?string $gateway = null, bool $activeOnly = true): Collection
    {
        $query = SavedPaymentMethod::where('user_id', $user->id);

        if ($gateway) {
            $query->forGateway($gateway);
        }

        if ($activeOnly) {
            $query->active()->notExpired();
        }

        return $query->orderByDesc('is_default')
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get the default payment method for a user.
     */
    public function getDefaultPaymentMethod(User $user, ?string $gateway = null): ?SavedPaymentMethod
    {
        $query = SavedPaymentMethod::where('user_id', $user->id)
            ->active()
            ->notExpired()
            ->default();

        if ($gateway) {
            $query->forGateway($gateway);
        }

        return $query->first();
    }

    /**
     * Get a usable payment method for a user (default or most recent).
     */
    public function getUsablePaymentMethod(User $user, ?string $gateway = null): ?SavedPaymentMethod
    {
        // Try default first
        $default = $this->getDefaultPaymentMethod($user, $gateway);
        if ($default) {
            return $default;
        }

        // Fall back to most recently used active card
        $query = SavedPaymentMethod::where('user_id', $user->id)
            ->active()
            ->notExpired();

        if ($gateway) {
            $query->forGateway($gateway);
        }

        return $query->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Set a payment method as the default for the user.
     */
    public function setDefaultPaymentMethod(User $user, SavedPaymentMethod $paymentMethod): bool
    {
        if ($paymentMethod->user_id !== $user->id) {
            Log::warning('Attempted to set another user\'s payment method as default', [
                'user_id' => $user->id,
                'payment_method_user_id' => $paymentMethod->user_id,
            ]);

            return false;
        }

        $paymentMethod->markAsDefault();

        // Also update user's default_payment_method_id
        $user->update(['default_payment_method_id' => $paymentMethod->id]);

        return true;
    }

    /**
     * Delete a payment method.
     */
    public function deletePaymentMethod(SavedPaymentMethod $paymentMethod): bool
    {
        return DB::transaction(function () use ($paymentMethod) {
            // If this was the default, clear user's default
            if ($paymentMethod->is_default) {
                $paymentMethod->user?->update(['default_payment_method_id' => null]);
            }

            // Try to delete token from gateway
            try {
                $gateway = $this->gatewayManager->driver($paymentMethod->gateway);
                if ($gateway instanceof SupportsTokenization) {
                    $gateway->deleteToken($paymentMethod->token);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to delete token from gateway', [
                    'payment_method_id' => $paymentMethod->id,
                    'gateway' => $paymentMethod->gateway,
                    'error' => $e->getMessage(),
                ]);
                // Continue with local deletion even if gateway deletion fails
            }

            // Soft delete the payment method
            $paymentMethod->delete();

            Log::info('Payment method deleted', [
                'payment_method_id' => $paymentMethod->id,
                'user_id' => $paymentMethod->user_id,
            ]);

            return true;
        });
    }

    /**
     * Deactivate a payment method (soft disable without deletion).
     */
    public function deactivatePaymentMethod(SavedPaymentMethod $paymentMethod): bool
    {
        return $paymentMethod->deactivate();
    }

    /**
     * Charge a saved payment method.
     *
     * @param  SavedPaymentMethod  $paymentMethod  The payment method to charge
     * @param  int  $amountInCents  Amount to charge in cents
     * @param  string  $currency  Currency code
     * @param  array  $metadata  Additional metadata (payment_id, academy_id, etc.)
     */
    public function chargePaymentMethod(
        SavedPaymentMethod $paymentMethod,
        int $amountInCents,
        string $currency,
        array $metadata = []
    ): PaymentResult {
        if (! $paymentMethod->isUsable()) {
            return PaymentResult::failed(
                errorCode: 'PAYMENT_METHOD_UNUSABLE',
                errorMessage: 'Payment method is expired or inactive',
                errorMessageAr: __('payments.method_service.expired_or_inactive'),
            );
        }

        try {
            // Get gateway with academy configuration
            $academy = $paymentMethod->academy;
            $gateway = $academy
                ? $this->gatewayFactory->getGateway($academy, $paymentMethod->gateway)
                : $this->gatewayManager->driver($paymentMethod->gateway);

            if (! $gateway instanceof SupportsRecurringPayments) {
                return PaymentResult::failed(
                    errorCode: 'GATEWAY_NO_RECURRING',
                    errorMessage: 'Gateway does not support recurring payments',
                    errorMessageAr: __('payments.method_service.recurring_not_supported'),
                );
            }

            if (! $gateway->supportsRecurring()) {
                return PaymentResult::failed(
                    errorCode: 'RECURRING_NOT_CONFIGURED',
                    errorMessage: 'Recurring payments not properly configured',
                    errorMessageAr: __('payments.method_service.recurring_not_configured'),
                );
            }

            // Charge the payment method
            $result = $gateway->chargeSavedPaymentMethod(
                $paymentMethod,
                $amountInCents,
                $currency,
                $metadata
            );

            // Log the result
            Log::info('Charged saved payment method', [
                'payment_method_id' => $paymentMethod->id,
                'amount_cents' => $amountInCents,
                'currency' => $currency,
                'success' => $result->isSuccessful(),
                'transaction_id' => $result->transactionId,
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Exception charging saved payment method', [
                'payment_method_id' => $paymentMethod->id,
                'error' => $e->getMessage(),
            ]);

            return PaymentResult::failed(
                errorCode: 'CHARGE_EXCEPTION',
                errorMessage: $e->getMessage(),
                errorMessageAr: __('payments.method_service.payment_error'),
            );
        }
    }

    /**
     * Tokenize a new card and optionally save it.
     *
     * @param  User  $user  The user tokenizing the card
     * @param  Academy  $academy  The academy context
     * @param  string  $gateway  The payment gateway to use
     * @param  array  $cardData  Card details (number, expiry, cvv, holder_name)
     * @param  bool  $saveCard  Whether to save the card after tokenization
     */
    public function tokenizeAndSave(
        User $user,
        Academy $academy,
        string $gateway,
        array $cardData,
        bool $saveCard = true
    ): TokenizationResult {
        try {
            $gatewayInstance = $this->gatewayFactory->getGateway($academy, $gateway);

            if (! $gatewayInstance instanceof SupportsTokenization) {
                return TokenizationResult::failed(
                    errorCode: 'GATEWAY_NO_TOKENIZATION',
                    errorMessage: 'Gateway does not support tokenization',
                    errorMessageAr: __('payments.method_service.tokenization_not_supported'),
                );
            }

            if (! $gatewayInstance->supportsTokenization()) {
                return TokenizationResult::failed(
                    errorCode: 'TOKENIZATION_NOT_CONFIGURED',
                    errorMessage: 'Tokenization not properly configured',
                    errorMessageAr: __('payments.method_service.tokenization_not_configured'),
                );
            }

            // Tokenize the card
            $result = $gatewayInstance->tokenizeCard($cardData, $user->id);

            // Save the card if requested and tokenization succeeded
            if ($saveCard && $result->isSuccessful()) {
                $savedMethod = $this->saveFromTokenizationResult(
                    $user,
                    $academy,
                    $gateway,
                    $result,
                    setAsDefault: true
                );

                if ($savedMethod) {
                    // Add saved method info to result metadata
                    return TokenizationResult::success(
                        token: $result->token,
                        cardBrand: $result->cardBrand,
                        lastFour: $result->lastFour,
                        expiryMonth: $result->expiryMonth,
                        expiryYear: $result->expiryYear,
                        holderName: $result->holderName,
                        gatewayCustomerId: $result->gatewayCustomerId,
                        rawResponse: $result->rawResponse,
                        metadata: array_merge($result->metadata, [
                            'saved_payment_method_id' => $savedMethod->id,
                        ]),
                    );
                }
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Exception during tokenization', [
                'user_id' => $user->id,
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);

            return TokenizationResult::failed(
                errorCode: 'TOKENIZATION_EXCEPTION',
                errorMessage: $e->getMessage(),
                errorMessageAr: __('payments.method_service.save_card_error'),
            );
        }
    }

    /**
     * Get payment methods count for a user.
     */
    public function getPaymentMethodsCount(User $user, ?string $gateway = null): int
    {
        $query = SavedPaymentMethod::where('user_id', $user->id)->active();

        if ($gateway) {
            $query->forGateway($gateway);
        }

        return $query->count();
    }

    /**
     * Check if user has any saved payment methods.
     */
    public function hasPaymentMethods(User $user, ?string $gateway = null): bool
    {
        return $this->getPaymentMethodsCount($user, $gateway) > 0;
    }

    /**
     * Clean up expired payment methods for a user.
     */
    public function cleanupExpiredMethods(User $user): int
    {
        $expiredMethods = SavedPaymentMethod::where('user_id', $user->id)
            ->active()
            ->where(function ($query) {
                $query->whereNotNull('expires_at')
                    ->where('expires_at', '<=', now());
            })
            ->orWhere(function ($query) use ($user) {
                // Also check card expiry
                $query->where('user_id', $user->id)
                    ->where('type', SavedPaymentMethod::TYPE_CARD)
                    ->whereNotNull('expiry_month')
                    ->whereNotNull('expiry_year')
                    ->whereRaw("STR_TO_DATE(CONCAT(expiry_year, '-', expiry_month, '-01'), '%Y-%m-%d') < CURDATE()");
            })
            ->get();

        $count = 0;
        foreach ($expiredMethods as $method) {
            $method->deactivate();
            $count++;
        }

        if ($count > 0) {
            Log::info('Cleaned up expired payment methods', [
                'user_id' => $user->id,
                'count' => $count,
            ]);
        }

        return $count;
    }
}
