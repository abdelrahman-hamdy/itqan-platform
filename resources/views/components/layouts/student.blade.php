<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $title ?? auth()->user()->academy->name ?? 'أكاديمية إتقان' }}</title>
  <meta name="description" content="{{ $description ?? 'منصة التعلم الإلكتروني - ' . (auth()->user()->academy->name ?? 'أكاديمية إتقان') }}">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  
  <script src="https://cdn.tailwindcss.com/3.4.16"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
  
  <!-- LiveKit Client for Video Meetings - UMD Version for CORS compatibility -->
  <script>
    // LiveKit loading with fallback for CORS issues
    window.loadLiveKit = async function() {
      if (window.LiveKit) {
        console.log('LiveKit already loaded');
        return window.LiveKit;
      }
      
      try {
        // Try to load via script tag to avoid CORS issues
        console.log('Loading LiveKit via script tag...');
        
        return new Promise((resolve, reject) => {
          const script = document.createElement('script');
          // Try jsdelivr CDN first as it's often more reliable
          script.src = 'https://cdn.jsdelivr.net/npm/livekit-client@1.15.13/dist/livekit-client.umd.js';
          script.crossOrigin = 'anonymous';
          
          script.onload = function() {
            // Give the script a moment to expose globals
            setTimeout(() => {
              // Check various possible global names for LiveKit (including case variations)
              const livekit = window.LiveKit || window.LiveKitClient || window.livekit || window.LivekitClient;
              
              if (livekit) {
                window.LiveKit = livekit;
                console.log('LiveKit loaded successfully via UMD');
                resolve(livekit);
              } else {
                console.error('LiveKit globals after load:', Object.keys(window).filter(k => k.toLowerCase().includes('livekit')));
                reject(new Error('LiveKit UMD failed to load - no global found'));
              }
            }, 100);
          };
          
          script.onerror = function(error) {
            console.error('Failed to load LiveKit UMD:', error);
            reject(new Error('Failed to load LiveKit script'));
          };
          
          document.head.appendChild(script);
        });
        
      } catch (error) {
        console.error('Failed to load LiveKit:', error);
        throw new Error('لا يمكن تحميل نظام الاجتماعات. تأكد من الاتصال بالإنترنت وحاول مرة أخرى.');
      }
    };
    
    // Try to load immediately
    document.addEventListener('DOMContentLoaded', function() {
      window.loadLiveKit().catch(error => {
        console.log('Initial LiveKit load failed, will retry when needed:', error.message);
      });
    });
  </script>
  
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: "{{ auth()->user()->academy->primary_color ?? '#4169E1' }}",
            secondary: "{{ auth()->user()->academy->secondary_color ?? '#6495ED' }}",
          },
          borderRadius: {
            none: "0px",
            sm: "4px",
            DEFAULT: "8px",
            md: "12px",
            lg: "16px",
            xl: "20px",
            "2xl": "24px",
            "3xl": "32px",
            full: "9999px",
            button: "8px",
          },
        },
      },
    };
  </script>
  
  <style>
    :where([class^="ri-"])::before {
      content: "\f3c2";
    }

    .card-hover {
      transition: all 0.3s ease;
    }

    .card-hover:hover {
      transform: translateY(-4px);
      box-shadow: 0 20px 40px rgba(65, 105, 225, 0.15);
    }

    .stats-counter {
      font-family: 'Tajawal', sans-serif;
      font-weight: bold;
    }
    
    /* Focus indicators */
    .focus\:ring-custom:focus {
      outline: 2px solid {{ auth()->user()->academy->primary_color ?? '#4169E1' }};
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
</head>

<body class="bg-gray-50 text-gray-900">
  <!-- Navigation -->
  @include('components.navigation.student-nav')
  
  <!-- Sidebar -->
  @include('components.sidebar.student-sidebar')

  <!-- Main Content -->
  <main class="mr-80 pt-20 min-h-screen" id="main-content">
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
  </script>

  <script>
    // Toast notification function
    window.showToast = function(message, type = 'success') {
      const toast = document.createElement('div');
      toast.className = `fixed top-24 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white transition-all duration-300 transform translate-x-full ${
        type === 'success' ? 'bg-green-600' : 
        type === 'error' ? 'bg-red-600' : 
        type === 'warning' ? 'bg-yellow-600' : 
        'bg-blue-600'
      }`;
      
      const icon = type === 'success' ? 'ri-check-line' : 
                   type === 'error' ? 'ri-close-line' : 
                   type === 'warning' ? 'ri-alert-line' : 
                   'ri-information-line';
      
      toast.innerHTML = `
        <div class="flex items-center">
          <i class="${icon} ml-2"></i>
          <span>${message}</span>
        </div>
      `;
      
      document.body.appendChild(toast);
      
      // Animate in
      setTimeout(() => {
        toast.classList.remove('translate-x-full');
      }, 100);
      
      // Auto remove after 3 seconds
      setTimeout(() => {
        toast.classList.add('translate-x-full');
        setTimeout(() => {
          if (document.body.contains(toast)) {
            document.body.removeChild(toast);
          }
        }, 300);
      }, 3000);
    };
  </script>
  
  {{ $scripts ?? '' }}
</body>
</html>