@php
    $academy = auth()->user()->academy;
    $subdomain = request()->route('subdomain') ?? $academy->subdomain ?? 'itqan-academy';
@endphp

<x-student title="{{ $academy->name ?? 'أكاديمية إتقان' }} - اختباراتي">
    <x-slot name="description">عرض جميع الاختبارات المتاحة وسجل المحاولات - {{ $academy->name ?? 'أكاديمية إتقان' }}</x-slot>

    <!-- Header Section -->
    <x-student-page.header
        title="اختباراتي"
        description="عرض جميع الاختبارات المتاحة وسجل محاولاتك"
        :count="$quizzes->count()"
        countLabel="اختبارات متاحة"
        countColor="blue"
        :secondaryCount="$history->count()"
        secondaryCountLabel="إجمالي المحاولات"
        secondaryCountColor="green"
    />

    <div x-data="{ activeTab: '{{ request('tab', 'available') }}' }">
        <!-- Filter Tabs -->
        <div class="mb-6 bg-white rounded-xl shadow-sm border border-gray-200 p-2">
            <div class="flex flex-wrap gap-2">
                <button @click="activeTab = 'available'"
                        :class="activeTab === 'available' ? 'bg-blue-500 text-white' : 'text-gray-700 hover:bg-gray-100'"
                        class="px-4 py-2 rounded-lg font-medium transition-colors">
                    <i class="ri-play-circle-line ml-1"></i>
                    الاختبارات المتاحة ({{ $quizzes->count() }})
                </button>
                <button @click="activeTab = 'history'"
                        :class="activeTab === 'history' ? 'bg-blue-500 text-white' : 'text-gray-700 hover:bg-gray-100'"
                        class="px-4 py-2 rounded-lg font-medium transition-colors">
                    <i class="ri-history-line ml-1"></i>
                    سجل المحاولات ({{ $history->count() }})
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
                        اختبار متاح
                    </p>
                </div>

                <!-- Quizzes Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($quizzes as $quizData)
                        <x-quiz-card :quizData="$quizData" />
                    @endforeach
                </div>
            @else
                <x-student-page.empty-state
                    icon="ri-file-list-3-line"
                    title="لا توجد اختبارات متاحة حالياً"
                    description="ستظهر الاختبارات هنا عند تخصيصها من قبل المعلمين في حلقاتك أو دوراتك"
                    iconBgColor="blue"
                />
            @endif
        </div>

        <!-- History Tab -->
        <div x-show="activeTab === 'history'" x-cloak>
            @if($history->count() > 0)
                <x-quiz.history-table :history="$history" />
                <x-quiz.stats-summary :history="$history" />
            @else
                <x-student-page.empty-state
                    icon="ri-history-line"
                    title="لا توجد محاولات سابقة"
                    description="ستظهر سجلات محاولاتك للاختبارات هنا بعد إتمام أول اختبار"
                    iconBgColor="gray"
                />
            @endif
        </div>
    </div>
</x-student>
