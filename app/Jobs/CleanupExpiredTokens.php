<?php

namespace App\Jobs;

use App\Models\GoogleToken;
use App\Models\PlatformGoogleAccount;
use App\Services\GoogleCalendarService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\GoogleTokenExpiredNotification;
use Carbon\Carbon;

class CleanupExpiredTokens implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 2;

    private GoogleCalendarService $googleCalendarService;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->googleCalendarService = app(GoogleCalendarService::class);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting Google token cleanup job');

        $results = [
            'tokens_checked' => 0,
            'tokens_refreshed' => 0,
            'tokens_expired' => 0,
            'tokens_cleaned' => 0,
            'platform_accounts_checked' => 0,
            'platform_accounts_refreshed' => 0,
            'notifications_sent' => 0,
            'errors' => []
        ];

        try {
            // Process user tokens
            $userTokenResults = $this->processUserTokens();
            $results = array_merge_recursive($results, $userTokenResults);

            // Process platform accounts
            $platformResults = $this->processPlatformAccounts();
            $results = array_merge_recursive($results, $platformResults);

            // Clean up old expired tokens
            $cleanupResults = $this->cleanupOldTokens();
            $results['tokens_cleaned'] = $cleanupResults['cleaned'];

            // Reset daily usage for platform accounts
            $this->resetPlatformAccountUsage();

            Log::info('Google token cleanup completed', $results);

        } catch (\Exception $e) {
            Log::error('Token cleanup job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Process user Google tokens
     */
    private function processUserTokens(): array
    {
        $results = [
            'tokens_checked' => 0,
            'tokens_refreshed' => 0,
            'tokens_expired' => 0,
            'notifications_sent' => 0,
            'errors' => []
        ];

        // Get tokens that need refresh or are expired
        $tokensToProcess = GoogleToken::where('token_status', GoogleToken::STATUS_ACTIVE)
            ->where(function ($query) {
                $query->where('expires_at', '<', now()->addMinutes(30)) // Refresh 30 minutes early
                      ->orWhere('consecutive_errors', '>=', 3);
            })
            ->with('user')
            ->get();

        foreach ($tokensToProcess as $token) {
            $results['tokens_checked']++;

            try {
                if ($token->consecutive_errors >= 3) {
                    // Mark as expired
                    $this->markTokenAsExpired($token);
                    $results['tokens_expired']++;
                    $results['notifications_sent'] += $this->sendExpirationNotification($token);
                } else {
                    // Try to refresh
                    if ($this->refreshUserToken($token)) {
                        $results['tokens_refreshed']++;
                    } else {
                        $results['tokens_expired']++;
                        $results['notifications_sent'] += $this->sendExpirationNotification($token);
                    }
                }

            } catch (\Exception $e) {
                $results['errors'][] = [
                    'type' => 'user_token',
                    'token_id' => $token->id,
                    'user_id' => $token->user_id,
                    'error' => $e->getMessage()
                ];

                Log::error('Failed to process user token', [
                    'token_id' => $token->id,
                    'user_id' => $token->user_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Process platform Google accounts
     */
    private function processPlatformAccounts(): array
    {
        $results = [
            'platform_accounts_checked' => 0,
            'platform_accounts_refreshed' => 0,
            'errors' => []
        ];

        $platformAccounts = PlatformGoogleAccount::active()
            ->where(function ($query) {
                $query->where('expires_at', '<', now()->addMinutes(30))
                      ->orWhere('consecutive_errors', '>=', 3);
            })
            ->get();

        foreach ($platformAccounts as $account) {
            $results['platform_accounts_checked']++;

            try {
                if ($account->consecutive_errors >= 3) {
                    $account->update(['is_active' => false]);
                    Log::warning('Deactivated platform account due to errors', [
                        'account_id' => $account->id,
                        'academy_id' => $account->academy_id
                    ]);
                } else if ($this->refreshPlatformAccountToken($account)) {
                    $results['platform_accounts_refreshed']++;
                }

            } catch (\Exception $e) {
                $results['errors'][] = [
                    'type' => 'platform_account',
                    'account_id' => $account->id,
                    'error' => $e->getMessage()
                ];

                Log::error('Failed to process platform account', [
                    'account_id' => $account->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Refresh user token
     */
    private function refreshUserToken(GoogleToken $token): bool
    {
        try {
            if (!$token->refresh_token) {
                throw new \Exception('No refresh token available');
            }

            // Create Google client with refresh token
            $client = new \Google_Client();
            $client->setClientId(config('services.google.client_id'));
            $client->setClientSecret(config('services.google.client_secret'));
            $client->setAccessToken([
                'refresh_token' => decrypt($token->refresh_token)
            ]);

            $newToken = $client->fetchAccessTokenWithRefreshToken();

            if (isset($newToken['error'])) {
                throw new \Exception('Token refresh failed: ' . $newToken['error_description']);
            }

            // Update token
            $token->recordRefresh($newToken);

            Log::info('User token refreshed successfully', [
                'token_id' => $token->id,
                'user_id' => $token->user_id,
                'refresh_count' => $token->refresh_count
            ]);

            return true;

        } catch (\Exception $e) {
            $token->recordError('Token refresh failed: ' . $e->getMessage());

            Log::warning('User token refresh failed', [
                'token_id' => $token->id,
                'user_id' => $token->user_id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Refresh platform account token
     */
    private function refreshPlatformAccountToken(PlatformGoogleAccount $account): bool
    {
        try {
            if (!$account->refresh_token) {
                throw new \Exception('No refresh token available');
            }

            // Create Google client with refresh token
            $client = new \Google_Client();
            $client->setClientId(config('services.google.client_id'));
            $client->setClientSecret(config('services.google.client_secret'));
            $client->setAccessToken([
                'refresh_token' => decrypt($account->refresh_token)
            ]);

            $newToken = $client->fetchAccessTokenWithRefreshToken();

            if (isset($newToken['error'])) {
                throw new \Exception('Token refresh failed: ' . $newToken['error_description']);
            }

            // Update account
            $account->updateToken($newToken);

            Log::info('Platform account token refreshed', [
                'account_id' => $account->id,
                'academy_id' => $account->academy_id
            ]);

            return true;

        } catch (\Exception $e) {
            $account->recordError('Token refresh failed: ' . $e->getMessage());

            Log::warning('Platform account token refresh failed', [
                'account_id' => $account->id,
                'academy_id' => $account->academy_id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Mark token as expired
     */
    private function markTokenAsExpired(GoogleToken $token): void
    {
        $token->markAsExpired();

        // Update user record
        $token->user->update([
            'google_connected_at' => null,
            'google_disconnected_at' => now(),
            'google_calendar_enabled' => false,
        ]);

        Log::info('Marked user token as expired', [
            'token_id' => $token->id,
            'user_id' => $token->user_id
        ]);
    }

    /**
     * Send expiration notification
     */
    private function sendExpirationNotification(GoogleToken $token): int
    {
        try {
            $user = $token->user;
            $sentCount = 0;

            // Send to user
            if ($user->notify_on_google_disconnect) {
                Notification::send($user, new GoogleTokenExpiredNotification($token));
                $sentCount++;
            }

            // Send to admin if user wants admin notification
            if ($user->notify_admin_on_disconnect) {
                $admins = $user->academy->users()
                    ->where('role', 'admin')
                    ->get();

                if ($admins->count() > 0) {
                    Notification::send($admins, new GoogleTokenExpiredNotification($token, true));
                    $sentCount += $admins->count();
                }
            }

            Log::info('Sent token expiration notifications', [
                'token_id' => $token->id,
                'user_id' => $token->user_id,
                'notifications_sent' => $sentCount
            ]);

            return $sentCount;

        } catch (\Exception $e) {
            Log::error('Failed to send expiration notification', [
                'token_id' => $token->id,
                'error' => $e->getMessage()
            ]);

            return 0;
        }
    }

    /**
     * Clean up old expired tokens
     */
    private function cleanupOldTokens(): array
    {
        $results = ['cleaned' => 0];

        // Delete tokens that have been expired for more than 30 days
        $cutoffDate = now()->subDays(30);

        $deletedCount = GoogleToken::where('token_status', GoogleToken::STATUS_EXPIRED)
            ->where('updated_at', '<', $cutoffDate)
            ->delete();

        $results['cleaned'] = $deletedCount;

        if ($deletedCount > 0) {
            Log::info('Cleaned up old expired tokens', [
                'deleted_count' => $deletedCount,
                'cutoff_date' => $cutoffDate->toDateString()
            ]);
        }

        return $results;
    }

    /**
     * Reset daily usage for platform accounts
     */
    private function resetPlatformAccountUsage(): void
    {
        $accountsToReset = PlatformGoogleAccount::where('usage_reset_date', '<', today())
            ->get();

        foreach ($accountsToReset as $account) {
            $account->resetDailyUsage();
        }

        if ($accountsToReset->count() > 0) {
            Log::info('Reset daily usage for platform accounts', [
                'accounts_reset' => $accountsToReset->count()
            ]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CleanupExpiredTokens job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}