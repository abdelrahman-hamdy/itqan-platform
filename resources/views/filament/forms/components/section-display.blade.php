@php
    $sectionNames = [
        'hero' => 'القسم الرئيسي',
        'stats' => 'الإحصائيات',
        'reviews' => 'التقييمات',
        'quran' => 'القرآن الكريم',
        'academic' => 'الأكاديمي',
        'courses' => 'الدورات',
        'features' => 'المميزات',
    ];

    $sectionIcons = [
        'hero' => 'ri-home-line',
        'stats' => 'ri-bar-chart-line',
        'reviews' => 'ri-star-line',
        'quran' => 'ri-book-open-line',
        'academic' => 'ri-graduation-cap-line',
        'courses' => 'ri-video-line',
        'features' => 'ri-lightbulb-line',
    ];

    // Get the section value from state
    $sectionValue = $getState();

    // If getState() returns an array (repeater item), extract the 'section' key
    if (is_array($sectionValue)) {
        $sectionValue = $sectionValue['section'] ?? '';
    }

    $displayName = $sectionNames[$sectionValue] ?? $sectionValue;
    $iconClass = $sectionIcons[$sectionValue] ?? 'ri-layout-line';
@endphp

<div class="flex items-center gap-3 px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-800 dark:text-gray-200">
    <i class="{{ $iconClass }} text-primary-500 dark:text-primary-400"></i>
    <span class="font-medium">{{ $displayName }}</span>
</div>

{{-- Hidden input to preserve the value --}}
<input type="hidden" name="{{ $getStatePath() }}" value="{{ $sectionValue }}" />
