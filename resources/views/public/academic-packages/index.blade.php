@php
    // Get gradient palette colors
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $colors = $gradientPalette->getColors();
    $gradientFrom = $colors['from'];
    $gradientTo = $colors['to'];
    
    // Get hex values for tailwind config
    [$toColorName, $toShade] = explode('-', $gradientTo);
    try {
        $toTailwindColor = \App\Enums\TailwindColor::from($toColorName);
        $gradientToHex = $toTailwindColor->getHexValue((int)$toShade);
    } catch (\ValueError $e) {
        $gradientToHex = '#6366F1';
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ __('public.academic_packages.title') }} - {{ $academy->name ?? __('common.academy_default') }}</title>

  <!-- Vite Assets (Compiled CSS & JS) -->
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  <!-- RemixIcon -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">

  <style>
    :root {
      --color-primary-500: {{ $academy->brand_color?->getHexValue(500) ?? '#4169E1' }};
      --gradient-from: {{ $gradientPalette->getPreviewHex() }};
      --gradient-to: {{ $gradientToHex }};
    }
  </style>
</head>

<body class="bg-gray-50 font-sans">

  <!-- Header -->
  <header class="bg-white shadow-sm">
    <div class="container mx-auto px-4 py-4">
      <div class="flex items-center justify-between">
        <!-- Logo and Academy Name -->
        <div class="flex items-center gap-3">
          @if($academy->logo)
            <img src="{{ asset('storage/' . $academy->logo) }}" alt="{{ $academy->name }}" class="h-10 w-10 rounded-lg">
          @endif
          <div>
            <h1 class="text-xl font-bold text-gray-900">{{ $academy->name ?? __('common.academy_default') }}</h1>
            <p class="text-sm text-gray-600">{{ __('public.academic_packages.title') }}</p>
          </div>
        </div>

        <!-- Auth Actions -->
        <div class="flex items-center gap-3">
          @auth
            <a href="{{ route('student.profile', ['subdomain' => $academy->subdomain]) }}" 
               class="flex items-center gap-2 text-gray-600 hover:text-primary transition-colors">
              <span class="text-sm font-medium">{{ __('common.navigation.dashboard') }}</span>
              <i class="ri-dashboard-line text-xl"></i>
            </a>
          @else
            <a href="{{ route('login', ['subdomain' => $academy->subdomain]) }}" 
               class="text-sm font-medium text-gray-600 hover:text-primary transition-colors">
              {{ __('common.navigation.login') }}
            </a>
            <a href="{{ route('student.register', ['subdomain' => $academy->subdomain]) }}"
               class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-opacity-90 transition-colors">
              {{ __('common.navigation.register') }}
            </a>
          @endauth
        </div>
      </div>
    </div>
  </header>

  <!-- Hero Section -->
  <section class="bg-gradient-to-br from-primary to-secondary text-white py-16">
    <div class="container mx-auto px-4 text-center">
      <h1 class="text-4xl font-bold mb-4">{{ __('public.academic_packages.page_title') }}</h1>
      <p class="text-xl opacity-90 mb-8">{{ __('public.academic_packages.description') }}</p>
      
      <!-- Filter Buttons -->
      <div class="flex flex-wrap justify-center gap-4 mb-8">
        <button class="filter-btn active bg-white text-primary px-6 py-3 rounded-lg font-medium transition-all hover:shadow-lg" data-filter="all">
          {{ __('public.academic_packages.filters.all') }}
        </button>
        <button class="filter-btn bg-white/20 text-white px-6 py-3 rounded-lg font-medium transition-all hover:bg-white hover:text-primary" data-filter="individual">
          {{ __('public.academic_packages.filters.individual') }}
        </button>
        <button class="filter-btn bg-white/20 text-white px-6 py-3 rounded-lg font-medium transition-all hover:bg-white hover:text-primary" data-filter="group">
          {{ __('public.academic_packages.filters.group') }}
        </button>
      </div>
    </div>
  </section>

  <!-- Packages Section -->
  <section class="py-16">
    <div class="container mx-auto px-4">
      
      @if($packages->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-16">
          @foreach($packages as $package)
            <div class="package-card bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-200 {{ $package->package_type }}-package">
              <!-- Package Header -->
              <div class="bg-gradient-to-r from-primary to-secondary text-white p-6">
                <div class="flex items-center justify-between mb-2">
                  <h3 class="text-xl font-bold">{{ $package->name }}</h3>
                  <span class="bg-white/20 px-3 py-1 rounded-full text-sm">
                    {{ $package->package_type === 'individual' ? __('public.academic_packages.package_type.individual') : __('public.academic_packages.package_type.group') }}
                  </span>
                </div>
                @if($package->description)
                  <p class="text-white/90 text-sm">{{ $package->description }}</p>
                @endif
              </div>

              <!-- Package Details -->
              <div class="p-6">
                <!-- Pricing -->
                <div class="text-center mb-6">
                  <div class="text-3xl font-bold text-gray-900 mb-1">
                    {{ number_format($package->monthly_price) }}
                    <span class="text-sm text-gray-600">{{ getCurrencySymbol(null, $package->academy) }}</span>
                  </div>
                  <div class="text-sm text-gray-600">{{ __('public.academic_packages.pricing.monthly') }}</div>
                </div>

                <!-- Features -->
                <div class="space-y-3 mb-6">
                  <div class="flex items-center text-sm">
                    <i class="ri-check-line text-green-500 {{ app()->getLocale() === 'ar' ? 'ml-2' : 'mr-2' }}"></i>
                    <span>{{ $package->sessions_per_month }} {{ __('public.academic_packages.features.sessions') }}</span>
                  </div>
                  <div class="flex items-center text-sm">
                    <i class="ri-time-line text-blue-500 {{ app()->getLocale() === 'ar' ? 'ml-2' : 'mr-2' }}"></i>
                    <span>{{ $package->session_duration_minutes }} {{ __('public.academic_packages.features.session_duration') }}</span>
                  </div>
                  @if($package->features)
                    @foreach($package->features as $feature)
                      <div class="flex items-center text-sm">
                        <i class="ri-star-line text-yellow-500 ms-2"></i>
                        <span>{{ $feature }}</span>
                      </div>
                    @endforeach
                  @endif
                </div>



                <!-- CTA Button -->
                <button onclick="showTeachersModal({{ $package->id }})" class="w-full bg-primary text-white py-3 px-6 rounded-lg font-medium hover:bg-opacity-90 transition-colors">
                  {{ __('public.academic_packages.cta') }}
                </button>
              </div>
            </div>
          @endforeach
        </div>
      @else
        <div class="text-center py-16">
          <div class="text-6xl text-gray-300 mb-4">📚</div>
          <h3 class="text-2xl font-bold text-gray-700 mb-2">{{ __('public.academic_packages.no_packages') }}</h3>
          <p class="text-gray-600">{{ __('public.academic_packages.no_packages_message') }}</p>
        </div>
      @endif

      <!-- Teachers Section -->
      @if($teachers->count() > 0)
        <div class="mt-16">
          <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">{{ __('public.academic_packages.teachers.title') }}</h2>
            <p class="text-gray-600 text-lg">{{ __('public.academic_packages.teachers.subtitle') }}</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($teachers->take(6) as $teacher)
              <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-6 border border-gray-200">
                <!-- Teacher Info -->
                <div class="flex items-center gap-4 mb-4">
                  @if($teacher->user && $teacher->user->avatar)
                    <img src="{{ asset('storage/' . $teacher->user->avatar) }}" alt="{{ $teacher->user->name }}" class="w-16 h-16 rounded-full object-cover">
                  @else
                    <div class="w-16 h-16 rounded-full bg-primary text-white flex items-center justify-center text-xl font-bold">
                      {{ substr($teacher->user->name ?? 'M', 0, 1) }}
                    </div>
                  @endif
                  
                  <div>
                    <h3 class="font-bold text-gray-900">{{ $teacher->user->name ?? __('common.teacher') }}</h3>
                    <p class="text-gray-600 text-sm">{{ __('public.academic_packages.teachers.certified') }}</p>
                    @if($teacher->experience_years)
                      <p class="text-gray-500 text-xs">{{ $teacher->experience_years }} {{ __('public.academic_packages.teachers.experience') }}</p>
                    @endif
                  </div>
                </div>

                <!-- Teacher Subjects -->
                @if($teacher->subjects && $teacher->subjects->count() > 0)
                  <div class="mb-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">{{ __('public.academic_packages.teachers.specializations') }}</h4>
                    <div class="flex flex-wrap gap-2">
                      @foreach($teacher->subjects->take(3) as $subject)
                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">{{ $subject->name }}</span>
                      @endforeach
                      @if($teacher->subjects->count() > 3)
                        <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full">+{{ $teacher->subjects->count() - 3 }}</span>
                      @endif
                    </div>
                  </div>
                @endif

                <!-- View Profile Button -->
                <a href="{{ route('public.academic-packages.teacher', ['subdomain' => $academy->subdomain, 'teacher' => $teacher->id]) }}"
                   class="block w-full text-center bg-gray-100 text-gray-700 py-2 px-4 rounded-lg font-medium hover:bg-gray-200 transition-colors">
                  {{ __('public.academic_packages.teachers.view_profile') }}
                </a>
              </div>
            @endforeach
          </div>

          @if($teachers->count() > 6)
            <div class="text-center mt-8">
              <a href="{{ route('academic-teachers.index', ['subdomain' => $academy->subdomain]) }}"
                 class="inline-flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-lg font-medium hover:bg-opacity-90 transition-colors">
                <span>{{ __('public.academic_packages.teachers.view_all') }}</span>
                <i class="{{ app()->getLocale() === 'ar' ? 'ri-arrow-left-line' : 'ri-arrow-right-line' }}"></i>
              </a>
            </div>
          @endif
        </div>
      @endif
    </div>
  </section>

  <!-- Teachers Modal -->
  <div id="teachersModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
      <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-900">{{ __('public.academic_packages.modal.title') }}</h3>
            <button onclick="closeTeachersModal()" class="text-gray-500 hover:text-gray-700">
              <i class="ri-close-line text-2xl"></i>
            </button>
          </div>
        </div>
        
        <div id="modalTeachers" class="p-6">
          <!-- Teachers will be loaded here -->
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script>
    // Filter functionality
    document.querySelectorAll('.filter-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        // Update active button
        document.querySelectorAll('.filter-btn').forEach(b => {
          b.classList.remove('active', 'bg-white', 'text-primary');
          b.classList.add('bg-white/20', 'text-white');
        });
        btn.classList.add('active', 'bg-white', 'text-primary');
        btn.classList.remove('bg-white/20', 'text-white');

        // Filter packages
        const filter = btn.dataset.filter;
        document.querySelectorAll('.package-card').forEach(card => {
          if (filter === 'all' || card.classList.contains(`${filter}-package`)) {
            card.style.display = 'block';
          } else {
            card.style.display = 'none';
          }
        });
      });
    });

    // Modal functionality
    function showTeachersModal(packageId) {
      @auth
        // Load teachers for this package
        fetch(`/{{ $academy->subdomain }}/api/academic-packages/${packageId}/teachers`)
          .then(response => response.json())
          .then(data => {
            const modalContent = document.getElementById('modalTeachers');
            
            if (data.teachers && data.teachers.length > 0) {
              modalContent.innerHTML = data.teachers.map(teacher => `
                <div class="border border-gray-200 rounded-lg p-4 mb-4">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                      <div class="w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center font-bold">
                        ${teacher.name.charAt(0)}
                      </div>
                      <div>
                        <h4 class="font-bold">${teacher.name}</h4>
                        <p class="text-gray-600 text-sm">${teacher.subjects.join(', ')}</p>
                      </div>
                    </div>
                    <a href="/{{ $academy->subdomain }}/academic-packages/teachers/${teacher.id}/subscribe/${packageId}"
                       class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-opacity-90 transition-colors">
                      {{ __('public.academic_packages.modal.select_and_subscribe') }}
                    </a>
                  </div>
                </div>
              `).join('');
            } else {
              modalContent.innerHTML = '<p class="text-center text-gray-600">{{ __('public.academic_packages.modal.no_teachers') }}</p>';
            }
            
            document.getElementById('teachersModal').classList.remove('hidden');
          });
      @else
        window.location.href = '{{ route("login", ["subdomain" => $academy->subdomain]) }}';
      @endauth
    }

    function closeTeachersModal() {
      document.getElementById('teachersModal').classList.add('hidden');
    }

    // Close modal when clicking outside
    document.getElementById('teachersModal').addEventListener('click', (e) => {
      if (e.target.id === 'teachersModal') {
        closeTeachersModal();
      }
    });
  </script>

</body>
</html>
