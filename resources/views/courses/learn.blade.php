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
            <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $course->title }}</h1>
                        <p class="text-gray-600">تعلم بالسرعة التي تناسبك</p>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-600 mb-1">التقدم الإجمالي</div>
                        <div class="text-2xl font-bold text-primary">
                            {{ round(($completedLessons / max($totalLessons, 1)) * 100) }}%
                        </div>
                    </div>
                </div>
                
                <!-- Progress Bar -->
                <div class="mt-4">
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-primary h-2 rounded-full transition-all duration-300" 
                             style="width: {{ round(($completedLessons / max($totalLessons, 1)) * 100) }}%"></div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                        <span>{{ $completedLessons }} من {{ $totalLessons }} درس مكتمل</span>
                        <span>{{ $totalLessons - $completedLessons }} درس متبقي</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <!-- Course Content -->
                <div class="lg:col-span-3">
                    <!-- Video Player Area -->
                    <div class="bg-white rounded-xl shadow-sm mb-8">
                        <div class="aspect-video bg-gray-900 rounded-t-xl flex items-center justify-center">
                            <div class="text-center text-white">
                                <i class="ri-play-circle-line text-6xl mb-4"></i>
                                <h3 class="text-xl font-semibold mb-2">مشغل الفيديو</h3>
                                <p class="text-gray-300">اختر درساً من القائمة لبدء المشاهدة</p>
                            </div>
                        </div>
                        <div class="p-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-2">مرحباً بك في دورة {{ $course->title }}</h2>
                            <p class="text-gray-600">اختر أي درس من القائمة الجانبية لبدء التعلم. يمكنك متابعة التقدم والعودة في أي وقت.</p>
                        </div>
                    </div>

                    <!-- Lesson Notes -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">ملاحظاتي</h3>
                        <div class="border border-gray-200 rounded-lg p-4 min-h-[120px]">
                            <textarea 
                                class="w-full h-full border-none resize-none focus:outline-none" 
                                placeholder="اكتب ملاحظاتك هنا..."
                                id="lesson-notes"></textarea>
                        </div>
                        <div class="flex justify-end mt-4">
                            <button class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="ri-save-line ml-1"></i>
                                حفظ الملاحظات
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <!-- Course Curriculum -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">محتوى الدورة</h3>
                        
                        @if($course->lessons->count() > 0)
                        <div class="space-y-2">
                            @foreach($course->lessons->sortBy('id') as $index => $lesson)
                            <div class="p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors" 
                                 onclick="playLesson({{ $lesson->id }}, '{{ $lesson->title }}')" 
                                 data-lesson-id="{{ $lesson->id }}">
                                <div class="flex items-center gap-3">
                                    <!-- Lesson Number -->
                                    <div class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center flex-shrink-0">
                                        <span class="text-xs font-bold text-primary">{{ $index + 1 }}</span>
                                    </div>
                                    
                                    <!-- Play Icon -->
                                    <div class="w-6 h-6 bg-gray-100 rounded-full flex items-center justify-center flex-shrink-0">
                                        <i class="ri-play-circle-line text-primary text-sm"></i>
                                    </div>
                                    
                                    <div class="flex-1 min-w-0">
                                        <h5 class="font-medium text-gray-900 text-sm truncate">{{ $lesson->title }}</h5>
                                        @if($lesson->description)
                                            <p class="text-xs text-gray-600 truncate">{{ Str::limit($lesson->description, 80) }}</p>
                                        @endif
                                        @if($lesson->video_duration_seconds)
                                        <div class="text-xs text-gray-500">
                                            {{ gmdate('i:s', $lesson->video_duration_seconds) }} دقيقة
                                        </div>
                                        @endif
                                    </div>
                                    <div class="flex-shrink-0">
                                        <i class="ri-checkbox-blank-circle-line text-gray-300 text-sm lesson-status" 
                                           id="lesson-status-{{ $lesson->id }}"></i>
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

                    <!-- Course Stats -->
                    <div class="bg-white rounded-xl shadow-sm p-6 mt-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">إحصائيات التعلم</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">الدروس المكتملة</span>
                                <span class="font-semibold text-primary">{{ $completedLessons }}/{{ $totalLessons }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">وقت المشاهدة</span>
                                <span class="font-semibold text-gray-900">{{ $enrollment->watch_time_minutes ?? 0 }} دقيقة</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">تاريخ التسجيل</span>
                                <span class="font-semibold text-gray-900">{{ $enrollment->enrolled_at->format('Y/m/d') }}</span>
                            </div>
                            @if($enrollment->certificate_eligible)
                            <div class="pt-4 border-t border-gray-200">
                                <button class="w-full bg-green-600 text-white py-2 px-4 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
                                    <i class="ri-award-line ml-1"></i>
                                    تحميل الشهادة
                                </button>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
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
    
    // Update video player area
    const videoArea = document.querySelector('.aspect-video');
    videoArea.innerHTML = `
        <div class="text-center text-white">
            <i class="ri-play-circle-line text-6xl mb-4"></i>
            <h3 class="text-xl font-semibold mb-2">تشغيل: ${lessonTitle}</h3>
            <p class="text-gray-300">جاري تحضير الدرس...</p>
        </div>
    `;
    
    // Highlight current lesson
    document.querySelectorAll('[data-lesson-id]').forEach(el => {
        el.classList.remove('bg-blue-50', 'border-blue-200');
    });
    
    const currentLessonEl = document.querySelector(`[data-lesson-id="${lessonId}"]`);
    if (currentLessonEl) {
        currentLessonEl.classList.add('bg-blue-50', 'border-blue-200');
    }
    
    // Simulate lesson loading
    setTimeout(() => {
        videoArea.innerHTML = `
            <div class="text-center text-white">
                <i class="ri-video-line text-6xl mb-4"></i>
                <h3 class="text-xl font-semibold mb-2">${lessonTitle}</h3>
                <p class="text-gray-300">مشغل الفيديو سيكون متاحاً قريباً</p>
                <button onclick="markLessonComplete(${lessonId})" 
                        class="mt-4 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                    <i class="ri-check-line ml-1"></i>
                    تمت المشاهدة
                </button>
            </div>
        `;
    }, 1000);
}

function markLessonComplete(lessonId) {
    // Update lesson status
    const statusIcon = document.getElementById(`lesson-status-${lessonId}`);
    if (statusIcon) {
        statusIcon.className = 'ri-checkbox-circle-fill text-green-500 text-sm lesson-status';
    }
    
    // Show success message
    alert('تم تسجيل إكمال الدرس!');
    
    // Here you would typically make an API call to update progress
    // updateLessonProgress(lessonId);
}

// Auto-expand first section
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('section-content-0')) {
        toggleSection(0);
    }
});
</script>
@endsection
