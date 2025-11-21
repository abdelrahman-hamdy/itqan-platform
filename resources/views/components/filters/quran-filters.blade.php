@props([
    'route',
    'filters' => [],
    'showSearch' => true,
    'showStatus' => false,
    'showLevel' => false,
    'showDays' => false,
    'showExperience' => false,
    'showGender' => false,
    'color' => 'purple'
])

@php
    $colorClasses = [
        'focus' => "focus:ring-{$color}-500 focus:border-{$color}-500",
        'button' => "bg-{$color}-600 hover:bg-{$color}-700",
        'buttonHover' => "hover:border-{$color}-500 hover:text-{$color}-600"
    ];
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
    <form method="GET" action="{{ $route }}" class="space-y-4">
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="ri-filter-3-line ml-2"></i>
                تصفية النتائج
            </h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Search -->
            @if($showSearch)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-search-line ml-1"></i>
                    البحث
                </label>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="ابحث..."
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 {{ $colorClasses['focus'] }} transition-colors">
            </div>
            @endif

            <!-- Status Filter (for circles/subscriptions) -->
            @if($showStatus)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-user-follow-line ml-1"></i>
                    حالة التسجيل
                </label>
                <div class="relative">
                    <select name="enrollment_status"
                            style="appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: none;"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 pr-10 text-sm focus:ring-2 {{ $colorClasses['focus'] }} transition-colors bg-white">
                        <option value="">الكل</option>
                        <option value="enrolled" {{ request('enrollment_status') === 'enrolled' ? 'selected' : '' }}>حلقاتي</option>
                        <option value="available" {{ request('enrollment_status') === 'available' ? 'selected' : '' }}>متاحة للتسجيل</option>
                        <option value="open" {{ request('enrollment_status') === 'open' ? 'selected' : '' }}>مفتوحة</option>
                        <option value="full" {{ request('enrollment_status') === 'full' ? 'selected' : '' }}>مكتملة</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                        <i class="ri-arrow-down-s-line text-lg"></i>
                    </div>
                </div>
            </div>
            @endif

            <!-- Memorization Level -->
            @if($showLevel)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-bar-chart-line ml-1"></i>
                    مستوى الحفظ
                </label>
                <div class="relative">
                    <select name="memorization_level"
                            style="appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: none;"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 pr-10 text-sm focus:ring-2 {{ $colorClasses['focus'] }} transition-colors bg-white">
                        <option value="">جميع المستويات</option>
                        <option value="beginner" {{ request('memorization_level') === 'beginner' ? 'selected' : '' }}>مبتدئ</option>
                        <option value="intermediate" {{ request('memorization_level') === 'intermediate' ? 'selected' : '' }}>متوسط</option>
                        <option value="advanced" {{ request('memorization_level') === 'advanced' ? 'selected' : '' }}>متقدم</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                        <i class="ri-arrow-down-s-line text-lg"></i>
                    </div>
                </div>
            </div>
            @endif

            <!-- Experience Years (for teachers) -->
            @if($showExperience)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-time-line ml-1"></i>
                    سنوات الخبرة
                </label>
                <div class="relative">
                    <select name="experience"
                            style="appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: none;"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 pr-10 text-sm focus:ring-2 {{ $colorClasses['focus'] }} transition-colors bg-white">
                        <option value="">الكل</option>
                        <option value="1-3" {{ request('experience') === '1-3' ? 'selected' : '' }}>1-3 سنوات</option>
                        <option value="3-5" {{ request('experience') === '3-5' ? 'selected' : '' }}>3-5 سنوات</option>
                        <option value="5-10" {{ request('experience') === '5-10' ? 'selected' : '' }}>5-10 سنوات</option>
                        <option value="10+" {{ request('experience') === '10+' ? 'selected' : '' }}>أكثر من 10 سنوات</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                        <i class="ri-arrow-down-s-line text-lg"></i>
                    </div>
                </div>
            </div>
            @endif

            <!-- Gender Filter (for teachers) -->
            @if($showGender)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-user-line ml-1"></i>
                    الجنس
                </label>
                <div class="relative">
                    <select name="gender"
                            style="appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: none;"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 pr-10 text-sm focus:ring-2 {{ $colorClasses['focus'] }} transition-colors bg-white">
                        <option value="">الكل</option>
                        <option value="male" {{ request('gender') === 'male' ? 'selected' : '' }}>معلم</option>
                        <option value="female" {{ request('gender') === 'female' ? 'selected' : '' }}>معلمة</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                        <i class="ri-arrow-down-s-line text-lg"></i>
                    </div>
                </div>
            </div>
            @endif

            <!-- Schedule Days (Multi-select) -->
            @if($showDays)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-calendar-line ml-1"></i>
                    أيام الدراسة
                </label>
                <div class="relative" x-data="{ open: false, selected: {{ json_encode(request('schedule_days', [])) }} }">
                    <button type="button" @click="open = !open"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 pr-10 text-sm focus:ring-2 {{ $colorClasses['focus'] }} transition-colors bg-white text-right">
                        <span x-text="selected.length > 0 ? selected.length + ' أيام' : 'جميع الأيام'" class="text-gray-700"></span>
                    </button>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                        <i class="ri-arrow-down-s-line text-lg"></i>
                    </div>
                    <div x-show="open" @click.away="open = false"
                         x-cloak
                         class="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-auto">
                        @foreach(\App\Enums\WeekDays::cases() as $weekDay)
                        <label class="flex items-center px-4 py-2 hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" name="schedule_days[]" value="{{ $weekDay->value }}"
                                   x-model="selected"
                                   {{ in_array($weekDay->value, request('schedule_days', [])) ? 'checked' : '' }}
                                   class="ml-3 rounded border-gray-300 text-{{ $color }}-600 focus:ring-{{ $color }}-500">
                            <span class="text-sm text-gray-700">{{ $weekDay->label() }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Custom Filters Slot -->
            {{ $slot }}
        </div>

        <!-- Buttons Row -->
        <div class="flex items-center gap-3 pt-2">
            <button type="submit"
                    class="{{ $colorClasses['button'] }} text-white px-6 py-2.5 rounded-lg text-sm font-medium transition-colors">
                <i class="ri-search-line ml-1"></i>
                تطبيق الفلاتر
            </button>

            @if(request()->hasAny(['enrollment_status', 'memorization_level', 'schedule_days', 'search', 'experience', 'gender']))
            <a href="{{ $route }}"
               class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
                <i class="ri-close-circle-line ml-1"></i>
                إعادة تعيين
            </a>
            @endif
        </div>
    </form>
</div>
