{{-- Add Livewire and WireChat styles --}}
@push('styles')
  @livewireStyles
  @wirechatStyles
  <style>
    /* RTL Support for WireChat */
    [dir="rtl"] .wirechat-container {
      direction: rtl !important;
    }

    [dir="rtl"] .wirechat-conversations-list {
      text-align: right;
    }

    [dir="rtl"] .wirechat-message {
      text-align: right;
    }

    [dir="rtl"] .wirechat-input-container {
      direction: rtl;
    }

    /* Ensure full height for chat container */
    #chat-container {
      height: calc(100vh - 12rem);
      overflow: hidden;
    }

    .wirechat-wrapper {
      height: 100%;
    }

    /* Override WireChat default styles for better integration */
    .wc-container {
      height: 100% !important;
      border-radius: 12px;
      overflow: hidden;
    }

    /* Adjust for the platform's navigation and sidebar */
    @media (min-width: 768px) {
      #chat-container {
        height: calc(100vh - 8rem);
      }
    }
  </style>
@endpush

{{-- WireChat Component Container --}}
<div id="chat-container" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
  @livewire('wirechat')
</div>

{{-- Add Livewire and WireChat scripts --}}
@push('scripts')
  @livewireScripts
  @wirechatAssets

  {{-- WireChat Real-Time Integration --}}
  <script src="{{ asset('js/wirechat-realtime.js') }}"></script>

  <script>
    // Initialize WireChat when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {

      // Adjust height dynamically if needed
      function adjustChatHeight() {
        const navbar = document.querySelector('nav');
        const chatContainer = document.getElementById('chat-container');
        if (navbar && chatContainer) {
          const navHeight = navbar.offsetHeight;
          const windowHeight = window.innerHeight;
          chatContainer.style.height = (windowHeight - navHeight - 64) + 'px';
        }
      }

      // Initial adjustment
      adjustChatHeight();

      // Adjust on window resize
      window.addEventListener('resize', adjustChatHeight);
    });
  </script>
@endpush