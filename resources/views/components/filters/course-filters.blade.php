@props([
    'route',
    'subjects' => [],
    'gradeLevels' => [],
    'levels' => [],
    'showSearch' => true,
    'showSubject' => true,
    'showGradeLevel' => true,
    'showDifficulty' => true,
    'color' => 'cyan'
])

@php
    $colorClasses = [
        'focus' => "focus:ring-{$color}-500 focus:border-{$color}-500",
        'button' => "bg-{$color}-500 hover:bg-{$color}-600",
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
                       placeholder="ابحث في الدورات..."
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 {{ $colorClasses['focus'] }} transition-colors">
            </div>
            @endif

            <!-- Subject Filter -->
            @if($showSubject && count($subjects) > 0)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-book-line ml-1"></i>
                    المادة
                </label>
                <div class="relative">
                    <select name="subject_id"
                            style="appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: none;"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 pr-10 text-sm focus:ring-2 {{ $colorClasses['focus'] }} transition-colors bg-white">
                        <option value="">جميع المواد</option>
                        @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                            {{ $subject->name }}
                        </option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                        <i class="ri-arrow-down-s-line text-lg"></i>
                    </div>
                </div>
            </div>
            @endif

            <!-- Grade Level Filter -->
            @if($showGradeLevel && count($gradeLevels) > 0)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-medal-line ml-1"></i>
                    الصف الدراسي
                </label>
                <div class="relative">
                    <select name="grade_level_id"
                            style="appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: none;"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 pr-10 text-sm focus:ring-2 {{ $colorClasses['focus'] }} transition-colors bg-white">
                        <option value="">جميع الصفوف</option>
                        @foreach($gradeLevels as $gradeLevel)
                        <option value="{{ $gradeLevel->id }}" {{ request('grade_level_id') == $gradeLevel->id ? 'selected' : '' }}>
                            {{ $gradeLevel->name }}
                        </option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                        <i class="ri-arrow-down-s-line text-lg"></i>
                    </div>
                </div>
            </div>
            @endif

            <!-- Difficulty Level Filter -->
            @if($showDifficulty && count($levels) > 0)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-bar-chart-line ml-1"></i>
                    مستوى الصعوبة
                </label>
                <div class="relative">
                    <select name="level"
                            style="appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: none;"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 pr-10 text-sm focus:ring-2 {{ $colorClasses['focus'] }} transition-colors bg-white">
                        <option value="">جميع المستويات</option>
                        @foreach($levels as $level)
                        <option value="{{ $level }}" {{ request('level') == $level ? 'selected' : '' }}>
                            @switch($level)
                                @case('easy') سهل @break
                                @case('medium') متوسط @break
                                @case('hard') صعب @break
                                @default {{ $level }}
                            @endswitch
                        </option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                        <i class="ri-arrow-down-s-line text-lg"></i>
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

            @if(request()->hasAny(['search', 'subject_id', 'grade_level_id', 'level']))
            <a href="{{ $route }}"
               class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
                <i class="ri-close-circle-line ml-1"></i>
                إعادة تعيين
            </a>
            @endif
        </div>
    </form>
</div>
