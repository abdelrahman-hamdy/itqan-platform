@php
    $isTeacher = auth()->check() && (auth()->user()->isQuranTeacher() || auth()->user()->isAcademicTeacher());
    $viewType = $isTeacher ? 'teacher' : 'student';
@endphp

@livewire('payment.payment-gateway-modal', ['academyId' => $academy->id])

<div>
    <!-- Breadcrumb -->
    <x-ui.breadcrumb
        :items="[
            ['label' => __('student.group_circle.breadcrumb_circles'), 'route' => route('quran-circles.index', ['subdomain' => $academy->subdomain ?? 'itqan-academy'])],
            ['label' => $circle->name, 'truncate' => true],
        ]"
        :view-type="$viewType"
    />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-8" data-sticky-container>
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-4 md:space-y-6">
            <!-- Circle Header -->
            <x-circle.circle-header :circle="$circle" type="group" :view-type="$viewType" />

            @if($isEnrolled)
                @php
                    $allCircleSessions = collect($upcomingSessions)->merge($pastSessions);
                    $teacherProfile = $circle->quranTeacher?->quranTeacherProfile;
                    $teacherReviews = $teacherProfile ? $teacherProfile->approvedReviews()->with('student')->latest()->get() : collect();
                @endphp

                <!-- Tabs Component for Enrolled Students -->
                <x-tabs id="circle-content-tabs" default-tab="sessions" variant="default" color="primary">
                    <x-slot name="tabs">
                        <x-tabs.tab
                            id="sessions"
                            :label="__('student.group_circle.sessions_tab')"
                            icon="ri-calendar-line"
                            :badge="$allCircleSessions->count()"
                        />
                        <x-tabs.tab
                            id="quizzes"
                            :label="__('student.group_circle.quizzes_tab')"
                            icon="ri-file-list-3-line"
                        />
                        @if($teacherProfile)
                        <x-tabs.tab
                            id="reviews"
                            :label="__('student.group_circle.teacher_reviews_tab')"
                            icon="ri-star-line"
                            :badge="$teacherReviews->count()"
                        />
                        @endif
                    </x-slot>

                    <x-slot name="panels">
                        <x-tabs.panel id="sessions" padding="p-0 md:p-8">
                            <x-sessions.sessions-list
                                :sessions="$allCircleSessions"
                                :view-type="$viewType"
                                :show-tabs="false"
                                :circle="$circle"
                                :empty-message="__('student.group_circle.no_sessions_yet')" />
                        </x-tabs.panel>

                        <x-tabs.panel id="quizzes">
                            <livewire:quizzes-widget :assignable="$circle" />
                        </x-tabs.panel>

                        @if($teacherProfile)
                        <x-tabs.panel id="reviews">
                            <x-reviews.section
                                :reviewable-type="\App\Models\QuranTeacherProfile::class"
                                :reviewable-id="$teacherProfile->id"
                                review-type="teacher"
                                :reviews="$teacherReviews"
                                :rating="$teacherProfile->rating ?? 0"
                                :total-reviews="$teacherProfile->total_reviews ?? 0"
                                :show-summary="$teacherReviews->count() > 0"
                                :show-breakdown="true"
                                :show-review-form="$isEnrolled"
                            />
                        </x-tabs.panel>
                        @endif
                    </x-slot>
                </x-tabs>
            @endif

            <!-- Requirements Section (shown for all users) -->
            @if($circle->requirements)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <h3 class="text-base md:text-lg font-bold text-gray-900 mb-3 md:mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ __('student.group_circle.requirements_title') }}
                </h3>
                <div class="bg-orange-50 border-l-4 border-orange-400 p-4 rounded-r-lg">
                    @if(is_array($circle->requirements))
                    <div class="space-y-3">
                        @foreach($circle->requirements as $requirement)
                        <div class="flex items-start gap-3">
                            <div class="w-6 h-6 bg-orange-500 rounded-full flex items-center justify-center mt-0.5 flex-shrink-0">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <span class="text-gray-700 leading-relaxed">{{ $requirement }}</span>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-gray-700 leading-relaxed">{{ $circle->requirements }}</p>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1" data-sticky-sidebar>
            <div class="space-y-4 md:space-y-6">
                <!-- Circle Info Sidebar -->
                <x-circle.info-sidebar :circle="$circle" :view-type="$viewType" />

                @if(!$isTeacher)
                <!-- Quick Actions (only for enrolled students) -->
                @if($isEnrolled)
                <x-circle.quick-actions :circle="$circle" type="group" view-type="student" :isEnrolled="$isEnrolled" :canEnroll="$canEnroll" />
                @endif

                <!-- Certificate Section (for subscribed users) -->
                @if(isset($subscription) && $subscription)
                <x-certificate.student-certificate-section :subscription="$subscription" :circle="$circle" type="group_quran" />
                @endif

                <!-- Subscription/Enrollment Section -->
                @if(!isset($subscription) || !$subscription)
                    @if($canEnroll)
                        <!-- Enrollment Card - Show for students who can enroll -->
                        <x-student.subscription-enrollment-widget
                            type="quran_circle"
                            :is-enrolled="false"
                            :enrollable="$circle"
                            :can-enroll="$canEnroll"
                        >
                            <button onclick="showEnrollModal({{ $circle->id }})"
                                    class="min-h-[48px] w-full bg-gradient-to-r from-green-600 to-emerald-600 text-white py-3 md:py-4 px-4 md:px-6 rounded-xl font-bold text-base md:text-lg hover:from-green-700 hover:to-emerald-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                                <i class="ri-user-add-line text-xl md:text-2xl"></i>
                                {{ __('student.group_circle.enroll_button') }}
                            </button>
                        </x-student.subscription-enrollment-widget>
                    @elseif($isEnrolled)
                        {{-- Already enrolled but no subscription yet --}}
                    @endif
                @else
                    <!-- Subscription Details - Show for enrolled users with subscription -->
                    <x-circle.subscription-details
                        :subscription="$subscription"
                        view-type="student"
                    />
                @endif
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function openSessionDetail(sessionId) {
    @if(auth()->check())
        // Use Laravel route helper to generate correct URL for student sessions
        const sessionUrl = '{{ route("student.sessions.show", ["subdomain" => auth()->user()->academy->subdomain ?? "itqan-academy", "sessionId" => "SESSION_ID_PLACEHOLDER"]) }}';
        const finalUrl = sessionUrl.replace('SESSION_ID_PLACEHOLDER', sessionId);

        window.location.href = finalUrl;
    @else
    @endif
}

let pendingEnrollCircleId = null;

function showEnrollModal(circleId) {
    showConfirmModal({
        title: '{{ __('student.group_circle.modal_enroll_title') }}',
        message: '{{ __('student.group_circle.modal_enroll_message') }}',
        type: 'success',
        confirmText: '{{ __('student.group_circle.modal_enroll_confirm') }}',
        cancelText: '{{ __('student.group_circle.modal_cancel') }}',
        onConfirm: () => {
            @if(isset($circle) && $circle->monthly_fee && $circle->monthly_fee > 0)
                pendingEnrollCircleId = circleId;
                Livewire.dispatch('openGatewaySelection');
            @else
                enrollInCircle(circleId, null);
            @endif
        }
    });
}

// Listen for gateway selection
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Livewire !== 'undefined') {
        Livewire.on('gatewaySelected', ({ gateway }) => {
            if (pendingEnrollCircleId) {
                enrollInCircle(pendingEnrollCircleId, gateway);
                pendingEnrollCircleId = null;
            }
        });
    }
});

function showLeaveModal(circleId) {
    showConfirmModal({
        title: '{{ __('student.group_circle.modal_leave_title') }}',
        message: '{{ __('student.group_circle.modal_leave_message') }}',
        type: 'danger',
        confirmText: '{{ __('student.group_circle.modal_leave_confirm') }}',
        cancelText: '{{ __('student.group_circle.modal_leave_cancel') }}',
        onConfirm: () => leaveCircle(circleId)
    });
}

function enrollInCircle(circleId, paymentGateway) {
    const body = {};
    if (paymentGateway) {
        body.payment_gateway = paymentGateway;
    }

    fetch(`{{ route('student.circles.enroll', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circleId' => '__CIRCLE_ID__']) }}`.replace('__CIRCLE_ID__', circleId), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(body)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Check if payment is required - redirect to payment page
            if (data.data && data.data.requires_payment && data.data.redirect_url) {
                if (window.toast) {
                    window.toast.show({ type: 'info', message: data.message || '{{ __('student.group_circle.redirecting_to_payment') }}' });
                }
                // Redirect to payment page
                window.location.href = data.data.redirect_url;
                return;
            }

            // Free enrollment - show success and refresh
            if (window.toast) {
                window.toast.show({ type: 'success', message: data.message || '{{ __('student.group_circle.enroll_success') }}' });
            }

            // Refresh the page to show updated enrollment status
            window.location.reload(true);
        } else {
            showConfirmModal({
                title: '{{ __('student.group_circle.enroll_error_title') }}',
                message: data.error || data.message || '{{ __('student.group_circle.enroll_error_message') }}',
                type: 'danger',
                confirmText: '{{ __('student.group_circle.ok_button') }}'
            });
        }
    })
    .catch(error => {
        console.error('Enrollment error:', error);
        showConfirmModal({
            title: '{{ __('student.group_circle.connection_error_title') }}',
            message: '{{ __('student.group_circle.connection_error_message') }}',
            type: 'danger',
            confirmText: '{{ __('student.group_circle.ok_button') }}'
        });
    });
}

function leaveCircle(circleId) {
    fetch(`{{ route('student.circles.leave', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circleId' => '__CIRCLE_ID__']) }}`.replace('__CIRCLE_ID__', circleId), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Show success toast
            if (window.toast) {
                window.toast.show({ type: 'success', message: data.message || '{{ __('student.group_circle.leave_success') }}' });
            }

            // Refresh the page to show updated enrollment status
            window.location.reload(true);
        } else {
            showConfirmModal({
                title: '{{ __('student.group_circle.leave_error_title') }}',
                message: data.error || data.message || '{{ __('student.group_circle.leave_error_message') }}',
                type: 'danger',
                confirmText: '{{ __('student.group_circle.ok_button') }}'
            });
        }
    })
    .catch(error => {
        console.error('Leave circle error:', error);
        showConfirmModal({
            title: '{{ __('student.group_circle.connection_error_title') }}',
            message: '{{ __('student.group_circle.connection_error_message') }}',
            type: 'danger',
            confirmText: '{{ __('student.group_circle.ok_button') }}'
        });
    });
}
</script>
