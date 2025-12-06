@php
    $sectionNames = [
        'hero' => 'القسم الرئيسي (Hero)',
        'stats' => 'قسم الإحصائيات',
        'reviews' => 'قسم التقييمات',
        'quran' => 'قسم القرآن الكريم',
        'academic' => 'القسم الأكاديمي',
        'courses' => 'قسم الدورات',
        'features' => 'قسم المميزات',
    ];

    // Get the section value from state
    $sectionValue = $getState();

    // If getState() returns an array (repeater item), extract the 'section' key
    if (is_array($sectionValue)) {
        $sectionValue = $sectionValue['section'] ?? '';
    }

    $displayName = $sectionNames[$sectionValue] ?? $sectionValue;
@endphp

<div class="flex items-center gap-2 px-2 py-1 bg-white dark:bg-gray-900 rounded border border-gray-300 dark:border-gray-600">
    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
    </svg>
    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $displayName }}</span>
</div>

{{-- Hidden input to preserve the value --}}
<input type="hidden" name="{{ $getStatePath() }}" value="{{ $sectionValue }}" />
