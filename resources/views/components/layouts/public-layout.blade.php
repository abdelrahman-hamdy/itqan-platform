<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $academy->name ?? __('components.layouts.academy_default') }} - {{ $title ?? __('components.layouts.homepage') }}</title>
  <meta name="description" content="{{ $description ?? __('components.layouts.academy_elearning') }}">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- Vite Assets (Compiled CSS & JS) -->
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  <!-- RemixIcon -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
  <!-- Flag Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flag-icons@7.2.3/css/flag-icons.min.css">

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
</head>

<body class="bg-gray-50 text-gray-900 font-sans">
  <!-- Navigation -->
  @include('components.navigation.public-nav')
  
  <!-- Main Content -->
  <main class="pt-8 min-h-screen" id="main-content">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      {{ $slot ?? '' }}
      @yield('content')
    </div>
  </main>

  <!-- Footer -->
  <x-academy-footer :academy="$academy" />

  <!-- Confirmation Modal Component -->
  <div id="confirmModal" class="fixed inset-0 z-50 hidden modal-backdrop bg-black bg-opacity-50 items-center justify-center p-4">
    <div class="modal-content bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
      <div class="text-center">
        <div id="modalIcon" class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center">
          <!-- Icon will be inserted here -->
        </div>
        <h3 id="modalTitle" class="text-xl font-bold text-gray-900 mb-2"></h3>
        <p id="modalMessage" class="text-gray-600 mb-6"></p>
        <div class="flex gap-3 justify-center">
          <button id="modalCancel" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
            {{ __('common.actions.cancel') }}
          </button>
          <button id="modalConfirm" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary transition-colors">
            {{ __('common.actions.confirm') }}
          </button>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Translation strings for modal
    window.modalTranslations = {
      defaultTitle: "{{ __('common.confirm.title') }}",
      defaultMessage: "{{ __('common.messages.confirm_action') }}",
      confirmText: "{{ __('common.actions.confirm') }}",
      cancelText: "{{ __('common.actions.cancel') }}"
    };

    // Modal functionality
    window.showConfirmModal = function(options) {
      const modal = document.getElementById('confirmModal');
      const icon = document.getElementById('modalIcon');
      const title = document.getElementById('modalTitle');
      const message = document.getElementById('modalMessage');
      const confirmBtn = document.getElementById('modalConfirm');
      const cancelBtn = document.getElementById('modalCancel');

      // Set modal content using translations
      title.textContent = options.title || window.modalTranslations.defaultTitle;
      message.textContent = options.message || window.modalTranslations.defaultMessage;
      confirmBtn.textContent = options.confirmText || window.modalTranslations.confirmText;
      cancelBtn.textContent = options.cancelText || window.modalTranslations.cancelText;

      // Set icon
      if (options.type === 'danger') {
        icon.className = 'w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center bg-red-100';
        icon.innerHTML = '<i class="ri-error-warning-line text-3xl text-red-600"></i>';
        confirmBtn.className = 'px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors';
      } else if (options.type === 'success') {
        icon.className = 'w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center bg-green-100';
        icon.innerHTML = '<i class="ri-check-line text-3xl text-green-600"></i>';
        confirmBtn.className = 'px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors';
      } else {
        icon.className = 'w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center bg-blue-100';
        icon.innerHTML = '<i class="ri-question-line text-3xl text-blue-600"></i>';
        confirmBtn.className = 'px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary transition-colors';
      }

      // Show modal
      modal.classList.remove('hidden');
      modal.classList.add('flex');
      document.body.style.overflow = 'hidden';

      // Handle confirm
      const handleConfirm = () => {
        if (options.onConfirm) {
          options.onConfirm();
        }
        hideModal();
      };

      // Handle cancel
      const handleCancel = () => {
        if (options.onCancel) {
          options.onCancel();
        }
        hideModal();
      };

      // Remove old event listeners
      confirmBtn.onclick = null;
      cancelBtn.onclick = null;

      // Add new event listeners
      confirmBtn.onclick = handleConfirm;
      cancelBtn.onclick = handleCancel;

      // Close on backdrop click
      modal.onclick = (e) => {
        if (e.target === modal) {
          handleCancel();
        }
      };

      // Close on escape key
      const handleEscape = (e) => {
        if (e.key === 'Escape') {
          handleCancel();
          document.removeEventListener('keydown', handleEscape);
        }
      };
      document.addEventListener('keydown', handleEscape);

      function hideModal() {
        const content = modal.querySelector('.modal-content');
        content.classList.add('modal-exit');

        setTimeout(() => {
          modal.classList.add('hidden');
          modal.classList.remove('flex');
          content.classList.remove('modal-exit');
          document.body.style.overflow = '';
        }, 200);
      }
    };

    // Toast notification system (simple fallback for public pages)
    window.toast = {
      show: function(options) {
        const toast = document.createElement('div');
        const bgColor = options.type === 'success' ? 'bg-green-500' :
                       options.type === 'error' ? 'bg-red-500' : 'bg-blue-500';
        toast.className = `fixed bottom-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50 transition-opacity duration-300`;
        toast.textContent = options.message;
        document.body.appendChild(toast);
        setTimeout(() => {
          toast.classList.add('opacity-0');
          setTimeout(() => toast.remove(), 300);
        }, 3000);
      },
      info: function(message) {
        this.show({ type: 'info', message: message });
      }
    };
  </script>
</body>

</html>
