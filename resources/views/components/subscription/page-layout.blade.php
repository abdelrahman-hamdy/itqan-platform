@props(['academy', 'title'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $title }} - {{ $academy->name ?? __('components.subscription.page_layout.default_academy_name') }}</title>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

  <!-- Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">

  <!-- Alpine.js -->
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

  <!-- Vite Assets -->
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
