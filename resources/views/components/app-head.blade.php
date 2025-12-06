@props(['title' => null, 'description' => null])

@php
    $appName = config('app.name', 'منصة إتقان');
    $pageTitle = $title ?? $appName;
    $pageDescription = $description ?? 'منصة التعلم الإلكتروني';

    // Font configuration - matches tailwind.config.js
    $primaryFont = 'Tajawal';
    $fallbackFonts = ['Cairo', 'Amiri'];
    $fontWeights = '200;300;400;500;600;700;800;900';

    // Get current academy and colors
    $academy = auth()->user()?->academy ?? \App\Models\Academy::first();
    $primaryColor = $academy?->brand_color ?? \App\Enums\TailwindColor::SKY;
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $favicon = $academy?->favicon ?? null;

    // Generate CSS variables for all shades
    $primaryVars = $primaryColor->generateCssVariables('primary');

    // Get gradient colors for CSS variables
    $gradientColors = $gradientPalette->getColors();
    $gradientFrom = $gradientColors['from'];
    $gradientTo = $gradientColors['to'];
@endphp

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- Page Title -->
<title>{{ $pageTitle }}</title>
<meta name="description" content="{{ $pageDescription }}">

<!-- Favicon -->
@if($favicon)
<link rel="icon" type="image/png" href="{{ Storage::url($favicon) }}">
@else
<link rel="icon" type="image/svg+xml" href="{{ asset('images/itqan-logo.svg') }}">
<link rel="icon" type="image/png" href="{{ asset('favicon.ico') }}">
@endif

<!-- Fonts - Primary: Tajawal, Fallbacks: Cairo, Amiri -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family={{ $primaryFont }}:wght@{{ $fontWeights }}&family=Cairo:wght@{{ $fontWeights }}&family=Amiri:wght@400;700&display=swap" rel="stylesheet">

<!-- Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">

<!-- Styles -->
@vite(['resources/css/app.css', 'resources/js/app.js'])

<!-- Academy Colors CSS Variables -->
<style>
    :root {
        @foreach($primaryVars as $varName => $varValue)
        {{ $varName }}: {{ $varValue }};
        @endforeach

        /* Gradient palette variables */
        --gradient-from: {{ $gradientFrom }};
        --gradient-to: {{ $gradientTo }};
    }
</style>

<!-- Additional Head Content -->
{{ $slot }}
