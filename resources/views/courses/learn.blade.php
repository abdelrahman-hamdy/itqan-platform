@extends('components.layouts.student')

@section('title', 'تعلم: ' . $course->title . ' - ' . $academy->name)

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-7xl mx-auto">
            <!-- Breadcrumb -->
            <nav class="mb-8">
                <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
                    <li><a href="{{ route('courses.index', ['subdomain' => $academy->subdomain]) }}" class="hover:text-primary">الدورات المسجلة</a></li>
                    <li>/</li>
                    <li><a href="{{ route('courses.show', ['subdomain' => $academy->subdomain, 'id' => $course->id]) }}" class="hover:text-primary">{{ $course->title }}</a></li>
                    <li>/</li>
                    <li class="text-gray-900">التعلم</li>
                </ol>
            </nav>

            <!-- Course Header -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $course->title }}</h1>
                        <p class="text-gray-600">تعلم بالسرعة التي تناسبك</p>
                    </div>
                    <div class="flex items-center gap-6">
                        
                        <!-- Start Next Lesson Button -->
                        <button id="start-next-lesson-btn" 
                                onclick="startNextLesson()" 
                                class="hidden flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                            <i class="ri-play-circle-line ml-2"></i>
                            بدء الدرس التالي
                        </button>
                        
                        <!-- Progress Display -->
                        <div class="text-right">
                            <div class="text-sm text-gray-600 mb-1">التقدم الإجمالي</div>
                            <div class="text-2xl font-bold text-primary text-center">
                                <span id="progress-percentage">0</span>%
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Progress Bar -->
                <div class="mt-4">
                    <div class="w-full bg-gray-200 rounded-full h-2 mb-2 overflow-hidden">
                        <div class="bg-primary h-2 rounded-full transition-all duration-1000 ease-out shadow-sm" 
                             id="progress-bar"
                             style="width: 0%"></div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                        <span id="completed-lessons" class="transition-opacity duration-200">{{ $completedLessons }} من {{ $totalLessons }} درس مكتمل</span>
                        <span id="remaining-lessons" class="transition-opacity duration-200">{{ $totalLessons - $completedLessons }} درس متبقي</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Course Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Video Player Area -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        <div class="aspect-video bg-gray-900 flex items-center justify-center">
                            <div class="text-center text-white">
                                <i class="ri-video-line text-6xl mb-4"></i>
                                <h3 class="text-xl font-semibold mb-2">مشغل الفيديو</h3>
                                <p class="text-gray-300">اختر درساً من القائمة لبدء المشاهدة</p>
                            </div>
                        </div>
                        <div class="p-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-2">مرحباً بك في دورة {{ $course->title }}</h2>
                            <p class="text-gray-600">اختر أي درس من القائمة الجانبية لبدء التعلم. يمكنك متابعة التقدم والعودة في أي وقت.</p>
                        </div>
                    </div>

                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <!-- Course Curriculum -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">محتوى الدورة</h3>
                        
                        @if($course->lessons->count() > 0)
                        <div class="space-y-2">
                            @foreach($course->lessons->sortBy('id') as $index => $lesson)
                            <div class="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors" 
                                 data-lesson-id="{{ $lesson->id }}">
                                <div class="flex items-start gap-3">
                                    <!-- Lesson Number -->
                                    <div class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center flex-shrink-0">
                                        <span class="text-xs font-bold text-primary">{{ $index + 1 }}</span>
                                    </div>
                                    
                                    <div class="flex-1 min-w-0">
                                        <h5 class="font-medium text-gray-900 text-sm mb-2">{{ $lesson->title }}</h5>
                                        
                                        <!-- Lesson Meta -->
                                        <div class="flex items-center gap-3 mb-3">
                                            @if($lesson->video_duration_seconds)
                                            <div class="text-xs text-gray-500 flex items-center">
                                                <i class="ri-time-line ml-1"></i>
                                                {{ gmdate('i:s', $lesson->video_duration_seconds) }}
                                            </div>
                                            @endif
                                            @if($lesson->is_downloadable)
                                                <div class="text-xs text-green-600 flex items-center">
                                                    <i class="ri-download-line ml-1"></i>
                                                    قابل للتحميل
                                                </div>
                                            @endif
                                        </div>
                                        
                                        <!-- Action Buttons -->
                                        <div class="flex gap-2">
                                            <button onclick="playLesson({{ $lesson->id }}, '{{ $lesson->title }}')" 
                                                    class="flex items-center px-3 py-1.5 bg-green-600 text-white text-xs rounded-lg hover:bg-green-700 transition-colors">
                                                <i class="ri-play-line ml-1"></i>
                                                تشغيل
                                            </button>
                                            <a href="{{ route('lessons.show', ['subdomain' => $academy->subdomain, 'courseId' => $course->id, 'lessonId' => $lesson->id]) }}" 
                                               class="flex items-center px-3 py-1.5 bg-gray-600 text-white text-xs rounded-lg hover:bg-gray-700 transition-colors">
                                                <i class="ri-eye-line ml-1"></i>
                                            عرض الدرس
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <!-- Status Icon -->
                                    <div class="flex-shrink-0">
                                        @php
                                            $lessonProgress = \App\Models\StudentProgress::where('user_id', auth()->id())
                                                ->where('recorded_course_id', $course->id)
                                                ->where('lesson_id', $lesson->id)
                                                ->first();
                                        @endphp
                                        <button onclick="toggleLessonStatus({{ $lesson->id }})" 
                                                class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-gray-100 hover:scale-110 transition-all duration-200"
                                                title="انقر لتغيير حالة الدرس">
                                            @if($lessonProgress && $lessonProgress->is_completed)
                                                <i class="ri-checkbox-circle-fill text-green-500 text-2xl lesson-status" 
                                                   id="lesson-status-{{ $lesson->id }}"></i>
                                            @elseif($lessonProgress && $lessonProgress->progress_percentage > 0)
                                                <i class="ri-checkbox-circle-line text-blue-500 text-lg lesson-status" 
                                                   id="lesson-status-{{ $lesson->id }}"></i>
                                            @else
                                                <i class="ri-checkbox-blank-circle-line text-gray-300 text-lg lesson-status" 
                                                   id="lesson-status-{{ $lesson->id }}"></i>
                                            @endif
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="text-center py-8">
                            <p class="text-gray-600 text-sm">لا توجد دروس متاحة حالياً</p>
                        </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Animated counter function
function animateCounter(element, start, end, duration = 1000) {
    const startTime = performance.now();
    const difference = end - start;
    
    function updateCounter(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        // Easing function for smooth animation
        const easeOut = 1 - Math.pow(1 - progress, 3);
        const current = Math.round(start + (difference * easeOut));
        
        element.textContent = current;
        
        if (progress < 1) {
            requestAnimationFrame(updateCounter);
        } else {
            element.textContent = end;
        }
    }
    
    requestAnimationFrame(updateCounter);
}

// Animate progress bar
function animateProgressBar(targetWidth, duration = 1000) {
    const progressBar = document.getElementById('progress-bar');
    if (!progressBar) return;
    
    const startWidth = parseFloat(progressBar.style.width) || 0;
    const startTime = performance.now();
    const difference = targetWidth - startWidth;
    
    function updateProgress(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        // Easing function for smooth animation
        const easeOut = 1 - Math.pow(1 - progress, 3);
        const currentWidth = startWidth + (difference * easeOut);
        
        progressBar.style.width = currentWidth + '%';
        
        if (progress < 1) {
            requestAnimationFrame(updateProgress);
        } else {
            progressBar.style.width = targetWidth + '%';
        }
    }
    
    requestAnimationFrame(updateProgress);
}

let currentLessonId = null;

function toggleSection(sectionIndex) {
    const content = document.getElementById(`section-content-${sectionIndex}`);
    const arrow = document.getElementById(`section-arrow-${sectionIndex}`);
    
    if (content.classList.contains('hidden')) {
        content.classList.remove('hidden');
        arrow.classList.add('rotate-180');
    } else {
        content.classList.add('hidden');
        arrow.classList.remove('rotate-180');
    }
}

function playLesson(lessonId, lessonTitle) {
    currentLessonId = lessonId;
    
    // Update video player area with actual video player
    const videoArea = document.querySelector('.aspect-video');
    videoArea.innerHTML = `
        <video id="lesson-video" 
               class="w-full h-full" 
               controls
               preload="metadata"
               playsinline
               webkit-playsinline>
            <source src="/courses/{{ $course->id }}/lessons/${lessonId}/video" type="video/mp4">
            <div class="text-center text-white">
                <i class="ri-video-line text-4xl mb-4"></i>
                <p>متصفحك لا يدعم تشغيل الفيديو</p>
            </div>
        </video>
    `;
    
    // Update video player description
    const videoDescription = videoArea.nextElementSibling;
    if (videoDescription) {
        videoDescription.innerHTML = `
            <h2 class="text-xl font-bold text-gray-900 mb-2">${lessonTitle}</h2>
            <p class="text-gray-600">شاهد الدرس وتابع تقدمك في التعلم</p>
        `;
    }
    
    // Highlight current lesson
    document.querySelectorAll('[data-lesson-id]').forEach(el => {
        el.classList.remove('bg-blue-50', 'border-blue-200');
    });
    
    const currentLessonEl = document.querySelector(`[data-lesson-id="${lessonId}"]`);
    if (currentLessonEl) {
        currentLessonEl.classList.add('bg-blue-50', 'border-blue-200');
    }
    
    // Add video event listeners
    const video = document.getElementById('lesson-video');
    if (video) {
        let progressInterval;
        
        video.addEventListener('loadedmetadata', function() {
            console.log('Video metadata loaded, duration:', video.duration);
            console.log('Loading progress for lesson:', lessonId);
            // Load saved progress if available
            loadLessonProgress(lessonId);
        });
        
        video.addEventListener('play', function() {
            console.log('Video started playing, starting progress tracking');
            // Start progress tracking
            progressInterval = setInterval(function() {
                if (!video.paused && !video.ended) {
                    console.log('Updating progress - current time:', video.currentTime, 'duration:', video.duration);
                    updateLessonProgress(lessonId, video.currentTime, video.duration);
                }
            }, 5000); // Update every 5 seconds
        });
        
        video.addEventListener('pause', function() {
            // Stop progress tracking
            if (progressInterval) {
                clearInterval(progressInterval);
            }
            // Save current progress
            updateLessonProgress(lessonId, video.currentTime, video.duration);
        });
        
        video.addEventListener('ended', function() {
            console.log('Video ended, marking lesson complete and playing next');
            // Clear progress tracking
            if (progressInterval) {
                clearInterval(progressInterval);
            }
            // Mark lesson as complete
            markLessonComplete(lessonId);
            // Auto-play next lesson
            setTimeout(() => {
                playNextLesson(lessonId);
            }, 2000); // Wait 2 seconds before auto-playing next
        });
        
        video.addEventListener('error', function(e) {
            console.error('Video error:', e);
            if (progressInterval) {
                clearInterval(progressInterval);
            }
            videoArea.innerHTML = `
                <div class="text-center text-white">
                    <i class="ri-video-line text-4xl mb-4"></i>
                    <h3 class="text-xl font-semibold mb-2">خطأ في تشغيل الفيديو</h3>
                    <p class="text-gray-300">يرجى المحاولة مرة أخرى أو زيارة صفحة الدرس</p>
                    <a href="#" onclick="window.location.href='/courses/{{ $course->id }}/lessons/' + ${lessonId}" 
                       class="mt-4 inline-block bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                        <i class="ri-eye-line ml-1"></i>
                        عرض الدرس
                    </a>
                </div>
            `;
        });
    }
}

function markLessonComplete(lessonId) {
    console.log('Marking lesson complete:', lessonId);
    // Update lesson status
    const statusIcon = document.getElementById(`lesson-status-${lessonId}`);
    if (statusIcon) {
        statusIcon.className = 'ri-checkbox-circle-fill text-green-500 text-sm lesson-status';
    }
    
    // Make API call to mark lesson as complete
    fetch(`/api/courses/{{ $course->id }}/lessons/${lessonId}/complete`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        console.log('Lesson marked as complete:', data);
        if (data.success) {
            showNotification('تم إكمال الدرس بنجاح!', 'success');
            // Update progress bar with animation after a short delay
            setTimeout(() => {
                updateCourseProgress();
            }, 500);
        } else {
            showNotification('حدث خطأ في حفظ التقدم', 'error');
        }
    })
    .catch(error => {
        console.error('Error marking lesson complete:', error);
    });
}

function updateLessonProgress(lessonId, currentTime, totalTime) {
    const progressPercentage = totalTime > 0 ? (currentTime / totalTime) * 100 : 0;
    
    fetch(`/api/courses/{{ $course->id }}/lessons/${lessonId}/progress`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            current_time: currentTime,
            total_time: totalTime,
            progress_percentage: progressPercentage
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            console.log('Progress updated successfully');
        }
    })
    .catch(error => {
        console.error('Error updating progress:', error);
    });
}

function loadLessonProgress(lessonId) {
    console.log('Loading lesson progress for lesson:', lessonId);
    console.log('CSRF Token:', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'));
    
    fetch(`/api/courses/{{ $course->id }}/lessons/${lessonId}/progress`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('Lesson progress response status:', response.status);
        console.log('Lesson progress response headers:', response.headers);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Lesson progress data:', data);
        if (data.success && data.progress) {
            const video = document.getElementById('lesson-video');
            if (video && data.progress.current_position_seconds > 0) {
                video.currentTime = data.progress.current_position_seconds;
                console.log('Loaded saved progress:', data.progress.current_position_seconds, 'seconds');
            }
        }
    })
    .catch(error => {
        console.error('Error loading progress:', error);
    });
}

function playNextLesson(currentLessonId) {
    const lessons = document.querySelectorAll('[data-lesson-id]');
    let nextLessonId = null;
    let nextLessonTitle = '';
    
    for (let i = 0; i < lessons.length; i++) {
        const lessonId = parseInt(lessons[i].getAttribute('data-lesson-id'));
        if (lessonId === currentLessonId && i + 1 < lessons.length) {
            nextLessonId = parseInt(lessons[i + 1].getAttribute('data-lesson-id'));
            nextLessonTitle = lessons[i + 1].querySelector('h5').textContent;
            break;
        }
    }
    
    if (nextLessonId) {
        // Show notification
        showNotification(`بدء الدرس التالي: ${nextLessonTitle}`, 'info');
        // Play next lesson
        setTimeout(() => {
            playLesson(nextLessonId, nextLessonTitle);
        }, 1000);
    } else {
        // No more lessons
        showNotification('تهانينا! لقد أكملت جميع دروس هذه الدورة', 'success');
    }
}

function updateCourseProgress() {
    console.log('Updating course progress');
    console.log('CSRF Token:', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'));
    
    fetch(`/api/courses/{{ $course->id }}/progress`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('Course progress response status:', response.status);
        console.log('Course progress response headers:', response.headers);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Course progress data:', data);
        if (data.success) {
            const progressPercentageElement = document.getElementById('progress-percentage');
            const completedLessonsElement = document.getElementById('completed-lessons');
            const remainingLessonsElement = document.getElementById('remaining-lessons');
            
            if (progressPercentageElement) {
                const currentPercentage = parseInt(progressPercentageElement.textContent) || 0;
                const newPercentage = Math.round(data.progress_percentage);
                animateCounter(progressPercentageElement, currentPercentage, newPercentage, 1000);
            }
            
            const newPercentage = Math.round(data.progress_percentage);
            animateProgressBar(newPercentage, 1000);
            
            if (completedLessonsElement) {
                completedLessonsElement.textContent = `${data.completed_lessons} من ${data.total_lessons} درس مكتمل`;
            }
            if (remainingLessonsElement) {
                remainingLessonsElement.textContent = `${data.total_lessons - data.completed_lessons} درس متبقي`;
            }
        }
    })
    .catch(error => {
        console.error('Error updating course progress:', error);
    });
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg text-white ${
        type === 'success' ? 'bg-green-600' : 
        type === 'error' ? 'bg-red-600' : 
        'bg-blue-600'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Remove notification after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

function startNextLesson() {
    const lessons = document.querySelectorAll('[data-lesson-id]');
    let nextLessonId = null;
    let nextLessonTitle = '';
    
    // Find the first incomplete lesson
    for (let i = 0; i < lessons.length; i++) {
        const lessonId = parseInt(lessons[i].getAttribute('data-lesson-id'));
        const statusIcon = document.getElementById(`lesson-status-${lessonId}`);
        
        // Check if lesson is not completed (not green filled circle)
        if (statusIcon && !statusIcon.classList.contains('ri-checkbox-circle-fill')) {
            nextLessonId = lessonId;
            nextLessonTitle = lessons[i].querySelector('h5').textContent;
            break;
        }
    }
    
    if (nextLessonId) {
        playLesson(nextLessonId, nextLessonTitle);
        showNotification(`بدء الدرس: ${nextLessonTitle}`, 'info');
    } else {
        showNotification('تهانينا! لقد أكملت جميع دروس هذه الدورة', 'success');
    }
}

function playNextLesson() {
    const lessons = document.querySelectorAll('[data-lesson-id]');
    let nextLessonId = null;
    let nextLessonTitle = '';
    
    // Find the first incomplete lesson
    for (let i = 0; i < lessons.length; i++) {
        const lessonId = parseInt(lessons[i].getAttribute('data-lesson-id'));
        const statusIcon = document.getElementById(`lesson-status-${lessonId}`);
        
        // Check if lesson is not completed (not green filled circle)
        if (statusIcon && !statusIcon.classList.contains('ri-checkbox-circle-fill')) {
            nextLessonId = lessonId;
            nextLessonTitle = lessons[i].querySelector('h5').textContent;
            break;
        }
    }
    
    if (nextLessonId) {
        playLesson(nextLessonId, nextLessonTitle);
        showNotification(`تشغيل الدرس: ${nextLessonTitle}`, 'info');
    } else {
        showNotification('تهانينا! لقد أكملت جميع دروس هذه الدورة', 'success');
    }
}

function toggleLessonStatus(lessonId) {
    const statusIcon = document.getElementById(`lesson-status-${lessonId}`);
    if (!statusIcon) return;
    
    const isCurrentlyCompleted = statusIcon.classList.contains('ri-checkbox-circle-fill');
    
    // Show loading state
    const originalClass = statusIcon.className;
    statusIcon.className = 'ri-loader-4-line text-gray-400 text-lg animate-spin';
    
    // Make API call to toggle completion status
    const url = `/api/courses/{{ $course->id }}/lessons/${lessonId}/toggle`;
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the icon based on new status from API response
            if (data.progress.is_completed) {
                statusIcon.className = 'ri-checkbox-circle-fill text-green-500 text-2xl lesson-status';
            } else {
                // Check if there's any progress to determine the appropriate icon
                if (data.progress.progress_percentage > 0) {
                    statusIcon.className = 'ri-checkbox-circle-line text-blue-500 text-lg lesson-status';
                } else {
                    statusIcon.className = 'ri-checkbox-blank-circle-line text-gray-300 text-lg lesson-status';
                }
            }
            
            // Update course progress
            updateCourseProgress();
            
            // Show notification
            const message = data.progress.is_completed ? 'تم إكمال الدرس بنجاح!' : 'تم إلغاء إكمال الدرس';
            showNotification(message, 'success');
            
            // Update next lesson button visibility
            updateNextLessonButtonVisibility();
        } else {
            // Revert to original state on error
            statusIcon.className = originalClass;
            showNotification('حدث خطأ في تحديث حالة الدرس', 'error');
        }
    })
    .catch(error => {
        console.error('Error toggling lesson status:', error);
        // Revert to original state on error
        statusIcon.className = originalClass;
        showNotification('حدث خطأ في تحديث حالة الدرس', 'error');
    });
}

function updateNextLessonButtonVisibility() {
    const playNextBtn = document.getElementById('play-next-lesson-btn');
    const startNextBtn = document.getElementById('start-next-lesson-btn');
    
    const lessons = document.querySelectorAll('[data-lesson-id]');
    let hasIncompleteLesson = false;
    
    for (let i = 0; i < lessons.length; i++) {
        const lessonId = parseInt(lessons[i].getAttribute('data-lesson-id'));
        const statusIcon = document.getElementById(`lesson-status-${lessonId}`);
        
        if (statusIcon && !statusIcon.classList.contains('ri-checkbox-circle-fill')) {
            hasIncompleteLesson = true;
            break;
        }
    }
    
    if (hasIncompleteLesson) {
        if (playNextBtn) playNextBtn.classList.remove('hidden');
        if (startNextBtn) startNextBtn.classList.remove('hidden');
    } else {
        if (playNextBtn) playNextBtn.classList.add('hidden');
        if (startNextBtn) startNextBtn.classList.add('hidden');
    }
}

// Auto-expand first section
document.addEventListener('DOMContentLoaded', function() {
    // Animate initial progress on page load
    const progressPercentageElement = document.getElementById('progress-percentage');
    const progressBar = document.getElementById('progress-bar');
    
    if (progressPercentageElement && progressBar) {
        // Get the actual progress value from the server data
        const finalPercentage = {{ round(($completedLessons / max($totalLessons, 1)) * 100) }};
        
        // Only animate if there's actual progress to show
        if (finalPercentage > 0) {
            // Animate both counter and progress bar after a short delay
            setTimeout(() => {
                animateCounter(progressPercentageElement, 0, finalPercentage, 1500);
                animateProgressBar(finalPercentage, 1500);
            }, 300);
        }
    }
    
    // Initialize next lesson button visibility
    updateNextLessonButtonVisibility();
    
    if (document.getElementById('section-content-0')) {
        toggleSection(0);
    }
});
</script>
@endsection
