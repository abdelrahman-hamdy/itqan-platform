@props([
    'recording',
    'autoplay' => false,
    'showControls' => true,
    'showInfo' => true
])

@php
    $isAvailable = $recording->isAvailable();
    $streamUrl = $isAvailable ? route('recordings.stream', ['recordingId' => $recording->id]) : null;
    $downloadUrl = $isAvailable ? route('recordings.download', ['recordingId' => $recording->id]) : null;

    $startedAt = $recording->started_at ? \App\Helpers\TimeHelper::toSaudiTime($recording->started_at) : null;
@endphp

<div {{ $attributes->merge(['class' => 'bg-black rounded-xl overflow-hidden shadow-lg']) }}>
    @if($isAvailable && $streamUrl)
        <!-- Video Player -->
        <div class="relative aspect-video bg-gray-900">
            <video
                id="recording-player-{{ $recording->id }}"
                class="w-full h-full"
                controls
                {{ $autoplay ? 'autoplay' : '' }}
                preload="metadata"
                playsinline
            >
                <source src="{{ $streamUrl }}" type="video/mp4">
                <p class="text-white p-4">
                    متصفحك لا يدعم تشغيل الفيديو.
                    <a href="{{ $downloadUrl }}" class="text-primary underline">تحميل الفيديو</a>
                </p>
            </video>

            <!-- Loading Overlay -->
            <div id="video-loading-{{ $recording->id }}" class="absolute inset-0 flex items-center justify-center bg-gray-900/50 transition-opacity">
                <div class="text-center">
                    <i class="ri-loader-4-line text-white text-4xl animate-spin"></i>
                    <p class="text-white text-sm mt-2">جاري تحميل الفيديو...</p>
                </div>
            </div>
        </div>

        @if($showInfo)
            <!-- Recording Info Bar -->
            <div class="bg-gray-900 px-4 py-3 text-white">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div class="flex items-center gap-4 text-sm">
                        @if($startedAt)
                            <span class="flex items-center gap-1">
                                <i class="ri-calendar-line text-gray-400"></i>
                                {{ $startedAt->format('Y/m/d') }}
                            </span>
                        @endif

                        @if($recording->duration)
                            <span class="flex items-center gap-1">
                                <i class="ri-timer-line text-gray-400"></i>
                                {{ $recording->formatted_duration }}
                            </span>
                        @endif

                        @if($recording->file_size)
                            <span class="flex items-center gap-1">
                                <i class="ri-hard-drive-2-line text-gray-400"></i>
                                {{ $recording->formatted_file_size }}
                            </span>
                        @endif
                    </div>

                    @if($downloadUrl)
                        <a href="{{ $downloadUrl }}"
                           class="inline-flex items-center gap-1 px-3 py-1.5 bg-white/10 hover:bg-white/20 rounded-lg text-sm transition-colors">
                            <i class="ri-download-line"></i>
                            تحميل
                        </a>
                    @endif
                </div>
            </div>
        @endif

        @push('scripts')
        <script>
        (function() {
            const video = document.getElementById('recording-player-{{ $recording->id }}');
            const loading = document.getElementById('video-loading-{{ $recording->id }}');

            if (video && loading) {
                // Hide loading when video is ready
                video.addEventListener('loadeddata', function() {
                    loading.style.opacity = '0';
                    setTimeout(() => loading.style.display = 'none', 300);
                });

                // Show loading on waiting
                video.addEventListener('waiting', function() {
                    loading.style.display = 'flex';
                    loading.style.opacity = '1';
                });

                // Hide loading on playing
                video.addEventListener('playing', function() {
                    loading.style.opacity = '0';
                    setTimeout(() => loading.style.display = 'none', 300);
                });

                // Handle errors
                video.addEventListener('error', function() {
                    loading.innerHTML = `
                        <div class="text-center text-white">
                            <i class="ri-error-warning-line text-4xl text-red-500"></i>
                            <p class="mt-2">حدث خطأ أثناء تحميل الفيديو</p>
                            <a href="{{ $downloadUrl }}" class="text-primary underline text-sm">تحميل الفيديو</a>
                        </div>
                    `;
                });
            }
        })();
        </script>
        @endpush
    @else
        <!-- Not Available State -->
        <div class="aspect-video bg-gray-900 flex items-center justify-center">
            <div class="text-center text-white">
                @if($recording->isProcessing())
                    <i class="ri-loader-4-line text-4xl text-amber-500 animate-spin"></i>
                    <p class="mt-3 font-medium">جاري معالجة التسجيل</p>
                    <p class="text-sm text-gray-400 mt-1">سيتوفر للمشاهدة قريباً</p>
                @elseif($recording->hasFailed())
                    <i class="ri-error-warning-line text-4xl text-red-500"></i>
                    <p class="mt-3 font-medium">فشل التسجيل</p>
                    @if($recording->processing_error)
                        <p class="text-sm text-gray-400 mt-1">{{ $recording->processing_error }}</p>
                    @endif
                @else
                    <i class="ri-video-off-line text-4xl text-gray-500"></i>
                    <p class="mt-3 font-medium">التسجيل غير متاح</p>
                @endif
            </div>
        </div>
    @endif
</div>
