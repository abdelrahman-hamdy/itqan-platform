<x-layouts.parent-layout :title="__('parent.quizzes.title')">
    <div class="space-y-6">

        <!-- Page Header -->
        <div class="mb-4 md:mb-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 md:gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 flex items-center">
                        <i class="ri-file-list-3-line text-blue-600 ms-2 md:ms-3"></i>
                        {{ __('parent.quizzes.title') }}
                    </h1>
                    <p class="text-sm md:text-base text-gray-600 mt-1 md:mt-2">{{ __('parent.quizzes.description') }}</p>
                </div>
                <div class="flex items-center gap-2 md:gap-4 text-xs md:text-sm">
                    <div class="bg-blue-100 text-blue-800 px-2.5 md:px-4 py-1.5 md:py-2 rounded-lg font-medium">
                        <i class="ri-play-circle-line ms-1"></i>
                        {{ $quizzes->count() }} {{ __('parent.quizzes.quiz_count') }}
                    </div>
                    <div class="bg-green-100 text-green-800 px-2.5 md:px-4 py-1.5 md:py-2 rounded-lg font-medium">
                        <i class="ri-history-line ms-1"></i>
                        {{ $history->count() }} {{ __('parent.quizzes.attempt_count') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div x-data="{ activeTab: 'available' }">
            <div class="mb-4 md:mb-6 bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-1.5 md:p-2">
                <div class="flex flex-wrap gap-1.5 md:gap-2">
                    <button @click="activeTab = 'available'"
                            :class="activeTab === 'available' ? 'bg-blue-500 text-white' : 'text-gray-700 hover:bg-gray-100'"
                            class="min-h-[40px] md:min-h-[44px] px-3 md:px-4 py-1.5 md:py-2 rounded-lg font-medium transition-colors text-xs md:text-sm flex items-center gap-1">
                        <i class="ri-play-circle-line"></i>
                        <span>{{ __('parent.quizzes.available_tab') }} ({{ $quizzes->count() }})</span>
                    </button>
                    <button @click="activeTab = 'history'"
                            :class="activeTab === 'history' ? 'bg-blue-500 text-white' : 'text-gray-700 hover:bg-gray-100'"
                            class="min-h-[40px] md:min-h-[44px] px-3 md:px-4 py-1.5 md:py-2 rounded-lg font-medium transition-colors text-xs md:text-sm flex items-center gap-1">
                        <i class="ri-history-line"></i>
                        <span>{{ __('parent.quizzes.history_tab') }} ({{ $history->count() }})</span>
                    </button>
                </div>
            </div>

            <!-- Available Quizzes Tab -->
            <div x-show="activeTab === 'available'" x-cloak>
                @if($quizzes->count() > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                        @foreach($quizzes as $quizData)
                            <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                                <div class="p-4 md:p-6">
                                    <!-- Quiz Header -->
                                    <div class="flex items-start justify-between gap-3 mb-3 md:mb-4">
                                        <div class="flex-1 min-w-0">
                                            <h3 class="font-bold text-gray-900 mb-1 text-sm md:text-base truncate">{{ $quizData['title'] ?? __('parent.quizzes.quiz_count') }}</h3>
                                            @if(isset($quizData['child_name']))
                                            <span class="inline-flex items-center px-1.5 md:px-2 py-0.5 md:py-1 rounded-full text-[10px] md:text-xs font-medium bg-purple-100 text-purple-800">
                                                <i class="ri-user-line ms-0.5 md:ms-1"></i>
                                                {{ $quizData['child_name'] }}
                                            </span>
                                            @endif
                                        </div>
                                        <div class="w-10 h-10 md:w-12 md:h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <i class="ri-file-list-3-line text-lg md:text-xl text-blue-600"></i>
                                        </div>
                                    </div>

                                    <!-- Quiz Info -->
                                    <div class="space-y-1.5 md:space-y-2 text-xs md:text-sm text-gray-600 mb-3 md:mb-4">
                                        @if(isset($quizData['questions_count']))
                                        <div class="flex items-center">
                                            <i class="ri-questionnaire-line ms-1.5 md:ms-2 text-gray-400"></i>
                                            {{ $quizData['questions_count'] }} {{ __('parent.quizzes.questions_count') }}
                                        </div>
                                        @endif
                                        @if(isset($quizData['duration']))
                                        <div class="flex items-center">
                                            <i class="ri-time-line ms-1.5 md:ms-2 text-gray-400"></i>
                                            {{ $quizData['duration'] }} {{ __('parent.quizzes.duration_minutes') }}
                                        </div>
                                        @endif
                                        @if(isset($quizData['due_date']))
                                        <div class="flex items-center">
                                            <i class="ri-calendar-line ms-1.5 md:ms-2 text-gray-400"></i>
                                            <span class="truncate">{{ __('parent.quizzes.due_date', ['date' => \Carbon\Carbon::parse($quizData['due_date'])->format('d/m/Y')]) }}</span>
                                        </div>
                                        @endif
                                    </div>

                                    <!-- Status Badge -->
                                    @if(isset($quizData['status']))
                                    <div class="mb-3 md:mb-4">
                                        @if($quizData['status'] === 'pending')
                                        <span class="inline-flex items-center px-2 md:px-3 py-0.5 md:py-1 rounded-full text-[10px] md:text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <i class="ri-time-line ms-0.5 md:ms-1"></i>
                                            {{ __('parent.quizzes.status.pending') }}
                                        </span>
                                        @elseif($quizData['status'] === 'in_progress')
                                        <span class="inline-flex items-center px-2 md:px-3 py-0.5 md:py-1 rounded-full text-[10px] md:text-xs font-medium bg-blue-100 text-blue-800">
                                            <i class="ri-play-line ms-0.5 md:ms-1"></i>
                                            {{ __('parent.quizzes.status.in_progress') }}
                                        </span>
                                        @elseif($quizData['status'] === 'completed')
                                        <span class="inline-flex items-center px-2 md:px-3 py-0.5 md:py-1 rounded-full text-[10px] md:text-xs font-medium bg-green-100 text-green-800">
                                            <i class="ri-check-line ms-0.5 md:ms-1"></i>
                                            {{ __('parent.quizzes.status.completed') }}
                                        </span>
                                        @endif
                                    </div>
                                    @endif

                                    <!-- Attempts Info -->
                                    @if(isset($quizData['attempts_used']) && isset($quizData['max_attempts']))
                                    <div class="text-xs md:text-sm text-gray-500">
                                        {{ __('parent.quizzes.attempts_used', ['used' => $quizData['attempts_used'], 'max' => $quizData['max_attempts']]) }}
                                    </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-8 md:p-12 text-center">
                        <div class="w-16 h-16 md:w-20 md:h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                            <i class="ri-file-list-3-line text-gray-400 text-2xl md:text-4xl"></i>
                        </div>
                        <h3 class="text-base md:text-xl font-semibold text-gray-900 mb-1 md:mb-2">{{ __('parent.quizzes.no_quizzes_title') }}</h3>
                        <p class="text-sm md:text-base text-gray-600">{{ __('parent.quizzes.no_quizzes_description') }}</p>
                    </div>
                @endif
            </div>

            <!-- History Tab -->
            <div x-show="activeTab === 'history'" x-cloak>
                @if($history->count() > 0)
                    <!-- Desktop Table View -->
                    <div class="hidden md:block bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-4 md:px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('parent.quizzes.quiz_name') }}</th>
                                        <th scope="col" class="px-4 md:px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('parent.quizzes.student_name') }}</th>
                                        <th scope="col" class="px-4 md:px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('parent.quizzes.score') }}</th>
                                        <th scope="col" class="px-4 md:px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('parent.quizzes.date') }}</th>
                                        <th scope="col" class="px-4 md:px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('parent.quizzes.status_label') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($history as $attempt)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                            <div class="font-medium text-gray-900 text-sm">{{ $attempt->assignment?->quiz?->title ?? __('parent.quizzes.quiz_count') }}</div>
                                        </td>
                                        <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                <i class="ri-user-line ms-1"></i>
                                                {{ $attempt->child_name ?? __('parent.quizzes.not_specified') }}
                                            </span>
                                        </td>
                                        <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                            @if($attempt->score !== null)
                                            <span class="font-bold {{ $attempt->score >= 70 ? 'text-green-600' : ($attempt->score >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                                {{ number_format($attempt->score, 1) }}%
                                            </span>
                                            @else
                                            <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $attempt->completed_at ? $attempt->completed_at->format('d/m/Y H:i') : '-' }}
                                        </td>
                                        <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                            @if($attempt->status === 'completed')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="ri-check-line ms-1"></i>
                                                {{ __('parent.quizzes.status.completed') }}
                                            </span>
                                            @elseif($attempt->status === 'in_progress')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <i class="ri-play-line ms-1"></i>
                                                {{ __('parent.quizzes.status.in_progress') }}
                                            </span>
                                            @else
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                {{ $attempt->status }}
                                            </span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Mobile Card View -->
                    <div class="md:hidden space-y-3">
                        @foreach($history as $attempt)
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-medium text-gray-900 text-sm truncate">{{ $attempt->assignment?->quiz?->title ?? __('parent.quizzes.quiz_count') }}</h4>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-purple-100 text-purple-800 mt-1">
                                        <i class="ri-user-line ms-0.5"></i>
                                        {{ $attempt->child_name ?? __('parent.quizzes.not_specified') }}
                                    </span>
                                </div>
                                @if($attempt->status === 'completed')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-green-100 text-green-800 flex-shrink-0">
                                    <i class="ri-check-line ms-0.5"></i>
                                    {{ __('parent.quizzes.status.completed') }}
                                </span>
                                @elseif($attempt->status === 'in_progress')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-blue-100 text-blue-800 flex-shrink-0">
                                    <i class="ri-play-line ms-0.5"></i>
                                    {{ __('parent.quizzes.status.in_progress') }}
                                </span>
                                @endif
                            </div>
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span>{{ $attempt->completed_at ? $attempt->completed_at->format('d/m/Y') : '-' }}</span>
                                @if($attempt->score !== null)
                                <span class="font-bold {{ $attempt->score >= 70 ? 'text-green-600' : ($attempt->score >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                    {{ number_format($attempt->score, 1) }}%
                                </span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Stats Summary -->
                    @php
                        $completedAttempts = $history->where('status', 'completed');
                        $avgScore = $completedAttempts->avg('score');
                        $passedCount = $completedAttempts->where('score', '>=', 70)->count();
                    @endphp
                    <div class="grid grid-cols-3 gap-3 md:gap-6 mt-4 md:mt-6">
                        <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-3 md:p-6 text-center">
                            <p class="text-xl md:text-3xl font-bold text-blue-600">{{ $completedAttempts->count() }}</p>
                            <p class="text-[10px] md:text-sm text-gray-500 mt-0.5 md:mt-1">{{ __('parent.quizzes.completed_count') }}</p>
                        </div>
                        <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-3 md:p-6 text-center">
                            <p class="text-xl md:text-3xl font-bold text-green-600">{{ number_format($avgScore ?? 0, 1) }}%</p>
                            <p class="text-[10px] md:text-sm text-gray-500 mt-0.5 md:mt-1">{{ __('parent.quizzes.average_score') }}</p>
                        </div>
                        <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-3 md:p-6 text-center">
                            <p class="text-xl md:text-3xl font-bold text-purple-600">{{ $passedCount }}</p>
                            <p class="text-[10px] md:text-sm text-gray-500 mt-0.5 md:mt-1">{{ __('parent.quizzes.passed_count') }}</p>
                        </div>
                    </div>
                @else
                    <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-8 md:p-12 text-center">
                        <div class="w-16 h-16 md:w-20 md:h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                            <i class="ri-history-line text-gray-400 text-2xl md:text-4xl"></i>
                        </div>
                        <h3 class="text-base md:text-xl font-semibold text-gray-900 mb-1 md:mb-2">{{ __('parent.quizzes.no_history_title') }}</h3>
                        <p class="text-sm md:text-base text-gray-600">{{ __('parent.quizzes.no_history_description') }}</p>
                    </div>
                @endif
            </div>
        </div>

    </div>
</x-layouts.parent-layout>
