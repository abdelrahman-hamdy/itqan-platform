@props(['quizData'])

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow duration-300">
    <!-- Card Header with Gradient Background -->
    <div class="h-32 relative overflow-hidden bg-gradient-to-br from-blue-500 to-indigo-600">
        <!-- Quiz Icon -->
        <div class="absolute inset-0 flex items-center justify-center">
            <i class="ri-file-list-3-fill text-5xl text-white/30"></i>
        </div>

        <!-- Status Badge -->
        <div class="absolute top-4 right-4">
            @if($quizData->passed)
                <span class="inline-flex items-center px-3 py-1.5 bg-white/95 backdrop-blur-sm rounded-full text-sm font-medium text-green-700">
                    <i class="ri-check-line ms-1.5 text-green-600"></i>
                    ناجح
                </span>
            @elseif($quizData->in_progress_attempt)
                <span class="inline-flex items-center px-3 py-1.5 bg-white/95 backdrop-blur-sm rounded-full text-sm font-medium text-yellow-700">
                    <i class="ri-time-line ms-1.5 text-yellow-600"></i>
                    قيد التقدم
                </span>
            @elseif($quizData->completed_attempts > 0)
                <span class="inline-flex items-center px-3 py-1.5 bg-white/95 backdrop-blur-sm rounded-full text-sm font-medium text-red-700">
                    <i class="ri-close-line ms-1.5 text-red-600"></i>
                    لم ينجح
                </span>
            @else
                <span class="inline-flex items-center px-3 py-1.5 bg-white/95 backdrop-blur-sm rounded-full text-sm font-medium text-blue-700">
                    <i class="ri-star-line ms-1.5 text-blue-600"></i>
                    جديد
                </span>
            @endif
        </div>
    </div>

    <!-- Card Body -->
    <div class="p-5">
        <!-- Quiz Title -->
        <div class="mb-4">
            <h3 class="font-bold text-gray-900 text-lg mb-1">{{ $quizData->quiz->title }}</h3>
            @if($quizData->assignable_name ?? null)
            <p class="text-sm text-gray-500 flex items-center">
                <i class="ri-bookmark-line ms-1"></i>
                {{ $quizData->assignable_name }}
            </p>
            @endif
        </div>

        @if($quizData->quiz->description)
            <p class="text-gray-600 text-sm mb-4 line-clamp-2">{{ $quizData->quiz->description }}</p>
        @endif

        <!-- Stats Grid -->
        <div class="grid grid-cols-2 gap-2 mb-4 pb-4 border-b border-gray-100">
            <div class="text-center p-2 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500">عدد الأسئلة</p>
                <p class="font-bold text-gray-900">{{ $quizData->quiz->questions->count() }}</p>
            </div>
            <div class="text-center p-2 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500">درجة النجاح</p>
                <p class="font-bold text-gray-900">{{ $quizData->quiz->passing_score }}%</p>
            </div>
            @if($quizData->quiz->duration_minutes)
            <div class="text-center p-2 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500">المدة</p>
                <p class="font-bold text-gray-900">{{ $quizData->quiz->duration_minutes }} د</p>
            </div>
            @endif
            <div class="text-center p-2 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500">المحاولات</p>
                <p class="font-bold text-gray-900">{{ $quizData->completed_attempts }}/{{ $quizData->assignment->max_attempts }}</p>
            </div>
        </div>

        <!-- Best Score (if exists) -->
        @if($quizData->best_score !== null)
            <div class="mb-4 p-3 rounded-lg {{ $quizData->passed ? 'bg-green-50' : 'bg-gray-50' }}">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">أفضل درجة</span>
                    <span class="font-bold text-lg {{ $quizData->passed ? 'text-green-600' : 'text-gray-900' }}">{{ $quizData->best_score }}%</span>
                </div>
            </div>
        @endif

        <!-- Action Buttons -->
        <div class="flex gap-2">
            @if($quizData->in_progress_attempt)
                <a href="{{ route('student.quiz.take', ['subdomain' => $subdomain, 'attempt_id' => $quizData->in_progress_attempt->id]) }}"
                   class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-yellow-500 hover:bg-yellow-600 text-white font-medium rounded-lg transition-colors">
                    <i class="ri-play-fill ms-2"></i>
                    متابعة الاختبار
                </a>
            @elseif($quizData->can_attempt)
                <a href="{{ route('student.quiz.start', ['subdomain' => $subdomain, 'quiz_id' => $quizData->assignment->id]) }}"
                   class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                    <i class="ri-play-circle-line ms-2"></i>
                    {{ $quizData->completed_attempts > 0 ? 'إعادة الاختبار' : 'بدء الاختبار' }}
                </a>
            @elseif($quizData->completed_attempts > 0)
                <a href="{{ route('student.quiz.result', ['subdomain' => $subdomain, 'quiz_id' => $quizData->assignment->id]) }}"
                   class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors">
                    <i class="ri-eye-line ms-2"></i>
                    عرض النتيجة
                </a>
            @else
                <button disabled
                        class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-gray-100 text-gray-400 font-medium rounded-lg cursor-not-allowed">
                    <i class="ri-lock-line ms-2"></i>
                    غير متاح
                </button>
            @endif
        </div>
    </div>
</div>
