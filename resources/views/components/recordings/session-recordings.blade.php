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
                <i class="ri-video-line text-primary ms-2 rtl:ms-2 ltr:me-2"></i>
                @if($viewType === 'teacher')
                    {{ __('components.recordings.session_recordings.title_teacher') }}
                @else
                    {{ __('components.recordings.session_recordings.title_student') }}
                @endif
            </h3>

            @if($hasAnyRecordings)
                <span class="text-sm text-gray-500">
                    {{ $completedRecordings->count() }} {{ __('components.recordings.session_recordings.recordings_available') }}
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
            <h4 class="text-lg font-medium text-gray-900 mb-2">{{ __('components.recordings.session_recordings.recording_not_available') }}</h4>
            <p class="text-gray-600 text-sm">{{ __('components.recordings.session_recordings.recording_not_supported') }}</p>
        </div>
    @elseif(!$isRecordingEnabled && $viewType === 'teacher')
        <!-- Recording Disabled -->
        <div class="text-center py-8">
            <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="ri-video-off-line text-amber-600 text-2xl"></i>
            </div>
            <h4 class="text-lg font-medium text-gray-900 mb-2">{{ __('components.recordings.session_recordings.recording_disabled') }}</h4>
            <p class="text-gray-600 text-sm">{{ __('components.recordings.session_recordings.recording_disabled_note') }}</p>
        </div>
    @elseif(!$hasAnyRecordings)
        <!-- No Recordings Yet -->
        <div class="text-center py-8">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="ri-film-line text-gray-400 text-2xl"></i>
            </div>
            <h4 class="text-lg font-medium text-gray-900 mb-2">{{ __('components.recordings.session_recordings.no_recordings_yet') }}</h4>
            <p class="text-gray-600 text-sm">
                @if($viewType === 'teacher')
                    {{ __('components.recordings.session_recordings.no_recordings_teacher_note') }}
                @else
                    {{ __('components.recordings.session_recordings.no_recordings_student_note') }}
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
                            <h4 class="font-semibold text-red-900">{{ __('components.recordings.session_recordings.recording_now') }}</h4>
                            @if($activeRecording->started_at)
                                <p class="text-sm text-red-700">
                                    {{ __('components.recordings.session_recordings.started_at') }} {{ \App\Helpers\TimeHelper::toSaudiTime($activeRecording->started_at)->format('H:i') }}
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
                        <h4 class="font-semibold text-amber-900">{{ __('components.recordings.session_recordings.processing_recordings') }}</h4>
                    </div>
                    <p class="text-sm text-amber-700 mb-3">
                        {{ $processingRecordings->count() }} {{ __('components.recordings.session_recordings.processing_note') }}
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
                            <i class="ri-check-circle-line text-green-600 ms-1 rtl:ms-1 ltr:me-1"></i>
                            {{ __('components.recordings.session_recordings.ready_recordings') }}
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
                        <h4 class="font-semibold text-red-900">{{ __('components.recordings.session_recordings.failed_recordings') }}</h4>
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
                            <div class="text-xs text-gray-600">{{ __('components.recordings.session_recordings.total_recordings') }}</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <div class="text-2xl font-bold text-green-600">{{ $recordingStats['completed_recordings'] ?? 0 }}</div>
                            <div class="text-xs text-gray-600">{{ __('components.recordings.session_recordings.completed') }}</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <div class="text-lg font-bold text-gray-900">{{ $recordingStats['total_duration_formatted'] ?? '00:00' }}</div>
                            <div class="text-xs text-gray-600">{{ __('components.recordings.session_recordings.total_duration') }}</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <div class="text-lg font-bold text-gray-900">{{ $recordingStats['total_size_formatted'] ?? '0 B' }}</div>
                            <div class="text-xs text-gray-600">{{ __('components.recordings.session_recordings.total_size') }}</div>
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
    const confirmMessage = '{{ __('components.recordings.session_recordings.confirm_delete') }}: "' + recordingName + '"?\n\n{{ __('components.recordings.session_recordings.cannot_undo') }}';
    if (!confirm(confirmMessage)) {
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
            showNotification('success', '{{ __('components.recordings.session_recordings.delete_success') }}');
            // Reload page to refresh the list
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showNotification('error', data.error || '{{ __('components.recordings.session_recordings.delete_error') }}');
            // Restore button state
            buttons.forEach(btn => {
                btn.disabled = false;
                btn.innerHTML = '<i class="ri-delete-bin-line"></i><span class="hidden sm:inline">{{ __('common.delete') }}</span>';
            });
        }
    })
    .catch(error => {
        showNotification('error', '{{ __('components.recordings.session_recordings.delete_error_occurred') }}');
        // Restore button state
        buttons.forEach(btn => {
            btn.disabled = false;
            btn.innerHTML = '<i class="ri-delete-bin-line"></i><span class="hidden sm:inline">{{ __('common.delete') }}</span>';
        });
    });
}

function showNotification(type, message) {
    // Use unified toast system
    if (window.toast) {
        const toastMethod = window.toast[type] || window.toast.info;
        toastMethod(message);
    }
}
</script>
@endpush
@endif
@endif
