<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
  <x-app-head :title="$title ?? 'لوحة المعلم - ' . (auth()->user()->academy->name ?? 'أكاديمية إتقان')" :description="$description ?? 'لوحة التحكم للمعلم - ' . (auth()->user()->academy->name ?? 'أكاديمية إتقان')">
    <style>
      /* Card Hover Effects */
      .card-hover {
        transition: all 0.3s ease;
      }

      .card-hover:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 40px rgba(65, 105, 225, 0.15);
      }

      /* Stats Counter - Uses Tajawal font */
      .stats-counter {
        font-family: 'Tajawal', sans-serif;
        font-weight: bold;
      }

      /* Focus indicators */
      .focus\:ring-custom:focus {
        outline: 2px solid var(--color-primary-500);
        outline-offset: 2px;
      }

      /* Modal Styles */
      .modal-backdrop {
        backdrop-filter: blur(4px);
      }

      .modal-content {
        animation: modalSlideIn 0.3s ease-out;
      }

      @keyframes modalSlideIn {
        from {
          opacity: 0;
          transform: scale(0.9) translateY(-20px);
        }
        to {
          opacity: 1;
          transform: scale(1) translateY(0);
        }
      }

      .modal-exit {
        animation: modalSlideOut 0.2s ease-in;
      }

      @keyframes modalSlideOut {
        from {
          opacity: 1;
          transform: scale(1) translateY(0);
        }
        to {
          opacity: 0;
          transform: scale(0.9) translateY(-20px);
        }
      }
    </style>

    @livewireStyles
    {{ $head ?? '' }}
    @stack('styles')
  </x-app-head>
</head>

<body class="bg-gray-50 text-gray-900" style="background-image: url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80' viewBox='0 0 80 80'%3E%3Cpolygon points='40,8 47,23 63,17 57,33 72,40 57,47 63,63 47,57 40,72 33,57 17,63 23,47 8,40 23,33 17,17 33,23' fill='none' stroke='%2394a3b8' stroke-width='0.8' stroke-opacity='0.18'/%3E%3Cline x1='40' y1='8' x2='40' y2='0' stroke='%2394a3b8' stroke-width='0.6' stroke-opacity='0.15'/%3E%3Cline x1='72' y1='40' x2='80' y2='40' stroke='%2394a3b8' stroke-width='0.6' stroke-opacity='0.15'/%3E%3Cline x1='40' y1='72' x2='40' y2='80' stroke='%2394a3b8' stroke-width='0.6' stroke-opacity='0.15'/%3E%3Cline x1='8' y1='40' x2='0' y2='40' stroke='%2394a3b8' stroke-width='0.6' stroke-opacity='0.15'/%3E%3Cline x1='63' y1='17' x2='80' y2='0' stroke='%2394a3b8' stroke-width='0.6' stroke-opacity='0.15'/%3E%3Cline x1='63' y1='63' x2='80' y2='80' stroke='%2394a3b8' stroke-width='0.6' stroke-opacity='0.15'/%3E%3Cline x1='17' y1='63' x2='0' y2='80' stroke='%2394a3b8' stroke-width='0.6' stroke-opacity='0.15'/%3E%3Cline x1='17' y1='17' x2='0' y2='0' stroke='%2394a3b8' stroke-width='0.6' stroke-opacity='0.15'/%3E%3Ccircle cx='40' cy='40' r='6' fill='none' stroke='%2394a3b8' stroke-width='0.5' stroke-opacity='0.12'/%3E%3C/svg%3E&quot;); background-size: 80px 80px;">
  <!-- Navigation -->
  <x-navigation.app-navigation role="teacher" />

  <!-- Sidebar -->
  @include('components.sidebar.teacher-sidebar')

  <!-- Main Content - margins handled by CSS based on dir attribute -->
  <main class="pt-20 md:pt-[5.5rem] min-h-screen transition-all duration-300" id="main-content">
    <!-- Email Verification Banner -->
    <x-alerts.email-verification-banner />

    <div class="dynamic-content-wrapper px-4 sm:px-6 lg:px-8 py-6 md:py-8">
      @isset($slot)
        {{ $slot }}
      @else
        @yield('content')
      @endisset
    </div>
  </main>

  <!-- Early Alpine component registration listener - MUST be before @livewireScripts -->
  <script>
    // Set up alpine:init listener BEFORE Livewire loads Alpine
    // This ensures we can register components before Alpine processes the DOM
    document.addEventListener('alpine:init', function() {
      // Wait for tabsComponent to be available (loaded by Vite bundle)
      if (window.tabsComponent && window.Alpine) {
        window.Alpine.data('tabsComponent', window.tabsComponent);
      }
    });
  </script>

  @livewireScripts

  <!-- Unified Toast Notification System -->
  <x-ui.toast-container />

  <!-- Unified Confirmation Modal -->
  <x-ui.confirmation-modal />

  {{ $scripts ?? '' }}
  @stack('scripts')
</body>
</html>
