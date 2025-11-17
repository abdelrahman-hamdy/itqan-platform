<x-layouts.student 
    :title="$circle->name . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'تفاصيل حلقة القرآن: ' . $circle->name">

<div>
    <!-- Breadcrumb -->
    <nav class="mb-8">
        <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
            <li><a href="{{ route('student.quran-circles', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">حلقات القرآن</a></li>
            <li>/</li>
            <li class="text-gray-900">{{ $circle->name }}</li>
        </ol>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Circle Header -->
            <x-circle.circle-header :circle="$circle" type="group" view-type="student" />

            <!-- Enhanced Sessions List -->
            @if($isEnrolled)
                @php
                    // Combine all sessions for the unified display
                    // Upcoming sessions are already sorted ASC (closest first)
                    // Past sessions are already sorted DESC (newest first)
                    // Don't re-sort to maintain proper order
                    $allCircleSessions = collect($upcomingSessions)->merge($pastSessions);
                @endphp

                <x-sessions.sessions-list
                    :sessions="$allCircleSessions"
                    title="جلسات الحلقة"
                    view-type="student"
                    :show-tabs="false"
                    :circle="$circle"
                    empty-message="لا توجد جلسات مجدولة بعد" />
            @endif

            <!-- Requirements Section -->
            @if($circle->requirements)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
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
        <div class="lg:col-span-1 space-y-6">
            <!-- Circle Info Sidebar -->
            <x-circle.info-sidebar :circle="$circle" view-type="student" />

            <!-- Enrollment Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                @if($isEnrolled)
                <!-- Already Enrolled -->
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-green-600 mb-2">مسجل في الحلقة</h3>
                    <p class="text-sm text-gray-600">أنت مسجل في هذه الحلقة ويمكنك حضور الجلسات</p>
                </div>
                
                @if($circle->room_link)
                <a href="{{ $circle->room_link }}" target="_blank"
                   class="w-full bg-green-500 text-white py-3 px-4 rounded-lg font-medium hover:bg-green-600 transition-colors text-center block mb-4">
                    دخول الجلسة
                </a>
                @endif
                
                @elseif($canEnroll)
                <!-- Can Enroll -->
                <div class="text-center mb-6">
                    @if($circle->monthly_fee && $circle->monthly_fee > 0)
                    <div class="text-3xl font-bold text-gray-900 mb-2">
                        {{ number_format($circle->monthly_fee) }} {{ $circle->currency ?? 'ريال' }}
                    </div>
                    <div class="text-sm text-gray-600 mb-4">رسوم شهرية</div>
                    @else
                    <div class="text-3xl font-bold text-green-600 mb-2">مجاني</div>
                    <div class="text-sm text-gray-600 mb-4">بدون رسوم</div>
                    @endif
                </div>
                
                <div class="text-center">
                    <p class="text-sm text-gray-600">{{ $circle->max_students - $circle->enrolled_students }} مقعد متبقي</p>
                </div>
                
                @else
                <!-- Cannot Enroll -->
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-600 mb-2">غير متاح للتسجيل</h3>
                    <p class="text-sm text-gray-600">
                        @if($circle->enrollment_status === 'full')
                            هذه الحلقة مكتملة العدد
                        @elseif($circle->enrollment_status === 'closed')
                            التسجيل مغلق حالياً
                        @else
                            الحلقة غير نشطة
                        @endif
                    </p>
                </div>
                @endif
            </div>

            <!-- Quick Actions -->
            <x-circle.quick-actions :circle="$circle" type="group" view-type="student" :isEnrolled="$isEnrolled" :canEnroll="$canEnroll" />

            <!-- Circle Features -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4">مميزات الحلقة</h3>
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-sm text-gray-700">تعلم جماعي تفاعلي</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-sm text-gray-700">معلم مؤهل ومعتمد</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-sm text-gray-700">متابعة مستمرة للتقدم</span>
                    </div>
                    @if($circle->recording_enabled)
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-sm text-gray-700">تسجيل الجلسات للمراجعة</span>
                    </div>
                    @endif
                    @if($circle->certificates_enabled)
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-sm text-gray-700">شهادة إتمام</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<x-slot name="scripts">
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
</x-slot>

</x-layouts.student>