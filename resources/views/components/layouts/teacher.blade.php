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

<body class="bg-gray-50 text-gray-900">
  <!-- Navigation -->
  <x-navigation.app-navigation role="teacher" />

  <!-- Email Verification Banner -->
  <x-alerts.email-verification-banner />

  <!-- Sidebar -->
  @include('components.sidebar.teacher-sidebar')

  <!-- Main Content - margins handled by CSS based on dir attribute -->
  <main class="pt-20 min-h-screen transition-all duration-300" id="main-content">
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
