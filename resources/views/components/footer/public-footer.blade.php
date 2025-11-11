<footer class="bg-gray-900 text-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
      <!-- Academy Info -->
      <div class="col-span-1 md:col-span-2">
        <div class="flex items-center mb-4">
          @if($academy->logo_url)
            <img src="{{ $academy->logo_url }}" alt="{{ $academy->name }}" class="h-10 w-auto ml-3">
          @else
            <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
              <i class="ri-book-open-line text-white text-xl"></i>
            </div>
          @endif
          <span class="text-xl font-bold">{{ $academy->name ?? 'أكاديمية إتقان' }}</span>
        </div>
        <p class="text-gray-400 mb-4">
          {{ $academy->description ?? 'منصة تعليمية متكاملة تقدم دورات عالية الجودة في مختلف المجالات' }}
        </p>
        <div class="flex space-x-4 space-x-reverse">
          <a href="#" class="text-gray-400 hover:text-white transition-colors">
            <i class="ri-facebook-fill text-xl"></i>
          </a>
          <a href="#" class="text-gray-400 hover:text-white transition-colors">
            <i class="ri-twitter-fill text-xl"></i>
          </a>
          <a href="#" class="text-gray-400 hover:text-white transition-colors">
            <i class="ri-instagram-fill text-xl"></i>
          </a>
          <a href="#" class="text-gray-400 hover:text-white transition-colors">
            <i class="ri-youtube-fill text-xl"></i>
          </a>
        </div>
      </div>

      <!-- Quick Links -->
      <div>
        <h3 class="text-lg font-semibold mb-4">روابط سريعة</h3>
        <ul class="space-y-2">
          <li>
            <a href="{{ route('academy.home', ['subdomain' => $academy->subdomain]) }}" 
               class="text-gray-400 hover:text-white transition-colors">
              الرئيسية
            </a>
          </li>
          <li>
            <a href="{{ route('courses.index', ['subdomain' => $academy->subdomain]) }}" 
               class="text-gray-400 hover:text-white transition-colors">
              الدورات المسجلة
            </a>
          </li>
          <li>
            <a href="#" class="text-gray-400 hover:text-white transition-colors">
              عن الأكاديمية
            </a>
          </li>
          <li>
            <a href="#" class="text-gray-400 hover:text-white transition-colors">
              اتصل بنا
            </a>
          </li>
        </ul>
      </div>

      <!-- Contact Info -->
      <div>
        <h3 class="text-lg font-semibold mb-4">معلومات التواصل</h3>
        <ul class="space-y-2">
          <li class="flex items-center text-gray-400">
            <i class="ri-phone-line ml-2"></i>
            <span>{{ $academy->phone ?? '+966 50 000 0000' }}</span>
          </li>
          <li class="flex items-center text-gray-400">
            <i class="ri-mail-line ml-2"></i>
            <span>{{ $academy->email ?? 'info@itqan.com' }}</span>
          </li>
          <li class="flex items-center text-gray-400">
            <i class="ri-map-pin-line ml-2"></i>
            <span>{{ $academy->address ?? 'الرياض، المملكة العربية السعودية' }}</span>
          </li>
        </ul>
      </div>
    </div>

    <!-- Bottom Bar -->
    <div class="border-t border-gray-800 mt-8 pt-8 flex flex-col md:flex-row justify-between items-center">
      <p class="text-gray-400 text-sm">
        © {{ date('Y') }} {{ $academy->name ?? 'أكاديمية إتقان' }}. جميع الحقوق محفوظة.
      </p>
      <div class="flex space-x-6 space-x-reverse mt-4 md:mt-0">
        <a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">
          سياسة الخصوصية
        </a>
        <a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">
          شروط الاستخدام
        </a>
      </div>
    </div>
  </div>
</footer>
