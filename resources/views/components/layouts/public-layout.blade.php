<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $academy->name ?? __('components.layouts.academy_default') }} - {{ $title ?? __('components.layouts.homepage') }}</title>
  <meta name="description" content="{{ $description ?? __('components.layouts.academy_elearning') }}">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- Fonts -->
  @include('partials.fonts')

  <!-- Favicon -->
  {!! getFaviconLinkTag($academy) !!}

  <!-- Vite Assets (Compiled CSS & JS - includes RemixIcon & Flag-icons) -->
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  <!-- Toast Queue Bootstrap - Must load before any other JS -->
  <script src="{{ asset('js/toast-queue.js') }}"></script>

  <!-- Alpine.js is bundled with Livewire 3 (inject_assets: true in config/livewire.php) -->

  <style>
    :root {
      --color-primary-500: {{ $academy->brand_color?->getHexValue(500) ?? '#0ea5e9' }};
      --color-secondary-500: {{ $academy->secondary_color?->getHexValue(500) ?? '#10B981' }};
    }

    .card-hover {
      transition: all 0.3s ease;
    }

    .card-hover:hover {
      transform: translateY(-4px);
      box-shadow: 0 20px 40px rgba(65, 105, 225, 0.15);
    }

    .filter-card {
      backdrop-filter: blur(10px);
      background: rgba(255, 255, 255, 0.95);
    }

    /* Custom button radius */
    .rounded-button {
      border-radius: 8px;
    }
  </style>

  @stack('styles')
</head>

<body class="bg-gray-50 text-gray-900 font-sans public-view">
  <!-- Navigation -->
  @include('components.navigation.public-nav')

  <!-- Main Content -->
  <main class="pt-24 min-h-screen" id="main-content">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      {{ $slot ?? '' }}
      @yield('content')
    </div>
  </main>

  <!-- Footer -->
  <x-academy-footer :academy="$academy" />

  <!-- Unified Confirmation Modal -->
  <x-ui.confirmation-modal />

  <!-- Unified Toast Notification System -->
  <x-ui.toast-container />

  @stack('scripts')
</body>

</html>
