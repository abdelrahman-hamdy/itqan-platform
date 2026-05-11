<?php

namespace App\Health\Checks;

use Illuminate\Support\Facades\Cache;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

/**
 * Parses the Let's Encrypt cert on disk and surfaces approaching expiry.
 * Default path matches the prod deployment at itqanway.com — override via
 * ->certPath('/etc/letsencrypt/live/other-domain/cert.pem') if reused.
 *
 * Falls back to OK with a note when the cert file is unreadable (e.g. dev
 * machine without Let's Encrypt) so this check is safe to register globally.
 */
class SslCertExpiryCheck extends Check
{
    protected string $certPath = '/etc/letsencrypt/live/itqanway.com/cert.pem';

    protected int $warnDays = 14;

    protected int $failDays = 3;

    public function getName(): string
    {
        return 'SSL Cert Expiry';
    }

    public function certPath(string $path): self
    {
        $this->certPath = $path;

        return $this;
    }

    public function warnWhenDaysRemainingBelow(int $days): self
    {
        $this->warnDays = $days;

        return $this;
    }

    public function failWhenDaysRemainingBelow(int $days): self
    {
        $this->failDays = $days;

        return $this;
    }

    public function run(): Result
    {
        if (! is_readable($this->certPath)) {
            return Result::make()
                ->shortSummary('cert file not readable — skipping')
                ->meta(['cert_path' => $this->certPath])
                ->ok();
        }

        // Cache the parsed expiry by mtime — certs rotate every ~60 days, so
        // re-reading the file + running openssl on every 5-min Spatie tick is
        // pure waste. The mtime suffix auto-busts on renewal.
        $mtime = @filemtime($this->certPath) ?: 0;
        $cacheKey = "health:ssl_expiry:{$this->certPath}:{$mtime}";

        $parsedExpiry = Cache::remember($cacheKey, 3600, function (): ?array {
            $pem = @file_get_contents($this->certPath);
            if ($pem === false) {
                return null;
            }
            $parsed = @openssl_x509_parse($pem);
            if (! is_array($parsed) || empty($parsed['validTo_time_t'])) {
                return null;
            }

            return [
                'expires_at' => (int) $parsed['validTo_time_t'],
                'cn' => $parsed['subject']['CN'] ?? null,
            ];
        });

        if ($parsedExpiry === null) {
            return Result::make()
                ->shortSummary('cert parse failed')
                ->meta(['cert_path' => $this->certPath])
                ->warning('Unable to read or parse cert');
        }

        $expiresAt = $parsedExpiry['expires_at'];
        $daysRemaining = (int) floor(($expiresAt - time()) / 86400);

        $meta = [
            'cert_path' => $this->certPath,
            'expires_at_utc' => gmdate('Y-m-d H:i:s', $expiresAt),
            'days_remaining' => $daysRemaining,
            'cn' => $parsedExpiry['cn'],
        ];

        $result = Result::make()
            ->shortSummary("{$daysRemaining}d remaining")
            ->meta($meta);

        if ($daysRemaining <= $this->failDays) {
            return $result->failed("Cert expires in {$daysRemaining} day(s)");
        }

        if ($daysRemaining <= $this->warnDays) {
            return $result->warning("Cert expires in {$daysRemaining} day(s)");
        }

        return $result->ok();
    }
}
