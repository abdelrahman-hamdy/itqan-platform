@php
    $brandColor = $academy?->brand_color ?? \App\Enums\TailwindColor::SKY;
    $brandHex50 = $brandColor->getHexValue(50);
    $brandHex100 = $brandColor->getHexValue(100);
    $brandHex200 = $brandColor->getHexValue(200);
    $brandHex400 = $brandColor->getHexValue(400);
    $brandHex500 = $brandColor->getHexValue(500);
    $brandHex600 = $brandColor->getHexValue(600);
    $brandHex700 = $brandColor->getHexValue(700);
    $brandHex800 = $brandColor->getHexValue(800);
    $brandHex900 = $brandColor->getHexValue(900);

    $heroHeading = $heading ?? __('academy.hero.default_heading');
    $heroSubheading = $subheading ?? __('academy.hero.default_subheading');
@endphp

<section id="hero-section" class="relative overflow-hidden" role="main" style="min-height: min(100vh, 56rem);">
  {{-- Dark diagonal section --}}
  <div class="absolute inset-0" style="background: {{ $brandHex800 }}; clip-path: polygon(0 0, 100% 0, 100% 78%, 0 92%);">
    {{-- Noise texture overlay --}}
    <div class="absolute inset-0 opacity-[0.03]" style="background-image: url('data:image/svg+xml,%3Csvg viewBox=%220 0 256 256%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cfilter id=%22noise%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.9%22 numOctaves=%224%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22100%25%22 height=%22100%25%22 filter=%22url(%23noise)%22/%3E%3C/svg%3E');"></div>
    {{-- Subtle grid lines --}}
    <div class="absolute inset-0 opacity-[0.04]" style="background-image: linear-gradient(rgba(255,255,255,0.1) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.1) 1px, transparent 1px); background-size: 60px 60px;"></div>
    {{-- Colored accent glow --}}
    <div class="absolute top-1/4 end-0 w-[500px] h-[500px] rounded-full opacity-20 blur-3xl" style="background: {{ $brandHex500 }};"></div>
  </div>

  {{-- White bottom area --}}
  <div class="absolute inset-0 bg-white" style="clip-path: polygon(0 88%, 100% 74%, 100% 100%, 0 100%);"></div>

  <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full flex flex-col justify-center" style="min-height: min(100vh, 56rem);">
    <div class="pt-16 sm:pt-20 pb-32 sm:pb-40 lg:pb-48">
      {{-- Frosted glass badge --}}
      <div class="flex justify-center lg:justify-start mb-8">
        <div class="inline-flex items-center gap-2.5 px-5 py-2 rounded-full backdrop-blur-xl t2-badge-sparkle"
             style="background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.2);">
          <span class="w-2 h-2 rounded-full t2-sparkle-dot" style="background: {{ $brandHex400 }};"></span>
          <span class="text-sm font-semibold text-white/90 tracking-wide">{{ __('academy.hero.badge_template2') }}</span>
        </div>
      </div>

      {{-- Main Content --}}
      <div class="max-w-3xl mx-auto lg:mx-0 text-center lg:text-start">
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black leading-[1.1] text-white mb-6">
          {{ $heroHeading }}
        </h1>
        <p class="text-base sm:text-lg text-white/60 leading-relaxed max-w-xl mx-auto lg:mx-0 mb-10">
          {{ $heroSubheading }}
        </p>

        {{-- CTA — inverted white button --}}
        <a href="{{ route('student.register', ['subdomain' => $academy->subdomain ?? 'test-academy']) }}"
           class="group inline-flex items-center gap-3 px-8 py-4 bg-white rounded-xl font-bold text-lg transition-all duration-300 hover:shadow-2xl hover:-translate-y-0.5"
           style="color: {{ $brandHex700 }};">
          <span>{{ __('academy.hero.cta_button') }}</span>
          <i class="ri-arrow-left-line text-xl transition-transform duration-300 group-hover:-translate-x-1 ltr:rotate-180 ltr:group-hover:translate-x-1"></i>
        </a>
      </div>
    </div>

    {{-- Feature strips — overlapping the diagonal boundary --}}
    @if($academy->hero_show_boxes ?? true)
    <div class="relative -mt-20 sm:-mt-24 lg:-mt-28 pb-12 sm:pb-16 grid grid-cols-1 sm:grid-cols-3 gap-4 lg:gap-5">
      {{-- Card 1 --}}
      <div class="bg-white rounded-xl p-6 lg:p-7 transition-all duration-300 hover:-translate-y-1"
           style="border-top: 4px solid {{ $brandHex500 }}; box-shadow: 0 4px 24px rgba(0,0,0,0.08);">
        <div class="w-12 h-12 rounded-lg flex items-center justify-center mb-4" style="background: {{ $brandHex50 }};">
          <svg class="w-6 h-6" style="color: {{ $brandHex600 }};" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.627 48.627 0 0 1 12 20.904a48.627 48.627 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/>
          </svg>
        </div>
        <h3 class="text-base font-bold text-gray-900 mb-1.5">{{ __('academy.hero.features.expert_teachers_title') }}</h3>
        <p class="text-sm text-gray-500 leading-relaxed">{{ __('academy.hero.features.expert_teachers_desc') }}</p>
      </div>

      {{-- Card 2 --}}
      <div class="bg-white rounded-xl p-6 lg:p-7 transition-all duration-300 hover:-translate-y-1"
           style="border-top: 4px solid {{ $brandHex500 }}; box-shadow: 0 4px 24px rgba(0,0,0,0.08);">
        <div class="w-12 h-12 rounded-lg flex items-center justify-center mb-4" style="background: {{ $brandHex50 }};">
          <svg class="w-6 h-6" style="color: {{ $brandHex600 }};" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z"/>
          </svg>
        </div>
        <h3 class="text-base font-bold text-gray-900 mb-1.5">{{ __('academy.hero.features.interactive_learning_title') }}</h3>
        <p class="text-sm text-gray-500 leading-relaxed">{{ __('academy.hero.features.interactive_learning_desc') }}</p>
      </div>

      {{-- Card 3 --}}
      <div class="bg-white rounded-xl p-6 lg:p-7 transition-all duration-300 hover:-translate-y-1"
           style="border-top: 4px solid {{ $brandHex500 }}; box-shadow: 0 4px 24px rgba(0,0,0,0.08);">
        <div class="w-12 h-12 rounded-lg flex items-center justify-center mb-4" style="background: {{ $brandHex50 }};">
          <svg class="w-6 h-6" style="color: {{ $brandHex600 }};" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
          </svg>
        </div>
        <h3 class="text-base font-bold text-gray-900 mb-1.5">{{ __('academy.hero.features.flexible_schedule_title') }}</h3>
        <p class="text-sm text-gray-500 leading-relaxed">{{ __('academy.hero.features.flexible_schedule_desc') }}</p>
      </div>
    </div>
    @endif
  </div>
</section>

<style>
  .t2-badge-sparkle { animation: t2-sparkle 4s ease-in-out infinite; }
  .t2-sparkle-dot { animation: t2-dot-pulse 2s ease-in-out infinite; }
  @keyframes t2-sparkle {
    0%, 100% { box-shadow: 0 0 12px rgba(255,255,255,0.08); }
    50% { box-shadow: 0 0 24px rgba(255,255,255,0.18); }
  }
  @keyframes t2-dot-pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(0.7); }
  }
</style>
