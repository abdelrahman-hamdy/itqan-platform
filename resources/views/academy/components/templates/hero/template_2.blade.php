@php
    $brandColor = $academy?->brand_color ?? \App\Enums\TailwindColor::SKY;
    $brandHex50  = $brandColor->getHexValue(50);
    $brandHex100 = $brandColor->getHexValue(100);
    $brandHex200 = $brandColor->getHexValue(200);
    $brandHex300 = $brandColor->getHexValue(300);
    $brandHex400 = $brandColor->getHexValue(400);
    $brandHex500 = $brandColor->getHexValue(500);
    $brandHex600 = $brandColor->getHexValue(600);
    $brandHex700 = $brandColor->getHexValue(700);
    $brandHex800 = $brandColor->getHexValue(800);
    $brandHex900 = $brandColor->getHexValue(900);
    $brandHex950 = $brandColor->getHexValue(950);

    $heroHeading    = $heading ?? __('academy.hero.default_heading');
    $heroSubheading = $subheading ?? __('academy.hero.default_subheading');
@endphp

<section id="hero-section" class="relative overflow-hidden" role="main">
  {{-- ── Dark canvas ── --}}
  <div class="absolute inset-0" style="background: {{ $brandHex950 }};">
    {{-- Diagonal accent stripe --}}
    <div class="absolute inset-0 opacity-[0.07]"
         style="background: repeating-linear-gradient(
           -45deg,
           transparent,
           transparent 80px,
           {{ $brandHex400 }} 80px,
           {{ $brandHex400 }} 81px
         );"></div>
    {{-- Radial glow --}}
    <div class="absolute -top-1/4 start-1/2 -translate-x-1/2 w-[900px] h-[900px] rounded-full opacity-[0.12]"
         style="background: radial-gradient(circle, {{ $brandHex500 }}, transparent 70%);"></div>
  </div>

  <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid lg:grid-cols-12 gap-8 lg:gap-12 items-center py-24 sm:py-28 lg:py-0" style="min-height: min(100vh, 56rem);">

      {{-- ── Text column ── --}}
      <div class="lg:col-span-7 text-center lg:text-start">
        {{-- Animated badge --}}
        <div class="inline-flex items-center gap-2 px-4 py-1.5 mb-8 rounded-sm t2-hero-badge"
             style="background: {{ $brandHex500 }}20; border: 1px solid {{ $brandHex500 }}35;">
          <span class="block w-1.5 h-1.5 rounded-full t2-pulse-dot" style="background: {{ $brandHex400 }};"></span>
          <span class="text-xs font-bold uppercase tracking-[0.2em]" style="color: {{ $brandHex300 }};">{{ __('academy.hero.badge_template2') }}</span>
        </div>

        <h1 class="text-4xl sm:text-5xl lg:text-[3.5rem] xl:text-6xl font-black leading-[1.08] text-white mb-6 tracking-tight">
          {{ $heroHeading }}
        </h1>

        <p class="text-base sm:text-lg text-white/50 leading-relaxed max-w-lg mx-auto lg:mx-0 mb-10">
          {{ $heroSubheading }}
        </p>

        <a href="{{ route('student.register', ['subdomain' => $academy->subdomain ?? 'test-academy']) }}"
           class="group inline-flex items-center gap-3 px-8 py-4 bg-white text-gray-900 font-bold text-base rounded-sm transition-all duration-300 hover:gap-4"
           style="box-shadow: 0 0 0 0 {{ $brandHex500 }}00; transition: box-shadow .3s, gap .3s;"
           onmouseover="this.style.boxShadow=(document.documentElement.dir==='rtl'?'-4px 4px':'4px 4px')+' 0 0 {{ $brandHex500 }}'"
           onmouseout="this.style.boxShadow='0 0 0 0 {{ $brandHex500 }}00'"
          <span>{{ __('academy.hero.cta_button') }}</span>
          <i class="ri-arrow-left-line text-lg ltr:rotate-180"></i>
        </a>
      </div>

      {{-- ── Feature column ── --}}
      @if($academy->hero_show_boxes ?? true)
      <div class="lg:col-span-5">
        <div class="space-y-4">
          @php
            $features = [
                ['icon' => 'M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.627 48.627 0 0 1 12 20.904a48.627 48.627 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5',
                 'title' => __('academy.hero.features.expert_teachers_title'),
                 'desc' => __('academy.hero.features.expert_teachers_desc'),
                 'num' => '01'],
                ['icon' => 'm15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z',
                 'title' => __('academy.hero.features.interactive_learning_title'),
                 'desc' => __('academy.hero.features.interactive_learning_desc'),
                 'num' => '02'],
                ['icon' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
                 'title' => __('academy.hero.features.flexible_schedule_title'),
                 'desc' => __('academy.hero.features.flexible_schedule_desc'),
                 'num' => '03'],
            ];
          @endphp

          @foreach($features as $i => $f)
          <div class="group flex items-start gap-5 p-5 rounded-sm transition-all duration-300 hover:bg-white/[0.04]"
               style="border-inline-start: 3px solid {{ $brandHex500 }}; animation: t2FadeUp .5s {{ ($i * 0.1) + 0.3 }}s both;">
            <span class="text-2xl font-black tabular-nums shrink-0" style="color: {{ $brandHex500 }}; opacity: .35;">{{ $f['num'] }}</span>
            <div>
              <h3 class="text-sm font-bold text-white mb-1">{{ $f['title'] }}</h3>
              <p class="text-xs text-white/40 leading-relaxed">{{ $f['desc'] }}</p>
            </div>
          </div>
          @endforeach
        </div>
      </div>
      @endif

    </div>
  </div>

  {{-- Bottom angled divider --}}
  <div class="absolute bottom-0 inset-x-0 h-16 sm:h-24 bg-white" style="clip-path: polygon(0 40%, 100% 0, 100% 100%, 0 100%);"></div>
</section>

<style>
  .t2-hero-badge { animation: t2BadgeGlow 3s ease-in-out infinite; }
  .t2-pulse-dot  { animation: t2DotPulse 2s ease-in-out infinite; }
  @keyframes t2BadgeGlow {
    0%,100% { box-shadow: 0 0 0 0 {{ $brandHex500 }}00; }
    50%     { box-shadow: 0 0 20px 0 {{ $brandHex500 }}15; }
  }
  @keyframes t2DotPulse {
    0%,100% { opacity:1; }
    50%     { opacity:.3; }
  }
  @keyframes t2FadeUp {
    from { opacity:0; transform:translateY(12px); }
    to   { opacity:1; transform:translateY(0); }
  }
</style>
