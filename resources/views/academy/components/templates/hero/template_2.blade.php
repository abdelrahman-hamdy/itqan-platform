@php
    // Get brand color with hex values
    $brandColor = $academy?->brand_color ?? \App\Enums\TailwindColor::SKY;
    $brandHex50 = $brandColor->getHexValue(50);
    $brandHex100 = $brandColor->getHexValue(100);
    $brandHex200 = $brandColor->getHexValue(200);
    $brandHex500 = $brandColor->getHexValue(500);
    $brandHex600 = $brandColor->getHexValue(600);
    $brandHex700 = $brandColor->getHexValue(700);

    $heroHeading = $heading ?? __('academy.hero.default_heading');
    $heroSubheading = $subheading ?? __('academy.hero.default_subheading');
@endphp

<!-- Hero Section - Template 2: Educational Platform Design -->
<section id="hero-section" class="relative flex items-center overflow-hidden bg-white py-20 sm:py-16 lg:py-0" role="main" style="min-height: min(100vh, 56rem);">
  <!-- Educational Background Pattern -->
  <div class="absolute inset-0 overflow-hidden">
    <!-- Soft Gradient Overlay -->
    <div class="absolute inset-0" style="background: radial-gradient(ellipse at center, {{ $brandHex50 }}80, white 70%);"></div>

    <!-- SVG Educational Pattern (Books/Graduation) -->
    <div class="absolute inset-0 opacity-[0.06]">
      <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
        <defs>
          <pattern id="edu-pattern" x="0" y="0" width="120" height="120" patternUnits="userSpaceOnUse">
            <!-- Open Book Shape -->
            <path d="M30 70 L30 40 Q45 35 60 40 Q75 35 90 40 L90 70 Q75 65 60 70 Q45 65 30 70Z" fill="none" stroke="{{ $brandHex500 }}" stroke-width="1.5" opacity="0.6"/>
            <line x1="60" y1="40" x2="60" y2="70" stroke="{{ $brandHex500 }}" stroke-width="1" opacity="0.4"/>
            <!-- Graduation Cap -->
            <polygon points="60,10 40,20 60,30 80,20" fill="none" stroke="{{ $brandHex500 }}" stroke-width="1.2" opacity="0.5"/>
            <line x1="60" y1="30" x2="60" y2="38" stroke="{{ $brandHex500 }}" stroke-width="1" opacity="0.4"/>
            <line x1="75" y1="22" x2="75" y2="32" stroke="{{ $brandHex500 }}" stroke-width="1" opacity="0.3"/>
            <circle cx="75" cy="33" r="2" fill="none" stroke="{{ $brandHex500 }}" stroke-width="0.8" opacity="0.3"/>
            <!-- Small Dots Accent -->
            <circle cx="10" cy="95" r="1.5" fill="{{ $brandHex500 }}" opacity="0.15"/>
            <circle cx="110" cy="95" r="1.5" fill="{{ $brandHex500 }}" opacity="0.15"/>
            <circle cx="10" cy="5" r="1.5" fill="{{ $brandHex500 }}" opacity="0.15"/>
            <circle cx="110" cy="5" r="1.5" fill="{{ $brandHex500 }}" opacity="0.15"/>
          </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#edu-pattern)" />
      </svg>
    </div>

    <!-- Subtle Dot Grid -->
    <div class="absolute inset-0 opacity-[0.04]" style="background-image: radial-gradient(circle, {{ $brandHex500 }} 1px, transparent 1px); background-size: 32px 32px;"></div>
  </div>

  <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
    <!-- Content Area -->
    <div class="text-center max-w-3xl mx-auto space-y-8">
      <!-- Floating Badge -->
      <div class="inline-flex items-center gap-3 px-5 py-2.5 rounded-lg shadow-sm animate-float"
           style="background-color: {{ $brandHex50 }}; border-inline-start: 3px solid {{ $brandHex500 }};">
        <i class="ri-book-read-line text-lg" style="color: {{ $brandHex600 }};"></i>
        <span class="text-sm font-semibold" style="color: {{ $brandHex700 }};">{{ __('academy.hero.badge_template2') }}</span>
      </div>

      <!-- Main Heading -->
      <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold leading-tight text-gray-900">
        {{ $heroHeading }}
      </h1>

      <!-- Subheading -->
      <p class="text-lg lg:text-xl text-gray-600 leading-relaxed max-w-2xl mx-auto">
        {{ $heroSubheading }}
      </p>

      <!-- CTA Button -->
      <div>
        <a href="{{ route('student.register', ['subdomain' => $academy->subdomain ?? 'test-academy']) }}"
           class="group relative inline-flex items-center gap-3 px-8 py-4 text-white rounded-2xl font-semibold text-lg transition-all duration-300 hover:shadow-xl hover:-translate-y-1"
           style="background-color: {{ $brandHex600 }};"
           onmouseover="this.style.backgroundColor='{{ $brandHex700 }}'"
           onmouseout="this.style.backgroundColor='{{ $brandHex600 }}'">
          <span>{{ __('academy.hero.cta_button') }}</span>
          <i class="ri-arrow-left-line text-xl transition-transform duration-300 group-hover:-translate-x-1 ltr:rotate-180 ltr:group-hover:translate-x-1"></i>
        </a>
      </div>
    </div>

    <!-- Feature Cards -->
    @if($academy->hero_show_boxes ?? true)
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 lg:gap-6 mt-14 lg:mt-16 max-w-4xl mx-auto">
      <!-- Expert Teachers -->
      <div class="group flex items-center gap-4 bg-white/80 backdrop-blur-sm rounded-xl p-4 lg:p-5 border border-gray-100 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md">
        <div class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center shadow-sm"
             style="background: linear-gradient(135deg, {{ $brandHex500 }}, {{ $brandHex600 }});">
          <i class="ri-user-star-line text-xl text-white"></i>
        </div>
        <div class="min-w-0">
          <h3 class="text-sm font-bold text-gray-900">{{ __('academy.hero.features.expert_teachers_title') }}</h3>
          <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ __('academy.hero.features.expert_teachers_desc') }}</p>
        </div>
      </div>

      <!-- Interactive Learning -->
      <div class="group flex items-center gap-4 bg-white/80 backdrop-blur-sm rounded-xl p-4 lg:p-5 border border-gray-100 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md">
        <div class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center shadow-sm"
             style="background: linear-gradient(135deg, {{ $brandHex500 }}, {{ $brandHex600 }});">
          <i class="ri-live-line text-xl text-white"></i>
        </div>
        <div class="min-w-0">
          <h3 class="text-sm font-bold text-gray-900">{{ __('academy.hero.features.interactive_learning_title') }}</h3>
          <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ __('academy.hero.features.interactive_learning_desc') }}</p>
        </div>
      </div>

      <!-- Flexible Schedule -->
      <div class="group flex items-center gap-4 bg-white/80 backdrop-blur-sm rounded-xl p-4 lg:p-5 border border-gray-100 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md">
        <div class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center shadow-sm"
             style="background: linear-gradient(135deg, {{ $brandHex500 }}, {{ $brandHex600 }});">
          <i class="ri-calendar-check-line text-xl text-white"></i>
        </div>
        <div class="min-w-0">
          <h3 class="text-sm font-bold text-gray-900">{{ __('academy.hero.features.flexible_schedule_title') }}</h3>
          <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ __('academy.hero.features.flexible_schedule_desc') }}</p>
        </div>
      </div>
    </div>
    @endif
  </div>
</section>

<style>
  @keyframes float {
    0%, 100% {
      transform: translateY(0px);
    }
    50% {
      transform: translateY(-6px);
    }
  }

  .animate-float {
    animation: float 3s ease-in-out infinite;
  }
</style>
