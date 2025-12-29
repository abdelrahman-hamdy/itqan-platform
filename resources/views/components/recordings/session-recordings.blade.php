@props([
    'session',
    'viewType' => 'teacher', // teacher, student
    'showHeader' => true,
    'collapsible' => false
])

@php
    // Check if session implements RecordingCapable
    $supportsRecording = $session instanceof \App\Contracts\RecordingCapable;

    if ($supportsRecording) {
        $recordings = $session->getRecordings();
        $activeRecording = $session->getActiveRecording();
        $completedRecordings = $recordings->where('status', 'completed');
        $processingRecordings = $recordings->where('status', 'processing');
        $failedRecordings = $recordings->where('status', 'failed');
        $recordingStats = method_exists($session, 'getRecordingStats') ? $session->getRecordingStats() : null;
        $canRecord = $session->canBeRecorded();
        $isRecordingEnabled = $session->isRecordingEnabled();
    } else {
        $recordings = collect();
        $activeRecording = null;
        $completedRecordings = collect();
        $processingRecordings = collect();
        $failedRecordings = collect();
        $recordingStats = null;
        $canRecord = false;
        $isRecordingEnabled = false;
    }

    $hasAnyRecordings = $recordings->count() > 0;
    $hasAvailableRecordings = $completedRecordings->count() > 0;

    // For students, only show if there are completed recordings
    $shouldShow = $viewType === 'teacher' || $hasAvailableRecordings;
@endphp

@if($shouldShow)
<div {{ $attributes->merge(['class' => 'bg-white rounded-xl shadow-sm border border-gray-200 p-6']) }}>
    @if($showHeader)
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">
                <i class="ri-video-line text-primary ml-2"></i>
                @if($viewType === 'teacher')
                    تسجيلات الجلسة
                @else
                    التسجيلات المتاحة
                @endif
            </h3>

            @if($hasAnyRecordings)
                <span class="text-sm text-gray-500">
                    {{ $completedRecordings->count() }} تسجيل متاح
                </span>
            @endif
        </div>
    @endif

    @if(!$supportsRecording)
        <!-- Recording Not Supported -->
        <div class="text-center py-8">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="ri-video-off-line text-gray-400 text-2xl"></i>
            </div>
            <h4 class="text-lg font-medium text-gray-900 mb-2">التسجيل غير متاح</h4>
            <p class="text-gray-600 text-sm">هذا النوع من الجلسات لا يدعم التسجيل</p>
        </div>
    @elseif(!$isRecordingEnabled && $viewType === 'teacher')
        <!-- Recording Disabled -->
        <div class="text-center py-8">
            <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="ri-video-off-line text-amber-600 text-2xl"></i>
            </div>
            <h4 class="text-lg font-medium text-gray-900 mb-2">التسجيل غير مفعل</h4>
            <p class="text-gray-600 text-sm">التسجيل غير مفعل لهذه الدورة. يمكن تفعيله من إعدادات الدورة.</p>
        </div>
    @elseif(!$hasAnyRecordings)
        <!-- No Recordings Yet -->
        <div class="text-center py-8">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="ri-film-line text-gray-400 text-2xl"></i>
            </div>
            <h4 class="text-lg font-medium text-gray-900 mb-2">لا توجد تسجيلات بعد</h4>
            <p class="text-gray-600 text-sm">
                @if($viewType === 'teacher')
                    يمكنك بدء تسجيل الجلسة من خلال زر التسجيل أثناء الاجتماع
                @else
                    لم يتم تسجيل أي جلسات حتى الآن
                @endif
            </p>
        </div>
    @else
        <div class="space-y-4">
            <!-- Active Recording Alert -->
            @if($activeRecording)
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0">
                            <span class="flex h-3 w-3 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-red-600"></span>
                            </span>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-semibold text-red-900">جاري التسجيل الآن</h4>
                            @if($activeRecording->started_at)
                                <p class="text-sm text-red-700">
                                    بدأ في {{ \App\Helpers\TimeHelper::toSaudiTime($activeRecording->started_at)->format('H:i') }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Processing Recordings -->
            @if($processingRecordings->count() > 0)
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <i class="ri-loader-4-line text-amber-600 animate-spin"></i>
                        <h4 class="font-semibold text-amber-900">جاري معالجة التسجيلات</h4>
                    </div>
                    <p class="text-sm text-amber-700 mb-3">
                        {{ $processingRecordings->count() }} تسجيل قيد المعالجة. سيتم إتاحتها بعد اكتمال المعالجة.
                    </p>
                    <div class="space-y-2">
                        @foreach($processingRecordings as $recording)
                            <x-recordings.recording-item :recording="$recording" :view-type="$viewType" :show-actions="false" compact />
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Completed Recordings -->
            @if($completedRecordings->count() > 0)
                <div class="space-y-3">
                    @if($viewType === 'teacher' && ($processingRecordings->count() > 0 || $failedRecordings->count() > 0))
                        <h4 class="font-semibold text-gray-900 text-sm">
                            <i class="ri-check-circle-line text-green-600 ml-1"></i>
                            التسجيلات الجاهزة
                        </h4>
                    @endif

                    @foreach($completedRecordings->sortByDesc('completed_at') as $recording)
                        <x-recordings.recording-item :recording="$recording" :view-type="$viewType" />
                    @endforeach
                </div>
            @endif

            <!-- Failed Recordings (Teacher Only) -->
            @if($viewType === 'teacher' && $failedRecordings->count() > 0)
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <i class="ri-error-warning-line text-red-600"></i>
                        <h4 class="font-semibold text-red-900">تسجيلات فاشلة</h4>
                    </div>
                    <div class="space-y-2">
                        @foreach($failedRecordings as $recording)
                            <x-recordings.recording-item :recording="$recording" :view-type="$viewType" :show-actions="false" compact />
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Recording Stats (Teacher Only) -->
            @if($viewType === 'teacher' && $recordingStats && $completedRecordings->count() > 0)
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
                        <div class="bg-gray-50 rounded-lg p-3">
                            <div class="text-2xl font-bold text-primary">{{ $recordingStats['total_recordings'] ?? 0 }}</div>
                            <div class="text-xs text-gray-600">إجمالي التسجيلات</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <div class="text-2xl font-bold text-green-600">{{ $recordingStats['completed_recordings'] ?? 0 }}</div>
                            <div class="text-xs text-gray-600">مكتملة</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <div class="text-lg font-bold text-gray-900">{{ $recordingStats['total_duration_formatted'] ?? '00:00' }}</div>
                            <div class="text-xs text-gray-600">إجمالي المدة</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <div class="text-lg font-bold text-gray-900">{{ $recordingStats['total_size_formatted'] ?? '0 B' }}</div>
                            <div class="text-xs text-gray-600">إجمالي الحجم</div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>

@if($viewType === 'teacher')
<!-- Delete Confirmation Script -->
@push('scripts')
<script>
function confirmDeleteRecording(recordingId, recordingName) {
    if (!confirm(`هل أنت متأكد من حذف التسجيل "${recordingName}"؟\n\nلا يمكن التراجع عن هذا الإجراء.`)) {
        return;
    }

    // Show loading state
    const buttons = document.querySelectorAll(`button[onclick*="confirmDeleteRecording(${recordingId}"]`);
    buttons.forEach(btn => {
        btn.disabled = true;
        btn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i>';
    });

    fetch(`/api/recordings/${recordingId}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success notification
            showNotification('success', 'تم حذف التسجيل بنجاح');
            // Reload page to refresh the list
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showNotification('error', data.error || 'فشل حذف التسجيل');
            // Restore button state
            buttons.forEach(btn => {
                btn.disabled = false;
                btn.innerHTML = '<i class="ri-delete-bin-line"></i><span class="hidden sm:inline">حذف</span>';
            });
        }
    })
    .catch(error => {
        showNotification('error', 'حدث خطأ أثناء حذف التسجيل');
        // Restore button state
        buttons.forEach(btn => {
            btn.disabled = false;
            btn.innerHTML = '<i class="ri-delete-bin-line"></i><span class="hidden sm:inline">حذف</span>';
        });
    });
}

function showNotification(type, message) {
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500'
    };

    const notification = document.createElement('div');
    notification.className = `fixed top-4 left-1/2 transform -translate-x-1/2 ${colors[type] || colors.info} text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2`;
    notification.innerHTML = `
        <i class="ri-${type === 'success' ? 'check' : type === 'error' ? 'error-warning' : 'information'}-line"></i>
        <span>${message}</span>
    `;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.transition = 'opacity 0.3s';
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}
</script>
@endpush
@endif
@endif
