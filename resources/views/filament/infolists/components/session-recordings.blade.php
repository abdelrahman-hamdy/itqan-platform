@php
    use App\Enums\RecordingStatus;

    $record = $getRecord();
    $recordings = $record->getRecordings();
    $completedRecordings = $recordings->filter(fn ($r) => $r->status === RecordingStatus::COMPLETED);
    $processingRecordings = $recordings->filter(fn ($r) => $r->status === RecordingStatus::PROCESSING);
    $activeRecording = $recordings->filter(fn ($r) => $r->status === RecordingStatus::RECORDING)->sortByDesc('created_at')->first();
@endphp

<div class="space-y-4">
    @if($recordings->isEmpty())
        <div class="flex items-center gap-2 text-sm text-gray-500">
            <x-heroicon-o-film class="w-5 h-5" />
            <span>{{ __('recordings.no_recordings_yet') }}</span>
        </div>
    @else
        {{-- Active Recording --}}
        @if($activeRecording)
            <div class="flex items-center gap-3 rounded-lg bg-red-50 dark:bg-red-950/20 p-3 border border-red-200 dark:border-red-800">
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-red-600"></span>
                </span>
                <span class="font-semibold text-red-900 dark:text-red-200">{{ __('recordings.recording_in_progress') }}</span>
            </div>
        @endif

        {{-- Processing Recordings --}}
        @foreach($processingRecordings as $recording)
            <div class="flex items-center gap-3 rounded-lg bg-amber-50 dark:bg-amber-950/20 p-3 border border-amber-200 dark:border-amber-800">
                <x-heroicon-o-arrow-path class="w-5 h-5 text-amber-600 animate-spin" />
                <span class="text-amber-900 dark:text-amber-200">{{ __('recordings.processing') }}</span>
            </div>
        @endforeach

        {{-- Completed Recordings --}}
        @foreach($completedRecordings->sortByDesc('completed_at') as $recording)
            @if($recording->isAvailable())
                <button type="button"
                    x-data
                    @click="$dispatch('open-audio-player', {
                        streamUrl: '{{ route('recordings.stream', ['recordingId' => $recording->id]) }}',
                        downloadUrl: '{{ route('recordings.download', ['recordingId' => $recording->id]) }}',
                        date: '{{ $recording->started_at ? toAcademyTimezone($recording->started_at)->format('Y/m/d H:i') : '' }}',
                        duration: '{{ $recording->formatted_duration }}',
                        size: '{{ $recording->formatted_file_size }}'
                    })"
                    class="w-full flex items-center justify-between rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors cursor-pointer text-start"
                >
                    <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                        @if($recording->started_at)
                            <span class="flex items-center gap-1">
                                <x-heroicon-o-calendar class="w-4 h-4" />
                                {{ toAcademyTimezone($recording->started_at)->format('Y/m/d H:i') }}
                            </span>
                        @endif
                        @if($recording->duration)
                            <span class="flex items-center gap-1">
                                <x-heroicon-o-clock class="w-4 h-4" />
                                {{ $recording->formatted_duration }}
                            </span>
                        @endif
                        @if($recording->file_size)
                            <span class="flex items-center gap-1">
                                <x-heroicon-o-server class="w-4 h-4" />
                                {{ $recording->formatted_file_size }}
                            </span>
                        @endif
                    </div>
                    <x-heroicon-o-play-circle class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                </button>
            @endif
        @endforeach
    @endif

    @if($completedRecordings->isNotEmpty())
        <p class="text-xs text-gray-400 mt-2">{{ __('recordings.retention_notice') }}</p>
    @endif
</div>

{{-- Audio Player Modal --}}
<x-recordings.audio-player-modal />
