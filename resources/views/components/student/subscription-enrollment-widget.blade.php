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
                'currency' => $enrollable->currency ?? 'ريال',
                'billing_cycle' => 'شهرياً',
                'available_spots' => $enrollable->max_students - ($enrollable->enrolled_students ?? 0),
                'total_spots' => $enrollable->max_students,
            ];
        } elseif ($type === 'interactive_course') {
            $enrollmentInfo = [
                'price' => $enrollable->student_price ?? 0,
                'currency' => 'ر.س',
                'billing_cycle' => 'دفعة واحدة',
                'available_spots' => $enrollable->max_students - ($enrollable->enrolled_students ?? 0),
                'total_spots' => $enrollable->max_students,
                'enrollment_deadline' => $enrollable->enrollment_deadline,
            ];
        } elseif ($type === 'academic_class' && $subscription && $subscription->package) {
            $enrollmentInfo = [
                'price' => $subscription->package->price ?? 0,
                'currency' => $subscription->package->currency ?? 'ريال',
                'billing_cycle' => $subscription->package->billing_cycle === 'monthly' ? 'شهرياً' : 'دفعة واحدة',
                'total_sessions' => $subscription->package->total_sessions ?? 0,
            ];
        }
    }
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    @if($isEnrolled && $details)
        {{-- ENROLLED USER: Show Subscription Details --}}
        <h3 class="font-bold text-gray-900 mb-4">
            {{ $type === 'interactive_course' ? 'تفاصيل التسجيل' : 'تفاصيل الاشتراك' }}
        </h3>

        @if($type !== 'interactive_course')
            {{-- Subscription Status Badge --}}
            <div class="mb-4 flex items-center justify-between">
                <span class="text-sm text-gray-600">حالة الاشتراك</span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $details['status_badge_class'] ?? 'bg-green-100 text-green-800' }}">
                    @if(isset($details['status']))
                        @if($type === 'quran_circle')
                            {{ app(QuranSubscriptionDetailsService::class)->getStatusTextArabic($details['status']) }}
                        @else
                            {{ $details['status']?->label() ?? 'نشط' }}
                        @endif
                    @else
                        نشط
                    @endif
                </span>
            </div>

            {{-- Billing Cycle --}}
            <div class="mb-6 p-4 bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg border border-blue-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-blue-700 mb-1">نوع الاشتراك</p>
                        <p class="text-lg font-bold text-blue-900">{{ $details['billing_cycle_ar'] ?? 'شهري' }}</p>
                    </div>
                    @if($formattedPrice)
                    <div class="text-left">
                        <p class="text-xs text-blue-700 mb-1">المبلغ</p>
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
                        <p class="text-xs text-green-700 mb-1">حالة التسجيل</p>
                        <p class="text-lg font-bold text-green-900">مسجل</p>
                    </div>
                    <div class="text-left">
                        <p class="text-xs text-green-700 mb-1">نسبة الإنجاز</p>
                        <p class="text-lg font-bold text-green-900">{{ $details['completion_percentage'] ?? 0 }}%</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Sessions Progress --}}
        <div class="mb-6">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium text-gray-700">تقدم الجلسات</span>
                <span class="text-sm font-bold text-primary">{{ $details['sessions_percentage'] ?? 0 }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="bg-primary h-3 rounded-full transition-all duration-500"
                     style="width: {{ $details['sessions_percentage'] ?? 0 }}%"></div>
            </div>
            <div class="flex justify-between mt-2 text-xs text-gray-600">
                <span>{{ $details['sessions_used'] ?? 0 }} مستخدمة</span>
                <span>{{ $details['sessions_remaining'] ?? 0 }} متبقية</span>
            </div>
        </div>

        {{-- Statistics Grid --}}
        <div class="grid grid-cols-2 gap-4 mb-6">
            {{-- Sessions Used --}}
            <div class="text-center p-4 bg-green-50 rounded-lg border border-green-200">
                <div class="text-2xl font-bold text-green-600">{{ $details['sessions_used'] ?? 0 }}</div>
                <div class="text-xs text-green-700 font-medium">جلسة مستخدمة</div>
            </div>

            {{-- Sessions Remaining --}}
            <div class="text-center p-4 bg-blue-50 rounded-lg border border-blue-200">
                <div class="text-2xl font-bold text-blue-600">{{ $details['sessions_remaining'] ?? 0 }}</div>
                <div class="text-xs text-blue-700 font-medium">جلسة متبقية</div>
            </div>
        </div>

        {{-- Subscription Details --}}
        <div class="space-y-3">
            {{-- Total Sessions --}}
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex items-center">
                    <i class="ri-calendar-check-line text-gray-600 ml-2"></i>
                    <span class="text-sm text-gray-700">إجمالي الجلسات</span>
                </div>
                <span class="text-sm font-bold text-gray-900">{{ $details['total_sessions'] ?? 0 }} جلسة</span>
            </div>

            @if($type !== 'interactive_course')
                {{-- Payment Status --}}
                @if(isset($details['payment_status']))
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="ri-money-dollar-circle-line text-gray-600 ml-2"></i>
                        <span class="text-sm text-gray-700">حالة الدفع</span>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $details['payment_status_badge_class'] ?? 'bg-green-100 text-green-800' }}">
                        @if($type === 'quran_circle')
                            {{ app(QuranSubscriptionDetailsService::class)->getPaymentStatusTextArabic($details['payment_status']) }}
                        @else
                            {{ $details['payment_status']?->label() ?? 'مدفوع' }}
                        @endif
                    </span>
                </div>
                @endif

                {{-- Start Date --}}
                @if(isset($details['starts_at']) && $details['starts_at'])
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="ri-calendar-2-line text-gray-600 ml-2"></i>
                        <span class="text-sm text-gray-700">تاريخ البداية</span>
                    </div>
                    <span class="text-sm font-bold text-gray-900">{{ $details['starts_at']->format('Y/m/d') }}</span>
                </div>
                @endif

                {{-- Next Payment Date --}}
                @if(isset($details['next_payment_at']) && $details['next_payment_at'] && $details['status'] === 'active')
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="ri-time-line text-gray-600 ml-2"></i>
                        <span class="text-sm text-gray-700">التجديد القادم</span>
                    </div>
                    <div class="text-left">
                        <span class="text-sm font-bold text-gray-900 block">{{ $details['next_payment_at']->format('Y/m/d') }}</span>
                        @if(isset($details['days_until_next_payment']) && $details['days_until_next_payment'] !== null)
                            <span class="text-xs text-gray-600">
                                @if($details['days_until_next_payment'] > 0)
                                    بعد {{ $details['days_until_next_payment'] }} يوم
                                @elseif($details['days_until_next_payment'] === 0)
                                    اليوم
                                @else
                                    متأخر {{ abs($details['days_until_next_payment']) }} يوم
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
                        <i class="ri-refresh-line text-gray-600 ml-2"></i>
                        <span class="text-sm text-gray-700">التجديد التلقائي</span>
                    </div>
                    <span class="text-sm font-bold {{ $details['auto_renew'] ? 'text-green-600' : 'text-gray-600' }}">
                        {{ $details['auto_renew'] ? 'مفعّل' : 'معطّل' }}
                    </span>
                </div>
                @endif
            @else
                {{-- Interactive Course Start Date --}}
                @if(isset($details['starts_at']) && $details['starts_at'])
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="ri-calendar-2-line text-gray-600 ml-2"></i>
                        <span class="text-sm text-gray-700">تاريخ البداية</span>
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
                    <i class="ri-information-line text-yellow-600 text-lg ml-2 mt-0.5"></i>
                    <p class="text-sm text-yellow-800">{{ $renewalMessage }}</p>
                </div>
            </div>
        @endif

        {{-- Trial Info (if applicable) --}}
        @if(isset($details['is_trial_active']) && $details['is_trial_active'])
            <div class="mt-6 p-4 bg-purple-50 border border-purple-200 rounded-lg">
                <div class="flex items-start">
                    <i class="ri-gift-line text-purple-600 text-lg ml-2 mt-0.5"></i>
                    <div>
                        <p class="text-sm font-medium text-purple-900 mb-1">فترة تجريبية نشطة</p>
                        <p class="text-xs text-purple-700">أنت حالياً في الفترة التجريبية المجانية</p>
                    </div>
                </div>
            </div>
        @endif

    @elseif(!$isEnrolled && $enrollmentInfo)
        {{-- NON-ENROLLED USER: Show Enrollment Card --}}
        <h3 class="font-bold text-gray-900 mb-4">
            {{ $type === 'interactive_course' ? 'التسجيل في الكورس' : 'الاشتراك في ' . ($type === 'quran_circle' ? 'الحلقة' : 'الدرس') }}
        </h3>

        {{-- Price Header --}}
        @if($enrollmentInfo['price'] > 0)
        <div class="mb-6 p-6 bg-gradient-to-r from-{{ $type === 'quran_circle' ? 'green' : 'blue' }}-50 to-{{ $type === 'quran_circle' ? 'green' : 'blue' }}-100 rounded-lg border border-{{ $type === 'quran_circle' ? 'green' : 'blue' }}-200">
            <div class="text-center">
                <p class="text-xs text-{{ $type === 'quran_circle' ? 'green' : 'blue' }}-700 mb-2">{{ $type === 'quran_circle' ? 'الرسوم الشهرية' : 'سعر الكورس' }}</p>
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
                        <span class="text-sm font-medium text-gray-600">المقاعد المتاحة</span>
                        <span class="text-sm font-bold text-gray-900">{{ $enrollmentInfo['total_spots'] - $enrollmentInfo['available_spots'] }} / {{ $enrollmentInfo['total_spots'] }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                        <div class="h-2.5 rounded-full transition-all duration-500 {{ $enrollmentInfo['available_spots'] <= 3 ? 'bg-red-500' : ($enrollmentInfo['available_spots'] <= 5 ? 'bg-orange-500' : 'bg-green-500') }}"
                             style="width: {{ $occupancyPercentage }}%"></div>
                    </div>
                </div>

                <div class="flex items-center justify-center gap-2 p-3 rounded-lg border {{ $seatClass }}">
                    <i class="ri-{{ $enrollmentInfo['available_spots'] <= 3 ? 'alarm-warning' : 'checkbox-circle' }}-line text-xl"></i>
                    <span class="font-bold">{{ $enrollmentInfo['available_spots'] }} {{ $enrollmentInfo['available_spots'] === 1 ? 'مقعد متبقي' : 'مقاعد متبقية' }}</span>
                </div>
            </div>
        @endif

        {{-- Features List --}}
        <div class="mb-6 space-y-2">
            @if($type === 'quran_circle')
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <i class="ri-calendar-line text-green-600"></i>
                    <span>جلسات منتظمة حسب الجدول</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <i class="ri-group-line text-green-600"></i>
                    <span>حلقة جماعية تفاعلية</span>
                </div>
            @elseif($type === 'interactive_course')
                @if($enrollable && $enrollable->duration_weeks)
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <i class="ri-calendar-line text-blue-500"></i>
                    <span>مدة الكورس: {{ $enrollable->duration_weeks }} أسبوع</span>
                </div>
                @endif
                @if(isset($enrollmentInfo['total_sessions']))
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <i class="ri-time-line text-blue-500"></i>
                    <span>عدد الجلسات: {{ $enrollmentInfo['total_sessions'] }} جلسة</span>
                </div>
                @endif
            @endif
            <div class="flex items-center gap-2 text-sm text-gray-600">
                <i class="ri-user-star-line text-{{ $type === 'quran_circle' ? 'green' : 'blue' }}-600"></i>
                <span>معلم مؤهل ومعتمد</span>
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
            <h4 class="text-base font-medium text-gray-900 mb-2">لا يوجد اشتراك نشط</h4>
            <p class="text-sm text-gray-600">
                لم يتم ربط اشتراك {{ $type === 'interactive_course' ? 'بهذا الكورس' : ($type === 'quran_circle' ? 'بهذه الحلقة' : 'بهذا الدرس') }} بعد
            </p>
        </div>
    @endif
</div>
