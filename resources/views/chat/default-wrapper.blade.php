<x-layouts.student title="المحادثات">
  <x-slot name="head">
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

      /* Ensure full height for chat container */
      #chat-container {
        height: calc(100vh - 10rem);
      }

      .wirechat-wrapper,
      .wc-container,
      [data-wirechat] {
        height: 100%;
      }
    </style>
  </x-slot>

  <div id="chat-container" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden" style="height: calc(100vh - 12rem);">
    {!! $slot !!}
  </div>

  <x-slot name="scripts">
    @wirechatAssets
  </x-slot>
</x-layouts.student>