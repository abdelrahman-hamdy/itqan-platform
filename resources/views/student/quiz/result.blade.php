<x-layouts.student title="نتيجة الاختبار">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-8">
        <!-- Back Button -->
        @php
            $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
        @endphp
        <div class="mb-4 md:mb-6">
            <a href="{{ route('student.quizzes', ['subdomain' => $subdomain]) }}" class="inline-flex items-center min-h-[44px] text-blue-600 hover:text-blue-800 transition-colors text-sm md:text-base">
                <i class="ri-arrow-right-line ml-1"></i>
                العودة لقائمة الاختبارات
            </a>
        </div>

        <!-- Result Summary -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-4 md:mb-6">
            <h1 class="text-xl md:text-2xl font-bold text-gray-900 mb-4">{{ $quiz->title }}</h1>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6">
                <!-- Best Score -->
                <div class="text-center p-4 md:p-6 rounded-xl {{ $bestAttempt->passed ? 'bg-green-50' : 'bg-red-50' }}">
                    <div class="text-4xl md:text-5xl font-bold {{ $bestAttempt->passed ? 'text-green-600' : 'text-red-600' }}">
                        {{ $bestAttempt->score }}%
                    </div>
                    <p class="mt-2 text-sm md:text-base text-gray-600">أفضل درجة</p>
                    @if($bestAttempt->passed)
                        <span class="inline-flex items-center mt-2 px-3 py-1 rounded-full text-xs md:text-sm font-medium bg-green-100 text-green-800">
                            <i class="ri-check-line ml-1"></i>
                            ناجح
                        </span>
                    @else
                        <span class="inline-flex items-center mt-2 px-3 py-1 rounded-full text-xs md:text-sm font-medium bg-red-100 text-red-800">
                            <i class="ri-close-line ml-1"></i>
                            لم ينجح
                        </span>
                    @endif
                </div>

                <!-- Stats -->
                <div class="text-center p-4 md:p-6 rounded-xl bg-gray-50">
                    <div class="text-4xl md:text-5xl font-bold text-gray-900">
                        {{ $attempts->count() }}
                    </div>
                    <p class="mt-2 text-sm md:text-base text-gray-600">عدد المحاولات</p>
                    <p class="mt-1 text-xs md:text-sm text-gray-500">
                        من أصل {{ $assignment->max_attempts }}
                    </p>
                </div>

                <!-- Pass Score -->
                <div class="text-center p-4 md:p-6 rounded-xl bg-blue-50">
                    <div class="text-4xl md:text-5xl font-bold text-blue-600">
                        {{ $quiz->passing_score }}%
                    </div>
                    <p class="mt-2 text-sm md:text-base text-gray-600">درجة النجاح</p>
                </div>
            </div>
        </div>

        <!-- All Attempts -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
            <h2 class="text-lg md:text-xl font-bold text-gray-900 mb-4">سجل المحاولات</h2>

            <!-- Desktop Table -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-right py-3 px-4 text-sm font-medium text-gray-500">المحاولة</th>
                            <th class="text-right py-3 px-4 text-sm font-medium text-gray-500">الدرجة</th>
                            <th class="text-right py-3 px-4 text-sm font-medium text-gray-500">الحالة</th>
                            <th class="text-right py-3 px-4 text-sm font-medium text-gray-500">تاريخ التقديم</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($attempts as $index => $attempt)
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3 px-4 text-gray-900">
                                    المحاولة {{ $attempts->count() - $index }}
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-bold {{ $attempt->score >= $quiz->passing_score ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $attempt->score }}%
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    @if($attempt->passed)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            ناجح
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            لم ينجح
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-gray-600 text-sm">
                                    {{ $attempt->submitted_at->format('Y-m-d H:i') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div class="md:hidden space-y-3">
                @foreach($attempts as $index => $attempt)
                    <div class="border border-gray-200 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-medium text-gray-900">المحاولة {{ $attempts->count() - $index }}</span>
                            <span class="font-bold text-lg {{ $attempt->score >= $quiz->passing_score ? 'text-green-600' : 'text-red-600' }}">
                                {{ $attempt->score }}%
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            @if($attempt->passed)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    ناجح
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    لم ينجح
                                </span>
                            @endif
                            <span class="text-gray-600 text-xs">
                                {{ $attempt->submitted_at->format('Y-m-d H:i') }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Retry Button -->
        @if($assignment->canStudentAttempt(auth()->user()->studentProfile->id))
            @php
                $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
            @endphp
            <div class="mt-6 text-center">
                <a href="{{ route('student.quiz.start', ['subdomain' => $subdomain, 'quiz_id' => $assignment->id]) }}"
                   class="inline-flex items-center justify-center min-h-[48px] w-full sm:w-auto px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-colors">
                    <i class="ri-restart-line ml-2"></i>
                    محاولة جديدة
                </a>
            </div>
        @endif
    </div>
</x-layouts.student>
