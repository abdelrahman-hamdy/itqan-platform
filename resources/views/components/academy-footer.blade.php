@props(['academy'])

<!-- Footer -->
<footer class="bg-gray-900 text-white py-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">
      <!-- Academy Info & Social Media -->
      <div>
        <div class="flex items-center mb-6">
          @if($academy->logo_url ?? false)
            <img src="{{ $academy->logo_url }}" alt="{{ $academy->name }}" class="h-8 w-auto ms-2">
          @else
            <div class="w-8 h-8 flex items-center justify-center">
              <i class="ri-book-open-line text-2xl text-primary"></i>
            </div>
          @endif
          <span class="me-2 text-xl font-bold">{{ $academy->name ?? __('components.footer.academy_default') }}</span>
        </div>
        <p class="text-gray-400 mb-6 leading-relaxed">
          {{ $academy->description ?? __('components.footer.default_description') }}
        </p>
        <div class="flex space-x-4 space-x-reverse">
          @if($academy->social_media ?? false)
            @if($academy->social_media->facebook ?? false)
              <a href="{{ $academy->social_media->facebook }}" target="_blank" rel="noopener noreferrer" class="w-10 h-10 flex items-center justify-center bg-primary/20 rounded-full hover:bg-primary transition-colors duration-200" aria-label="Facebook">
                <i class="ri-facebook-fill"></i>
              </a>
            @endif
            @if($academy->social_media->twitter ?? false)
              <a href="{{ $academy->social_media->twitter }}" target="_blank" rel="noopener noreferrer" class="w-10 h-10 flex items-center justify-center bg-primary/20 rounded-full hover:bg-primary transition-colors duration-200" aria-label="Twitter">
                <i class="ri-twitter-fill"></i>
              </a>
            @endif
            @if($academy->social_media->instagram ?? false)
              <a href="{{ $academy->social_media->instagram }}" target="_blank" rel="noopener noreferrer" class="w-10 h-10 flex items-center justify-center bg-primary/20 rounded-full hover:bg-primary transition-colors duration-200" aria-label="Instagram">
                <i class="ri-instagram-fill"></i>
              </a>
            @endif
            @if($academy->social_media->youtube ?? false)
              <a href="{{ $academy->social_media->youtube }}" target="_blank" rel="noopener noreferrer" class="w-10 h-10 flex items-center justify-center bg-primary/20 rounded-full hover:bg-primary transition-colors duration-200" aria-label="YouTube">
                <i class="ri-youtube-fill"></i>
              </a>
            @endif
          @endif
        </div>
      </div>

      <!-- Main Sections -->
      <div>
        <h3 class="text-lg font-bold mb-6">{{ __('components.footer.main_sections') }}</h3>
        <ul class="space-y-3">
          <li>
            <a href="{{ route('academy.home', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}" class="text-gray-400 hover:text-white transition-colors duration-200">
              {{ __('components.footer.home') }}
            </a>
          </li>
          @if($academy->quran_enabled ?? true)
            <li>
              <a href="{{ route('quran-circles.index', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}" class="text-gray-400 hover:text-white transition-colors duration-200">
                {{ __('components.footer.quran_circles') }}
              </a>
            </li>
            <li>
              <a href="{{ route('quran-teachers.index', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}" class="text-gray-400 hover:text-white transition-colors duration-200">
                {{ __('components.footer.quran_teachers') }}
              </a>
            </li>
          @endif
          @if($academy->academic_enabled ?? true)
            <li>
              <a href="{{ route('interactive-courses.index', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}" class="text-gray-400 hover:text-white transition-colors duration-200">
                {{ __('components.footer.interactive_courses') }}
              </a>
            </li>
            <li>
              <a href="{{ route('academic-teachers.index', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}" class="text-gray-400 hover:text-white transition-colors duration-200">
                {{ __('components.footer.academic_teachers') }}
              </a>
            </li>
          @endif
          @if($academy->recorded_courses_enabled ?? true)
            <li>
              <a href="{{ route('courses.index', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}" class="text-gray-400 hover:text-white transition-colors duration-200">
                {{ __('components.footer.recorded_courses') }}
              </a>
            </li>
          @endif
        </ul>
      </div>

      <!-- Important Links -->
      <div>
        <h3 class="text-lg font-bold mb-6">{{ __('components.footer.important_links') }}</h3>
        <ul class="space-y-3">
          <li>
            <a href="{{ route('academy.about-us', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}" class="text-gray-400 hover:text-white transition-colors duration-200">
              {{ __('components.footer.about_us') }}
            </a>
          </li>
          <li>
            <a href="{{ route('academy.privacy-policy', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}" class="text-gray-400 hover:text-white transition-colors duration-200">
              {{ __('components.footer.privacy_policy') }}
            </a>
          </li>
          <li>
            <a href="{{ route('academy.terms', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}" class="text-gray-400 hover:text-white transition-colors duration-200">
              {{ __('components.footer.terms_conditions') }}
            </a>
          </li>
          <li>
            <a href="{{ route('academy.refund-policy', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}" class="text-gray-400 hover:text-white transition-colors duration-200">
              {{ __('components.footer.refund_policy') }}
            </a>
          </li>
        </ul>
      </div>

      <!-- Contact Information -->
      <div>
        <h3 class="text-lg font-bold mb-6">{{ __('components.footer.contact_us') }}</h3>
        <ul class="space-y-3">
          @if($academy->phone ?? false)
            <li class="flex items-start text-gray-400">
              <div class="w-5 h-5 flex items-center justify-center ms-3 mt-0.5 flex-shrink-0">
                <i class="ri-phone-line"></i>
              </div>
              <span>{{ $academy->phone }}</span>
            </li>
          @endif
          @if($academy->email ?? false)
            <li class="flex items-start text-gray-400">
              <div class="w-5 h-5 flex items-center justify-center ms-3 mt-0.5 flex-shrink-0">
                <i class="ri-mail-line"></i>
              </div>
              <span>{{ $academy->email }}</span>
            </li>
          @endif
          @if($academy->address ?? false)
            <li class="flex items-start text-gray-400">
              <div class="w-5 h-5 flex items-center justify-center ms-3 mt-0.5 flex-shrink-0">
                <i class="ri-map-pin-line"></i>
              </div>
              <span>{{ $academy->address }}</span>
            </li>
          @endif
        </ul>
      </div>
    </div>

    <!-- Bottom Bar -->
    <div class="border-t border-gray-800 pt-8 text-center">
      <p class="text-gray-400">
        © {{ date('Y') }} {{ $academy->name ?? __('components.footer.academy_default') }}. {{ __('components.footer.all_rights_reserved') }}.
      </p>
    </div>
  </div>
</footer>
