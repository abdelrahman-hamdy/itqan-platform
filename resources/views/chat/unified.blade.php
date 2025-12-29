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
  <!-- Chat CSS is loaded via resources/css/chat.css through Vite -->
  <!-- Academy-specific colors are applied via inline style below -->
  <style>
    :root {
      --wc-primary: {{ $academy->primary_color ?? '#6366f1' }};
      --wc-primary-hover: {{ $academy->secondary_color ?? '#4f46e5' }};
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

  </script>

  <!-- WireChat Real-Time Bridge -->
  <script src="{{ asset('js/wirechat-realtime.js') }}?v={{ time() }}"></script>
@endpush
