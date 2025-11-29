@props(['quizData'])

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow duration-300">
    <div class="flex flex-col md:flex-row">
        <!-- Left Section: Icon & Status -->
        <div class="md:w-24 lg:w-32 bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center p-4 md:p-0 relative">
            <i class="ri-file-list-3-fill text-4xl text-white/50"></i>
            <!-- Mobile Status Badge -->
            <div class="absolute top-2 left-2 md:hidden">
                @if($quizData->passed)
                    <span class="inline-flex items-center px-2 py-1 bg-white/95 rounded-full text-xs font-medium text-green-700">
                        <i class="ri-check-line ml-1"></i>
                        ناجح
                    </span>
                @elseif($quizData->in_progress_attempt)
                    <span class="inline-flex items-center px-2 py-1 bg-white/95 rounded-full text-xs font-medium text-yellow-700">
                        <i class="ri-time-line ml-1"></i>
                        قيد التقدم
                    </span>
                @elseif($quizData->completed_attempts > 0)
                    <span class="inline-flex items-center px-2 py-1 bg-white/95 rounded-full text-xs font-medium text-red-700">
                        <i class="ri-close-line ml-1"></i>
                        لم ينجح
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-1 bg-white/95 rounded-full text-xs font-medium text-blue-700">
                        <i class="ri-star-line ml-1"></i>
                        جديد
                    </span>
                @endif
            </div>
        </div>

        <!-- Middle Section: Content -->
        <div class="flex-1 p-4 md:p-5">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                <!-- Title & Info -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="font-bold text-gray-900 text-lg truncate">{{ $quizData->quiz->title }}</h3>
                        <!-- Desktop Status Badge -->
                        <div class="hidden md:block flex-shrink-0">
                            @if($quizData->passed)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="ri-check-line ml-1"></i>
                                    ناجح
                                </span>
                            @elseif($quizData->in_progress_attempt)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <i class="ri-time-line ml-1"></i>
                                    قيد التقدم
                                </span>
                            @elseif($quizData->completed_attempts > 0)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <i class="ri-close-line ml-1"></i>
                                    لم ينجح
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <i class="ri-star-line ml-1"></i>
                                    جديد
                                </span>
                            @endif
                        </div>
                    </div>

                    @if($quizData->quiz->description)
                        <p class="text-gray-600 text-sm mt-1 line-clamp-1">{{ $quizData->quiz->description }}</p>
                    @endif

                    <!-- Stats Row -->
                    <div class="flex flex-wrap items-center gap-3 mt-3 text-sm text-gray-500">
                        <span class="inline-flex items-center">
                            <i class="ri-question-line ml-1 text-gray-400"></i>
                            {{ $quizData->quiz->questions->count() }} سؤال
                        </span>
                        <span class="inline-flex items-center">
                            <i class="ri-percent-line ml-1 text-gray-400"></i>
                            درجة النجاح: {{ $quizData->quiz->passing_score }}%
                        </span>
                        @if($quizData->quiz->duration_minutes)
                            <span class="inline-flex items-center">
                                <i class="ri-time-line ml-1 text-gray-400"></i>
                                {{ $quizData->quiz->duration_minutes }} دقيقة
                            </span>
                        @endif
                        <span class="inline-flex items-center">
                            <i class="ri-restart-line ml-1 text-gray-400"></i>
                            المحاولات: {{ $quizData->completed_attempts }}/{{ $quizData->assignment->max_attempts }}
                        </span>
                        @if($quizData->best_score !== null)
                            <span class="inline-flex items-center {{ $quizData->passed ? 'text-green-600' : 'text-gray-600' }} font-medium">
                                <i class="ri-trophy-line ml-1"></i>
                                أفضل درجة: {{ $quizData->best_score }}%
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Action Button -->
                <div class="flex-shrink-0 lg:mr-4">
                    @if($quizData->in_progress_attempt)
                        <a href="{{ route('student.quiz.take', ['subdomain' => $subdomain, 'attempt_id' => $quizData->in_progress_attempt->id]) }}"
                           class="inline-flex items-center justify-center px-5 py-2.5 bg-yellow-500 hover:bg-yellow-600 text-white font-medium rounded-lg transition-colors w-full lg:w-auto">
                            <i class="ri-play-fill ml-2"></i>
                            متابعة الاختبار
                        </a>
                    @elseif($quizData->can_attempt)
                        <a href="{{ route('student.quiz.start', ['subdomain' => $subdomain, 'quiz_id' => $quizData->assignment->id]) }}"
                           class="inline-flex items-center justify-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors w-full lg:w-auto">
                            <i class="ri-play-circle-line ml-2"></i>
                            {{ $quizData->completed_attempts > 0 ? 'إعادة الاختبار' : 'بدء الاختبار' }}
                        </a>
                    @elseif($quizData->completed_attempts > 0)
                        <a href="{{ route('student.quiz.result', ['subdomain' => $subdomain, 'quiz_id' => $quizData->assignment->id]) }}"
                           class="inline-flex items-center justify-center px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors w-full lg:w-auto">
                            <i class="ri-eye-line ml-2"></i>
                            عرض النتيجة
                        </a>
                    @else
                        <button disabled
                                class="inline-flex items-center justify-center px-5 py-2.5 bg-gray-100 text-gray-400 font-medium rounded-lg cursor-not-allowed w-full lg:w-auto">
                            <i class="ri-lock-line ml-2"></i>
                            غير متاح
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
