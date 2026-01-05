@props(['academy', 'title'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $title }} - {{ $academy->name ?? __('components.subscription.page_layout.default_academy_name') }}</title>

  <!-- Fonts -->
  @include('partials.fonts')

  <!-- Alpine.js is bundled with Livewire 3 (inject_assets: true in config/livewire.php) -->

  <!-- Vite Assets (includes RemixIcon & Flag-icons) -->
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  @php
    // Generate CSS variables for primary color
    $primaryColor = $academy->brand_color ?? \App\Enums\TailwindColor::SKY;
    $primaryVars = $primaryColor->generateCssVariables('primary');
  @endphp

  <!-- Academy Colors CSS Variables -->
  <style>
    :root {
      @foreach($primaryVars as $varName => $varValue)
      {{ $varName }}: {{ $varValue }};
      @endforeach
    }
    .card-hover {
      transition: all 0.3s ease;
    }
    .card-hover:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(65, 105, 225, 0.1);
    }
    [x-cloak] { display: none !important; }
  </style>
</head>

<body class="bg-gray-50 font-sans">
  {{ $slot }}
</body>
</html>
