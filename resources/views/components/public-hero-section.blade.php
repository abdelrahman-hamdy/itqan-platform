@props(['academy', 'title', 'subtitle', 'icon', 'stats' => []])

<!-- Hero Section -->
<section class="bg-gradient-to-l from-primary to-secondary text-white py-16 relative overflow-hidden">
  
  <div class="container mx-auto px-4 text-center relative z-10">
    <!-- Big White Icon -->
    @if($icon)
      <div class="mb-8">
        <div class="w-24 h-24 mx-auto bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mb-6">
          <i class="{{ $icon }} text-4xl text-white"></i>
        </div>
      </div>
    @endif
    
    <!-- Title and Subtitle -->
    <h2 class="text-4xl md:text-5xl font-bold mb-4">{{ $title }}</h2>
    <p class="text-xl opacity-90 max-w-2xl mx-auto mb-8">
      {{ $subtitle }}
    </p>
    
    <!-- Stats Section -->
    @if(count($stats) > 0)
      <div class="mt-8 bg-white/10 backdrop-blur-sm rounded-lg p-6 max-w-lg mx-auto">
        <div class="grid gap-4 text-center" style="grid-template-columns: repeat({{ count($stats) }}, 1fr);">
          @foreach($stats as $stat)
            <div>
              <div class="text-2xl font-bold">{{ $stat['value'] }}</div>
              <div class="text-sm opacity-75">{{ $stat['label'] }}</div>
            </div>
          @endforeach
        </div>
      </div>
    @endif
  </div>
  
  <!-- Decorative Elements -->
  <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -translate-y-32 translate-x-32"></div>
  <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/5 rounded-full translate-y-24 -translate-x-24"></div>
</section>
