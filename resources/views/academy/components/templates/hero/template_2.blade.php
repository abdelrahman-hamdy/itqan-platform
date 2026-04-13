@php
    $brandColor = $academy?->brand_color ?? \App\Enums\TailwindColor::SKY;
    $brandHex50  = $brandColor->getHexValue(50);
    $brandHex100 = $brandColor->getHexValue(100);
    $brandHex200 = $brandColor->getHexValue(200);
    $brandHex500 = $brandColor->getHexValue(500);
    $brandHex600 = $brandColor->getHexValue(600);
    $brandHex700 = $brandColor->getHexValue(700);

    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $gradientHex     = $gradientPalette->getHexColors();
    $gradientFrom    = $gradientHex['from'];
    $gradientTo      = $gradientHex['to'];

    $heroHeading    = $heading ?? __('academy.hero.default_heading');
    $heroSubheading = $subheading ?? __('academy.hero.default_subheading');
    $heroImage = $academy?->hero_image ? asset('storage/' . $academy->hero_image) : null;
@endphp

<section id="hero-section" class="relative overflow-hidden" role="main"
         style="background: linear-gradient(160deg, {{ $brandHex50 }}, white 50%, {{ $brandHex50 }});">

  {{-- Islamic arabesque pattern --}}
  <div class="absolute inset-0">
    <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <pattern id="t2-islamic" x="0" y="0" width="80" height="80" patternUnits="userSpaceOnUse">
          {{-- Interlocking arches (mashrabiya motif) --}}
          <path d="M0 40 Q20 0 40 40 Q60 0 80 40" fill="none" stroke="{{ $brandHex500 }}" stroke-width="0.7" opacity="0.15"/>
          <path d="M0 40 Q20 80 40 40 Q60 80 80 40" fill="none" stroke="{{ $brandHex500 }}" stroke-width="0.7" opacity="0.15"/>
          {{-- Vertical arches --}}
          <path d="M40 0 Q0 20 40 40 Q0 60 40 80" fill="none" stroke="{{ $brandHex500 }}" stroke-width="0.7" opacity="0.12"/>
          <path d="M40 0 Q80 20 40 40 Q80 60 40 80" fill="none" stroke="{{ $brandHex500 }}" stroke-width="0.7" opacity="0.12"/>
          {{-- Center rosette --}}
          <circle cx="40" cy="40" r="8" fill="none" stroke="{{ $brandHex500 }}" stroke-width="0.5" opacity="0.18"/>
          <circle cx="40" cy="40" r="3" fill="{{ $brandHex500 }}" opacity="0.06"/>
          {{-- Corner diamonds --}}
          <path d="M0 0 L8 0 L0 8 Z" fill="{{ $brandHex500 }}" opacity="0.04"/>
          <path d="M80 0 L72 0 L80 8 Z" fill="{{ $brandHex500 }}" opacity="0.04"/>
          <path d="M0 80 L8 80 L0 72 Z" fill="{{ $brandHex500 }}" opacity="0.04"/>
          <path d="M80 80 L72 80 L80 72 Z" fill="{{ $brandHex500 }}" opacity="0.04"/>
          {{-- Junction dots --}}
          <circle cx="0" cy="40" r="2" fill="{{ $brandHex500 }}" opacity="0.1"/>
          <circle cx="80" cy="40" r="2" fill="{{ $brandHex500 }}" opacity="0.1"/>
          <circle cx="40" cy="0" r="2" fill="{{ $brandHex500 }}" opacity="0.1"/>
          <circle cx="40" cy="80" r="2" fill="{{ $brandHex500 }}" opacity="0.1"/>
        </pattern>
      </defs>
      <rect width="100%" height="100%" fill="url(#t2-islamic)"/>
    </svg>
  </div>

  {{-- Gradient palette overlay --}}
  <div class="absolute inset-0" style="background: linear-gradient(135deg, {{ $gradientFrom }}15, transparent 50%, {{ $gradientTo }}15);"></div>

  <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    {{-- Main two-column area --}}
    <div class="grid lg:grid-cols-2 gap-8 lg:gap-12 items-center py-24 sm:py-28 lg:py-0" style="min-height: {{ $heroImage ? 'auto' : 'min(100vh, 56rem)' }}; {{ $heroImage ? 'padding-top: 6rem; padding-bottom: 2rem;' : '' }}">

      {{-- Content column --}}
      <div class="text-center lg:text-start {{ $heroImage ? '' : 'lg:col-span-2 max-w-3xl mx-auto' }}">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 mb-8 rounded-lg t2-hero-badge"
             style="background: {{ $brandHex500 }}12; border: 1px solid {{ $brandHex500 }}25;">
          <span class="block w-1.5 h-1.5 rounded-full t2-pulse-dot" style="background: {{ $brandHex500 }};"></span>
          <span class="text-xs font-semibold" style="color: {{ $brandHex700 }};">{{ __('academy.hero.badge_template2') }}</span>
        </div>

        <h1 class="text-4xl sm:text-5xl lg:text-[3.5rem] xl:text-6xl font-black leading-[1.08] text-black mb-6">
          {{ $heroHeading }}
        </h1>

        <p class="text-base sm:text-lg text-gray-500 leading-relaxed max-w-lg {{ $heroImage ? 'mx-auto lg:mx-0' : 'mx-auto' }} mb-10">
          {{ $heroSubheading }}
        </p>

        <a href="{{ route('student.register', ['subdomain' => $academy->subdomain ?? 'test-academy']) }}"
           class="group inline-flex items-center gap-3 px-8 py-4 text-white font-bold text-base rounded-lg transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5"
           style="background: {{ $brandHex600 }};">
          <span>{{ __('academy.hero.cta_button') }}</span>
          <i class="ri-arrow-left-line text-lg ltr:rotate-180"></i>
        </a>
      </div>

      {{-- Hero image --}}
      @if($heroImage)
      <div class="flex items-center justify-center">
        <img src="{{ $heroImage }}" alt="{{ __('academy.hero.image_placeholder') }}"
             class="w-full max-w-sm sm:max-w-md xl:max-w-lg rounded-xl object-cover"
             style="max-height: 480px;">
      </div>
      @endif
    </div>

    {{-- Feature cards --}}
    @if($academy->hero_show_boxes ?? true)
    @php
      $features = [
          ['icon' => 'M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.627 48.627 0 0 1 12 20.904a48.627 48.627 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5',
           'title' => __('academy.hero.features.expert_teachers_title'),
           'desc' => __('academy.hero.features.expert_teachers_desc')],
          ['icon' => 'm15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z',
           'title' => __('academy.hero.features.interactive_learning_title'),
           'desc' => __('academy.hero.features.interactive_learning_desc')],
          ['icon' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
           'title' => __('academy.hero.features.flexible_schedule_title'),
           'desc' => __('academy.hero.features.flexible_schedule_desc')],
      ];
    @endphp
    <div class="hidden sm:grid grid-cols-3 gap-4 pb-16 sm:pb-20 lg:pb-24 {{ $heroImage ? '' : '-mt-4' }}">
      @foreach($features as $i => $f)
      <div class="group flex items-start gap-4 bg-white rounded-lg p-4 sm:p-5 border border-gray-100 transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5"
           style="animation: t2FadeUp .5s {{ ($i * 0.12) + 0.3 }}s both;">
        <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0" style="background: {{ $brandHex500 }}10;">
          <svg class="w-5 h-5" style="color: {{ $brandHex600 }};" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $f['icon'] }}"/>
          </svg>
        </div>
        <div class="min-w-0">
          <h3 class="text-sm font-bold text-gray-900 mb-1">{{ $f['title'] }}</h3>
          <p class="text-xs text-gray-500 leading-relaxed">{{ $f['desc'] }}</p>
        </div>
      </div>
      @endforeach
    </div>
    @endif
  </div>
</section>

<style>
  .t2-hero-badge { animation: t2BadgeGlow 3s ease-in-out infinite; }
  .t2-pulse-dot  { animation: t2DotPulse 2s ease-in-out infinite; }
  @keyframes t2BadgeGlow {
    0%,100% { box-shadow: 0 0 0 0 {{ $brandHex500 }}00; }
    50%     { box-shadow: 0 0 16px 0 {{ $brandHex500 }}18; }
  }
  @keyframes t2DotPulse { 0%,100% { opacity:1; } 50% { opacity:.3; } }
  @keyframes t2FadeUp { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }
</style>
