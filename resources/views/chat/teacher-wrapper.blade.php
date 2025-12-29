@extends('components.layouts.teacher')

@section('title', 'المحادثات')

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

    /* Ensure full height for chat container */
    #chat-container {
      height: calc(100vh - 12rem);
    }

    @media (min-width: 768px) {
      #chat-container {
        height: calc(100vh - 8rem);
      }
    }

    .wirechat-wrapper,
    .wc-container,
    [data-wirechat] {
      height: 100%;
    }
  </style>
@endpush

@section('content')
  <div id="chat-container" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden" style="height: calc(100vh - 12rem);">
    {!! $slot !!}
  </div>
@endsection

@push('scripts')
  @livewireScripts
  @wirechatAssets
  <script>
    document.addEventListener('DOMContentLoaded', function() {
    });
  </script>
@endpush