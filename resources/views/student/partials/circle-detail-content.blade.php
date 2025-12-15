@php
    $isTeacher = auth()->check() && (auth()->user()->isQuranTeacher() || auth()->user()->isAcademicTeacher());
    $viewType = $isTeacher ? 'teacher' : 'student';
@endphp

<div>
    <!-- Breadcrumb -->
    <x-ui.breadcrumb
        :items="[
            ['label' => 'حلقات القرآن', 'route' => route('quran-circles.index', ['subdomain' => $academy->subdomain ?? 'itqan-academy'])],
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
                            label="الجلسات"
                            icon="ri-calendar-line"
                            :badge="$allCircleSessions->count()"
                        />
                        <x-tabs.tab
                            id="quizzes"
                            label="الاختبارات"
                            icon="ri-file-list-3-line"
                        />
                        @if($teacherProfile)
                        <x-tabs.tab
                            id="reviews"
                            label="تقييمات المعلم"
                            icon="ri-star-line"
                            :badge="$teacherReviews->count()"
                        />
                        @endif
                    </x-slot>

                    <x-slot name="panels">
                        <x-tabs.panel id="sessions">
                            <x-sessions.sessions-list
                                :sessions="$allCircleSessions"
                                :view-type="$viewType"
                                :show-tabs="false"
                                :circle="$circle"
                                empty-message="لا توجد جلسات مجدولة بعد" />
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
                    متطلبات الانضمام
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
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                            <h3 class="font-bold text-gray-900 mb-3 md:mb-4 flex items-center gap-2">
                                <i class="ri-user-add-line text-purple-500 text-lg" style="font-weight: 100;"></i>
                                الانضمام للحلقة
                            </h3>
                            <button onclick="showEnrollModal({{ $circle->id }})"
                                    class="min-h-[48px] w-full bg-gradient-to-r from-green-600 to-emerald-600 text-white py-3 md:py-4 px-4 md:px-6 rounded-xl font-bold text-base md:text-lg hover:from-green-700 hover:to-emerald-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                                <i class="ri-user-add-line text-xl md:text-2xl"></i>
                                سجل الآن في الحلقة
                            </button>
                        </div>
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

        console.log('Student Session URL:', finalUrl);
        window.location.href = finalUrl;
    @else
        console.error('User not authenticated');
    @endif
}

function showEnrollModal(circleId) {
    showConfirmModal({
        title: 'انضمام للحلقة',
        message: 'هل أنت متأكد من الانضمام لهذه الحلقة؟ سيتم تفعيل اشتراكك فوراً.',
        type: 'success',
        confirmText: 'انضم الآن',
        cancelText: 'إلغاء',
        onConfirm: () => enrollInCircle(circleId)
    });
}

function showLeaveModal(circleId) {
    showConfirmModal({
        title: 'إلغاء التسجيل',
        message: 'هل أنت متأكد من إلغاء التسجيل من هذه الحلقة؟ ستفقد إمكانية الوصول لجميع المواد.',
        type: 'danger',
        confirmText: 'إلغاء التسجيل',
        cancelText: 'البقاء في الحلقة',
        onConfirm: () => leaveCircle(circleId)
    });
}

function enrollInCircle(circleId) {
    fetch(`{{ route('student.circles.enroll', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circleId' => '__CIRCLE_ID__']) }}`.replace('__CIRCLE_ID__', circleId), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success toast
            if (typeof showToast !== 'undefined') {
                showToast(data.message || 'تم تسجيلك في الحلقة بنجاح', 'success');
            }

            // Automatically refresh the current page to show updated enrollment status
            setTimeout(() => {
                location.reload();
            }, 800);
        } else {
            showConfirmModal({
                title: 'خطأ في التسجيل',
                message: data.error || 'حدث خطأ أثناء التسجيل',
                type: 'danger',
                confirmText: 'موافق'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showConfirmModal({
            title: 'خطأ في الاتصال',
            message: 'حدث خطأ أثناء التسجيل. يرجى المحاولة مرة أخرى',
            type: 'danger',
            confirmText: 'موافق'
        });
    });
}

function leaveCircle(circleId) {
    fetch(`{{ route('student.circles.leave', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circleId' => '__CIRCLE_ID__']) }}`.replace('__CIRCLE_ID__', circleId), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success toast
            if (typeof showToast !== 'undefined') {
                showToast(data.message || 'تم إلغاء تسجيلك من الحلقة بنجاح', 'success');
            }

            // Automatically refresh the current page to show updated enrollment status
            setTimeout(() => {
                location.reload();
            }, 800);
        } else {
            showConfirmModal({
                title: 'خطأ',
                message: data.error || 'حدث خطأ أثناء إلغاء التسجيل',
                type: 'danger',
                confirmText: 'موافق'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showConfirmModal({
            title: 'خطأ في الاتصال',
            message: 'حدث خطأ أثناء إلغاء التسجيل. يرجى المحاولة مرة أخرى',
            type: 'danger',
            confirmText: 'موافق'
        });
    });
}
</script>
