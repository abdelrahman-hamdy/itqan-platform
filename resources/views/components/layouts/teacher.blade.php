<!DOCTYPE html>
<html lang="ar" dir="rtl">

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

  <!-- Main Content -->
  <main class="mr-0 md:mr-80 pt-20 min-h-screen transition-all duration-300" id="main-content">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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
            إلغاء
          </button>
          <button id="modalConfirm" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary transition-colors">
            تأكيد
          </button>
        </div>
      </div>
    </div>
  </div>

  @livewireScripts

  <script>
    // Modal functionality
    window.showConfirmModal = function(options) {
      const modal = document.getElementById('confirmModal');
      const icon = document.getElementById('modalIcon');
      const title = document.getElementById('modalTitle');
      const message = document.getElementById('modalMessage');
      const confirmBtn = document.getElementById('modalConfirm');
      const cancelBtn = document.getElementById('modalCancel');
      
      // Set modal content
      title.textContent = options.title || 'تأكيد العملية';
      message.textContent = options.message || 'هل أنت متأكد من المتابعة؟';
      confirmBtn.textContent = options.confirmText || 'تأكيد';
      cancelBtn.textContent = options.cancelText || 'إلغاء';
      
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

    // Toast notification function
    window.showToast = function(message, type = 'success') {
      const toast = document.createElement('div');
      toast.className = `fixed top-24 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white transition-all duration-300 transform translate-x-full ${
        type === 'success' ? 'bg-green-600' : 
        type === 'error' ? 'bg-red-600' : 
        type === 'warning' ? 'bg-yellow-600' : 'bg-blue-600'
      }`;
      
      const icon = type === 'success' ? 'ri-check-line' : 
                   type === 'error' ? 'ri-error-warning-line' : 
                   type === 'warning' ? 'ri-alert-line' : 'ri-information-line';
      
      toast.innerHTML = `
        <div class="flex items-center space-x-2 space-x-reverse">
          <i class="${icon}"></i>
          <span>${message}</span>
        </div>
      `;
      
      document.body.appendChild(toast);
      
      // Animate in
      setTimeout(() => {
        toast.classList.remove('translate-x-full');
      }, 100);
      
      // Auto remove after 5 seconds
      setTimeout(() => {
        toast.classList.add('translate-x-full');
        setTimeout(() => {
          document.body.removeChild(toast);
        }, 300);
      }, 5000);
    };
  </script>

  <!-- Unified Confirmation Modal -->
  <x-ui.confirmation-modal />

  {{ $scripts ?? '' }}
  @stack('scripts')
</body>
</html>
