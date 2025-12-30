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

  </script>

  <!-- Unified Toast Notification System -->
  <x-ui.toast-container />

  <!-- Unified Confirmation Modal -->
  <x-ui.confirmation-modal />

  {{ $scripts ?? '' }}
  @stack('scripts')
</body>
</html>
