<nav class="bg-white shadow-sm border-b border-gray-200 fixed top-0 left-0 right-0 z-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center h-16">
      <!-- Logo and Brand -->
      <div class="flex items-center">
        <a href="{{ route('academy.home', ['subdomain' => $academy->subdomain]) }}" class="flex items-center">
          @if($academy->logo_url)
            <img src="{{ $academy->logo_url }}" alt="{{ $academy->name }}" class="h-8 w-auto ml-3">
          @else
            <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
              <i class="ri-book-open-line text-white text-lg"></i>
            </div>
          @endif
          <span class="text-xl font-bold text-gray-900 mr-2">{{ $academy->name ?? 'أكاديمية إتقان' }}</span>
        </a>
      </div>

      <!-- Navigation Links -->
      <div class="hidden md:flex items-center space-x-8 space-x-reverse">
        <a href="{{ route('academy.home', ['subdomain' => $academy->subdomain]) }}" 
           class="text-gray-700 hover:text-primary transition-colors">
          الرئيسية
        </a>
        <a href="{{ route('courses.index', ['subdomain' => $academy->subdomain]) }}" 
           class="text-gray-700 hover:text-primary transition-colors">
          الدورات المسجلة
        </a>
        <a href="#" class="text-gray-700 hover:text-primary transition-colors">
          عن الأكاديمية
        </a>
        <a href="#" class="text-gray-700 hover:text-primary transition-colors">
          اتصل بنا
        </a>
      </div>

      <!-- Auth Buttons -->
      <div class="flex items-center space-x-4 space-x-reverse">
        @auth
          @if(auth()->user()->role === 'student')
            <a href="{{ route('student.dashboard', ['subdomain' => $academy->subdomain]) }}" 
               class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors">
              لوحة التحكم
            </a>
          @elseif(auth()->user()->role === 'teacher')
            <a href="{{ route('teacher.dashboard', ['subdomain' => $academy->subdomain]) }}" 
               class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors">
              لوحة التحكم
            </a>
          @else
            <a href="{{ route('filament.admin.pages.dashboard') }}" 
               class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors">
              لوحة التحكم
            </a>
          @endif
        @else
          <a href="{{ route('login', ['subdomain' => $academy->subdomain]) }}" 
             class="text-gray-700 hover:text-primary transition-colors">
            تسجيل الدخول
          </a>
          <a href="{{ route('register', ['subdomain' => $academy->subdomain]) }}" 
             class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors">
            إنشاء حساب
          </a>
        @endauth
      </div>

      <!-- Mobile menu button -->
      <div class="md:hidden">
        <button type="button" class="text-gray-700 hover:text-primary focus:outline-none focus:text-primary" 
                onclick="toggleMobileMenu()">
          <i class="ri-menu-line text-xl"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- Mobile menu -->
  <div id="mobile-menu" class="md:hidden hidden bg-white border-t border-gray-200">
    <div class="px-2 pt-2 pb-3 space-y-1">
      <a href="{{ route('academy.home', ['subdomain' => $academy->subdomain]) }}" 
         class="block px-3 py-2 text-gray-700 hover:text-primary transition-colors">
        الرئيسية
      </a>
      <a href="{{ route('courses.index', ['subdomain' => $academy->subdomain]) }}" 
         class="block px-3 py-2 text-gray-700 hover:text-primary transition-colors">
        الدورات المسجلة
      </a>
      <a href="#" class="block px-3 py-2 text-gray-700 hover:text-primary transition-colors">
        عن الأكاديمية
      </a>
      <a href="#" class="block px-3 py-2 text-gray-700 hover:text-primary transition-colors">
        اتصل بنا
      </a>
      
      @guest
        <div class="border-t border-gray-200 pt-4 mt-4">
          <a href="{{ route('login', ['subdomain' => $academy->subdomain]) }}" 
             class="block px-3 py-2 text-gray-700 hover:text-primary transition-colors">
            تسجيل الدخول
          </a>
          <a href="{{ route('register', ['subdomain' => $academy->subdomain]) }}" 
             class="block px-3 py-2 bg-primary text-white rounded-lg mx-3 mt-2 text-center">
            إنشاء حساب
          </a>
        </div>
      @endguest
    </div>
  </div>
</nav>

<script>
function toggleMobileMenu() {
  const mobileMenu = document.getElementById('mobile-menu');
  mobileMenu.classList.toggle('hidden');
}
</script>
