@props([
    'recording',
    'viewType' => 'teacher',
    'showActions' => true,
    'compact' => false
])

@php
    $isAvailable = $recording->isAvailable();
    $isRecording = $recording->isRecording();
    $isProcessing = $recording->isProcessing();
    $hasFailed = $recording->hasFailed();

    $startedAt = $recording->started_at ? toAcademyTimezone($recording->started_at) : null;
    $completedAt = $recording->completed_at ? toAcademyTimezone($recording->completed_at) : null;
@endphp

<div class="group {{ $compact ? 'p-3' : 'p-3 md:p-4' }} bg-gray-50 rounded-lg border border-gray-200 hover:border-primary/30 hover:bg-gray-100/50 transition-all duration-200">
    <div class="flex items-start gap-3">
        {{-- Icon --}}
        <div class="flex-shrink-0">
            @if($isRecording)
                <div class="w-8 h-8 md:w-10 md:h-10 bg-red-100 rounded-full flex items-center justify-center">
                    <i class="ri-record-circle-fill text-red-600 text-sm md:text-lg animate-pulse"></i>
                </div>
            @elseif($isProcessing)
                <div class="w-8 h-8 md:w-10 md:h-10 bg-amber-100 rounded-full flex items-center justify-center">
                    <i class="ri-loader-4-line text-amber-600 text-sm md:text-lg animate-spin"></i>
                </div>
            @elseif($isAvailable)
                <div class="w-8 h-8 md:w-10 md:h-10 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="ri-headphone-line text-green-600 text-sm md:text-lg"></i>
                </div>
            @elseif($hasFailed)
                <div class="w-8 h-8 md:w-10 md:h-10 bg-red-100 rounded-full flex items-center justify-center">
                    <i class="ri-error-warning-line text-red-600 text-sm md:text-lg"></i>
                </div>
            @else
                <div class="w-8 h-8 md:w-10 md:h-10 bg-gray-100 rounded-full flex items-center justify-center">
                    <i class="ri-film-line text-gray-600 text-sm md:text-lg"></i>
                </div>
            @endif
        </div>

        {{-- Content --}}
        <div class="flex-1 min-w-0">
            {{-- Title + Badge --}}
            <div class="flex items-center gap-1.5 flex-wrap">
                <h4 class="font-medium text-gray-900 text-sm truncate max-w-[200px] md:max-w-none">
                    {{ $recording->display_name }}
                </h4>
                <x-recordings.status-badge :status="$recording->status" size="sm" />
            </div>

            {{-- Metadata --}}
            <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 mt-1 text-xs text-gray-500">
                @if($startedAt)
                    <span class="flex items-center gap-1">
                        <i class="ri-calendar-line"></i>
                        {{ $startedAt->format('Y/m/d') }}
                    </span>
                    <span class="flex items-center gap-1">
                        <i class="ri-time-line"></i>
                        {{ $startedAt->format('H:i') }}
                    </span>
                @endif
                @if($recording->duration)
                    <span class="flex items-center gap-1">
                        <i class="ri-timer-line"></i>
                        {{ $recording->formatted_duration }}
                    </span>
                @endif
                @if($recording->file_size && $isAvailable)
                    <span class="flex items-center gap-1">
                        <i class="ri-hard-drive-2-line"></i>
                        {{ $recording->formatted_file_size }}
                    </span>
                @endif
            </div>

            @if($hasFailed && $recording->processing_error)
                <p class="text-[10px] text-red-600 mt-1 truncate">{{ $recording->processing_error }}</p>
            @endif

            {{-- Actions (below content on all screens) --}}
            @if($showActions)
                <div class="flex items-center gap-2 mt-2">
                    @if($isAvailable)
                        <button type="button" x-data
                            @click="$dispatch('open-audio-player', {
                                streamUrl: '{{ route('recordings.stream', ['recordingId' => $recording->id]) }}',
                                downloadUrl: '{{ route('recordings.download', ['recordingId' => $recording->id]) }}',
                                date: '{{ $startedAt?->format('Y/m/d') }}',
                                duration: '{{ $recording->formatted_duration }}',
                                size: '{{ $recording->formatted_file_size }}'
                            })"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-xs">
                            <i class="ri-headphone-line"></i>
                            {{ __('components.recordings.recording_item.listen') }}
                        </button>

                        @if(in_array($viewType, ['admin', 'supervisor']))
                            <button type="button"
                                    onclick="confirmDeleteRecording({{ $recording->id }}, '{{ addslashes($recording->display_name) }}')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-xs">
                                <i class="ri-delete-bin-line"></i>
                                <span class="hidden sm:inline">{{ __('components.recordings.recording_item.delete') }}</span>
                            </button>
                        @endif
                    @elseif($isRecording)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-red-100 text-red-700 rounded-lg text-xs">
                            <span class="w-1.5 h-1.5 bg-red-600 rounded-full animate-pulse"></span>
                            {{ __('components.recordings.recording_item.recording_in_progress') }}
                        </span>
                    @elseif($isProcessing)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-amber-100 text-amber-700 rounded-lg text-xs">
                            <i class="ri-loader-4-line animate-spin text-[10px]"></i>
                            {{ __('components.recordings.recording_item.processing_in_progress') }}
                        </span>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
