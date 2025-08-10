<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use App\Models\User;

class AcademyGoogleSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'google_project_id',
        'google_client_id',
        'google_client_secret',
        'google_service_account_key',
        'oauth_redirect_uri',
        'oauth_scopes',
        'fallback_account_email',
        'fallback_account_credentials',
        'fallback_account_enabled',
        'fallback_daily_limit',
        'auto_create_meetings',
        'meeting_prep_minutes',
        'auto_record_sessions',
        'default_session_duration',
        'notify_on_teacher_disconnect',
        'send_meeting_reminders',
        'reminder_times',
        'is_configured',
        'last_tested_at',
        'last_test_result',
        'configured_at',
        'configured_by',
    ];

    protected $casts = [
        'oauth_scopes' => 'array',
        'reminder_times' => 'array',
        'fallback_account_enabled' => 'boolean',
        'auto_create_meetings' => 'boolean',
        'auto_record_sessions' => 'boolean',
        'notify_on_teacher_disconnect' => 'boolean',
        'send_meeting_reminders' => 'boolean',
        'is_configured' => 'boolean',
        'last_tested_at' => 'datetime',
        'configured_at' => 'datetime',
        'fallback_daily_limit' => 'integer',
        'meeting_prep_minutes' => 'integer',
        'default_session_duration' => 'integer',
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function configuredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'configured_by');
    }

    // Accessors
    public function getDecryptedClientSecretAttribute(): ?string
    {
        return $this->google_client_secret ? Crypt::decryptString($this->google_client_secret) : null;
    }

    public function getDecryptedServiceAccountKeyAttribute(): ?array
    {
        if (!$this->google_service_account_key) {
            return null;
        }
        
        return json_decode(Crypt::decryptString($this->google_service_account_key), true);
    }

    public function getDecryptedFallbackCredentialsAttribute(): ?array
    {
        if (!$this->fallback_account_credentials) {
            return null;
        }
        
        return json_decode(Crypt::decryptString($this->fallback_account_credentials), true);
    }

    public function getConfigurationStatusAttribute(): string
    {
        if (!$this->is_configured) {
            return 'غير مُكون';
        }
        
        if (!$this->last_tested_at) {
            return 'مُكون - لم يتم الاختبار';
        }
        
        if ($this->last_tested_at->lt(now()->subDays(7))) {
            return 'مُكون - يحتاج اختبار';
        }
        
        if ($this->last_test_result === 'success') {
            return 'مُكون ويعمل بشكل صحيح';
        }
        
        return 'مُكون - يوجد خطأ';
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->configuration_status) {
            'غير مُكون' => 'red',
            'مُكون - لم يتم الاختبار' => 'yellow',
            'مُكون - يحتاج اختبار' => 'orange',
            'مُكون ويعمل بشكل صحيح' => 'green',
            'مُكون - يوجد خطأ' => 'red',
            default => 'gray'
        };
    }

    // Mutators
    public function setGoogleClientSecretAttribute(?string $value): void
    {
        $this->attributes['google_client_secret'] = $value ? Crypt::encryptString($value) : null;
    }

    public function setGoogleServiceAccountKeyAttribute(?string $value): void
    {
        $this->attributes['google_service_account_key'] = $value ? Crypt::encryptString($value) : null;
    }

    public function setFallbackAccountCredentialsAttribute(?string $value): void
    {
        $this->attributes['fallback_account_credentials'] = $value ? Crypt::encryptString($value) : null;
    }

    // Methods
    public function testConnection(): array
    {
        try {
            // Test basic Google OAuth configuration (no calendar integration needed)
            $client = new \Google_Client();
            $client->setClientId($this->google_client_id);
            $client->setClientSecret($this->decrypted_client_secret);
            
            // Test OAuth configuration with basic profile scopes only
            $client->addScope([
                'https://www.googleapis.com/auth/userinfo.email',
                'https://www.googleapis.com/auth/userinfo.profile',
            ]);
            
            // Just test that we can create a valid auth URL
            $authUrl = $client->createAuthUrl();
            
            if (!empty($authUrl) && filter_var($authUrl, FILTER_VALIDATE_URL)) {
                $result = [
                    'success' => true,
                    'message' => 'إعدادات Google OAuth جاهزة ✅ | اللقاءات ستتم عبر Jitsi Meet (مجاني)',
                    'scopes_tested' => ['userinfo.email', 'userinfo.profile'],
                    'meeting_platform' => 'Jitsi Meet - لا حاجة لتفعيل Google Calendar',
                    'auth_url_valid' => true,
                    'tested_at' => now(),
                ];
            } else {
                throw new \Exception('فشل في إنشاء رابط التفويض');
            }
            
        } catch (\Exception $e) {
            $result = [
                'success' => false,
                'message' => 'فشل اختبار إعدادات Google: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'tested_at' => now(),
            ];
        }
        
        // Update settings with test result
        $this->update([
            'last_tested_at' => now(),
            'last_test_result' => $result['success'] ? 'success' : 'failed',
        ]);
        
        return $result;
    }

    public function markAsConfigured(User $user): self
    {
        $this->update([
            'is_configured' => true,
            'configured_at' => now(),
            'configured_by' => $user->id,
        ]);
        
        return $this;
    }

    public function getDefaultOAuthScopes(): array
    {
        // Only basic profile scopes - no sensitive calendar scopes
        // Calendar integration disabled for quick deployment
        return [
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/userinfo.profile',
        ];
    }

    public function getOAuthRedirectUri(): string
    {
        if ($this->oauth_redirect_uri) {
            return $this->oauth_redirect_uri;
        }
        
        // For local development - Google OAuth only supports localhost, NOT .test domains
        if (config('app.env') === 'local') {
            // Always use localhost for Google OAuth in local development
            // .test domains are not supported by Google's OAuth 2.0 policies
            return 'http://localhost:8000/google/callback';
        }
        
        // For production, generate proper subdomain URL
        $subdomain = $this->academy->subdomain ?? 'itqan-academy';
        $domain = config('app.domain', 'yourdomain.com');
        
        return "https://{$subdomain}.{$domain}/google/callback";
    }

    /**
     * Check if we're using Laravel Valet
     */
    private function isUsingValet(): bool
    {
        $homeDir = $_SERVER['HOME'] ?? ('/Users/' . posix_getpwuid(posix_getuid())['name']);
        
        // Multiple indicators for Valet usage
        $indicators = [
            // Check if current request is from .test domain (web context)
            isset($_SERVER['HTTP_HOST']) && str_contains($_SERVER['HTTP_HOST'], '.test'),
            // Check if APP_URL is set to .test domain
            str_contains(config('app.url', ''), '.test'),
            // Check if Valet executable exists using HOME directory
            file_exists($homeDir . '/.composer/vendor/bin/valet'),
            // Check common Valet directory
            is_dir($homeDir . '/.config/valet'),
            // Check if we can execute valet command
            $this->canExecuteValet(),
        ];
        
        // If any indicator is true, we're likely using Valet
        // But also require at least the executable to exist for safety
        $hasValet = file_exists($homeDir . '/.composer/vendor/bin/valet') || $this->canExecuteValet();
        $hasIndicators = in_array(true, array_slice($indicators, 0, 2), true); // Only check URL-based indicators
        
        return $hasValet && ($hasIndicators || count(array_filter($indicators)) >= 2);
    }
    
    /**
     * Check if valet command is available
     */
    private function canExecuteValet(): bool
    {
        try {
            $output = shell_exec('which valet 2>/dev/null');
            return !empty(trim($output));
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getDefaultReminderTimes(): array
    {
        return $this->reminder_times ?? [60, 15]; // 1 hour and 15 minutes before
    }

    /**
     * Get or create settings for academy
     */
    public static function forAcademy(Academy $academy): self
    {
        return self::firstOrCreate(
            ['academy_id' => $academy->id],
            [
                'oauth_scopes' => (new self())->getDefaultOAuthScopes(),
                'reminder_times' => (new self())->getDefaultReminderTimes(),
                'meeting_prep_minutes' => 60,
                'default_session_duration' => 60,
                'fallback_daily_limit' => 100,
                'auto_create_meetings' => true,
                'send_meeting_reminders' => true,
                'notify_on_teacher_disconnect' => true,
            ]
        );
    }
}