@php
  $user = auth()->user();
  $userType = $user ? $user->user_type : 'guest';
  $academy = $user->academy;
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>المحادثات - {{ $academy->name ?? 'أكاديمية إتقان' }}</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="user-id" content="{{ auth()->id() }}">
  <meta name="academy-id" content="{{ auth()->user()->academy_id ?? '' }}">

  <script src="https://cdn.tailwindcss.com/3.4.16"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;400;600;700;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">

  @vite(['resources/js/app.js'])

  @wirechatStyles

  <style>
    body {
      font-family: 'Cairo', sans-serif;
    }

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

  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: "{{ $academy->primary_color ?? '#4169E1' }}",
            secondary: "{{ $academy->secondary_color ?? '#6495ED' }}",
          },
        },
      },
    };
  </script>
</head>

<body class="bg-gray-50">
  @if(in_array($userType, ['quran_teacher', 'academic_teacher']))
    {{-- Teacher Navigation and Sidebar --}}
    @include('components.navigation.teacher-nav')
    @include('components.sidebar.teacher-sidebar')

    <main class="mr-80 pt-20 min-h-screen">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div id="chat-container" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
          {{ $slot }}
        </div>
      </div>
    </main>
  @elseif($userType === 'student')
    {{-- Student Navigation and Sidebar --}}
    @include('components.navigation.student-nav')
    @include('components.sidebar.student-sidebar')

    <main class="mr-80 pt-20 min-h-screen">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div id="chat-container" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
          {{ $slot }}
        </div>
      </div>
    </main>
  @else
    {{-- Default layout --}}
    <main class="min-h-screen p-4">
      <div class="max-w-7xl mx-auto">
        <div id="chat-container" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
          {{ $slot }}
        </div>
      </div>
    </main>
  @endif

  @wirechatAssets

  {{-- Echo is already loaded via @vite(['resources/js/app.js']) above, don't load again --}}

  {{-- WireChat Real-Time Bridge - wait for Echo to be available --}}
  <script src="{{ asset('js/wirechat-realtime.js') }}?v={{ time() }}"></script>
</body>
</html>
