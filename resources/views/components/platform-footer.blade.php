<footer class="bg-gray-900 text-white border-t-4 border-green-500">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Company Info -->
            <div class="col-span-1 md:col-span-2">
                @if(isset($platformSettings) && $platformSettings->logo)
                    <img src="{{ asset('storage/' . $platformSettings->logo) }}" alt="منصة إتقان" class="h-12 w-auto mb-4">
                @else
                    <h3 class="text-2xl font-bold text-green-400 mb-4">منصة إتقان</h3>
                @endif
                <p class="text-gray-300 mb-4">
                    منصة متكاملة تجمع بين خدمات الأعمال الاحترافية والتعليم الإلكتروني،
                    نسعى لتقديم حلول مبتكرة للمؤسسات التعليمية الإسلامية.
                </p>
                <!-- Social Links -->
                @if(isset($platformSettings) && $platformSettings->social_links && count($platformSettings->social_links) > 0)
                    <div class="flex gap-4">
                        @foreach($platformSettings->social_links as $link)
                            <a href="{{ $link['url'] ?? '#' }}" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-green-400 transition-colors" title="{{ $link['name'] ?? '' }}" aria-label="{{ $link['name'] ?? 'رابط اجتماعي' }}">
                                <i class="{{ $link['icon'] ?? 'ri-link' }} text-xl"></i>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="flex gap-4">
                        <a href="#" class="text-gray-400 hover:text-green-400 transition-colors" aria-label="تويتر">
                            <i class="ri-twitter-x-fill text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-green-400 transition-colors" aria-label="فيسبوك">
                            <i class="ri-facebook-fill text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-green-400 transition-colors" aria-label="انستغرام">
                            <i class="ri-instagram-fill text-xl"></i>
                        </a>
                    </div>
                @endif
            </div>

            <!-- Quick Links -->
            <div>
                <h4 class="text-lg font-semibold text-green-400 mb-4">روابط سريعة</h4>
                <ul class="space-y-2">
                    <li><a href="{{ route('platform.home') }}" class="text-gray-300 hover:text-green-400 transition-colors">الرئيسية</a></li>
                    <li><a href="{{ route('platform.business-services') }}" class="text-gray-300 hover:text-green-400 transition-colors">خدمات الأعمال</a></li>
                    <li><a href="{{ route('platform.portfolio') }}" class="text-gray-300 hover:text-green-400 transition-colors">البورتفوليو</a></li>
                    <li><a href="{{ route('platform.about') }}" class="text-gray-300 hover:text-green-400 transition-colors">من نحن</a></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div>
                <h4 class="text-lg font-semibold text-green-400 mb-4">معلومات الاتصال</h4>
                <ul class="space-y-3 text-gray-300">
                    @if(isset($platformSettings) && $platformSettings->email)
                        <li class="flex items-center gap-3">
                            <i class="ri-mail-line text-lg text-green-400 flex-shrink-0"></i>
                            <span>{{ $platformSettings->email }}</span>
                        </li>
                    @endif
                    @if(isset($platformSettings) && $platformSettings->phone)
                        <li class="flex items-center gap-3">
                            <i class="ri-phone-line text-lg text-green-400 flex-shrink-0"></i>
                            <span dir="ltr">{{ $platformSettings->phone }}</span>
                        </li>
                    @endif
                    @if(isset($platformSettings) && $platformSettings->address)
                        <li class="flex items-center gap-3">
                            <i class="ri-map-pin-line text-lg text-green-400 flex-shrink-0"></i>
                            <span>{{ $platformSettings->address }}</span>
                        </li>
                    @endif
                    <li class="flex items-center gap-3">
                        <i class="ri-time-line text-lg text-green-400 flex-shrink-0"></i>
                        <span>{{ $platformSettings->working_hours ?? 'الأحد - الخميس: 9:00 ص - 6:00 م' }}</span>
                    </li>
                    @if(!isset($platformSettings) || (!$platformSettings->email && !$platformSettings->phone && !$platformSettings->address))
                        <li class="flex items-center gap-3">
                            <i class="ri-mail-line text-lg text-green-400 flex-shrink-0"></i>
                            <span>{{ config('app.contact_email', 'info@itqanway.com') }}</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="ri-phone-line text-lg text-green-400 flex-shrink-0"></i>
                            <span dir="ltr">{{ config('app.contact_phone', '+966 50 123 4567') }}</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="ri-map-pin-line text-lg text-green-400 flex-shrink-0"></i>
                            <span>{{ config('app.contact_address', 'الرياض، المملكة العربية السعودية') }}</span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="border-t border-gray-800 mt-8 pt-8 text-center">
            <p class="text-gray-400">
                جميع الحقوق محفوظة &copy; {{ date('Y') }} منصة إتقان. تم التطوير بواسطة فريق إتقان.
            </p>
        </div>
    </div>
</footer>
