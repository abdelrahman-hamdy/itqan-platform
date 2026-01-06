<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Request Logging Middleware
 *
 * Logs API requests and responses for auditing and debugging.
 * Automatically redacts sensitive fields to protect user privacy.
 *
 * Features:
 * - Logs request method, path, user, academy
 * - Logs response status and timing
 * - Redacts sensitive fields (passwords, tokens, etc.)
 * - Uses dedicated 'api' log channel
 * - Only logs in non-production or when explicitly enabled
 */
class LogApiRequests
{
    /**
     * Fields to redact from request data.
     */
    protected array $sensitiveFields = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        'api_key',
        'api_secret',
        'secret',
        'authorization',
        'credit_card',
        'card_number',
        'cvv',
        'ssn',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only log if enabled
        if (! $this->shouldLog()) {
            return $next($request);
        }

        $requestId = $request->header('X-Request-ID', (string) Str::uuid());
        $startTime = microtime(true);

        // Log the incoming request
        $this->logRequest($request, $requestId);

        $response = $next($request);

        // Log the response
        $this->logResponse($request, $response, $requestId, $startTime);

        return $response;
    }

    /**
     * Determine if logging should occur.
     */
    protected function shouldLog(): bool
    {
        // Log in local/staging, or if explicitly enabled in production
        return app()->environment(['local', 'staging', 'testing'])
            || config('logging.api_logging_enabled', false);
    }

    /**
     * Log the incoming request.
     */
    protected function logRequest(Request $request, string $requestId): void
    {
        $user = $request->user();
        $academy = $request->attributes->get('academy');

        $logData = [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
            'full_url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => Str::limit($request->userAgent(), 200),
            'user_id' => $user?->id,
            'user_type' => $user?->user_type,
            'academy_id' => $academy?->id ?? $user?->academy_id,
            'academy_subdomain' => $request->header('X-Academy-Subdomain'),
            'content_type' => $request->header('Content-Type'),
            'query_params' => $this->redactSensitiveData($request->query()),
            'body_params' => $this->redactSensitiveData($request->except(['_token', '_method'])),
        ];

        Log::channel('api')->info('API Request', $logData);
    }

    /**
     * Log the response.
     */
    protected function logResponse(Request $request, Response $response, string $requestId, float $startTime): void
    {
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $logData = [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'response_size' => strlen($response->getContent()),
        ];

        // Log level based on status code
        $level = match (true) {
            $response->getStatusCode() >= 500 => 'error',
            $response->getStatusCode() >= 400 => 'warning',
            default => 'info',
        };

        Log::channel('api')->{$level}('API Response', $logData);

        // Log slow requests
        if ($duration > 1000) { // Over 1 second
            Log::channel('api')->warning('Slow API Request', [
                'request_id' => $requestId,
                'path' => $request->path(),
                'duration_ms' => $duration,
            ]);
        }
    }

    /**
     * Redact sensitive fields from data.
     */
    protected function redactSensitiveData(array $data): array
    {
        $redacted = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $redacted[$key] = $this->redactSensitiveData($value);
            } elseif ($this->isSensitiveField($key)) {
                $redacted[$key] = '[REDACTED]';
            } else {
                $redacted[$key] = $value;
            }
        }

        return $redacted;
    }

    /**
     * Check if a field is sensitive.
     */
    protected function isSensitiveField(string $field): bool
    {
        $field = strtolower($field);

        foreach ($this->sensitiveFields as $sensitive) {
            if (str_contains($field, $sensitive)) {
                return true;
            }
        }

        return false;
    }
}
