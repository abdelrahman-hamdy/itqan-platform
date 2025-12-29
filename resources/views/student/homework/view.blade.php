<x-layouts.student title="تفاصيل الواجب">
    <div class="space-y-4 md:space-y-6">
        <!-- Back Button -->
        <div>
            <a href="{{ route('student.homework.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="inline-flex items-center min-h-[44px] text-blue-600 hover:text-blue-800 transition-colors text-sm md:text-base">
                <i class="ri-arrow-right-line ml-1"></i>
                العودة إلى قائمة الواجبات
            </a>
        </div>

        @if(isset($homework) && isset($submission))
            <div class="space-y-4 md:space-y-6">
                <!-- Homework Header -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4 mb-4">
                        <div class="flex-1 min-w-0">
                            <h1 class="text-xl md:text-2xl font-bold text-gray-900 mb-2">{{ $homework['title'] }}</h1>

                            <div class="flex flex-wrap items-center gap-2 md:gap-3">
                                <!-- Type Badge -->
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                    {{ $homework['type'] === 'academic' ? 'bg-purple-100 text-purple-800' :
                                       ($homework['type'] === 'quran' ? 'bg-green-100 text-green-800' :
                                       'bg-blue-100 text-blue-800') }}">
                                    <i class="{{ $homework['type'] === 'academic' ? 'ri-book-line' :
                                                 ($homework['type'] === 'quran' ? 'ri-book-open-line' :
                                                 'ri-presentation-line') }} ml-1"></i>
                                    {{ $homework['type'] === 'academic' ? 'أكاديمي' :
                                       ($homework['type'] === 'quran' ? 'قرآن' :
                                       'دورة تفاعلية') }}
                                </span>

                                <!-- Status Badge -->
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                    {{ $homework['status'] === 'not_submitted' ? 'bg-gray-100 text-gray-800' :
                                       ($homework['status'] === 'draft' ? 'bg-yellow-100 text-yellow-800' :
                                       (in_array($homework['status'], ['submitted', 'late']) ? 'bg-blue-100 text-blue-800' :
                                       'bg-green-100 text-green-800')) }}">
                                    {{ $homework['status_text'] }}
                                </span>

                                @if($homework['is_late'])
                                <span class="inline-flex items-center px-2 py-1 rounded text-sm font-medium bg-red-100 text-red-800">
                                    <i class="ri-error-warning-line ml-1"></i>
                                    متأخر
                                </span>
                                @endif
                            </div>
                        </div>

                        @if($homework['score'] !== null)
                            <div class="text-center bg-gray-50 rounded-xl p-3 md:p-4 md:mr-4 w-full md:w-auto">
                                <div class="text-3xl md:text-4xl font-bold {{ $homework['score_percentage'] >= 80 ? 'text-green-600' :
                                                                   ($homework['score_percentage'] >= 60 ? 'text-yellow-600' :
                                                                   'text-red-600') }}">
                                    {{ number_format($homework['score_percentage'], 1) }}%
                                </div>
                                <div class="text-xs md:text-sm text-gray-600 mt-1">
                                    {{ $homework['score'] }}/{{ $homework['max_score'] }}
                                </div>
                            </div>
                        @endif
                    </div>

                    @if($homework['description'])
                        <div class="mt-4 p-3 md:p-4 bg-gray-50 rounded-xl">
                            <p class="text-sm font-medium text-gray-700 mb-2">وصف الواجب:</p>
                            <p class="text-sm md:text-base text-gray-800">{{ $homework['description'] }}</p>
                        </div>
                    @endif
                </div>

                <!-- Dates Info -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 md:gap-4">
                    @if($homework['due_date'])
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                        <div class="flex items-center">
                            <div class="bg-blue-100 rounded-full p-3 ml-3">
                                <i class="ri-calendar-line text-blue-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">موعد التسليم</p>
                                <p class="font-semibold text-gray-900">{{ $homework['due_date']->format('Y-m-d') }}</p>
                                <p class="text-xs text-gray-500">{{ $homework['due_date']->format('h:i A') }}</p>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($homework['submitted_at'])
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                        <div class="flex items-center">
                            <div class="bg-green-100 rounded-full p-3 ml-3">
                                <i class="ri-send-plane-line text-green-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">تم التسليم</p>
                                <p class="font-semibold text-gray-900">{{ $homework['submitted_at']->format('Y-m-d') }}</p>
                                <p class="text-xs text-gray-500">{{ $homework['submitted_at']->format('h:i A') }}</p>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($homework['graded_at'])
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                        <div class="flex items-center">
                            <div class="bg-purple-100 rounded-full p-3 ml-3">
                                <i class="ri-star-line text-purple-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">تم التصحيح</p>
                                <p class="font-semibold text-gray-900">{{ $homework['graded_at']->format('Y-m-d') }}</p>
                                <p class="text-xs text-gray-500">{{ $homework['graded_at']->format('h:i A') }}</p>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Submission Details -->
                @if($submission && $submission->submission_text)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                    <h3 class="text-base md:text-lg font-semibold text-gray-900 mb-3 md:mb-4 flex items-center">
                        <i class="ri-file-text-line text-blue-600 ml-2"></i>
                        إجابة الطالب
                    </h3>
                    <div class="prose prose-sm max-w-none">
                        <div class="p-3 md:p-4 bg-gray-50 rounded-xl">
                            <p class="text-sm md:text-base text-gray-800 whitespace-pre-wrap">{{ $submission->submission_text }}</p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Submitted Files -->
                @if($submission && $submission->submission_files && count($submission->submission_files) > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                    <h3 class="text-base md:text-lg font-semibold text-gray-900 mb-3 md:mb-4 flex items-center">
                        <i class="ri-attachment-line text-blue-600 ml-2"></i>
                        الملفات المرفقة
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($submission->submission_files as $file)
                            <a href="{{ Storage::url($file['path']) }}"
                               target="_blank"
                               class="flex items-center min-h-[56px] p-3 bg-gray-50 hover:bg-gray-100 rounded-xl transition-colors group">
                                <div class="bg-blue-100 rounded-lg p-2 ml-3">
                                    <i class="ri-file-line text-blue-600 text-lg md:text-xl"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate group-hover:text-blue-600">
                                        {{ $file['original_name'] ?? 'ملف مرفق' }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ number_format(($file['size'] ?? 0) / 1024, 2) }} KB
                                    </p>
                                </div>
                                <i class="ri-external-link-line text-gray-400 group-hover:text-blue-600"></i>
                            </a>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Teacher Feedback -->
                @if($homework['teacher_feedback'])
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl shadow-sm border border-blue-200 p-4 md:p-6">
                    <h3 class="text-base md:text-lg font-semibold text-blue-900 mb-3 md:mb-4 flex items-center">
                        <i class="ri-feedback-line text-blue-600 ml-2"></i>
                        ملاحظات المعلم
                    </h3>
                    <div class="bg-white rounded-xl p-3 md:p-4">
                        <p class="text-sm md:text-base text-gray-800 leading-relaxed whitespace-pre-wrap">{{ $homework['teacher_feedback'] }}</p>
                    </div>
                </div>
                @endif

                <!-- Quality Scores (if available for academic homework) -->
                @if($submission && ($submission->content_quality_score || $submission->presentation_score || $submission->effort_score))
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                    <h3 class="text-base md:text-lg font-semibold text-gray-900 mb-3 md:mb-4 flex items-center">
                        <i class="ri-bar-chart-line text-purple-600 ml-2"></i>
                        تقييم الجودة
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 md:gap-4">
                        @if($submission->content_quality_score)
                        <div class="text-center p-3 md:p-4 bg-purple-50 rounded-xl">
                            <p class="text-xs md:text-sm text-purple-700 mb-1">جودة المحتوى</p>
                            <p class="text-2xl md:text-3xl font-bold text-purple-600">{{ $submission->content_quality_score }}</p>
                            <p class="text-xs text-purple-600">/100</p>
                        </div>
                        @endif

                        @if($submission->presentation_score)
                        <div class="text-center p-3 md:p-4 bg-blue-50 rounded-xl">
                            <p class="text-xs md:text-sm text-blue-700 mb-1">العرض والتنسيق</p>
                            <p class="text-2xl md:text-3xl font-bold text-blue-600">{{ $submission->presentation_score }}</p>
                            <p class="text-xs text-blue-600">/100</p>
                        </div>
                        @endif

                        @if($submission->effort_score)
                        <div class="text-center p-3 md:p-4 bg-green-50 rounded-xl">
                            <p class="text-xs md:text-sm text-green-700 mb-1">الجهد المبذول</p>
                            <p class="text-2xl md:text-3xl font-bold text-green-600">{{ $submission->effort_score }}</p>
                            <p class="text-xs text-green-600">/100</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        @else
            <!-- Error State -->
            <div class="bg-red-50 border border-red-200 rounded-xl p-6 md:p-8 text-center">
                <div class="w-16 h-16 md:w-20 md:h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="ri-error-warning-line text-red-600 text-3xl md:text-4xl"></i>
                </div>
                <h3 class="text-lg md:text-xl font-semibold text-red-900 mb-2">خطأ في تحميل الواجب</h3>
                <p class="text-sm md:text-base text-red-700 mb-4">
                    عذراً، لم نتمكن من تحميل معلومات الواجب المطلوب.
                </p>
                <a href="{{ route('student.homework.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="inline-flex items-center justify-center min-h-[48px] w-full sm:w-auto px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl transition-colors">
                    <i class="ri-arrow-right-line ml-2"></i>
                    العودة إلى قائمة الواجبات
                </a>
            </div>
        @endif
    </div>
</x-layouts.student>
