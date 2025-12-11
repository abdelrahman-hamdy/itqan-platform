@props([
    'cols' => 4,
    'sm' => null,
    'md' => null,
    'lg' => null,
    'xl' => null,
    'gap' => 6,
    'gapY' => null,
])

@php
    // Default responsive behavior based on cols
    $defaultSm = min($cols, 2);
    $defaultMd = min($cols, 3);
    $defaultLg = $cols;

    $smCols = $sm ?? $defaultSm;
    $mdCols = $md ?? $defaultMd;
    $lgCols = $lg ?? $defaultLg;
    $xlCols = $xl ?? $lgCols;

    $gapClass = "gap-{$gap}";
    $gapYClass = $gapY ? "gap-y-{$gapY}" : '';

    $gridClasses = "grid grid-cols-1 sm:grid-cols-{$smCols} md:grid-cols-{$mdCols} lg:grid-cols-{$lgCols} xl:grid-cols-{$xlCols} {$gapClass} {$gapYClass}";
@endphp

<div {{ $attributes->merge(['class' => trim($gridClasses)]) }}>
    {{ $slot }}
</div>
