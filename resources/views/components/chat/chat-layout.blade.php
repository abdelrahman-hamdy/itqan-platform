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
      'nav' => 'components.navigation.student-nav',
      'sidebar' => 'components.sidebar.student-sidebar',
      'description' => 'تواصل مع معلميك وزملائك في الأكاديمية',
      'icon' => 'ri-user-line',
      'badge' => null
    ],
    'quran_teacher' => [
      'nav' => 'components.navigation.teacher-nav',
      'sidebar' => 'components.sidebar.teacher-sidebar',
      'description' => 'تواصل مع طلابك وإدارة الأكاديمية',
      'icon' => 'ri-graduation-cap-line',
      'badge' => 'معلم قرآن'
    ],
    'academic_teacher' => [
      'nav' => 'components.navigation.teacher-nav',
      'sidebar' => 'components.sidebar.teacher-sidebar',
      'description' => 'تواصل مع طلابك وإدارة الأكاديمية',
      'icon' => 'ri-book-line',
      'badge' => 'معلم أكاديمي'
    ],
    'parent' => [
      'nav' => 'components.navigation.parent-nav',
      'sidebar' => 'components.sidebar.parent-sidebar',
      'description' => 'تابع تقدم أطفالك وتواصل مع المعلمين',
      'icon' => 'ri-parent-line',
      'badge' => 'ولي أمر'
    ],
    'supervisor' => [
      'nav' => 'components.navigation.supervisor-nav',
      'sidebar' => 'components.sidebar.supervisor-sidebar',
      'description' => 'إدارة التواصل مع جميع أعضاء الأكاديمية',
      'icon' => 'ri-shield-user-line',
      'badge' => 'مشرف'
    ],
    'academy_admin' => [
      'nav' => 'components.navigation.academy-admin-nav',
      'sidebar' => 'components.sidebar.academy-admin-sidebar',
      'description' => 'إدارة التواصل العامة لجميع أعضاء الأكاديمية',
      'icon' => 'ri-admin-line',
      'badge' => 'مدير أكاديمية'
    ],
    'admin' => [
      'nav' => 'components.navigation.academy-admin-nav',
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
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $pageTitle ?? 'الدردشة - منصة إتقان' }}</title>
  <style>
    body, html {
      overscroll-behavior: none;
      -webkit-overflow-scrolling: touch;
    }
  </style>
  <meta name="description" content="نظام الرسائل - {{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }}">
  <script src="https://cdn.tailwindcss.com/3.4.16"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
  
  <!-- CSRF Token -->
  <meta name="csrf-token" content="{{ csrf_token() }}">
  
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: "{{ auth()->user()->academy->primary_color ?? '#4169E1' }}",
            secondary: "{{ auth()->user()->academy->secondary_color ?? '#6495ED' }}",
          },
        },
      },
    };
  </script>
</head>

<body class="bg-gray-50 text-gray-900">
  <!-- Role-Specific Navigation -->
  @include($config['nav'])
  
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
        console.log('🔧 Initializing chat configuration...');
        
        // Debug auth status
        @auth
            console.log('✅ User authenticated - ID: {{ auth()->id() }}');
        @else
            console.error('❌ User not authenticated');
        @endauth
        
        // Initialize chat configuration for Reverb WebSocket
        try {
            window.chatConfig = {
                userId: {{ auth()->id() ?? 'null' }},
                csrfToken: '{{ csrf_token() }}',
                usePublicChannel: {{ config('app.env') === 'local' && config('app.debug') ? 'true' : 'false' }}, // Public in debug mode, private in production
                authEndpoint: '{{ url('/broadcasting/auth') }}',
                apiEndpoints: {
                    contacts: '{{ url("/chat/api/getContacts") }}',
                    fetchMessages: '{{ url("/chat/api/fetchMessages") }}',
                    sendMessage: '{{ url("/chat/api/sendMessage") }}'
                }
            };
            
            console.log('✅ Chat config created successfully:', window.chatConfig);
            console.log('🚀 Loading pure Reverb chat system...');
        } catch (error) {
            console.error('❌ Failed to create chat config:', error);
            window.chatConfig = null;
        }
    </script>
    
    <!-- Load Pure Reverb Chat System -->
    <script src="{{ asset('js/chat-system-reverb.js') }}?v={{ time() }}"></script>

  <!-- Mobile Sidebar Toggle -->
  <button id="sidebar-toggle" class="fixed bottom-6 right-6 md:hidden bg-primary text-white p-3 rounded-full shadow-lg z-50">
    <i class="ri-menu-line text-xl"></i>
  </button>
</body>
</html>
