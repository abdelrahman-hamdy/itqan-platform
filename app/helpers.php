<?php

use App\Models\Academy;
use App\Services\AcademyContextService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Enums\UserType;
use App\Helpers\FaviconHelper;

if (! function_exists('current_academy')) {
    /**
     * Get the current academy from the resolved tenant
     */
    function current_academy(): ?Academy
    {
        return AcademyContextService::getCurrentAcademy();
    }
}

if (! function_exists('academy_url')) {
    /**
     * Generate URL for an academy
     */
    function academy_url(Academy $academy, string $path = '/'): string
    {
        $protocol = app()->environment('local') ? 'http' : 'https';

        return $protocol.'://'.$academy->full_domain.$path;
    }
}

if (! function_exists('humanize_time_remaining_arabic')) {
    /**
     * Humanize the time remaining until a session in Arabic
     */
    function humanize_time_remaining_arabic(Carbon $sessionTime): array
    {
        $now = now();
        $diff = $now->diffInMinutes($sessionTime, false);

        // If session is in the past
        if ($diff < 0) {
            return [
                'text' => 'انتهت الجلسة',
                'can_join' => false,
                'is_past' => true,
            ];
        }

        // Format the remaining time in a human-readable Arabic format
        if ($diff < 60) {
            $minutes = (int) $diff;
            if ($minutes == 0) {
                $text = 'متاح الآن';
            } elseif ($minutes == 1) {
                $text = 'متاح خلال دقيقة واحدة';
            } elseif ($minutes == 2) {
                $text = 'متاح خلال دقيقتين';
            } elseif ($minutes <= 10) {
                $text = "متاح خلال {$minutes} دقائق";
            } else {
                $text = "متاح خلال {$minutes} دقيقة";
            }
        } else {
            $hours = (int) ($diff / 60);
            $remainingMinutes = (int) ($diff % 60);

            if ($hours == 1) {
                if ($remainingMinutes == 0) {
                    $text = 'متاح خلال ساعة واحدة';
                } else {
                    $text = "متاح خلال ساعة و {$remainingMinutes} دقيقة";
                }
            } elseif ($hours == 2) {
                if ($remainingMinutes == 0) {
                    $text = 'متاح خلال ساعتين';
                } else {
                    $text = "متاح خلال ساعتين و {$remainingMinutes} دقيقة";
                }
            } elseif ($hours <= 10) {
                if ($remainingMinutes == 0) {
                    $text = "متاح خلال {$hours} ساعات";
                } else {
                    $text = "متاح خلال {$hours} ساعات و {$remainingMinutes} دقيقة";
                }
            } elseif ($hours <= 24) {
                if ($remainingMinutes == 0) {
                    $text = "متاح خلال {$hours} ساعة";
                } else {
                    $text = "متاح خلال {$hours} ساعة و {$remainingMinutes} دقيقة";
                }
            } else {
                $days = (int) ($hours / 24);
                $remainingHours = (int) ($hours % 24);

                if ($days == 1) {
                    if ($remainingHours == 0) {
                        $text = 'متاح خلال يوم واحد';
                    } else {
                        $text = "متاح خلال يوم و {$remainingHours} ساعة";
                    }
                } elseif ($days == 2) {
                    if ($remainingHours == 0) {
                        $text = 'متاح خلال يومين';
                    } else {
                        $text = "متاح خلال يومين و {$remainingHours} ساعة";
                    }
                } else {
                    if ($remainingHours == 0) {
                        $text = "متاح خلال {$days} أيام";
                    } else {
                        $text = "متاح خلال {$days} أيام و {$remainingHours} ساعة";
                    }
                }
            }
        }

        // Determine if user can join (within 30 minutes)
        $canJoin = $diff <= 30;

        return [
            'text' => $text,
            'can_join' => $canJoin,
            'is_past' => false,
            'minutes_remaining' => (int) $diff,
        ];
    }
}

if (! function_exists('can_test_meetings')) {
    /**
     * Check if the current user can bypass meeting time restrictions for testing
     */
    function can_test_meetings(): bool
    {
        if (! Auth::check()) {
            return false;
        }

        $user = Auth::user();

        // Allow super admins and admins to test meetings
        return $user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value]) ||
               request()->has('test_mode') &&
               ($user->hasRole([UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value, UserType::SUPERVISOR->value, UserType::STUDENT->value]) || app()->environment('local'));
    }
}

if (! function_exists('getFavicon')) {
    /**
     * Get the favicon URL for the current academy
     *
     * @param Academy|null $academy Academy instance (auto-resolves if null)
     * @return string Favicon URL
     */
    function getFavicon(?Academy $academy = null): string
    {
        return FaviconHelper::get($academy);
    }
}

if (! function_exists('getFaviconLinkTag')) {
    /**
     * Get favicon HTML link tag
     *
     * @param Academy|null $academy Academy instance (auto-resolves if null)
     * @return string HTML link tag
     */
    function getFaviconLinkTag(?Academy $academy = null): string
    {
        return FaviconHelper::linkTag($academy);
    }
}

if (! function_exists('hasCustomFavicon')) {
    /**
     * Check if academy has a custom favicon
     *
     * @param Academy|null $academy Academy instance (auto-resolves if null)
     * @return bool True if academy has custom favicon
     */
    function hasCustomFavicon(?Academy $academy = null): bool
    {
        return FaviconHelper::hasCustom($academy);
    }
}

// Include TimeHelper functions
require_once __DIR__.'/Helpers/TimeHelper.php';

// Include CurrencyHelper functions
require_once __DIR__.'/Helpers/CurrencyHelper.php';
