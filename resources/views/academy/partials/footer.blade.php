<!-- Footer -->
<footer class="bg-gray-900 dark:bg-black text-white" id="footer">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Main Footer Content -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 py-16">
            <!-- Academy Information -->
            <div class="lg:col-span-2">
                <!-- Academy Logo & Name -->
                <div class="flex items-center gap-3 mb-6">
                    @if($academy->logo_url)
                        <img src="{{ $academy->logo_url }}" alt="{{ $academy->name }}" class="h-12 w-12 rounded-lg">
                    @else
                        <div class="h-12 w-12 academy-bg-primary rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-lg">{{ substr($academy->name, 0, 1) }}</span>
                        </div>
                    @endif
                    <h3 class="text-2xl font-bold">{{ $academy->name }}</h3>
                </div>

                <!-- Academy Description -->
                @if($academy->description)
                    <p class="text-gray-300 mb-6 leading-relaxed max-w-md">
                        {{ $academy->description }}
                    </p>
                @endif

                <!-- Contact Information -->
                <div class="space-y-3">
                    @if($academy->email)
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20 4H4C2.9 4 2.01 4.9 2.01 6L2 18C2 19.1 2.9 20 4 20H20C21.1 20 22 19.1 22 18V6C22 4.9 21.1 4 20 4ZM20 8L12 13L4 8V6L12 11L20 6V8Z"/>
                            </svg>
                            <a href="mailto:{{ $academy->email }}" class="text-gray-300 hover:text-white transition-colors">
                                {{ $academy->email }}
                            </a>
                        </div>
                    @endif

                    @if($academy->phone)
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M6.62 10.79C8.06 13.62 10.38 15.94 13.21 17.38L15.41 15.18C15.69 14.9 16.08 14.82 16.43 14.93C17.55 15.3 18.75 15.5 20 15.5C20.55 15.5 21 15.95 21 16.5V20C21 20.55 20.55 21 20 21C10.61 21 3 13.39 3 4C3 3.45 3.45 3 4 3H7.5C8.05 3 8.5 3.45 8.5 4C8.5 5.25 8.7 6.45 9.07 7.57C9.18 7.92 9.1 8.31 8.82 8.59L6.62 10.79Z"/>
                            </svg>
                            <a href="tel:{{ $academy->phone }}" class="text-gray-300 hover:text-white transition-colors">
                                {{ $academy->phone }}
                            </a>
                        </div>
                    @endif

                    @if($academy->website)
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12S6.48 22 12 22 22 17.52 22 12 17.52 2 12 2ZM11 19.93C7.05 19.44 4 16.08 4 12C4 11.38 4.08 10.79 4.21 10.21L9 15V16C9 17.1 9.9 18 11 18V19.93ZM17.9 17.39C17.64 16.58 16.9 16 16 16H15V13C15 12.45 14.55 12 14 12H8V10H10C10.55 10 11 9.55 11 9V7H13C14.1 7 15 6.1 15 5V4.59C17.93 5.77 20 8.65 20 12C20 14.08 19.2 15.97 17.9 17.39Z"/>
                            </svg>
                            <a href="{{ $academy->website }}" target="_blank" class="text-gray-300 hover:text-white transition-colors">
                                {{ str_replace(['http://', 'https://'], '', $academy->website) }}
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Social Media Links (if available) -->
                <div class="flex gap-4 mt-6">
                    <!-- Example social media icons - you can make these configurable -->
                    <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:academy-bg-primary transition-colors">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 4.557C23.117 4.949 22.168 5.213 21.172 5.332C22.189 4.723 22.97 3.758 23.337 2.608C22.386 3.172 21.332 3.582 20.21 3.803C19.313 2.846 18.032 2.248 16.616 2.248C13.437 2.248 11.101 5.214 11.819 8.293C7.728 8.088 4.1 6.128 1.671 3.149C0.381 5.362 1.002 8.257 3.194 9.723C2.388 9.697 1.628 9.476 0.965 9.107C0.911 11.388 2.546 13.522 4.914 13.997C4.221 14.185 3.462 14.229 2.69 14.081C3.316 16.037 5.134 17.46 7.29 17.5C5.22 19.123 2.612 19.848 0 19.54C2.179 20.937 4.768 21.752 7.548 21.752C16.69 21.752 21.855 14.031 21.543 7.106C22.505 6.411 23.34 5.544 24 4.557Z"/>
                        </svg>
                    </a>
                    <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:academy-bg-primary transition-colors">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M22.46 6C21.69 6.35 20.86 6.58 20 6.69C20.88 6.16 21.56 5.32 21.88 4.31C21.05 4.81 20.13 5.16 19.16 5.36C18.37 4.5 17.26 4 16 4C13.65 4 11.73 5.92 11.73 8.29C11.73 8.63 11.77 8.96 11.84 9.27C8.28 9.09 5.11 7.38 3 4.79C2.63 5.42 2.42 6.16 2.42 6.94C2.42 8.43 3.17 9.75 4.33 10.5C3.62 10.5 2.96 10.3 2.38 10C2.38 10 2.38 10 2.38 10.03C2.38 12.11 3.86 13.85 5.82 14.24C5.46 14.34 5.08 14.39 4.69 14.39C4.42 14.39 4.15 14.36 3.89 14.31C4.43 16 6 17.26 7.89 17.29C6.43 18.45 4.58 19.13 2.56 19.13C2.22 19.13 1.88 19.11 1.54 19.07C3.44 20.29 5.7 21 8.12 21C16 21 20.33 14.46 20.33 8.79C20.33 8.6 20.33 8.42 20.32 8.23C21.16 7.63 21.88 6.87 22.46 6Z"/>
                        </svg>
                    </a>
                    <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:academy-bg-primary transition-colors">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12.017 0C5.396 0 .029 5.367.029 11.987C.029 17.396 3.542 21.983 8.369 23.439V15.188H6.18V11.987H8.369V9.348C8.369 7.189 9.915 5.756 11.839 5.756C12.761 5.756 13.726 5.931 13.726 5.931V8.058H12.663C11.617 8.058 11.268 8.706 11.268 9.372V11.987H13.627L13.227 15.188H11.268V23.439C16.095 21.983 19.608 17.396 19.608 11.987C19.608 5.367 14.241.001 12.017.001Z"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Quick Links -->
            <div>
                <h4 class="text-lg font-semibold mb-6">روابط سريعة</h4>
                <ul class="space-y-3">
                    <li>
                        <a href="{{ route('academy.home', ['subdomain' => $academy->subdomain]) }}" class="text-gray-300 hover:text-white transition-colors">
                            الرئيسية
                        </a>
                    </li>
                    <li>
                        <a href="#quran-services" class="text-gray-300 hover:text-white transition-colors">
                            خدمات القرآن
                        </a>
                    </li>
                    <li>
                        <a href="#academic-services" class="text-gray-300 hover:text-white transition-colors">
                            الخدمات الأكاديمية
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('courses.index', ['subdomain' => $academy->subdomain]) }}" class="text-gray-300 hover:text-white transition-colors">
                            الدورات المسجلة
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('login', ['subdomain' => $academy->subdomain]) }}" class="text-gray-300 hover:text-white transition-colors">
                            تسجيل الدخول
                        </a>
                    </li>
                    @auth
                    <li>
                        <a href="{{ route('student.dashboard') }}" class="text-gray-300 hover:text-white transition-colors">
                            لوحة التحكم
                        </a>
                    </li>
                    @endauth
                </ul>
            </div>

            <!-- Services -->
            <div>
                <h4 class="text-lg font-semibold mb-6">خدماتنا</h4>
                <ul class="space-y-3">
                    <li class="text-gray-300">تحفيظ القرآن الكريم</li>
                    <li class="text-gray-300">دورات أكاديمية تفاعلية</li>
                    <li class="text-gray-300">معلمون خصوصيون</li>
                    <li class="text-gray-300">دورات مسجلة</li>
                    <li class="text-gray-300">حلقات قرآنية جماعية</li>
                    <li class="text-gray-300">متابعة أكاديمية</li>
                </ul>

                <!-- Download App Section -->
                <div class="mt-8">
                    <h5 class="text-sm font-semibold mb-4 text-gray-400">حمل التطبيق</h5>
                    <div class="space-y-2">
                        <a href="#" class="block">
                            <img src="https://developer.apple.com/assets/elements/badges/download-on-the-app-store.svg" alt="Download on App Store" class="h-10 opacity-80 hover:opacity-100 transition-opacity">
                        </a>
                        <a href="#" class="block">
                            <img src="https://play.google.com/intl/en_us/badges/static/images/badges/en_badge_web_generic.png" alt="Get it on Google Play" class="h-10 opacity-80 hover:opacity-100 transition-opacity">
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Footer -->
        <div class="border-t border-gray-800 py-8">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <!-- Copyright -->
                <div class="text-gray-400 text-sm">
                    <p>© {{ date('Y') }} {{ $academy->name }}. جميع الحقوق محفوظة.</p>
                </div>

                <!-- Platform Credit -->
                <div class="text-gray-400 text-sm">
                    <p>مدعوم بمنصة <span class="academy-primary font-semibold">إتقان</span> التعليمية</p>
                </div>

                <!-- Footer Links -->
                <div class="flex gap-6 text-sm">
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">سياسة الخصوصية</a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">شروط الاستخدام</a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">الدعم الفني</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Back to Top Button -->
    <button id="back-to-top" class="fixed bottom-8 right-8 w-12 h-12 academy-bg-primary rounded-full flex items-center justify-center text-white shadow-lg hover:opacity-90 transition-all duration-300 opacity-0 pointer-events-none">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
        </svg>
    </button>
</footer>

<script>
    // Back to top functionality
    window.addEventListener('scroll', function() {
        const backToTopButton = document.getElementById('back-to-top');
        if (window.pageYOffset > 300) {
            backToTopButton.classList.remove('opacity-0', 'pointer-events-none');
            backToTopButton.classList.add('opacity-100', 'pointer-events-auto');
        } else {
            backToTopButton.classList.add('opacity-0', 'pointer-events-none');
            backToTopButton.classList.remove('opacity-100', 'pointer-events-auto');
        }
    });

    document.getElementById('back-to-top').addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
</script>