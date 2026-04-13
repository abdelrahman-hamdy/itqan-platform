@php
    $brandColor = $academy?->brand_color ?? \App\Enums\TailwindColor::SKY;
    $brandHex50 = $brandColor->getHexValue(50);
    $brandHex100 = $brandColor->getHexValue(100);
    $brandHex200 = $brandColor->getHexValue(200);
    $brandHex500 = $brandColor->getHexValue(500);
    $brandHex600 = $brandColor->getHexValue(600);
    $brandHex700 = $brandColor->getHexValue(700);
    $brandHex900 = $brandColor->getHexValue(900);

    $heroHeading = $heading ?? __('academy.hero.default_heading');
    $heroSubheading = $subheading ?? __('academy.hero.default_subheading');
@endphp

<!-- Hero Section - Template 2: Academic Platform Design -->
<section id="hero-section" class="relative flex items-center overflow-hidden py-20 sm:py-16 lg:py-0" role="main"
         style="min-height: min(100vh, 56rem); background: linear-gradient(160deg, {{ $brandHex50 }}, white 40%, {{ $brandHex50 }}90 70%, {{ $brandHex100 }}60);">

  <!-- Seamless Academic Pattern Background -->
  <div class="absolute inset-0 overflow-hidden">
    <svg class="absolute inset-0 w-full h-full" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
      <defs>
        <pattern id="academic-grid" width="80" height="80" patternUnits="userSpaceOnUse" patternTransform="rotate(5)">
          <!-- Hexagonal academic grid -->
          <path d="M40 0 L80 20 L80 60 L40 80 L0 60 L0 20 Z" fill="none" stroke="{{ $brandHex500 }}" stroke-width="0.5" opacity="0.08"/>
          <!-- Inner cross lines -->
          <line x1="0" y1="40" x2="80" y2="40" stroke="{{ $brandHex500 }}" stroke-width="0.3" opacity="0.06"/>
          <line x1="40" y1="0" x2="40" y2="80" stroke="{{ $brandHex500 }}" stroke-width="0.3" opacity="0.06"/>
          <!-- Corner dots -->
          <circle cx="40" cy="0" r="2" fill="{{ $brandHex500 }}" opacity="0.1"/>
          <circle cx="80" cy="20" r="1.5" fill="{{ $brandHex500 }}" opacity="0.07"/>
          <circle cx="0" cy="20" r="1.5" fill="{{ $brandHex500 }}" opacity="0.07"/>
          <circle cx="40" cy="40" r="2.5" fill="{{ $brandHex500 }}" opacity="0.05"/>
        </pattern>
      </defs>
      <rect width="100%" height="100%" fill="url(#academic-grid)" />
    </svg>
    <!-- Soft vignette -->
    <div class="absolute inset-0" style="background: radial-gradient(ellipse at 50% 30%, transparent 40%, {{ $brandHex50 }}40 100%);"></div>
  </div>

  <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
    <!-- Content Area -->
    <div class="text-center max-w-3xl mx-auto space-y-7">

      <!-- Animated Badge - Typing/Glow style -->
      <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full hero-badge-glow"
           style="background: linear-gradient(135deg, white, {{ $brandHex50 }}); border: 1px solid {{ $brandHex200 }}; box-shadow: 0 0 20px {{ $brandHex200 }}60, 0 2px 8px rgba(0,0,0,0.04);">
        <span class="relative flex h-2.5 w-2.5">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-60" style="background-color: {{ $brandHex500 }};"></span>
          <span class="relative inline-flex rounded-full h-2.5 w-2.5" style="background-color: {{ $brandHex500 }};"></span>
        </span>
        <span class="text-sm font-semibold tracking-wide" style="color: {{ $brandHex700 }};">{{ __('academy.hero.badge_template2') }}</span>
        <svg class="w-4 h-4" style="color: {{ $brandHex500 }};" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
        </svg>
      </div>

      <!-- Main Heading -->
      <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold leading-tight" style="color: {{ $brandHex900 }};">
        {{ $heroHeading }}
      </h1>

      <!-- Subheading -->
      <p class="text-lg lg:text-xl text-gray-600 leading-relaxed max-w-2xl mx-auto">
        {{ $heroSubheading }}
      </p>

      <!-- CTA Button -->
      <div class="pt-2">
        <a href="{{ route('student.register', ['subdomain' => $academy->subdomain ?? 'test-academy']) }}"
           class="group relative inline-flex items-center gap-3 px-8 py-4 text-white rounded-2xl font-semibold text-lg transition-all duration-300 hover:shadow-xl hover:-translate-y-1"
           style="background: linear-gradient(135deg, {{ $brandHex500 }}, {{ $brandHex700 }});">
          <span>{{ __('academy.hero.cta_button') }}</span>
          <i class="ri-arrow-left-line text-xl transition-transform duration-300 group-hover:-translate-x-1 ltr:rotate-180 ltr:group-hover:translate-x-1"></i>
        </a>
      </div>
    </div>

    <!-- Feature Cards - Unique colored design with distinct SVG icons -->
    @if($academy->hero_show_boxes ?? true)
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 lg:gap-6 mt-14 lg:mt-16 max-w-5xl mx-auto">

      <!-- Expert Teachers Card -->
      <div class="group relative overflow-hidden rounded-2xl p-5 lg:p-6 transition-all duration-300 hover:-translate-y-1.5 hover:shadow-lg"
           style="background: linear-gradient(145deg, #ecfdf5, #d1fae5); border: 1px solid #a7f3d0;">
        <div class="flex items-start gap-4">
          <div class="flex-shrink-0 w-14 h-14 rounded-2xl bg-white/80 flex items-center justify-center shadow-sm">
            <svg class="w-7 h-7 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.627 48.627 0 0 1 12 20.904a48.627 48.627 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/>
            </svg>
          </div>
          <div class="min-w-0">
            <h3 class="text-sm font-bold text-emerald-900 mb-1">{{ __('academy.hero.features.expert_teachers_title') }}</h3>
            <p class="text-xs text-emerald-700/70 leading-relaxed">{{ __('academy.hero.features.expert_teachers_desc') }}</p>
          </div>
        </div>
        <div class="absolute -bottom-3 -end-3 w-20 h-20 rounded-full bg-emerald-300/20 group-hover:scale-150 transition-transform duration-500"></div>
      </div>

      <!-- Interactive Learning Card -->
      <div class="group relative overflow-hidden rounded-2xl p-5 lg:p-6 transition-all duration-300 hover:-translate-y-1.5 hover:shadow-lg"
           style="background: linear-gradient(145deg, #eff6ff, #dbeafe); border: 1px solid #93c5fd;">
        <div class="flex items-start gap-4">
          <div class="flex-shrink-0 w-14 h-14 rounded-2xl bg-white/80 flex items-center justify-center shadow-sm">
            <svg class="w-7 h-7 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z"/>
            </svg>
          </div>
          <div class="min-w-0">
            <h3 class="text-sm font-bold text-blue-900 mb-1">{{ __('academy.hero.features.interactive_learning_title') }}</h3>
            <p class="text-xs text-blue-700/70 leading-relaxed">{{ __('academy.hero.features.interactive_learning_desc') }}</p>
          </div>
        </div>
        <div class="absolute -bottom-3 -end-3 w-20 h-20 rounded-full bg-blue-300/20 group-hover:scale-150 transition-transform duration-500"></div>
      </div>

      <!-- Flexible Schedule Card -->
      <div class="group relative overflow-hidden rounded-2xl p-5 lg:p-6 transition-all duration-300 hover:-translate-y-1.5 hover:shadow-lg"
           style="background: linear-gradient(145deg, #fdf4ff, #f3e8ff); border: 1px solid #d8b4fe;">
        <div class="flex items-start gap-4">
          <div class="flex-shrink-0 w-14 h-14 rounded-2xl bg-white/80 flex items-center justify-center shadow-sm">
            <svg class="w-7 h-7 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
            </svg>
          </div>
          <div class="min-w-0">
            <h3 class="text-sm font-bold text-purple-900 mb-1">{{ __('academy.hero.features.flexible_schedule_title') }}</h3>
            <p class="text-xs text-purple-700/70 leading-relaxed">{{ __('academy.hero.features.flexible_schedule_desc') }}</p>
          </div>
        </div>
        <div class="absolute -bottom-3 -end-3 w-20 h-20 rounded-full bg-purple-300/20 group-hover:scale-150 transition-transform duration-500"></div>
      </div>
    </div>
    @endif
  </div>
</section>

<style>
  .hero-badge-glow {
    animation: badge-glow 3s ease-in-out infinite;
  }
  @keyframes badge-glow {
    0%, 100% { box-shadow: 0 0 20px {{ $brandHex200 }}60, 0 2px 8px rgba(0,0,0,0.04); }
    50% { box-shadow: 0 0 30px {{ $brandHex200 }}90, 0 4px 12px rgba(0,0,0,0.06); }
  }
</style>
