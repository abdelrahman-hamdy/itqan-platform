@extends('components.layouts.student')

@section('content')
<!-- Breadcrumb -->
<x-ui.breadcrumb
    :items="[
        ['label' => __('student.lesson_detail.recorded_courses'), 'route' => route('courses.index', ['subdomain' => $academy->subdomain]), 'icon' => 'ri-play-circle-line'],
        ['label' => $course->title, 'route' => route('courses.show', ['subdomain' => $academy->subdomain, 'id' => $course->id]), 'truncate' => true],
        ['label' => $lesson->title, 'truncate' => true],
    ]"
    view-type="student"
/>

<!-- Content Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Video Player -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="aspect-video bg-black relative">
                        @if($lesson->video_url)
                            <video id="lesson-video" 
                                   class="w-full h-full" 
                                   controls
                                   @if(!$lesson->is_downloadable) controlsList="nodownload" @endif
                                   preload="metadata"
                                   playsinline
                                   webkit-playsinline
                                   @if(!$lesson->is_downloadable) oncontextmenu="return false;" @endif
                                   onerror="handleVideoError()"
                                   onloadstart="handleVideoLoadStart()"
                                   onloadedmetadata="handleVideoMetadata()">
                                <source src="{{ route('lessons.video', ['subdomain' => $academy->subdomain, 'courseId' => $course->id, 'lessonId' => $lesson->id]) }}" type="video/mp4">
                            </video>
                            
                            @if(!$lesson->is_downloadable)
                            <!-- Download Protection Overlay -->
                            <div class="absolute inset-0 pointer-events-none" style="background: linear-gradient(transparent 0%, transparent 100%);"></div>
                            @endif
                            
                            <!-- Video Error Placeholder (hidden by default) -->
                            <div id="video-error-placeholder" class="absolute inset-0 bg-gray-900 flex items-center justify-center hidden">
                                <div class="text-center text-white">
                                    <i class="ri-video-line text-4xl mb-4"></i>
                                    <p class="text-lg font-semibold mb-2">{{ __('student.lesson_detail.video_unavailable') }}</p>
                                    <p class="text-sm opacity-75">{{ __('student.lesson_detail.video_coming_soon') }}</p>
                                </div>
                            </div>
                        @else
                            <div class="w-full h-full flex items-center justify-center">
                                <div class="text-center text-white">
                                    <i class="ri-video-line text-4xl mb-4"></i>
                                    <p>{{ __('student.lesson_detail.no_video') }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Lesson Information -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1 min-w-0">
                            <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $lesson->title }}</h1>
                            @if($lesson->description)
                                <div class="text-gray-600 prose prose-sm max-w-none">
                                    {!! $lesson->description !!}
                                </div>
                            @endif
                        </div>
                        <div class="flex-shrink-0 ms-4">
                            @if($lesson->is_free_preview)
                                <span class="px-3 py-1 bg-green-100 text-green-700 text-sm font-medium rounded-full whitespace-nowrap">
                                    <i class="ri-eye-line ms-1"></i>
                                    {{ __('student.lesson_detail.free_preview') }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Lesson Meta -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 py-4">
                        @if($lesson->video_duration_seconds)
                        <div class="text-center">
                            <div class="text-lg font-bold text-gray-900">{{ gmdate('i:s', $lesson->video_duration_seconds) }}</div>
                            <div class="text-sm text-gray-600">{{ __('student.lesson_detail.duration') }}</div>
                        </div>
                        @endif

                        @if($lesson->estimated_study_time_minutes)
                        <div class="text-center">
                            <div class="text-lg font-bold text-gray-900">{{ $lesson->estimated_study_time_minutes }}</div>
                            <div class="text-sm text-gray-600">{{ __('student.lesson_detail.study_time_minutes') }}</div>
                        </div>
                        @endif
                    </div>

                    <!-- Learning Objectives -->
                    @if($lesson->learning_objectives)
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">{{ __('student.lesson_detail.learning_objectives') }}</h3>
                        <ul class="space-y-2">
                            @foreach(json_decode($lesson->learning_objectives, true) ?? [] as $objective)
                                <li class="flex items-start">
                                    <i class="ri-check-line text-green-500 mt-1 ms-2"></i>
                                    <span class="text-gray-700">{{ $objective }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <!-- Notes -->
                    @if($lesson->notes)
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">{{ __('student.lesson_detail.notes') }}</h3>
                        <div class="prose prose-gray max-w-none">
                            {!! nl2br(e($lesson->notes)) !!}
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('student.lesson_detail.quick_actions') }}</h3>
                    <div class="space-y-3">
                        <!-- Return to Learn Page Button -->
                        <a href="{{ route('courses.learn', ['subdomain' => $academy->subdomain, 'id' => $course->id]) }}"
                           class="w-full flex items-center justify-center px-4 py-3 bg-cyan-500 text-white rounded-lg hover:bg-cyan-600 transition-colors">
                            <i class="ri-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}-line ms-2"></i>
                            {{ __('student.lesson_detail.return_to_learn') }}
                        </a>

                        <!-- Download Video Button (only if downloadable) -->
                        @if($lesson->is_downloadable)
                        <button onclick="downloadVideo()"
                                class="w-full flex items-center justify-center px-4 py-3 bg-cyan-500 text-white rounded-lg hover:bg-cyan-600 transition-colors">
                            <i class="ri-download-line ms-2"></i>
                            {{ __('student.lesson_detail.download_video') }}
                        </button>
                        @endif
                    </div>
                </div>

                <!-- Course Progress -->
                @if($isEnrolled)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('student.lesson_detail.course_progress') }}</h3>
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">{{ __('student.lesson_detail.overall_progress') }}</span>
                                <span class="text-gray-900">{{ $progressPercentage }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-cyan-500 h-2 rounded-full" style="width: {{ $progressPercentage }}%"></div>
                            </div>
                        </div>
                        <div class="text-sm text-gray-600">
                            {{ __('student.lesson_detail.lessons_completed', ['completed' => $completedLessons, 'total' => $totalLessons]) }}
                        </div>
                    </div>
                </div>
                @endif


                <!-- Course Lessons -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('student.lesson_detail.course_lessons') }}</h3>
                    <div class="space-y-2">
                        @foreach($course->lessons->sortBy('id') as $index => $courseLesson)
                        @if($isEnrolled || $courseLesson->is_free_preview)
                        <a href="{{ url('/courses/' . $course->id . '/lessons/' . $courseLesson->id) }}"
                           class="block p-3 rounded-lg border transition-colors no-underline
                            {{ $courseLesson->id === $lesson->id ? 'bg-cyan-50 border-cyan-500' : 'border-gray-200' }}
                            hover:bg-gray-50 cursor-pointer">
                        @else
                        <div class="p-3 rounded-lg border transition-colors
                            {{ $courseLesson->id === $lesson->id ? 'bg-cyan-50 border-cyan-500' : 'border-gray-200' }}
                            cursor-not-allowed opacity-75">
                        @endif
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-cyan-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="text-xs font-bold text-cyan-500">{{ $index + 1 }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-medium text-gray-900 text-sm truncate">{{ $courseLesson->title }}</h4>
                                    @if($courseLesson->video_duration_seconds)
                                    <div class="text-xs text-gray-500">
                                        {{ gmdate('i:s', $courseLesson->video_duration_seconds) }} {{ __('student.lesson_detail.minute') }}
                                    </div>
                                    @endif
                                </div>
                                <div class="flex-shrink-0">
                                    @if($courseLesson->id === $lesson->id)
                                        <i class="ri-play-circle-fill text-cyan-500 text-2xl"></i>
                                    @elseif($isEnrolled || $courseLesson->is_free_preview)
                                        <i class="ri-play-circle-line text-gray-400 text-2xl"></i>
                                    @else
                                        <i class="ri-lock-line text-gray-400 text-2xl"></i>
                                    @endif
                                </div>
                            </div>
                        @if($isEnrolled || $courseLesson->is_free_preview)
                        </a>
                        @else
                        </div>
                        @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Track video progress
document.addEventListener('DOMContentLoaded', function() {
    const video = document.getElementById('lesson-video');
    if (video) {
        let progressInterval;
        
        video.addEventListener('play', function() {
            progressInterval = setInterval(function() {
                if (!video.paused && !video.ended) {
                    // Send progress update
                    updateLessonProgress({{ $lesson->id }}, video.currentTime, video.duration);
                }
            }, 10000); // Update every 10 seconds
        });
        
        video.addEventListener('pause', function() {
            clearInterval(progressInterval);
        });
        
        video.addEventListener('ended', function() {
            clearInterval(progressInterval);
            // Mark lesson as completed
            markLessonCompleted({{ $lesson->id }});
        });
    }
});

function updateLessonProgress(lessonId, currentTime, duration) {
    fetch(`/courses/{{ $course->id }}/lessons/${lessonId}/progress`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            current_position: Math.floor(currentTime),
            is_completed: false
        })
    });
}

function markLessonCompleted(lessonId) {
    fetch(`/courses/{{ $course->id }}/lessons/${lessonId}/complete`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    });
}

// Download Protection for Non-Downloadable Videos
@if(!$lesson->is_downloadable)
document.addEventListener('DOMContentLoaded', function() {
    const video = document.getElementById('lesson-video');
    if (video) {
        // Disable right-click context menu
        video.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            return false;
        });
        
        // Disable common keyboard shortcuts for saving
        document.addEventListener('keydown', function(e) {
            // Disable Ctrl+S, Ctrl+A, Ctrl+U, F12, Ctrl+Shift+I
            if ((e.ctrlKey && (e.key === 's' || e.key === 'a' || e.key === 'u')) || 
                e.key === 'F12' || 
                (e.ctrlKey && e.shiftKey && e.key === 'I')) {
                e.preventDefault();
                return false;
            }
        });
        
        // Disable drag and drop
        video.addEventListener('dragstart', function(e) {
            e.preventDefault();
            return false;
        });
        
        // Disable selection
        video.addEventListener('selectstart', function(e) {
            e.preventDefault();
            return false;
        });
        
        // Disable text selection on the video element
        video.style.userSelect = 'none';
        video.style.webkitUserSelect = 'none';
        video.style.mozUserSelect = 'none';
        video.style.msUserSelect = 'none';
    }
});
@endif

// Download Video Function
function downloadVideo() {
    // Download the video file
    window.open(`{{ route('lessons.video', ['subdomain' => $academy->subdomain, 'courseId' => $course->id, 'lessonId' => $lesson->id]) }}`, '_blank');
}

// Handle Video Load Start
function handleVideoLoadStart() {
    const video = document.getElementById('lesson-video');
    const placeholder = document.getElementById('video-error-placeholder');
    
    if (video && placeholder) {
        // Hide placeholder when video starts loading
        placeholder.classList.add('hidden');
        video.style.display = '';
        video.style.visibility = 'visible';
    }
}

// Handle Video Metadata Loaded
function handleVideoMetadata() {
    const video = document.getElementById('lesson-video');
    if (video) {
        
        // Ensure controls are visible
        if (video.controls) {
        } else {
            video.controls = true;
        }
    }
}

// Handle Video Error
function handleVideoError() {
    const video = document.getElementById('lesson-video');
    const placeholder = document.getElementById('video-error-placeholder');
    
    if (video && placeholder) {
        video.style.display = 'none';
        placeholder.classList.remove('hidden');
    }
}

// Check video availability on page load
document.addEventListener('DOMContentLoaded', function() {
    const video = document.getElementById('lesson-video');
    if (video) {
        // Add event listeners for better error handling
        video.addEventListener('loadedmetadata', function() {
            handleVideoMetadata();
        });
        
        video.addEventListener('canplay', function() {
            // Ensure controls are visible when video can play
            if (!video.controls) {
                video.controls = true;
            }
        });
        
        video.addEventListener('error', function(e) {
            handleVideoError();
        });
        
        // Set a longer timeout to check if video loads
        setTimeout(function() {
            if (video.readyState === 0 && video.error) { // HAVE_NOTHING and has error
                handleVideoError();
            }
        }, 10000); // Wait 10 seconds for video to load
    }
});
</script>
@endsection
