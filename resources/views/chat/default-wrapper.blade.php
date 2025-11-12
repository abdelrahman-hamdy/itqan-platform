@extends('layouts.app')

@section('title', 'المحادثات')

@push('styles')
  @livewireStyles
  @wirechatStyles
  <style>
    /* RTL Support for WireChat */
    body {
      font-family: 'Cairo', sans-serif;
    }

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
@endpush

@section('content')
  <!-- Navigation -->
  @include('components.navigation.student-nav')

  <!-- Main Content -->
  <div class="pt-20 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div id="chat-container" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden" style="height: calc(100vh - 12rem);">
        {!! $slot !!}
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  @livewireScripts
  @wirechatAssets
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      console.log('WireChat integrated');
    });
  </script>
@endpush