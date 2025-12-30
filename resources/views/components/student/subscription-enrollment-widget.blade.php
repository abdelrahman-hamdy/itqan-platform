@props([
    'type' => 'quran_circle', // 'quran_circle', 'academic_class', 'interactive_course'
    'isEnrolled' => false,
    'subscription' => null, // For enrolled users
    'enrollment' => null, // For interactive courses
    'enrollable' => null, // Circle/Course object for non-enrolled
    'canEnroll' => false,
])

@php
    use App\Services\QuranSubscriptionDetailsService;
    use App\Services\AcademicSubscriptionDetailsService;
    use App\Services\CourseSubscriptionDetailsService;

    // For enrolled users with subscription, get details from service
    $details = null;
    $renewalMessage = null;
    $formattedPrice = null;

    if ($isEnrolled) {
        // Interactive courses use InteractiveCourseEnrollment (NOT a subscription)
        if ($type === 'interactive_course' && $enrollment) {
            // Extract details manually from InteractiveCourseEnrollment
            // InteractiveCourseEnrollment is NOT a BaseSubscription, so we handle it separately
            $course = $enrollable ?? $enrollment->course;

            // Use snapshotted total_possible_attendance (set at enrollment time)
            // This ensures the count doesn't change if course adds/removes sessions later
            // and correctly handles late enrollments (only counts available sessions)
            $totalSessions = $enrollment->total_possible_attendance ?? 0;

            // Fallback: if not set, count current course sessions
            if ($totalSessions === 0 && $course) {
                $totalSessions = $course->sessions()->count();
            }

            $attendanceCount = $enrollment->attendance_count ?? 0;
            $sessionsRemaining = max(0, $totalSessions - $attendanceCount);
            $sessionsPercentage = $totalSessions > 0 ? round(($attendanceCount / $totalSessions) * 100, 1) : 0;

            $details = [
                'type' => 'interactive_course',
                'enrollment_status' => $enrollment->enrollment_status ?? 'enrolled',
                'payment_status' => $enrollment->payment_status ?? 'paid',

                // Sessions (using snapshotted values)
                'total_sessions' => $totalSessions,
                'sessions_used' => $attendanceCount,
                'sessions_remaining' => $sessionsRemaining,
                'sessions_percentage' => $sessionsPercentage,

                // Dates
                'starts_at' => $course->start_date ?? $enrollment->enrollment_date,
                'enrolled_at' => $enrollment->enrollment_date,

                // Progress
                'completion_percentage' => $enrollment->completion_percentage ?? 0,
                'final_grade' => $enrollment->final_grade,
                'attendance_count' => $attendanceCount,
                'total_possible_attendance' => $totalSessions,

                // Certificate
                'certificate_issued' => $enrollment->certificate_issued ?? false,

                // Price
                'payment_amount' => $enrollment->payment_amount ?? 0,
                'discount_applied' => $enrollment->discount_applied ?? 0,
            ];

            $renewalMessage = null; // Interactive courses don't auto-renew
            $formattedPrice = number_format($enrollment->payment_amount ?? 0, 2) . ' ر.س';
        } elseif ($subscription) {
            // Handle actual subscription models (Quran, Academic, Course)
            if ($type === 'quran_circle') {
                // Use QuranSubscriptionDetailsService for Quran subscriptions
                $service = app(QuranSubscriptionDetailsService::class);
                $details = $service->getSubscriptionDetails($subscription);
                $renewalMessage = $service->getRenewalMessage($subscription);
                $formattedPrice = $service->getFormattedPrice($subscription);
            } elseif ($type === 'academic_class') {
                // Use AcademicSubscriptionDetailsService for Academic subscriptions
                $service = app(AcademicSubscriptionDetailsService::class);
                $details = $service->getSubscriptionDetails($subscription);
                $renewalMessage = $service->getRenewalMessage($subscription);
                $formattedPrice = $service->getFormattedPrice($subscription);
            }
        }
    }

    // For non-enrolled users, get enrollment info
    $enrollmentInfo = null;
    if (!$isEnrolled && $enrollable) {
        if ($type === 'quran_circle') {
            $enrollmentInfo = [
                'price' => $enrollable->monthly_fee ?? 0,
                'currency' => $enrollable->currency ?? __('components.student.subscription_enrollment_widget.currency_riyal'),
                'billing_cycle' => __('components.student.subscription_enrollment_widget.monthly'),
                'available_spots' => $enrollable->max_students - ($enrollable->enrolled_students ?? 0),
                'total_spots' => $enrollable->max_students,
            ];
        } elseif ($type === 'interactive_course') {
            $enrollmentInfo = [
                'price' => $enrollable->student_price ?? 0,
                'currency' => __('components.student.subscription_enrollment_widget.currency_sar'),
                'billing_cycle' => __('components.student.subscription_enrollment_widget.one_time_payment'),
                'available_spots' => $enrollable->max_students - ($enrollable->enrolled_students ?? 0),
                'total_spots' => $enrollable->max_students,
                'enrollment_deadline' => $enrollable->enrollment_deadline,
            ];
        } elseif ($type === 'academic_class' && $subscription && $subscription->package) {
            $enrollmentInfo = [
                'price' => $subscription->package->price ?? 0,
                'currency' => $subscription->package->currency ?? __('components.student.subscription_enrollment_widget.currency_riyal'),
                'billing_cycle' => $subscription->package->billing_cycle === 'monthly' ? __('components.student.subscription_enrollment_widget.monthly') : __('components.student.subscription_enrollment_widget.one_time_payment'),
                'total_sessions' => $subscription->package->total_sessions ?? 0,
            ];
        }
    }
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    @if($isEnrolled && $details)
        {{-- ENROLLED USER: Show Subscription Details --}}
        <h3 class="font-bold text-gray-900 mb-4">
            {{ $type === 'interactive_course' ? __('components.student.subscription_enrollment_widget.enrollment_details') : __('components.student.subscription_enrollment_widget.subscription_details') }}
        </h3>

        @if($type !== 'interactive_course')
            {{-- Subscription Status Badge --}}
            <div class="mb-4 flex items-center justify-between">
                <span class="text-sm text-gray-600">{{ __('components.student.subscription_enrollment_widget.subscription_status') }}</span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $details['status_badge_class'] ?? 'bg-green-100 text-green-800' }}">
                    @if(isset($details['status']))
                        @if($type === 'quran_circle')
                            {{ app(QuranSubscriptionDetailsService::class)->getStatusTextArabic($details['status']) }}
                        @else
                            {{ $details['status']?->label() ?? __('components.student.subscription_enrollment_widget.active') }}
                        @endif
                    @else
                        {{ __('components.student.subscription_enrollment_widget.active') }}
                    @endif
                </span>
            </div>

            {{-- Billing Cycle --}}
            <div class="mb-6 p-4 bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg border border-blue-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-blue-700 mb-1">{{ __('components.student.subscription_enrollment_widget.subscription_type') }}</p>
                        <p class="text-lg font-bold text-blue-900">{{ $details['billing_cycle_ar'] ?? __('components.student.subscription_enrollment_widget.monthly') }}</p>
                    </div>
                    @if($formattedPrice)
                    <div class="text-end rtl:text-end ltr:text-start">
                        <p class="text-xs text-blue-700 mb-1">{{ __('components.student.subscription_enrollment_widget.amount') }}</p>
                        <p class="text-lg font-bold text-blue-900">{{ $formattedPrice }}</p>
                    </div>
                    @endif
                </div>
            </div>
        @else
            {{-- Interactive Course Enrollment Status --}}
            <div class="mb-6 p-4 bg-gradient-to-r from-green-50 to-green-100 rounded-lg border border-green-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-green-700 mb-1">{{ __('components.student.subscription_enrollment_widget.enrollment_status') }}</p>
                        <p class="text-lg font-bold text-green-900">{{ __('components.student.subscription_enrollment_widget.enrolled') }}</p>
                    </div>
                    <div class="text-end rtl:text-end ltr:text-start">
                        <p class="text-xs text-green-700 mb-1">{{ __('components.student.subscription_enrollment_widget.completion_percentage') }}</p>
                        <p class="text-lg font-bold text-green-900">{{ $details['completion_percentage'] ?? 0 }}%</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Sessions Progress --}}
        <div class="mb-6">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium text-gray-700">{{ __('components.student.subscription_enrollment_widget.sessions_progress') }}</span>
                <span class="text-sm font-bold text-primary">{{ $details['sessions_percentage'] ?? 0 }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="bg-primary h-3 rounded-full transition-all duration-500"
                     style="width: {{ $details['sessions_percentage'] ?? 0 }}%"></div>
            </div>
            <div class="flex justify-between mt-2 text-xs text-gray-600">
                <span>{{ $details['sessions_used'] ?? 0 }} {{ __('components.student.subscription_enrollment_widget.used') }}</span>
                <span>{{ $details['sessions_remaining'] ?? 0 }} {{ __('components.student.subscription_enrollment_widget.remaining') }}</span>
            </div>
        </div>

        {{-- Statistics Grid --}}
        <div class="grid grid-cols-2 gap-4 mb-6">
            {{-- Sessions Used --}}
            <div class="text-center p-4 bg-green-50 rounded-lg border border-green-200">
                <div class="text-2xl font-bold text-green-600">{{ $details['sessions_used'] ?? 0 }}</div>
                <div class="text-xs text-green-700 font-medium">{{ __('components.student.subscription_enrollment_widget.session_used') }}</div>
            </div>

            {{-- Sessions Remaining --}}
            <div class="text-center p-4 bg-blue-50 rounded-lg border border-blue-200">
                <div class="text-2xl font-bold text-blue-600">{{ $details['sessions_remaining'] ?? 0 }}</div>
                <div class="text-xs text-blue-700 font-medium">{{ __('components.student.subscription_enrollment_widget.session_remaining') }}</div>
            </div>
        </div>

        {{-- Subscription Details --}}
        <div class="space-y-3">
            {{-- Total Sessions --}}
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex items-center">
                    <i class="ri-calendar-check-line text-gray-600 ms-2 rtl:ms-2 ltr:me-2"></i>
                    <span class="text-sm text-gray-700">{{ __('components.student.subscription_enrollment_widget.total_sessions') }}</span>
                </div>
                <span class="text-sm font-bold text-gray-900">{{ $details['total_sessions'] ?? 0 }} {{ __('components.student.subscription_enrollment_widget.session') }}</span>
            </div>

            @if($type !== 'interactive_course')
                {{-- Payment Status --}}
                @if(isset($details['payment_status']))
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="ri-money-dollar-circle-line text-gray-600 ms-2 rtl:ms-2 ltr:me-2"></i>
                        <span class="text-sm text-gray-700">{{ __('components.student.subscription_enrollment_widget.payment_status') }}</span>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $details['payment_status_badge_class'] ?? 'bg-green-100 text-green-800' }}">
                        @if($type === 'quran_circle')
                            {{ app(QuranSubscriptionDetailsService::class)->getPaymentStatusTextArabic($details['payment_status']) }}
                        @else
                            {{ $details['payment_status']?->label() ?? __('components.student.subscription_enrollment_widget.paid') }}
                        @endif
                    </span>
                </div>
                @endif

                {{-- Start Date --}}
                @if(isset($details['starts_at']) && $details['starts_at'])
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="ri-calendar-2-line text-gray-600 ms-2 rtl:ms-2 ltr:me-2"></i>
                        <span class="text-sm text-gray-700">{{ __('components.student.subscription_enrollment_widget.start_date') }}</span>
                    </div>
                    <span class="text-sm font-bold text-gray-900">{{ $details['starts_at']->format('Y/m/d') }}</span>
                </div>
                @endif

                {{-- Next Payment Date --}}
                @if(isset($details['next_payment_at']) && $details['next_payment_at'] && $details['status'] === 'active')
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="ri-time-line text-gray-600 ms-2 rtl:ms-2 ltr:me-2"></i>
                        <span class="text-sm text-gray-700">{{ __('components.student.subscription_enrollment_widget.next_renewal') }}</span>
                    </div>
                    <div class="text-end rtl:text-end ltr:text-start">
                        <span class="text-sm font-bold text-gray-900 block">{{ $details['next_payment_at']->format('Y/m/d') }}</span>
                        @if(isset($details['days_until_next_payment']) && $details['days_until_next_payment'] !== null)
                            <span class="text-xs text-gray-600">
                                @if($details['days_until_next_payment'] > 0)
                                    {{ __('components.student.subscription_enrollment_widget.after_days', ['count' => $details['days_until_next_payment']]) }}
                                @elseif($details['days_until_next_payment'] === 0)
                                    {{ __('components.student.subscription_enrollment_widget.today') }}
                                @else
                                    {{ __('components.student.subscription_enrollment_widget.late_days', ['count' => abs($details['days_until_next_payment'])]) }}
                                @endif
                            </span>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Auto-Renew Status --}}
                @if(isset($details['status']) && $details['status'] === 'active' && isset($details['auto_renew']))
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="ri-refresh-line text-gray-600 ms-2 rtl:ms-2 ltr:me-2"></i>
                        <span class="text-sm text-gray-700">{{ __('components.student.subscription_enrollment_widget.auto_renewal') }}</span>
                    </div>
                    <span class="text-sm font-bold {{ $details['auto_renew'] ? 'text-green-600' : 'text-gray-600' }}">
                        {{ $details['auto_renew'] ? __('components.student.subscription_enrollment_widget.enabled') : __('components.student.subscription_enrollment_widget.disabled') }}
                    </span>
                </div>
                @endif
            @else
                {{-- Interactive Course Start Date --}}
                @if(isset($details['starts_at']) && $details['starts_at'])
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="ri-calendar-2-line text-gray-600 ms-2 rtl:ms-2 ltr:me-2"></i>
                        <span class="text-sm text-gray-700">{{ __('components.student.subscription_enrollment_widget.start_date') }}</span>
                    </div>
                    <span class="text-sm font-bold text-gray-900">{{ $details['starts_at']->format('Y/m/d') }}</span>
                </div>
                @endif
            @endif
        </div>

        {{-- Renewal Warning/Message --}}
        @if($renewalMessage)
            <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <div class="flex items-start">
                    <i class="ri-information-line text-yellow-600 text-lg ms-2 rtl:ms-2 ltr:me-2 mt-0.5"></i>
                    <p class="text-sm text-yellow-800">{{ $renewalMessage }}</p>
                </div>
            </div>
        @endif

        {{-- Trial Info (if applicable) --}}
        @if(isset($details['is_trial_active']) && $details['is_trial_active'])
            <div class="mt-6 p-4 bg-purple-50 border border-purple-200 rounded-lg">
                <div class="flex items-start">
                    <i class="ri-gift-line text-purple-600 text-lg ms-2 rtl:ms-2 ltr:me-2 mt-0.5"></i>
                    <div>
                        <p class="text-sm font-medium text-purple-900 mb-1">{{ __('common.trial.active_trial_period') }}</p>
                        <p class="text-xs text-purple-700">{{ __('common.trial.currently_in_free_trial') }}</p>
                    </div>
                </div>
            </div>
        @endif

    @elseif(!$isEnrolled && $enrollmentInfo)
        {{-- NON-ENROLLED USER: Show Enrollment Card --}}
        <h3 class="font-bold text-gray-900 mb-4">
            @if($type === 'interactive_course')
                {{ __('components.student.subscription_enrollment_widget.enroll_in_course') }}
            @elseif($type === 'quran_circle')
                {{ __('components.student.subscription_enrollment_widget.subscribe_to_circle') }}
            @else
                {{ __('components.student.subscription_enrollment_widget.subscribe_to_lesson') }}
            @endif
        </h3>

        {{-- Price Header --}}
        @if($enrollmentInfo['price'] > 0)
        <div class="mb-6 p-6 bg-gradient-to-r from-{{ $type === 'quran_circle' ? 'green' : 'blue' }}-50 to-{{ $type === 'quran_circle' ? 'green' : 'blue' }}-100 rounded-lg border border-{{ $type === 'quran_circle' ? 'green' : 'blue' }}-200">
            <div class="text-center">
                <p class="text-xs text-{{ $type === 'quran_circle' ? 'green' : 'blue' }}-700 mb-2">{{ $type === 'quran_circle' ? __('components.student.subscription_enrollment_widget.monthly_fees') : __('components.student.subscription_enrollment_widget.course_price') }}</p>
                <div class="flex items-baseline justify-center gap-2">
                    <span class="text-4xl font-black text-{{ $type === 'quran_circle' ? 'green' : 'blue' }}-900">{{ number_format($enrollmentInfo['price']) }}</span>
                    <span class="text-xl font-bold text-{{ $type === 'quran_circle' ? 'green' : 'blue' }}-700">{{ $enrollmentInfo['currency'] }}</span>
                </div>
                <p class="text-xs text-{{ $type === 'quran_circle' ? 'green' : 'blue' }}-600 mt-1">{{ $enrollmentInfo['billing_cycle'] }}</p>
            </div>
        </div>
        @endif

        {{-- Available Spots (if applicable) --}}
        @if(isset($enrollmentInfo['available_spots']) && isset($enrollmentInfo['total_spots']))
            @php
                $occupancyPercentage = (($enrollmentInfo['total_spots'] - $enrollmentInfo['available_spots']) / $enrollmentInfo['total_spots']) * 100;
                $seatClass = $enrollmentInfo['available_spots'] <= 3 ? 'text-red-600 bg-red-50 border-red-200' : ($enrollmentInfo['available_spots'] <= 5 ? 'text-orange-600 bg-orange-50 border-orange-200' : 'text-green-600 bg-green-50 border-green-200');
            @endphp

            <div class="mb-6">
                <div class="bg-gray-50 rounded-lg p-4 mb-3">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-600">{{ __('components.student.subscription_enrollment_widget.available_seats') }}</span>
                        <span class="text-sm font-bold text-gray-900">{{ $enrollmentInfo['total_spots'] - $enrollmentInfo['available_spots'] }} / {{ $enrollmentInfo['total_spots'] }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                        <div class="h-2.5 rounded-full transition-all duration-500 {{ $enrollmentInfo['available_spots'] <= 3 ? 'bg-red-500' : ($enrollmentInfo['available_spots'] <= 5 ? 'bg-orange-500' : 'bg-green-500') }}"
                             style="width: {{ $occupancyPercentage }}%"></div>
                    </div>
                </div>

                <div class="flex items-center justify-center gap-2 p-3 rounded-lg border {{ $seatClass }}">
                    <i class="ri-{{ $enrollmentInfo['available_spots'] <= 3 ? 'alarm-warning' : 'checkbox-circle' }}-line text-xl"></i>
                    <span class="font-bold">{{ $enrollmentInfo['available_spots'] }} {{ $enrollmentInfo['available_spots'] === 1 ? __('components.student.subscription_enrollment_widget.seat_remaining') : __('components.student.subscription_enrollment_widget.seats_remaining') }}</span>
                </div>
            </div>
        @endif

        {{-- Features List --}}
        <div class="mb-6 space-y-2">
            @if($type === 'quran_circle')
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <i class="ri-calendar-line text-green-600"></i>
                    <span>{{ __('components.student.subscription_enrollment_widget.regular_sessions') }}</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <i class="ri-group-line text-green-600"></i>
                    <span>{{ __('components.student.subscription_enrollment_widget.interactive_group') }}</span>
                </div>
            @elseif($type === 'interactive_course')
                @if($enrollable && $enrollable->duration_weeks)
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <i class="ri-calendar-line text-blue-500"></i>
                    <span>{{ __('components.student.subscription_enrollment_widget.course_duration', ['weeks' => $enrollable->duration_weeks]) }}</span>
                </div>
                @endif
                @if(isset($enrollmentInfo['total_sessions']))
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <i class="ri-time-line text-blue-500"></i>
                    <span>{{ __('components.student.subscription_enrollment_widget.sessions_count', ['count' => $enrollmentInfo['total_sessions']]) }}</span>
                </div>
                @endif
            @endif
            <div class="flex items-center gap-2 text-sm text-gray-600">
                <i class="ri-user-star-line text-{{ $type === 'quran_circle' ? 'green' : 'blue' }}-600"></i>
                <span>{{ __('components.student.subscription_enrollment_widget.qualified_teacher') }}</span>
            </div>
        </div>

        {{-- Enrollment Button Slot --}}
        {{ $slot }}

    @else
        {{-- No Subscription/Not Available --}}
        <div class="text-center py-8">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="ri-information-line text-3xl text-gray-400"></i>
            </div>
            <h4 class="text-base font-medium text-gray-900 mb-2">{{ __('components.student.subscription_enrollment_widget.no_active_subscription') }}</h4>
            <p class="text-sm text-gray-600">
                @if($type === 'interactive_course')
                    {{ __('components.student.subscription_enrollment_widget.no_subscription_linked_course') }}
                @elseif($type === 'quran_circle')
                    {{ __('components.student.subscription_enrollment_widget.no_subscription_linked_circle') }}
                @else
                    {{ __('components.student.subscription_enrollment_widget.no_subscription_linked_lesson') }}
                @endif
            </p>
        </div>
    @endif
</div>
