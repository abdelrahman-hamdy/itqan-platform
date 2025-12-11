@props([
    'gap' => 4,
    'mdGap' => null,
    'lgGap' => null,
    'divider' => false,
])

@php
    $gapClass = "gap-{$gap}";
    $mdGapClass = $mdGap ? "md:gap-{$mdGap}" : '';
    $lgGapClass = $lgGap ? "lg:gap-{$lgGap}" : '';
    $dividerClass = $divider ? 'divide-y divide-gray-200' : '';
@endphp

<div {{ $attributes->merge([
    'class' => trim("flex flex-col {$gapClass} {$mdGapClass} {$lgGapClass} {$dividerClass}")
]) }}>
    {{ $slot }}
</div>
