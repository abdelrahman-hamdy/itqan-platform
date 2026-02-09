<!-- Footer -->
<footer id="contact" class="bg-gray-900 text-white py-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Main Footer Content -->
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">
      <!-- Academy Info -->
      <div>
        <!-- Logo and Brand -->
        <div class="flex items-center mb-6">
          @if($academy->logo ?? null)
            <img src="{{ $academy->logo_url }}" alt="{{ $academy->name }}" class="w-8 h-8 ms-2">
          @else
            <div class="w-8 h-8 flex items-center justify-center">
              <svg class="w-8 h-8 text-primary-500" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 3L1 9l11 6 9-4.91V17h2V9M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82Z"/>
              </svg>
            </div>
          @endif
          <span class="mr-2 text-xl font-bold font-arabic">{{ $academy->name }}</span>
        </div>
        
        <!-- Academy Description -->
        <p class="text-gray-400 mb-6 leading-relaxed font-arabic">
          {{ $academy->description ?? __('academy.footer.default_description') }}
        </p>

        <!-- Social Media Links -->
        <div class="flex gap-4">
          @if($academy->facebook_url)
            <a href="{{ $academy->facebook_url }}" target="_blank" rel="noopener" 
               class="w-10 h-10 flex items-center justify-center bg-primary-500/20 rounded-full hover:bg-primary-500 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
              </svg>
            </a>
          @endif
          
          @if($academy->twitter_url)
            <a href="{{ $academy->twitter_url }}" target="_blank" rel="noopener"
               class="w-10 h-10 flex items-center justify-center bg-primary-500/20 rounded-full hover:bg-primary-500 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
              </svg>
            </a>
          @endif
          
          @if($academy->instagram_url)
            <a href="{{ $academy->instagram_url }}" target="_blank" rel="noopener"
               class="w-10 h-10 flex items-center justify-center bg-primary-500/20 rounded-full hover:bg-primary-500 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 6.62 5.367 11.987 11.988 11.987 6.62 0 11.987-5.367 11.987-11.987C24.014 5.367 18.637.001 12.017.001zM8.449 16.988c-1.297 0-2.448-.73-3.016-1.8L4.27 17.339l-1.151-1.151 2.151-2.151c-.365-.934-.282-1.998.282-2.849.848-.927 2.282-.927 3.13 0l2.151 2.151-1.151 1.151-1.153-2.151c-.566-1.07-1.719-1.8-3.016-1.8-.565 0-1.131.282-1.414.565-.565.565-.565 1.414 0 1.979.565.565 1.414.565 1.979 0l2.151-2.151 1.151 1.151-2.151 2.151c-.848.927-2.282.927-3.13 0z"/>
              </svg>
            </a>
          @endif
          
          @if($academy->youtube_url)
            <a href="{{ $academy->youtube_url }}" target="_blank" rel="noopener"
               class="w-10 h-10 flex items-center justify-center bg-primary-500/20 rounded-full hover:bg-primary-500 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
              </svg>
            </a>
          @endif
        </div>
      </div>

      <!-- Main Sections -->
      <div>
        <h3 class="text-lg font-bold mb-6 font-arabic">{{ __('academy.footer.main_sections') }}</h3>
        <ul class="space-y-3">
          @if($academy->quran_enabled ?? true)
            <li>
              <a href="#quran" class="text-gray-400 hover:text-white transition-colors duration-200 font-arabic">
                {{ __('academy.footer.quran_section') }}
              </a>
            </li>
          @endif

          @if($academy->academic_enabled ?? true)
            <li>
              <a href="#academic" class="text-gray-400 hover:text-white transition-colors duration-200 font-arabic">
                {{ __('academy.footer.academic_section') }}
              </a>
            </li>
          @endif

          @if($academy->recorded_courses_enabled ?? true)
            <li>
              <a href="#courses" class="text-gray-400 hover:text-white transition-colors duration-200 font-arabic">
                {{ __('academy.nav.sections.courses') }}
              </a>
            </li>
          @endif

          <li>
            <a href="#about" class="text-gray-400 hover:text-white transition-colors duration-200 font-arabic">
              {{ __('academy.footer.teachers') }}
            </a>
          </li>
        </ul>
      </div>

      <!-- Important Links -->
      <div>
        <h3 class="text-lg font-bold mb-6 font-arabic">{{ __('academy.footer.important_links') }}</h3>
        <ul class="space-y-3">
          <li>
            <a href="#about" class="text-gray-400 hover:text-white transition-colors duration-200 font-arabic">
              {{ __('academy.footer.about') }}
            </a>
          </li>
          <li>
            <a href="{{ Route::has('privacy') ? route('privacy', ['subdomain' => $academy->subdomain]) : '#' }}" class="text-gray-400 hover:text-white transition-colors duration-200 font-arabic">
              {{ __('academy.footer.privacy') }}
            </a>
          </li>
          <li>
            <a href="{{ Route::has('terms') ? route('terms', ['subdomain' => $academy->subdomain]) : '#' }}" class="text-gray-400 hover:text-white transition-colors duration-200 font-arabic">
              {{ __('academy.footer.terms_of_use') }}
            </a>
          </li>
          <li>
            <a href="{{ Route::has('faq') ? route('faq', ['subdomain' => $academy->subdomain]) : '#' }}" class="text-gray-400 hover:text-white transition-colors duration-200 font-arabic">
              {{ __('academy.footer.faq') }}
            </a>
          </li>
          <li>
            <a href="{{ Route::has('support') ? route('support', ['subdomain' => $academy->subdomain]) : '#' }}" class="text-gray-400 hover:text-white transition-colors duration-200 font-arabic">
              {{ __('academy.footer.support') }}
            </a>
          </li>
        </ul>
      </div>

      <!-- Contact Information -->
      <div>
        <h3 class="text-lg font-bold mb-6 font-arabic">{{ __('academy.footer.contact') }}</h3>
        <ul class="space-y-4">
          @if($academy->phone)
            <li class="flex items-start text-gray-400">
              <svg class="w-5 h-5 ms-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
              </svg>
              <div>
                <span class="block font-arabic">{{ $academy->phone }}</span>
              </div>
            </li>
          @endif
          
          @if($academy->email)
            <li class="flex items-start text-gray-400">
              <svg class="w-5 h-5 ms-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
              </svg>
              <div>
                <span class="block">{{ $academy->email }}</span>
              </div>
            </li>
          @endif
          
          @if($academy->address)
            <li class="flex items-start text-gray-400">
              <svg class="w-5 h-5 ms-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
              <div>
                <span class="block font-arabic">{{ $academy->address }}</span>
              </div>
            </li>
          @endif
          
          <!-- Default contact info if academy doesn't have specific details -->
          @if(!$academy->phone && !$academy->email && !$academy->address)
            <li class="flex items-start text-gray-400">
              <svg class="w-5 h-5 ms-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
              </svg>
              <span class="font-arabic">{{ config('app.contact_phone', '+966 50 123 4567') }}</span>
            </li>
            <li class="flex items-start text-gray-400">
              <svg class="w-5 h-5 ms-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
              </svg>
              <span>{{ config('app.contact_email', 'info@itqanway.com') }}</span>
            </li>
            <li class="flex items-start text-gray-400">
              <svg class="w-5 h-5 ms-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
              <span class="font-arabic">{{ __('academy.footer.default_address') }}</span>
            </li>
          @endif
        </ul>

        <!-- Contact Form Button -->
        <div class="mt-6">
          <a href="{{ Route::has('contact') ? route('contact', ['subdomain' => $academy->subdomain]) : '#contact' }}"
             class="inline-flex items-center px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white font-medium rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-gray-900 font-arabic">
            <svg class="w-5 h-5 ms-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
            </svg>
            {{ __('academy.footer.send_message') }}
          </a>
        </div>
      </div>
    </div>

    <!-- Footer Bottom -->
    <div class="border-t border-gray-800 pt-8 text-center">
      <p class="text-gray-400 font-arabic">
        © {{ date('Y') }} {{ $academy->name }}. {{ __('academy.footer.copyright') }}.
        <span class="mx-2">|</span>
        <a href="https://itqan.com" target="_blank" rel="noopener" class="hover:text-white transition-colors duration-200">
          {{ __('academy.footer.powered_by') }}
        </a>
      </p>
    </div>
  </div>
</footer>