@php
  $user = auth()->user();
  $userType = $user->user_type;
  $academy = $user->academy;

  // Determine layout based on user type
  $layout = match($userType) {
    'student' => 'components.layouts.student',
    'quran_teacher', 'academic_teacher' => 'components.layouts.teacher',
    default => 'components.layouts.student'
  };
@endphp

@extends($layout)

@section('title', 'المحادثات')

@push('styles')
  @livewireStyles
  @wirechatStyles

  <style>
    /* Modern Chat Styles with RTL Support */
    :root {
      --wc-primary: {{ $academy->primary_color ?? '#6366f1' }};
      --wc-primary-hover: {{ $academy->secondary_color ?? '#4f46e5' }};
      --wc-light-primary: #ffffff;
      --wc-light-secondary: #f9fafb;
      --wc-light-border: #e5e7eb;
      --wc-light-accent: #f3f4f6;
      --wc-dark-primary: #1f2937;
      --wc-dark-secondary: #111827;
      --wc-dark-border: #374151;
      --wc-dark-accent: #4b5563;
    }

    /* Chat Container */
    #chat-container {
      height: calc(100vh - 9rem);
      min-height: 600px;
    }

    @media (min-width: 768px) {
      #chat-container {
        height: calc(100vh - 8rem);
      }
    }

    /* RTL Support for WireChat */
    [dir="rtl"] .wirechat-container,
    [dir="rtl"] [data-wirechat],
    [dir="rtl"] .wc-container {
      direction: rtl !important;
    }

    /* Conversation List Styling */
    .wc-conversations-list {
      border-left: 1px solid var(--wc-light-border);
    }

    [dir="rtl"] .wc-conversations-list {
      border-left: none;
      border-right: 1px solid var(--wc-light-border);
    }

    /* Message Bubbles */
    .wc-message-bubble {
      max-width: 70%;
      word-wrap: break-word;
      border-radius: 1rem;
      padding: 0.75rem 1rem;
      box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    }

    /* Sent messages (align right in RTL) */
    .wc-message-sent {
      background: linear-gradient(135deg, var(--wc-primary) 0%, var(--wc-primary-hover) 100%);
      color: white;
      margin-right: auto;
      border-bottom-left-radius: 0.25rem;
    }

    [dir="rtl"] .wc-message-sent {
      margin-right: 0;
      margin-left: auto;
      border-bottom-left-radius: 1rem;
      border-bottom-right-radius: 0.25rem;
    }

    /* Received messages */
    .wc-message-received {
      background: var(--wc-light-accent);
      color: #1f2937;
      margin-left: auto;
      border-bottom-right-radius: 0.25rem;
    }

    [dir="rtl"] .wc-message-received {
      margin-left: 0;
      margin-right: auto;
      border-bottom-right-radius: 1rem;
      border-bottom-left-radius: 0.25rem;
    }

    /* Input Container */
    .wc-input-container {
      border-top: 1px solid var(--wc-light-border);
      background: var(--wc-light-primary);
      padding: 1rem;
    }

    /* Scrollbar Styling */
    .wc-scrollbar::-webkit-scrollbar {
      width: 6px;
    }

    .wc-scrollbar::-webkit-scrollbar-track {
      background: var(--wc-light-accent);
    }

    .wc-scrollbar::-webkit-scrollbar-thumb {
      background: var(--wc-light-border);
      border-radius: 3px;
    }

    .wc-scrollbar::-webkit-scrollbar-thumb:hover {
      background: #9ca3af;
    }

    /* Avatar Styling */
    .wc-avatar {
      width: 2.5rem;
      height: 2.5rem;
      border-radius: 9999px;
      object-fit: cover;
      border: 2px solid white;
      box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
    }

    /* Online Status Indicator */
    .wc-status-online {
      width: 0.75rem;
      height: 0.75rem;
      background: #10b981;
      border: 2px solid white;
      border-radius: 9999px;
      position: absolute;
      bottom: 0;
      right: 0;
    }

    [dir="rtl"] .wc-status-online {
      right: auto;
      left: 0;
    }

    /* Conversation Item Hover */
    .wc-conversation-item {
      transition: all 0.2s ease;
      border-radius: 0.75rem;
      padding: 0.75rem;
      cursor: pointer;
    }

    .wc-conversation-item:hover {
      background: var(--wc-light-accent);
      transform: translateX(-2px);
    }

    [dir="rtl"] .wc-conversation-item:hover {
      transform: translateX(2px);
    }

    .wc-conversation-item.active {
      background: var(--wc-light-accent);
      border-right: 3px solid var(--wc-primary);
    }

    [dir="rtl"] .wc-conversation-item.active {
      border-right: none;
      border-left: 3px solid var(--wc-primary);
    }

    /* Button Styles */
    .wc-btn {
      transition: all 0.2s ease;
      border-radius: 0.5rem;
      padding: 0.5rem 1rem;
      font-weight: 500;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    .wc-btn-primary {
      background: var(--wc-primary);
      color: white;
    }

    .wc-btn-primary:hover {
      background: var(--wc-primary-hover);
      transform: translateY(-1px);
      box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    }

    /* Loading Animation */
    .wc-loading {
      display: inline-block;
      width: 1rem;
      height: 1rem;
      border: 2px solid var(--wc-light-border);
      border-radius: 50%;
      border-top-color: var(--wc-primary);
      animation: wc-spin 0.6s linear infinite;
    }

    @keyframes wc-spin {
      to { transform: rotate(360deg); }
    }

    /* Typing Indicator */
    .wc-typing-indicator {
      display: flex;
      gap: 0.25rem;
      padding: 0.5rem 1rem;
    }

    .wc-typing-dot {
      width: 0.5rem;
      height: 0.5rem;
      background: #9ca3af;
      border-radius: 50%;
      animation: wc-typing 1.4s ease-in-out infinite;
    }

    .wc-typing-dot:nth-child(2) { animation-delay: 0.2s; }
    .wc-typing-dot:nth-child(3) { animation-delay: 0.4s; }

    @keyframes wc-typing {
      0%, 60%, 100% { transform: translateY(0); }
      30% { transform: translateY(-10px); }
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
      #chat-container {
        height: calc(100vh - 8rem);
      }

      .wc-message-bubble {
        max-width: 85%;
      }
    }

    /* Dark Mode Support */
    @media (prefers-color-scheme: dark) {
      :root {
        --wc-light-primary: #1f2937;
        --wc-light-secondary: #111827;
        --wc-light-border: #374151;
        --wc-light-accent: #374151;
      }

      .wc-message-received {
        background: var(--wc-dark-accent);
        color: white;
      }
    }
  </style>
@endpush

@section('content')
  <!-- Page Header -->
  <div class="mb-6">
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 mb-1">💬 المحادثات</h1>
        <p class="text-gray-600">تواصل مع {{ $userType === 'student' ? 'معلميك وزملائك' : 'طلابك وإدارة الأكاديمية' }}</p>
      </div>

      <!-- User Info Badge -->
      <div class="flex items-center gap-3">
        <div class="bg-white rounded-xl px-4 py-2.5 shadow-sm border border-gray-200">
          <div class="flex items-center gap-2 text-sm">
            <div class="relative">
              <img src="{{ auth()->user()->avatar ?? 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) }}"
                   alt="{{ auth()->user()->name }}"
                   class="w-8 h-8 rounded-full border-2 border-white shadow-sm">
              <span class="wc-status-online"></span>
            </div>
            <div>
              <div class="font-medium text-gray-900">{{ auth()->user()->name }}</div>
              <div class="text-xs text-gray-500">متصل الآن</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Chat Container -->
  <div id="chat-container" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="w-full h-full flex" style="min-height: 600px;">
      {{ $slot }}
    </div>
  </div>

  <!-- Info Card (Below Chat) -->
  <div class="mt-4 bg-blue-50 border border-blue-200 rounded-xl p-4">
    <div class="flex items-start gap-3">
      <i class="ri-information-line text-blue-600 text-xl mt-0.5"></i>
      <div class="flex-1">
        <h3 class="font-medium text-blue-900 mb-1">نصائح للاستخدام</h3>
        <ul class="text-sm text-blue-800 space-y-1">
          <li>• يمكنك إرسال الرسائل النصية والصور والملفات</li>
          <li>• استخدم أيقونة 😊 لإضافة رموز تعبيرية</li>
          <li>• سيتم إعلامك فوراً عند استلام رسائل جديدة</li>
        </ul>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  @livewireScripts
  @wirechatAssets

  <script>
    // WireChat Configuration
    window.chatConfig = {
      userId: {{ auth()->id() }},
      csrfToken: '{{ csrf_token() }}',
      reverbKey: '{{ config('broadcasting.connections.reverb.key') }}',
      reverbHost: '{{ config('broadcasting.connections.reverb.options.host') }}',
      reverbPort: {{ (int) config('broadcasting.connections.reverb.options.port') }},
      reverbScheme: '{{ config('broadcasting.connections.reverb.options.scheme') }}',
      participantEncodedType: '{{ \Namu\WireChat\Helpers\MorphClassResolver::encode(\App\Models\User::class) }}',
    };

    console.log('✅ Unified chat initialized for {{ $userType }}');
  </script>

  <!-- WireChat Real-Time Bridge -->
  <script src="{{ asset('js/wirechat-realtime.js') }}?v={{ time() }}"></script>
@endpush
