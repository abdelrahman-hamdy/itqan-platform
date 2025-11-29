<x-layouts.student-layout title="نتيجة الاختبار">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Back Button -->
        @php
            $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
        @endphp
        <div class="mb-6">
            <a href="{{ route('student.quizzes', ['subdomain' => $subdomain]) }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 transition-colors">
                <i class="ri-arrow-right-line ml-1"></i>
                العودة لقائمة الاختبارات
            </a>
        </div>

        <!-- Result Summary -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-4">{{ $quiz->title }}</h1>

            <div class="grid md:grid-cols-3 gap-6">
                <!-- Best Score -->
                <div class="text-center p-6 rounded-xl {{ $bestAttempt->passed ? 'bg-green-50' : 'bg-red-50' }}">
                    <div class="text-5xl font-bold {{ $bestAttempt->passed ? 'text-green-600' : 'text-red-600' }}">
                        {{ $bestAttempt->score }}%
                    </div>
                    <p class="mt-2 text-gray-600">أفضل درجة</p>
                    @if($bestAttempt->passed)
                        <span class="inline-flex items-center mt-2 px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            <i class="ri-check-line ml-1"></i>
                            ناجح
                        </span>
                    @else
                        <span class="inline-flex items-center mt-2 px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                            <i class="ri-close-line ml-1"></i>
                            لم ينجح
                        </span>
                    @endif
                </div>

                <!-- Stats -->
                <div class="text-center p-6 rounded-xl bg-gray-50">
                    <div class="text-5xl font-bold text-gray-900">
                        {{ $attempts->count() }}
                    </div>
                    <p class="mt-2 text-gray-600">عدد المحاولات</p>
                    <p class="mt-1 text-sm text-gray-500">
                        من أصل {{ $assignment->max_attempts }}
                    </p>
                </div>

                <!-- Pass Score -->
                <div class="text-center p-6 rounded-xl bg-blue-50">
                    <div class="text-5xl font-bold text-blue-600">
                        {{ $quiz->passing_score }}%
                    </div>
                    <p class="mt-2 text-gray-600">درجة النجاح</p>
                </div>
            </div>
        </div>

        <!-- All Attempts -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">سجل المحاولات</h2>

            <div class="overflow-x-auto">
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
        </div>

        <!-- Retry Button -->
        @if($assignment->canStudentAttempt(auth()->user()->studentProfile->id))
            @php
                $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
            @endphp
            <div class="mt-6 text-center">
                <a href="{{ route('student.quiz.start', ['subdomain' => $subdomain, 'quiz_id' => $assignment->id]) }}"
                   class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                    <i class="ri-restart-line ml-2"></i>
                    محاولة جديدة
                </a>
            </div>
        @endif
    </div>
</x-layouts.student-layout>
