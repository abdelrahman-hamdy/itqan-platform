@extends('layouts.app')

@section('title', 'المحادثات')

@section('content')
  <!-- Navigation -->
  @include('components.navigation.student-nav')

  <!-- Main Content -->
  <div class="pt-20 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      {{-- WireChat Component Container --}}
      <div id="chat-container" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden" style="height: calc(100vh - 12rem);">
        @livewire('wirechat')
      </div>
    </div>
  </div>
@endsection

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
  </style>
@endpush

@push('scripts')
  @livewireScripts
  @wirechatAssets
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      console.log('WireChat integrated for admin users');
    });
  </script>
@endpush