<?php

namespace App\Http\Controllers;

use App\Models\GoogleToken;
use App\Models\AcademyGoogleSettings;
use App\Models\User;
use App\Models\Academy;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Carbon\Carbon;

class GoogleAuthController extends Controller
{
    private GoogleCalendarService $googleService;

    public function __construct(GoogleCalendarService $googleService)
    {
        $this->googleService = $googleService;
    }

    /**
     * Redirect to Google OAuth
     */
    public function redirect(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        
        // Check if user has the right role
        if (!$user->hasRole(['quran_teacher', 'academic_teacher'])) {
            return redirect()->back()->with('error', 'هذه الخدمة متاحة للمعلمين فقط');
        }
        
        $academy = $user->academy;

        if (!$academy) {
            return redirect()->back()->with('error', 'لا يمكن العثور على الأكاديمية المرتبطة بحسابك');
        }

        // Get academy Google settings
        $settings = AcademyGoogleSettings::forAcademy($academy);

        if (!$settings->is_configured) {
            return redirect()->back()->with('error', 'لم يتم تكوين إعدادات Google Meet في هذه الأكاديمية. يرجى التواصل مع الإدارة.');
        }

        try {
            $client = new \Google_Client();
            $client->setClientId($settings->google_client_id);
            $client->setClientSecret($settings->decrypted_client_secret);
            
            // Use the callback redirect URI that matches current request context
            $redirectUri = $this->getCallbackRedirectUri($academy);
            $client->setRedirectUri($redirectUri);
            
            $client->setScopes($settings->oauth_scopes ?? $settings->getDefaultOAuthScopes());
            $client->setAccessType('offline');
            $client->setPrompt('consent'); // Forces refresh token

            // Log the configuration for debugging
            Log::info('Google OAuth redirect initiated', [
                'user_id' => $user->id,
                'academy_id' => $academy->id,
                'academy_subdomain' => $academy->subdomain,
                'client_id' => $settings->google_client_id,
                'redirect_uri' => $redirectUri,
                'scopes' => $settings->oauth_scopes ?? $settings->getDefaultOAuthScopes(),
            ]);

            // Add state parameter to prevent CSRF
            $state = encrypt([
                'user_id' => $user->id,
                'academy_id' => $academy->id,
                'timestamp' => now()->timestamp,
            ]);
            $client->setState($state);

            $authUrl = $client->createAuthUrl();

            return redirect($authUrl);

        } catch (\Exception $e) {
            Log::error('Google OAuth redirect failed', [
                'user_id' => $user->id,
                'academy_id' => $academy->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'فشل في الاتصال بـ Google. يرجى المحاولة مرة أخرى أو التواصل مع الدعم الفني. خطأ: ' . $e->getMessage());
        }
    }

    /**
     * Handle Google OAuth callback
     */
    public function callback(Request $request)
    {
        // Debug logging - log all callback attempts
        Log::info('Google OAuth callback received', [
            'url' => $request->fullUrl(),
            'query_params' => $request->query(),
            'has_error' => $request->has('error'),
            'has_code' => $request->has('code'),
            'has_state' => $request->has('state'),
            'user_id' => Auth::id(),
        ]);

        if ($request->has('error')) {
            Log::warning('Google OAuth callback error', [
                'error' => $request->get('error'),
                'error_description' => $request->get('error_description'),
            ]);

            return $this->getRedirectUrl('error', 'تم إلغاء عملية ربط حساب Google أو حدث خطأ: ' . $request->get('error_description'));
        }

        if (!$request->has('code') || !$request->has('state')) {
            return $this->getRedirectUrl('error', 'طلب غير صالح من Google. يرجى المحاولة مرة أخرى.');
        }

        try {
            // Verify state parameter
            $state = decrypt($request->get('state'));
            
            // Get the user from Auth or from the state parameter
            /** @var User $user */
            $user = Auth::user();
            
            // If user is not currently authenticated, try to find and re-authenticate them
            if (!$user) {
                $user = User::find($state['user_id']);
                if (!$user) {
                    return $this->getRedirectUrl('error', 'لم يتم العثور على المستخدم. يرجى تسجيل الدخول والمحاولة مرة أخرى.');
                }
                
                // Re-authenticate the user
                Auth::login($user);
                
                Log::info('User re-authenticated during OAuth callback', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                ]);
            }
            
            // Verify user matches the state
            if ($user->id !== $state['user_id']) {
                return $this->getRedirectUrl('error', 'خطأ في التحقق من المستخدم. يرجى المحاولة مرة أخرى.');
            }

            $academy = $user->academy;
            if (!$academy || $academy->id !== $state['academy_id']) {
                return $this->getRedirectUrl('error', 'خطأ في الأكاديمية. يرجى المحاولة مرة أخرى.');
            }

            // Check if state is not too old (prevent replay attacks)
            if (now()->timestamp - $state['timestamp'] > 3600) { // 1 hour
                return $this->getRedirectUrl('error', 'انتهت صلاحية الطلب. يرجى المحاولة مرة أخرى.');
            }

            $settings = AcademyGoogleSettings::forAcademy($academy);

            // Get the exact same redirect URI that was used for the initial OAuth request
            $redirectUri = $this->getCallbackRedirectUri($academy);

            Log::info('OAuth token exchange attempt', [
                'user_id' => $user->id,
                'academy_id' => $academy->id,
                'client_id' => $settings->google_client_id,
                'redirect_uri' => $redirectUri,
                'request_host' => $request->getHost(),
                'has_client_secret' => !empty($settings->decrypted_client_secret),
            ]);

            $client = new \Google_Client();
            $client->setClientId($settings->google_client_id);
            $client->setClientSecret($settings->decrypted_client_secret);
            $client->setRedirectUri($redirectUri);

            // Exchange authorization code for tokens
            Log::info('Starting token exchange with Google', [
                'auth_code_length' => strlen($request->get('code')),
                'client_id' => $settings->google_client_id,
                'redirect_uri' => $redirectUri,
                'has_client_secret' => !empty($settings->decrypted_client_secret),
                'client_secret_length' => $settings->decrypted_client_secret ? strlen($settings->decrypted_client_secret) : 0,
            ]);

            try {
                $token = $client->fetchAccessTokenWithAuthCode($request->get('code'));
                
                Log::info('Token exchange response received', [
                    'has_access_token' => isset($token['access_token']),
                    'has_refresh_token' => isset($token['refresh_token']),
                    'has_error' => isset($token['error']),
                    'token_keys' => array_keys($token),
                    'expires_in' => $token['expires_in'] ?? 'not_set',
                    'token_type' => $token['token_type'] ?? 'not_set',
                    'scope' => $token['scope'] ?? 'not_set',
                ]);

                if (isset($token['error'])) {
                    Log::error('Google token exchange error details', [
                        'error' => $token['error'],
                        'error_description' => $token['error_description'] ?? 'No description',
                        'error_uri' => $token['error_uri'] ?? 'No URI',
                    ]);
                    throw new \Exception('Token exchange failed: ' . ($token['error_description'] ?? $token['error']));
                }
                
                if (!isset($token['access_token'])) {
                    Log::error('No access token in response', ['token_response' => $token]);
                    throw new \Exception('No access token received from Google');
                }
                
            } catch (\Exception $e) {
                Log::error('Exception during token exchange', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }

            // Get user info from Google
            $client->setAccessToken($token);
            $googleUser = null;
            
            try {
                $oauth2 = new \Google\Service\Oauth2($client);
                $googleUser = $oauth2->userinfo->get();
            } catch (\Exception $e) {
                // If we can't get user info, we'll still proceed with the token
                $googleUser = null;
            }

            // Save or update tokens
            Log::info('Attempting to save Google token', [
                'user_id' => $user->id,
                'academy_id' => $academy->id,
                'has_refresh_token' => isset($token['refresh_token']),
                'expires_in' => $token['expires_in'] ?? null,
                'token_type' => $token['token_type'] ?? 'Bearer',
                'access_token_length' => isset($token['access_token']) ? strlen($token['access_token']) : 0,
                'refresh_token_length' => isset($token['refresh_token']) ? strlen($token['refresh_token']) : 0,
            ]);

            try {
                // First check if a token already exists
                $existingToken = GoogleToken::where('user_id', $user->id)->first();
                Log::info('Existing token check', [
                    'existing_token_found' => $existingToken ? true : false,
                    'existing_token_id' => $existingToken?->id,
                ]);

                $googleToken = GoogleToken::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'academy_id' => $academy->id,
                        'access_token' => encrypt($token['access_token']),
                        'refresh_token' => isset($token['refresh_token']) ? encrypt($token['refresh_token']) : null,
                        'expires_at' => Carbon::now()->addSeconds($token['expires_in'] ?? 3600),
                        'token_type' => $token['token_type'] ?? 'Bearer',
                        'scope' => $token['scope'] ?? implode(' ', $settings->oauth_scopes),
                        'token_status' => GoogleToken::STATUS_ACTIVE,
                        'refresh_count' => 0,
                        'consecutive_errors' => 0,
                    ]
                );

                Log::info('Google token saved successfully', [
                    'token_id' => $googleToken->id,
                    'user_id' => $user->id,
                    'academy_id' => $academy->id,
                    'expires_at' => $googleToken->expires_at->format('Y-m-d H:i:s'),
                    'token_status' => $googleToken->token_status,
                    'was_recently_created' => $googleToken->wasRecentlyCreated,
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to save Google token', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                    'academy_id' => $academy->id,
                    'trace' => $e->getTraceAsString(),
                ]);
                throw new \Exception('Failed to save Google token: ' . $e->getMessage());
            }

            Log::info('Google token saved successfully', [
                'token_id' => $googleToken->id,
                'user_id' => $user->id,
            ]);

            // Update user's Google info
            try {
                Log::info('Updating user Google connection info', [
                    'user_id' => $user->id,
                    'google_user_id' => $googleUser?->id ?? 'not_available',
                    'google_user_email' => $googleUser?->email ?? 'not_available',
                    'current_google_id' => $user->google_id,
                    'current_google_email' => $user->google_email,
                ]);

                $user->update([
                    'google_id' => $googleUser?->id ?? 'unknown',
                    'google_email' => $googleUser?->email ?? null,
                    'google_connected_at' => now(),
                    'google_calendar_enabled' => true,
                ]);

                // Refresh user from database to verify update
                $user->refresh();

                Log::info('User Google connection updated successfully', [
                    'user_id' => $user->id,
                    'updated_google_id' => $user->google_id,
                    'updated_google_email' => $user->google_email,
                    'updated_google_connected_at' => $user->google_connected_at?->format('Y-m-d H:i:s'),
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to update user Google connection', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                    'trace' => $e->getTraceAsString(),
                ]);
                throw new \Exception('Failed to update user connection info: ' . $e->getMessage());
            }

            Log::info('OAuth callback success, redirecting user', [
                'user_id' => $user->id,
                'academy_id' => $academy->id,
                'academy_subdomain' => $academy->subdomain,
            ]);

            return $this->getRedirectUrl('success', 'تم ربط حساب Google بنجاح! يمكنك الآن إنشاء اجتماعات Google Meet تلقائياً.');

        } catch (\Exception $e) {
            Log::error('Google OAuth callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->getRedirectUrl('error', 'فشل في ربط حساب Google: ' . $e->getMessage());
        }
    }

    /**
     * Disconnect Google account
     */
    public function disconnect(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            // Get user's token
            $googleToken = GoogleToken::where('user_id', $user->id)->first();

            if ($googleToken) {
                // Try to revoke the token from Google
                try {
                    $academy = $user->academy;
                    $settings = AcademyGoogleSettings::forAcademy($academy);

                    $client = new \Google_Client();
                    $client->setClientId($settings->google_client_id);
                    $client->setClientSecret($settings->decrypted_client_secret);
                    $client->setAccessToken([
                        'access_token' => $googleToken->decrypted_access_token,
                        'refresh_token' => $googleToken->decrypted_refresh_token,
                        'expires_in' => $googleToken->expires_at->diffInSeconds(now()),
                    ]);

                    $client->revokeToken();
                } catch (\Exception $e) {
                    // Even if revocation fails, we still want to delete local tokens
                    Log::warning('Failed to revoke Google token', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                // Delete local token
                $googleToken->delete();
            }

            // Clear user's Google connection info
            $user->update([
                'google_id' => null,
                'google_email' => null,
                'google_connected_at' => null,
                'google_disconnected_at' => now(),
                'google_calendar_enabled' => false,
            ]);

            // Notify admin/supervisor about disconnection
            $this->notifyAdminsAboutDisconnection($user);

            Log::info('Google account disconnected', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return redirect()->back()
                ->with('success', 'تم إلغاء ربط حساب Google بنجاح.');

        } catch (\Exception $e) {
            Log::error('Google account disconnection failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'فشل في إلغاء ربط حساب Google: ' . $e->getMessage());
        }
    }

    /**
     * Notify admins about teacher disconnection
     */
    private function notifyAdminsAboutDisconnection($user)
    {
        try {
            $academy = $user->academy;
            if (!$academy) return;

            // Get academy settings for notification preferences
            $settings = AcademyGoogleSettings::forAcademy($academy);
            
            if (!$settings->notify_on_teacher_disconnect) {
                return; // Notifications disabled
            }

            // Find admins and supervisors to notify
            $admins = $academy->users()
                ->whereHas('roles', function ($query) {
                    $query->whereIn('name', ['admin', 'supervisor']);
                })
                ->get();

            foreach ($admins as $admin) {
                // You could send notifications here
                // Notification::send($admin, new TeacherGoogleDisconnectedNotification($user));
                
                // For now, just log it
                Log::info('Should notify admin about teacher Google disconnection', [
                    'admin_id' => $admin->id,
                    'teacher_id' => $user->id,
                    'academy_id' => $academy->id,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to notify admins about Google disconnection', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check connection status (API endpoint)
     */
    public function status(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $googleToken = GoogleToken::where('user_id', $user->id)->first();

        $isConnected = $googleToken && $googleToken->expires_at->isFuture();
        $connectedAt = $user->google_connected_at;

        return response()->json([
            'connected' => $isConnected,
            'connected_at' => $connectedAt?->toISOString(),
            'google_id' => $user->google_id,
            'expires_at' => $googleToken?->expires_at?->toISOString(),
            'needs_refresh' => $googleToken && $googleToken->expires_at->isPast(),
        ]);
    }

    /**
     * Test connection (for troubleshooting)
     */
    public function test(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            // TODO: Implement testUserConnection method in GoogleCalendarService
            // $result = $this->googleService->testUserConnection($user);

            return response()->json([
                'success' => true,
                'message' => 'اتصال Google Calendar يعمل بشكل صحيح',
                'test_result' => ['status' => 'not_implemented'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل اختبار الاتصال: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get the appropriate redirect URL based on user role and context
     */
    private function getRedirectUrl(string $type, string $message)
    {
        /** @var User $user */
        $user = Auth::user();
        
        if (!$user) {
            return redirect('/')->with($type, $message);
        }

        // Determine the appropriate redirect based on user role
        if ($user->hasRole(['quran_teacher', 'academic_teacher'])) {
            // For teachers, redirect to teacher panel Google settings
            $academy = $user->academy;
            if ($academy) {
                // Build the correct subdomain-based URL
                $baseUrl = $this->getTeacherPanelUrl($academy);
                $redirectUrl = $baseUrl . '/teacher-panel/' . $academy->id . '/teacher-google-settings';
                
                Log::info('Redirecting teacher to Google settings page', [
                    'user_id' => $user->id,
                    'academy_id' => $academy->id,
                    'academy_subdomain' => $academy->subdomain,
                    'base_url' => $baseUrl,
                    'redirect_url' => $redirectUrl,
                    'message_type' => $type,
                ]);
                
                return redirect()->to($redirectUrl)->with($type, $message);
            }
        }

        // Default fallback to user dashboard or home
        return redirect('/dashboard')->with($type, $message);
    }

    /**
     * Get the correct teacher panel base URL for the academy
     */
    private function getTeacherPanelUrl(Academy $academy): string
    {
        // For local development with subdomain setup
        if (config('app.env') === 'local') {
            // Check if we're using .test domain (Laravel Valet/Herd)
            if (str_contains(config('app.url'), '.test') || str_contains(request()->getHost(), '.test')) {
                return "http://{$academy->subdomain}.itqan-platform.test";
            }
            // Fallback to localhost for other local setups
            return 'http://localhost:8000';
        }
        
        // For production, use the academy's subdomain
        $domain = config('app.domain', 'yourdomain.com');
        return "https://{$academy->subdomain}.{$domain}";
    }

    /**
     * Get the OAuth callback redirect URI that matches the current request context
     */
    private function getCallbackRedirectUri(Academy $academy): string
    {
        // For local development - Google OAuth does NOT support .test domains
        if (config('app.env') === 'local') {
            // Always use localhost for Google OAuth in local development
            // .test domains are not supported by Google's OAuth 2.0 policies
            return 'http://localhost:8000/google/callback';
        }
        
        // For production, use the academy's subdomain
        $domain = config('app.domain', 'yourdomain.com');
        return "https://{$academy->subdomain}.{$domain}/google/callback";
    }
}