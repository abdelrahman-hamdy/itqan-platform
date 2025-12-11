@props([
    'recording',
    'viewType' => 'teacher', // teacher, student
    'showActions' => true,
    'compact' => false
])

@php
    $isAvailable = $recording->isAvailable();
    $isRecording = $recording->isRecording();
    $isProcessing = $recording->isProcessing();
    $hasFailed = $recording->hasFailed();

    // Format dates using TimeHelper if available
    $startedAt = $recording->started_at ? \App\Helpers\TimeHelper::toSaudiTime($recording->started_at) : null;
    $completedAt = $recording->completed_at ? \App\Helpers\TimeHelper::toSaudiTime($recording->completed_at) : null;
@endphp

<div class="group {{ $compact ? 'p-3' : 'p-4' }} bg-gray-50 rounded-lg border border-gray-200 hover:border-primary/30 hover:bg-gray-100/50 transition-all duration-200">
    <div class="flex items-center justify-between gap-4">
        <!-- Recording Info -->
        <div class="flex items-center gap-3 flex-1 min-w-0">
            <!-- Icon -->
            <div class="flex-shrink-0">
                @if($isRecording)
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="ri-record-circle-fill text-red-600 text-lg animate-pulse"></i>
                    </div>
                @elseif($isProcessing)
                    <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center">
                        <i class="ri-loader-4-line text-amber-600 text-lg animate-spin"></i>
                    </div>
                @elseif($isAvailable)
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="ri-video-line text-green-600 text-lg"></i>
                    </div>
                @elseif($hasFailed)
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="ri-error-warning-line text-red-600 text-lg"></i>
                    </div>
                @else
                    <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center">
                        <i class="ri-film-line text-gray-600 text-lg"></i>
                    </div>
                @endif
            </div>

            <!-- Details -->
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <h4 class="font-medium text-gray-900 truncate">
                        {{ $recording->display_name }}
                    </h4>
                    <x-recordings.status-badge :status="$recording->status" size="sm" />
                </div>

                <div class="flex items-center gap-4 mt-1 text-sm text-gray-600">
                    @if($startedAt)
                        <span class="flex items-center gap-1">
                            <i class="ri-calendar-line text-gray-400"></i>
                            {{ $startedAt->format('Y/m/d') }}
                        </span>
                        <span class="flex items-center gap-1">
                            <i class="ri-time-line text-gray-400"></i>
                            {{ $startedAt->format('H:i') }}
                        </span>
                    @endif

                    @if($recording->duration)
                        <span class="flex items-center gap-1">
                            <i class="ri-timer-line text-gray-400"></i>
                            {{ $recording->formatted_duration }}
                        </span>
                    @endif

                    @if($recording->file_size && $isAvailable)
                        <span class="flex items-center gap-1">
                            <i class="ri-hard-drive-2-line text-gray-400"></i>
                            {{ $recording->formatted_file_size }}
                        </span>
                    @endif
                </div>

                @if($hasFailed && $recording->processing_error)
                    <p class="text-xs text-red-600 mt-1">
                        <i class="ri-error-warning-line ml-1"></i>
                        {{ $recording->processing_error }}
                    </p>
                @endif
            </div>
        </div>

        <!-- Actions -->
        @if($showActions)
            <div class="flex items-center gap-2 flex-shrink-0">
                @if($isAvailable)
                    <!-- Stream/Watch Button -->
                    <a href="{{ route('recordings.stream', ['recordingId' => $recording->id]) }}"
                       target="_blank"
                       class="inline-flex items-center gap-1 px-3 py-2 bg-primary text-white rounded-lg hover:bg-secondary transition-colors text-sm">
                        <i class="ri-play-circle-line"></i>
                        <span class="hidden sm:inline">مشاهدة</span>
                    </a>

                    @if($viewType === 'teacher')
                        <!-- Download Button -->
                        <a href="{{ route('recordings.download', ['recordingId' => $recording->id]) }}"
                           class="inline-flex items-center gap-1 px-3 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors text-sm">
                            <i class="ri-download-line"></i>
                            <span class="hidden sm:inline">تحميل</span>
                        </a>

                        <!-- Delete Button -->
                        <button type="button"
                                onclick="confirmDeleteRecording({{ $recording->id }}, '{{ addslashes($recording->display_name) }}')"
                                class="inline-flex items-center gap-1 px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm">
                            <i class="ri-delete-bin-line"></i>
                            <span class="hidden sm:inline">حذف</span>
                        </button>
                    @endif
                @elseif($isRecording)
                    <span class="inline-flex items-center gap-1 px-3 py-2 bg-red-100 text-red-700 rounded-lg text-sm">
                        <span class="w-2 h-2 bg-red-600 rounded-full animate-pulse"></span>
                        جاري التسجيل...
                    </span>
                @elseif($isProcessing)
                    <span class="inline-flex items-center gap-1 px-3 py-2 bg-amber-100 text-amber-700 rounded-lg text-sm">
                        <i class="ri-loader-4-line animate-spin"></i>
                        جاري المعالجة...
                    </span>
                @endif
            </div>
        @endif
    </div>
</div>
