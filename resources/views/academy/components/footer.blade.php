<!-- Footer -->
<footer class="bg-gray-900 text-white py-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">
      <div>
        <div class="flex items-center mb-6">
          <div class="w-8 h-8 flex items-center justify-center">
            <i class="ri-book-open-line text-2xl text-primary"></i>
          </div>
          <span class="mr-2 text-xl font-bold">{{ $academy->name ?? 'أكاديمية إتقان' }}</span>
        </div>
        <p class="text-gray-400 mb-6 leading-relaxed">
          {{ $academy->description ?? 'منصة تعليمية شاملة تهدف إلى تقديم أفضل تجربة تعليمية في القرآن الكريم والمواد الأكاديمية' }}
        </p>
        <div class="flex space-x-4 space-x-reverse">
          @if($academy->social_media ?? false)
            @if($academy->social_media->facebook)
              <a href="{{ $academy->social_media->facebook }}" class="w-10 h-10 flex items-center justify-center bg-primary/20 rounded-full hover:bg-primary transition-colors duration-200">
                <i class="ri-facebook-fill"></i>
              </a>
            @endif
            @if($academy->social_media->twitter)
              <a href="{{ $academy->social_media->twitter }}" class="w-10 h-10 flex items-center justify-center bg-primary/20 rounded-full hover:bg-primary transition-colors duration-200">
                <i class="ri-twitter-fill"></i>
              </a>
            @endif
            @if($academy->social_media->instagram)
              <a href="{{ $academy->social_media->instagram }}" class="w-10 h-10 flex items-center justify-center bg-primary/20 rounded-full hover:bg-primary transition-colors duration-200">
                <i class="ri-instagram-fill"></i>
              </a>
            @endif
            @if($academy->social_media->youtube)
              <a href="{{ $academy->social_media->youtube }}" class="w-10 h-10 flex items-center justify-center bg-primary/20 rounded-full hover:bg-primary transition-colors duration-200">
                <i class="ri-youtube-fill"></i>
              </a>
            @endif
          @else
            <a href="#" class="w-10 h-10 flex items-center justify-center bg-primary/20 rounded-full hover:bg-primary transition-colors duration-200">
              <i class="ri-facebook-fill"></i>
            </a>
            <a href="#" class="w-10 h-10 flex items-center justify-center bg-primary/20 rounded-full hover:bg-primary transition-colors duration-200">
              <i class="ri-twitter-fill"></i>
            </a>
            <a href="#" class="w-10 h-10 flex items-center justify-center bg-primary/20 rounded-full hover:bg-primary transition-colors duration-200">
              <i class="ri-instagram-fill"></i>
            </a>
            <a href="#" class="w-10 h-10 flex items-center justify-center bg-primary/20 rounded-full hover:bg-primary transition-colors duration-200">
              <i class="ri-youtube-fill"></i>
            </a>
          @endif
        </div>
      </div>
      <div>
        <h3 class="text-lg font-bold mb-6">الأقسام الرئيسية</h3>
        <ul class="space-y-3">
          @if($academy->quran_enabled ?? true)
            <li><a href="#quran" class="text-gray-400 hover:text-white transition-colors duration-200">قسم القرآن الكريم</a></li>
          @endif
          @if($academy->academic_enabled ?? true)
            <li><a href="#academic" class="text-gray-400 hover:text-white transition-colors duration-200">القسم الأكاديمي</a></li>
          @endif
          @if($academy->recorded_courses_enabled ?? true)
            <li><a href="#courses" class="text-gray-400 hover:text-white transition-colors duration-200">الكورسات المسجلة</a></li>
          @endif
          <li><a href="#teachers" class="text-gray-400 hover:text-white transition-colors duration-200">المعلمون</a></li>
        </ul>
      </div>
      <div>
        <h3 class="text-lg font-bold mb-6">روابط مهمة</h3>
        <ul class="space-y-3">
          <li><a href="{{ route('academy.about-us', ['subdomain' => $academy->subdomain]) }}" class="text-gray-400 hover:text-white transition-colors duration-200">من نحن</a></li>
          <li><a href="{{ route('academy.privacy-policy', ['subdomain' => $academy->subdomain]) }}" class="text-gray-400 hover:text-white transition-colors duration-200">سياسة الخصوصية</a></li>
          <li><a href="{{ route('academy.terms', ['subdomain' => $academy->subdomain]) }}" class="text-gray-400 hover:text-white transition-colors duration-200">الشروط والأحكام</a></li>
          <li><a href="{{ route('academy.refund-policy', ['subdomain' => $academy->subdomain]) }}" class="text-gray-400 hover:text-white transition-colors duration-200">سياسة الاسترجاع</a></li>
        </ul>
      </div>
      <div>
        <h3 class="text-lg font-bold mb-6">تواصل معنا</h3>
        <ul class="space-y-3">
          <li class="flex items-center text-gray-400">
            <div class="w-5 h-5 flex items-center justify-center ml-3">
              <i class="ri-phone-line"></i>
            </div>
            {{ $academy->phone ?? '+966 11 234 5678' }}
          </li>
          <li class="flex items-center text-gray-400">
            <div class="w-5 h-5 flex items-center justify-center ml-3">
              <i class="ri-mail-line"></i>
            </div>
            {{ $academy->email ?? 'info@itqan-academy.com' }}
          </li>
          <li class="flex items-center text-gray-400">
            <div class="w-5 h-5 flex items-center justify-center ml-3">
              <i class="ri-map-pin-line"></i>
            </div>
            {{ $academy->address ?? 'الرياض، المملكة العربية السعودية' }}
          </li>
        </ul>
      </div>
    </div>
    <div class="border-t border-gray-800 pt-8 text-center">
      <p class="text-gray-400">
        © {{ date('Y') }} {{ $academy->name ?? 'أكاديمية إتقان' }}. جميع الحقوق محفوظة.
      </p>
    </div>
  </div>
</footer> 