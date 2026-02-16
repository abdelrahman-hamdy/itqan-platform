<!-- Quran Services Section -->
<section class="py-16 bg-gradient-to-br from-green-50 to-emerald-100 dark:from-gray-800 dark:to-gray-900" id="quran-services">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Section Header -->
        <div class="text-center mb-16 animate-on-scroll">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full mb-6">
                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2L13.5 8.5L20 7L14.5 12.5L21 14L13.5 15.5L12 22L10.5 15.5L4 14L9.5 12.5L3 7L9.5 8.5L12 2Z"/>
                </svg>
            </div>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                خدمات القرآن الكريم
            </h2>
            <p class="text-lg text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
                نوفر برامج متخصصة لتعليم القرآن الكريم وعلومه مع معلمين حاصلين على إجازات قرآنية
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            <!-- Quran Circles Section -->
            <div class="animate-on-scroll">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 h-full">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9V7L15 7.5V9L21 9ZM3 9L9 8.5V7L3 7.5V9ZM13.5 7C14.6 7 15.5 7.9 15.5 9S14.6 11 13.5 11S11.5 10.1 11.5 9S12.4 7 13.5 7ZM9.5 7C10.6 7 11.5 7.9 11.5 9S10.6 11 9.5 11S7.5 10.1 7.5 9S8.4 7 9.5 7Z"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white">الحلقات القرآنية</h3>
                    </div>

                    <p class="text-gray-600 dark:text-gray-300 mb-8">
                        انضم إلى حلقاتنا القرآنية الجماعية واستفد من بيئة تعليمية تفاعلية مع زملاء من نفس مستواك
                    </p>

                    <!-- Quran Circles List -->
                    <div class="space-y-4 mb-8">
                        @if(isset($services['quran_circles']) && $services['quran_circles']->count() > 0)
                            @foreach($services['quran_circles']->take(3) as $circle)
                                <div class="flex items-center justify-between p-4 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors">
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white">{{ $circle->name }}</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-300">
                                            {{ $circle->level }} - {{ $circle->age_group }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-medium text-green-600 dark:text-green-400">
                                            {{ $circle->teacher->name }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $circle->schedule }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <!-- Default Circles if none exist -->
                            <div class="flex items-center justify-between p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">حلقة المبتدئين</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">تعليم أساسيات التلاوة والتجويد</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-medium text-green-600 dark:text-green-400">يومياً</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">5:00 م - 6:00 م</div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">حلقة المتقدمين</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">تحسين الأداء وإتقان الأحكام</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-medium text-green-600 dark:text-green-400">3 أيام أسبوعياً</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">7:00 م - 8:00 م</div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- CTA Button -->
                    <a href="#" class="w-full bg-green-600 text-white text-center py-3 rounded-lg font-medium hover:bg-green-700 transition-colors inline-block">
                        انضم للحلقات القرآنية
                    </a>
                </div>
            </div>

            <!-- Individual Quran Sessions Section -->
            <div class="animate-on-scroll">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 h-full">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12ZM12 14C9.33 14 4 15.34 4 18V20H20V18C20 15.34 14.67 14 12 14Z"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white">جلسات فردية</h3>
                    </div>

                    <p class="text-gray-600 dark:text-gray-300 mb-8">
                        احصل على دروس قرآنية فردية مع معلمين متخصصين لتحقيق تقدم أسرع وأكثر تخصصاً
                    </p>

                    <!-- Individual Sessions Features -->
                    <div class="space-y-4 mb-8">
                        <div class="flex items-start gap-3">
                            <div class="w-6 h-6 bg-emerald-100 dark:bg-emerald-900 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-white">تعليم مخصص</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-300">برنامج تعليمي يتناسب مع مستواك وأهدافك</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-6 h-6 bg-emerald-100 dark:bg-emerald-900 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-white">مرونة في المواعيد</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-300">اختر الأوقات التي تناسب جدولك اليومي</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-6 h-6 bg-emerald-100 dark:bg-emerald-900 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-white">معلمون حاصلون على إجازات</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-300">جميع معلمينا حاصلون على إجازات قرآنية</p>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing Info -->
                    <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-4 mb-6">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                                {{ __('components.cards.quran_teacher.starts_from') }} 150 {{ getCurrencySymbol() }} {{ __('common.units.per_month') }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-300">4 جلسات شهرياً</div>
                        </div>
                    </div>

                    <!-- CTA Button -->
                    <a href="#" class="w-full bg-emerald-600 text-white text-center py-3 rounded-lg font-medium hover:bg-emerald-700 transition-colors inline-block">
                        احجز جلسة تجريبية مجانية
                    </a>
                </div>
            </div>
        </div>

        <!-- Quran Services Benefits -->
        <div class="mt-16 animate-on-scroll">
            <div class="bg-gradient-to-r from-green-600 to-emerald-600 dark:from-green-800 dark:to-emerald-800 rounded-2xl shadow-lg p-8 text-white">
                <div class="text-center mb-8">
                    <h3 class="text-2xl md:text-3xl font-bold mb-4">لماذا تختار خدماتنا القرآنية؟</h3>
                    <p class="text-green-100">نقدم تعليماً قرآنياً متميزاً يجمع بين الأصالة والطرق التعليمية الحديثة</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2L15.09 8.26L22 9L17 14L18.18 21L12 17.77L5.82 21L7 14L2 9L8.91 8.26L12 2Z"/>
                            </svg>
                        </div>
                        <h4 class="font-semibold mb-2">معلمون حاصلون على إجازات</h4>
                        <p class="text-sm text-green-100">جميع معلمينا حاصلون على إجازات قرآنية من مشايخ كبار</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C13.1 2 14 2.9 14 4V12L19.5 17.5C19.9 17.9 19.9 18.6 19.5 19C19.1 19.4 18.4 19.4 18 19L12 13H4C2.9 13 2 12.1 2 11V4C2 2.9 2.9 2 4 2H12Z"/>
                            </svg>
                        </div>
                        <h4 class="font-semibold mb-2">منهج تدريجي</h4>
                        <p class="text-sm text-green-100">منهج علمي متدرج يناسب جميع الأعمار والمستويات</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M16 1H4C2.9 1 2 1.9 2 3V17H4V3H16V1ZM19 5H8C6.9 5 6 5.9 6 7V21C6 22.1 6.9 23 8 23H19C20.1 23 21 22.1 21 21V7C21 5.9 20.1 5 19 5ZM18 21H9V7H18V21Z"/>
                            </svg>
                        </div>
                        <h4 class="font-semibold mb-2">متابعة دقيقة</h4>
                        <p class="text-sm text-green-100">متابعة مستمرة لتقدم الطلاب وتقارير دورية للأهالي</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2L13.5 8.5L20 7L14.5 12.5L21 14L13.5 15.5L12 22L10.5 15.5L4 14L9.5 12.5L3 7L9.5 8.5L12 2Z"/>
                            </svg>
                        </div>
                        <h4 class="font-semibold mb-2">بيئة إسلامية</h4>
                        <p class="text-sm text-green-100">بيئة تعليمية إسلامية تعزز القيم والأخلاق النبيلة</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>