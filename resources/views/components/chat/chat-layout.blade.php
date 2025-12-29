{{-- 
  Chat Layout Component
  Unified layout for all user types with role-specific navigation
--}}
@props([
    'userRole' => auth()->user()->user_type ?? 'student',
    'pageTitle' => 'الرسائل والمحادثات',
    'pageDescription' => 'نظام التواصل المتطور'
])

@php
  // Role-specific configuration
  $roleConfig = [
    'student' => [
      'navRole' => 'student',
      'sidebar' => 'components.sidebar.student-sidebar',
      'description' => 'تواصل مع معلميك وزملائك في الأكاديمية',
      'icon' => 'ri-user-line',
      'badge' => null
    ],
    'quran_teacher' => [
      'navRole' => 'teacher',
      'sidebar' => 'components.sidebar.teacher-sidebar',
      'description' => 'تواصل مع طلابك وإدارة الأكاديمية',
      'icon' => 'ri-graduation-cap-line',
      'badge' => 'معلم قرآن'
    ],
    'academic_teacher' => [
      'navRole' => 'teacher',
      'sidebar' => 'components.sidebar.teacher-sidebar',
      'description' => 'تواصل مع طلابك وإدارة الأكاديمية',
      'icon' => 'ri-book-line',
      'badge' => 'معلم أكاديمي'
    ],
    'parent' => [
      'navRole' => 'student', // Using student nav as fallback for now
      'sidebar' => 'components.sidebar.parent-sidebar',
      'description' => 'تابع تقدم أطفالك وتواصل مع المعلمين',
      'icon' => 'ri-parent-line',
      'badge' => 'ولي أمر'
    ],
    'supervisor' => [
      'navRole' => 'teacher', // Using teacher nav as fallback for now
      'sidebar' => 'components.sidebar.supervisor-sidebar',
      'description' => 'إدارة التواصل مع جميع أعضاء الأكاديمية',
      'icon' => 'ri-shield-user-line',
      'badge' => 'مشرف'
    ],
    'academy_admin' => [
      'navRole' => 'teacher', // Using teacher nav as fallback for now
      'sidebar' => 'components.sidebar.academy-admin-sidebar',
      'description' => 'إدارة التواصل العامة لجميع أعضاء الأكاديمية',
      'icon' => 'ri-admin-line',
      'badge' => 'مدير أكاديمية'
    ],
    'admin' => [
      'navRole' => 'teacher', // Using teacher nav as fallback for now
      'sidebar' => 'components.sidebar.academy-admin-sidebar',
      'description' => 'إدارة التواصل العامة لجميع أعضاء الأكاديمية',
      'icon' => 'ri-shield-star-line',
      'badge' => 'مدير عام'
    ]
  ];

  $config = $roleConfig[$userRole] ?? $roleConfig['student'];
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <x-app-head :title="$pageTitle ?? 'الدردشة - منصة إتقان'" :description="'نظام الرسائل - ' . (auth()->user()->academy->name ?? 'أكاديمية إتقان')">
    <style>
      body, html {
        overscroll-behavior: none;
        -webkit-overflow-scrolling: touch;
      }
    </style>

    <!-- Pacifico Font for Decorative Elements -->
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">

    <!-- CSRF Token and User ID for Chat -->
    <meta name="user-id" content="{{ auth()->id() }}">

    <!-- Chat System Styles -->
    <link rel="stylesheet" href="{{ asset('css/chat-enhanced.css') }}?v={{ time() }}">

    <!-- Pusher and Laravel Echo for Real-time -->
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
  </x-app-head>
</head>

<body class="bg-gray-50 text-gray-900">
  <!-- Role-Specific Navigation -->
  <x-navigation.app-navigation :role="$config['navRole']" />
  
  <!-- Role-Specific Sidebar -->
  @include($config['sidebar'])

  <!-- Main Content -->
  <main class="mr-80 pt-20 min-h-screen" id="main-content">
    <div class="p-6">
      <!-- Unified Page Header -->
      <div class="mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $pageTitle }}</h1>
            <p class="text-gray-600">{{ $config['description'] }}</p>
          </div>
          <div class="flex items-center space-x-3 space-x-reverse">
            <div class="bg-white rounded-lg px-4 py-2 shadow-sm border">
              <div class="flex items-center text-sm text-gray-600">
                <i class="{{ $config['icon'] }} mr-2"></i>
                <span>{{ auth()->user()->name }}</span>
                @if($config['badge'])
                  <span class="mr-2 text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">{{ $config['badge'] }}</span>
                @endif
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Unified Chat Interface -->
      @include('components.chat.chat-interface')
    </div>
  </main>

    <!-- Initialize Chat Configuration -->
    <script>
        
        // Debug auth status
        @auth
        @else
        @endauth
        
        // Initialize chat configuration for Reverb WebSocket
        try {
            window.chatConfig = {
                userId: {{ auth()->id() ?? 'null' }},
                csrfToken: '{{ csrf_token() }}',
                usePublicChannel: {{ config('app.env') === 'local' && config('app.debug') ? 'true' : 'false' }}, // Public in debug mode, private in production
                authEndpoint: '{{ url('/broadcasting/auth') }}',
                apiEndpoints: {
                    contacts: '{{ route("contacts.get", ["subdomain" => request()->route("subdomain") ?? (auth()->user()->academy->subdomain ?? "itqan-academy")]) }}',
                    fetchMessages: '{{ route("fetch.messages", ["subdomain" => request()->route("subdomain") ?? (auth()->user()->academy->subdomain ?? "itqan-academy")]) }}',
                    sendMessage: '{{ route("send.message", ["subdomain" => request()->route("subdomain") ?? (auth()->user()->academy->subdomain ?? "itqan-academy")]) }}'
                },
                @if(isset($autoOpenUserId))
                autoOpenUserId: {!! json_encode($autoOpenUserId) !!},
                @endif
            };
            
        } catch (error) {
            window.chatConfig = null;
        }
    </script>
    
    <!-- WireChat Real-Time Bridge (Old custom chat system replaced with WireChat) -->
    {{-- <script src="{{ asset('js/chat-system-reverb.js') }}?v={{ time() }}"></script> --}}
    <script src="{{ asset('js/wirechat-realtime.js') }}?v={{ time() }}"></script>

  <!-- Mobile Sidebar Toggle -->
  <button id="sidebar-toggle" class="fixed bottom-6 right-6 md:hidden bg-primary text-white p-3 rounded-full shadow-lg z-50">
    <i class="ri-menu-line text-xl"></i>
  </button>
</body>
</html>
