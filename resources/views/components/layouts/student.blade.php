<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
  <x-app-head :title="$title ?? auth()->user()->academy->name ?? 'أكاديمية إتقان'" :description="$description ?? 'منصة التعلم الإلكتروني - ' . (auth()->user()->academy->name ?? 'أكاديمية إتقان')">
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

    {{ $head ?? '' }}
  </x-app-head>
</head>

<body class="bg-gray-50 text-gray-900">
  <!-- Navigation -->
  <x-navigation.app-navigation role="student" />

  <!-- Sidebar -->
  @include('components.sidebar.student-sidebar')

  <!-- Main Content - margins handled by CSS based on dir attribute -->
  <main class="pt-20 min-h-screen transition-all duration-300" id="main-content">
    <!-- Email Verification Banner -->
    <x-alerts.email-verification-banner />

    <div class="dynamic-content-wrapper px-4 sm:px-6 lg:px-8 py-6 md:py-8">
      <!-- Flash Messages -->
      @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg flex items-center gap-2" role="alert">
          <i class="ri-check-line text-xl"></i>
          <span>{{ session('success') }}</span>
          <button type="button" class="ms-auto text-green-700 hover:text-green-900" onclick="this.parentElement.remove()">
            <i class="ri-close-line text-xl"></i>
          </button>
        </div>
      @endif
      @if(session('error'))
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg flex items-center gap-2" role="alert">
          <i class="ri-error-warning-line text-xl"></i>
          <span>{{ session('error') }}</span>
          <button type="button" class="ms-auto text-red-700 hover:text-red-900" onclick="this.parentElement.remove()">
            <i class="ri-close-line text-xl"></i>
          </button>
        </div>
      @endif
      @if(session('info'))
        <div class="mb-4 p-4 bg-blue-100 border border-blue-400 text-blue-700 rounded-lg flex items-center gap-2" role="alert">
          <i class="ri-information-line text-xl"></i>
          <span>{{ session('info') }}</span>
          <button type="button" class="ms-auto text-blue-700 hover:text-blue-900" onclick="this.parentElement.remove()">
            <i class="ri-close-line text-xl"></i>
          </button>
        </div>
      @endif

      @isset($slot)
        {{ $slot }}
      @else
        @yield('content')
      @endisset
    </div>
  </main>

  <!-- Unified Toast Notification System -->
  <x-ui.toast-container />

  <!-- Unified Confirmation Modal -->
  <x-ui.confirmation-modal />

  {{ $scripts ?? '' }}

  @stack('scripts')
</body>
</html>