<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ __('public.booking.trial.title') }} - {{ $teacher->full_name }} - {{ $academy->name ?? __('common.academy_default') }}</title>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

  <!-- Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">

  <!-- Vite Assets -->
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  @php
    // Generate CSS variables for primary color
    $primaryColor = $academy->brand_color ?? \App\Enums\TailwindColor::SKY;
    $primaryVars = $primaryColor->generateCssVariables('primary');
  @endphp

  <!-- Academy Colors CSS Variables -->
  <style>
    :root {
      @foreach($primaryVars as $varName => $varValue)
      {{ $varName }}: {{ $varValue }};
      @endforeach
    }
  </style>
</head>

<body class="bg-gray-50 font-sans">

  @php
    // Get academy branding
    $brandColor = $academy && $academy->brand_color ? $academy->brand_color->value : 'sky';
    $brandColorClass = "text-{$brandColor}-600";
    $brandBgClass = "bg-{$brandColor}-600";
    $brandBgHoverClass = "hover:bg-{$brandColor}-700";
  @endphp

  <!-- Header -->
  <x-booking.top-bar
    :academy="$academy"
    :title="__('public.booking.top_bar.trial_booking')"
    :backRoute="route('quran-teachers.show', ['subdomain' => $academy->subdomain, 'teacherId' => $teacher->id])" />


  <!-- Main Content -->
  <section class="py-8">
    <div class="container mx-auto px-4 max-w-4xl">
      
      <!-- Teacher Info -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
        <div class="flex items-center gap-4">
          <x-avatar
            :user="$teacher"
            size="lg"
            userType="quran_teacher"
            :gender="$teacher->gender ?? $teacher->user?->gender ?? 'male'" />
          <div>
            <h2 class="text-xl font-bold text-gray-900">{{ $teacher->full_name }}</h2>
            <p class="text-gray-600">{{ __('public.booking.trial.certified_teacher') }}</p>
            <p class="text-sm text-gray-500">{{ $teacher->teacher_code }}</p>
          </div>
        </div>
      </div>

      <!-- Trial Session Form -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="mb-6">
          <h3 class="text-2xl font-bold text-gray-900 mb-2">
            <i class="ri-gift-line {{ $brandColorClass }} {{ app()->getLocale() === 'ar' ? 'ml-2' : 'mr-2' }}"></i>
            {{ __('public.booking.trial.title') }}
          </h3>
          <p class="text-gray-600">{{ __('public.booking.trial.subtitle') }}</p>
        </div>

        <form id="trial-form" action="{{ route('quran-teachers.trial.submit', ['subdomain' => $academy->subdomain, 'teacherId' => $teacher->id]) }}" method="POST" class="space-y-6">
          @csrf
          <input type="hidden" name="teacher_id" value="{{ $teacher->id }}">
          <input type="hidden" name="academy_id" value="{{ $academy->id }}">

          <!-- Error Messages -->
          @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
              <div class="flex">
                <i class="ri-error-warning-line text-red-500 mt-0.5 {{ app()->getLocale() === 'ar' ? 'ml-2' : 'mr-2' }}"></i>
                <div>
                  <h4 class="font-medium mb-1">{{ __('public.booking.quran.errors.title') }}</h4>
                  <ul class="text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                      <li>• {{ $error }}</li>
                    @endforeach
                  </ul>
                </div>
              </div>
            </div>
          @endif

          <!-- Success Messages -->
          @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
              <div class="flex">
                <i class="ri-check-line text-green-500 mt-0.5 ms-2"></i>
                <div>{{ session('success') }}</div>
              </div>
            </div>
          @endif

          <!-- Error Messages -->
          @if (session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
              <div class="flex">
                <i class="ri-error-warning-line text-red-500 mt-0.5 ms-2"></i>
                <div>{{ session('error') }}</div>
              </div>
            </div>
          @endif

          <!-- Student Info Display -->
          <div class="bg-gray-50 border border-gray-200 rounded-lg p-5 mb-6">
            <div class="flex items-center gap-2 mb-4">
              <div class="w-8 h-8 rounded-lg bg-{{ $brandColor }}-100 flex items-center justify-center">
                <i class="ri-user-line {{ $brandColorClass }} text-lg"></i>
              </div>
              <h4 class="font-semibold text-gray-900">{{ __('public.booking.quran.form.student_info') }}</h4>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
              <div class="flex flex-col gap-1">
                <span class="{{ $brandColorClass }} font-medium text-xs">{{ __('public.booking.quran.form.name') }}</span>
                <span class="font-medium text-gray-900">{{ auth()->user()->studentProfile?->full_name ?? auth()->user()->name }}</span>
              </div>
              <div class="flex flex-col gap-1">
                <span class="{{ $brandColorClass }} font-medium text-xs">{{ __('public.booking.quran.form.email') }}</span>
                <span class="font-medium text-gray-900">{{ auth()->user()->email }}</span>
              </div>
              @if(auth()->user()->studentProfile?->phone)
              <div class="flex flex-col gap-1">
                <span class="{{ $brandColorClass }} font-medium text-xs">{{ __('public.booking.quran.form.phone') }}</span>
                <span class="font-medium text-gray-900">{{ auth()->user()->studentProfile->phone }}</span>
              </div>
              @endif
              @if(auth()->user()->studentProfile?->birth_date)
              <div class="flex flex-col gap-1">
                <span class="{{ $brandColorClass }} font-medium text-xs">{{ __('public.booking.quran.form.age') }}</span>
                <span class="font-medium text-gray-900">{{ floor(auth()->user()->studentProfile->birth_date->diffInYears(now())) }} {{ __('public.booking.quran.form.years') }}</span>
              </div>
              @endif
            </div>
          </div>

          <!-- Current Level -->
          <div>
            <label for="current_level" class="block text-sm font-medium text-gray-700 mb-2">{{ __('public.booking.quran.form.current_level') }}</label>
            <select id="current_level" name="current_level" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-{{ $brandColor }}-500 focus:border-{{ $brandColor }}-600">
              <option value="">{{ __('public.booking.quran.form.select_level') }}</option>
              @foreach(\App\Enums\QuranLearningLevel::cases() as $level)
                <option value="{{ $level->value }}">{{ $level->label() }}</option>
              @endforeach
            </select>
          </div>

          <!-- Goals -->
          <div>
            <label for="learning_goals" class="block text-sm font-medium text-gray-700 mb-2">{{ __('public.booking.quran.form.learning_goals') }}</label>
            <div class="space-y-2">
              <label class="flex items-center">
                <input type="checkbox" name="learning_goals[]" value="reading" class="text-{{ $brandColor }}-600 focus:ring-{{ $brandColor }}-500 border-gray-300 rounded">
                <span class="{{ app()->getLocale() === 'ar' ? 'mr-2' : 'ml-2' }}">{{ __('public.booking.quran.form.goals.reading') }}</span>
              </label>
              <label class="flex items-center">
                <input type="checkbox" name="learning_goals[]" value="tajweed" class="text-{{ $brandColor }}-600 focus:ring-{{ $brandColor }}-500 border-gray-300 rounded">
                <span class="{{ app()->getLocale() === 'ar' ? 'mr-2' : 'ml-2' }}">{{ __('public.booking.quran.form.goals.tajweed') }}</span>
              </label>
              <label class="flex items-center">
                <input type="checkbox" name="learning_goals[]" value="memorization" class="text-{{ $brandColor }}-600 focus:ring-{{ $brandColor }}-500 border-gray-300 rounded">
                <span class="{{ app()->getLocale() === 'ar' ? 'mr-2' : 'ml-2' }}">{{ __('public.booking.quran.form.goals.memorization') }}</span>
              </label>
              <label class="flex items-center">
                <input type="checkbox" name="learning_goals[]" value="improvement" class="text-{{ $brandColor }}-600 focus:ring-{{ $brandColor }}-500 border-gray-300 rounded">
                <span class="{{ app()->getLocale() === 'ar' ? 'mr-2' : 'ml-2' }}">{{ __('public.booking.quran.form.goals.improvement') }}</span>
              </label>
            </div>
          </div>

          <!-- Preferred Time -->
          <div>
            <label for="preferred_time" class="block text-sm font-medium text-gray-700 mb-2">{{ __('public.booking.quran.form.preferred_time') }}</label>
            <select id="preferred_time" name="preferred_time"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-{{ $brandColor }}-500 focus:border-{{ $brandColor }}-600">
              <option value="">{{ __('public.booking.quran.form.preferred_time') }}</option>
              <option value="morning">{{ __('public.booking.quran.form.time_slots.morning') }}</option>
              <option value="afternoon">{{ __('public.booking.quran.form.time_slots.afternoon') }}</option>
              <option value="evening">{{ __('public.booking.quran.form.time_slots.evening') }}</option>
            </select>
          </div>

          <!-- Additional Notes -->
          <div>
            <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">{{ __('public.booking.quran.form.notes') }}</label>
            <textarea id="notes" name="notes" rows="4"
                      placeholder="{{ __('public.booking.quran.form.notes_placeholder') }}"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-{{ $brandColor }}-500 focus:border-{{ $brandColor }}-600"></textarea>
          </div>

          <!-- Information -->
          <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
            <div class="flex items-start gap-2">
              <i class="ri-information-line text-amber-600 text-xl mt-0.5"></i>
              <div class="flex-1">
                <h4 class="font-semibold text-amber-900 mb-2">{{ __('public.booking.trial.info_title') }}</h4>
                <ul class="text-sm text-amber-800 space-y-1.5">
                  <li>• {{ __('public.booking.trial.info_1') }}</li>
                  <li>• {{ __('public.booking.trial.info_2') }}</li>
                  <li>• {{ __('public.booking.trial.info_3') }}</li>
                  <li>• {{ __('public.booking.trial.info_4') }}</li>
                </ul>
              </div>
            </div>
          </div>



          <!-- Submit Button -->
          <div class="flex gap-4">
            <button type="submit" id="main-submit-btn"
                    class="flex-1 {{ $brandBgClass }} text-white py-3 px-6 rounded-lg font-medium {{ $brandBgHoverClass }} transition-colors">
              <i class="ri-send-plane-line {{ app()->getLocale() === 'ar' ? 'ml-2' : 'mr-2' }}"></i>
              {{ __('public.booking.trial.submit') }}
            </button>

            <a href="{{ route('quran-teachers.show', ['subdomain' => $academy->subdomain, 'teacherId' => $teacher->id]) }}"
               class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
              {{ __('public.booking.quran.form.cancel') }}
            </a>
          </div>
        </form>

      </div>

    </div>
  </section>

  <script>
    // Handle form submission with loading state
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('trial-form');
      if (!form) return;

      const submitBtn = form.querySelector('button[type="submit"]');

      form.addEventListener('submit', function() {
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> جاري الإرسال...';
        }
      });
    });
  </script>


</body>
</html>