<x-layouts.student-layout title="المحادثات">
  {{-- WireChat Component Container --}}
  <div id="chat-container" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden" style="height: calc(100vh - 12rem);">
    @livewire('wirechat')
  </div>

  @push('styles')
    @livewireStyles
    @wirechatStyles
    <style>
      /* RTL Support for WireChat */
      [dir="rtl"] .wirechat-container,
      [dir="rtl"] [data-wirechat] {
        direction: rtl !important;
      }

      [dir="rtl"] .wc-conversations-list {
        text-align: right;
      }

      [dir="rtl"] .wc-message {
        text-align: right;
      }

      [dir="rtl"] .wc-input-container {
        direction: rtl;
      }

      /* Override WireChat default styles for better integration */
      .wc-container,
      [data-wirechat] {
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

  @push('scripts')
    @livewireScripts
    @wirechatAssets
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        console.log('WireChat integrated for student');

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

        adjustChatHeight();
        window.addEventListener('resize', adjustChatHeight);
      });
    </script>
  @endpush
</x-layouts.student-layout>