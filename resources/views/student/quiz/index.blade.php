@php
    $academy = auth()->user()->academy;
    $subdomain = request()->route('subdomain') ?? $academy->subdomain ?? 'itqan-academy';
@endphp

<x-layouts.student title="{{ $academy->name ?? __('student.common.academy_default') }} - {{ __('student.quiz.title') }}">
    <x-slot name="description">{{ __('student.quiz.description') }} - {{ $academy->name ?? __('student.common.academy_default') }}</x-slot>

    <!-- Header Section -->
    <x-student-page.header
        title="{{ __('student.quiz.title') }}"
        description="{{ __('student.quiz.description') }}"
        :count="$quizzes->count()"
        countLabel="{{ __('student.quiz.available_count') }}"
        countColor="blue"
        :secondaryCount="$history->count()"
        secondaryCountLabel="{{ __('student.quiz.attempts_count') }}"
        secondaryCountColor="green"
    />

    <div x-data="{ activeTab: '{{ request('tab', 'available') }}' }">
        <!-- Filter Tabs -->
        <div class="mb-6 bg-white rounded-xl shadow-sm border border-gray-200 p-2">
            <div class="flex flex-wrap gap-2">
                <button @click="activeTab = 'available'"
                        :class="activeTab === 'available' ? 'bg-blue-500 text-white' : 'text-gray-700 hover:bg-gray-100'"
                        class="px-4 py-2 rounded-lg font-medium transition-colors">
                    <i class="ri-play-circle-line ms-1"></i>
                    {{ __('student.quiz.tab_available') }} ({{ $quizzes->count() }})
                </button>
                <button @click="activeTab = 'history'"
                        :class="activeTab === 'history' ? 'bg-blue-500 text-white' : 'text-gray-700 hover:bg-gray-100'"
                        class="px-4 py-2 rounded-lg font-medium transition-colors">
                    <i class="ri-history-line ms-1"></i>
                    {{ __('student.quiz.tab_history') }} ({{ $history->count() }})
                </button>
            </div>
        </div>

        <!-- Available Quizzes Tab -->
        <div x-show="activeTab === 'available'" x-cloak>
            @if($quizzes->count() > 0)
                <!-- Results Summary -->
                <div class="mb-6 flex items-center justify-between">
                    <p class="text-gray-600">
                        <span class="font-semibold text-gray-900">{{ $quizzes->count() }}</span>
                        {{ __('student.quiz.available_quiz') }}
                    </p>
                </div>

                <!-- Quizzes Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($quizzes as $quizData)
                        <x-quiz-card :quizData="$quizData" />
                    @endforeach
                </div>
            @else
                <x-ui.empty-state
                    icon="ri-file-list-3-line"
                    title="{{ __('student.quiz.no_quizzes_title') }}"
                    description="{{ __('student.quiz.no_quizzes_description') }}"
                    color="blue"
                />
            @endif
        </div>

        <!-- History Tab -->
        <div x-show="activeTab === 'history'" x-cloak>
            @if($history->count() > 0)
                <x-quiz.history-table :history="$history" />
                <x-quiz.stats-summary :history="$history" />
            @else
                <x-ui.empty-state
                    icon="ri-history-line"
                    title="{{ __('student.quiz.no_history_title') }}"
                    description="{{ __('student.quiz.no_history_description') }}"
                    color="gray"
                />
            @endif
        </div>
    </div>
</x-layouts.student>
