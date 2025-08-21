<?php

if (!function_exists('current_academy')) {
    /**
     * Get the current academy from the resolved tenant
     */
    function current_academy(): ?\App\Models\Academy
    {
        return app()->bound('current_academy') ? app('current_academy') : null;
    }
}

if (!function_exists('academy_url')) {
    /**
     * Generate URL for an academy
     */
    function academy_url(\App\Models\Academy $academy, string $path = '/'): string
    {
        $protocol = app()->environment('local') ? 'http' : 'https';
        return $protocol . '://' . $academy->full_domain . $path;
    }
}

if (!function_exists('humanize_time_remaining_arabic')) {
    /**
     * Humanize the time remaining until a session in Arabic
     */
    function humanize_time_remaining_arabic(\Carbon\Carbon $sessionTime): array
    {
        $now = now();
        $diff = $now->diffInMinutes($sessionTime, false);
        
        // If session is in the past
        if ($diff < 0) {
            return [
                'text' => 'انتهت الجلسة',
                'can_join' => false,
                'is_past' => true
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
            'minutes_remaining' => (int) $diff
        ];
    }
}

if (!function_exists('can_test_meetings')) {
    /**
     * Check if the current user can bypass meeting time restrictions for testing
     */
    function can_test_meetings(): bool
    {
        if (!\Illuminate\Support\Facades\Auth::check()) {
            return false;
        }
        
        $user = \Illuminate\Support\Facades\Auth::user();
        
        // Allow super admins and admins to test meetings
        return $user->hasRole(['super_admin', 'admin']) || 
               request()->has('test_mode') && 
               ($user->hasRole(['quran_teacher', 'academic_teacher', 'supervisor', 'student']) || app()->environment('local'));
    }
} 