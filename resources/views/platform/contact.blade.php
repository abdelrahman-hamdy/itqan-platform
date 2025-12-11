@extends('components.platform-layout')

@section('title', 'اتصل بنا - منصة إتقان')

@section('content')
    <!-- Hero Section -->
    <div class="relative flex items-center justify-center overflow-hidden bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 py-20 pt-40" style="min-height: 70vh;">
        <!-- Background Pattern Layer -->
        <div class="absolute inset-0 opacity-20">
            <div class="absolute inset-0" style="background-image: url('/storage/app-design-assets/bg-pattern1.png'); background-size: 100px 100px; background-repeat: repeat;"></div>
        </div>
        
        <!-- Enhanced Background Elements -->
        <div class="absolute top-0 right-0 w-[40rem] h-[40rem] bg-gradient-to-br from-blue-500/20 via-blue-600/30 to-indigo-600/25 rounded-full blur-3xl opacity-60 animate-pulse" style="animation-duration: 6s;"></div>
        <div class="absolute bottom-0 left-0 w-[45rem] h-[45rem] bg-gradient-to-tr from-emerald-500/20 via-green-600/25 to-teal-600/20 rounded-full blur-3xl opacity-55 animate-bounce" style="animation-duration: 8s;"></div>
        <div class="absolute top-1/2 right-0 transform translate-x-1/2 -translate-y-1/2 w-[35rem] h-[35rem] bg-gradient-to-l from-cyan-500/20 via-teal-600/25 to-blue-600/20 rounded-full blur-3xl opacity-50 animate-ping" style="animation-duration: 10s;"></div>
        <div class="absolute top-0 left-0 w-[38rem] h-[38rem] bg-gradient-to-br from-purple-500/20 via-violet-600/25 to-indigo-600/20 rounded-full blur-3xl opacity-45 animate-pulse" style="animation-duration: 7s; animation-delay: 2s;"></div>
        <div class="absolute bottom-0 right-0 w-[42rem] h-[42rem] bg-gradient-to-tl from-indigo-500/20 via-blue-600/25 to-purple-600/20 rounded-full blur-3xl opacity-50 animate-bounce" style="animation-duration: 9s; animation-delay: 3s;"></div>
        
        <!-- Additional subtle elements -->
        <div class="absolute top-1/4 left-1/4 w-[20rem] h-[20rem] bg-gradient-to-r from-rose-500/15 to-pink-600/20 rounded-full blur-2xl opacity-30 animate-pulse" style="animation-duration: 12s; animation-delay: 1s;"></div>
        <div class="absolute bottom-1/4 right-1/4 w-[25rem] h-[25rem] bg-gradient-to-r from-amber-500/15 to-yellow-600/20 rounded-full blur-2xl opacity-25 animate-bounce" style="animation-duration: 11s; animation-delay: 4s;"></div>
        
        <!-- Content -->
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <!-- Animated Label -->
            <div class="mb-8" data-aos="fade-up">
                <span class="inline-flex items-center gap-3 px-6 py-3 bg-white/10 text-blue-100 rounded-full text-sm font-semibold border border-blue-300/30 backdrop-blur-sm">
                    <i class="ri-phone-line text-lg"></i>
                    تواصل معنا
                </span>
            </div>
            
            <!-- Main Heading -->
            <h1 class="text-4xl md:text-6xl font-bold mb-6 leading-relaxed text-white" data-aos="fade-up" data-aos-delay="200">
                <span class="bg-gradient-to-r from-white via-blue-100 to-indigo-100 bg-clip-text text-transparent">
                    اتصل بنا
                </span>
            </h1>
            
            <!-- Subtitle -->
            <p class="text-xl md:text-2xl mb-8 text-blue-100 max-w-3xl mx-auto" data-aos="fade-up" data-aos-delay="400">
                نحن هنا لمساعدتك في تحقيق مشاريعك وأهدافك، تواصل معنا الآن
            </p>
            
            <!-- Action Button -->
            <div class="flex justify-center" data-aos="fade-up" data-aos-delay="600">
                <a href="#contact-info" class="bg-green-600 text-white px-8 py-4 rounded-xl font-semibold hover:bg-green-700 transition-all transform hover:scale-105">
                    <i class="ri-message-line ml-2"></i>
                    أرسل رسالة
                </a>
            </div>
        </div>
    </div>

    <!-- Contact Information -->
    <section id="contact-info" class="py-24 bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 relative overflow-hidden">
        <!-- Section Top Border -->
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-100 via-indigo-100 to-purple-100"></div>
        
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-5">
            <div class="absolute inset-0" style="background-image: url('/storage/app-design-assets/bg-pattern1.png'); background-size: 50px 50px; background-repeat: repeat;"></div>
        </div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
                <div data-aos="fade-right">
                    <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-8 leading-relaxed">
                        <span class="flex items-center gap-4">
                            <i class="ri-information-line text-5xl text-blue-600"></i>
                            <span class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 bg-clip-text text-transparent">
                                معلومات التواصل
                            </span>
                        </span>
                    </h2>
                    
                    <div class="space-y-6 md:space-y-8">
                        <div class="flex items-start gap-3 md:gap-4">
                            <div class="w-10 h-10 md:w-12 md:h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="ri-mail-line text-lg md:text-xl text-blue-600"></i>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-base md:text-lg font-semibold text-gray-900 mb-1 md:mb-2">البريد الإلكتروني</h3>
                                <p class="text-sm md:text-base text-gray-600 break-all">info@itqan.com</p>
                                <p class="text-sm md:text-base text-gray-600 break-all">support@itqan.com</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 md:gap-4">
                            <div class="w-10 h-10 md:w-12 md:h-12 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="ri-phone-line text-lg md:text-xl text-indigo-600"></i>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-base md:text-lg font-semibold text-gray-900 mb-1 md:mb-2">الهاتف</h3>
                                <p class="text-sm md:text-base text-gray-600">+966 50 123 4567</p>
                                <p class="text-sm md:text-base text-gray-600">+966 11 234 5678</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 md:gap-4">
                            <div class="w-10 h-10 md:w-12 md:h-12 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="ri-map-pin-line text-lg md:text-xl text-purple-600"></i>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-base md:text-lg font-semibold text-gray-900 mb-1 md:mb-2">العنوان</h3>
                                <p class="text-sm md:text-base text-gray-600">الرياض، المملكة العربية السعودية</p>
                                <p class="text-sm md:text-base text-gray-600">برج المملكة، الطابق 15</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 md:gap-4">
                            <div class="w-10 h-10 md:w-12 md:h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="ri-time-line text-lg md:text-xl text-blue-600"></i>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-base md:text-lg font-semibold text-gray-900 mb-1 md:mb-2">ساعات العمل</h3>
                                <p class="text-sm md:text-base text-gray-600">الأحد - الخميس: 8:00 ص - 6:00 م</p>
                                <p class="text-sm md:text-base text-gray-600">الجمعة - السبت: 9:00 ص - 2:00 م</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 md:mt-12">
                        <h3 class="text-lg md:text-xl font-semibold text-gray-900 mb-3 md:mb-4">تابعنا على وسائل التواصل الاجتماعي</h3>
                        <div class="flex gap-3 md:gap-4">
                            <a href="#" class="min-h-[44px] min-w-[44px] w-11 h-11 md:w-12 md:h-12 bg-blue-100 rounded-lg flex items-center justify-center hover:bg-blue-200 transition-colors">
                                <i class="ri-twitter-x-line text-lg md:text-xl text-blue-600"></i>
                            </a>
                            <a href="#" class="min-h-[44px] min-w-[44px] w-11 h-11 md:w-12 md:h-12 bg-indigo-100 rounded-lg flex items-center justify-center hover:bg-indigo-200 transition-colors">
                                <i class="ri-facebook-line text-lg md:text-xl text-indigo-600"></i>
                            </a>
                            <a href="#" class="min-h-[44px] min-w-[44px] w-11 h-11 md:w-12 md:h-12 bg-purple-100 rounded-lg flex items-center justify-center hover:bg-purple-200 transition-colors">
                                <i class="ri-linkedin-line text-lg md:text-xl text-purple-600"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-50 rounded-xl md:rounded-2xl p-4 sm:p-6 md:p-8 border border-gray-200 shadow-xl" data-aos="fade-left">
                    <h3 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 mb-4 md:mb-6">
                        <span class="flex items-center gap-2 md:gap-3">
                            <i class="ri-message-line text-2xl md:text-3xl text-blue-600"></i>
                            أرسل لنا رسالة
                        </span>
                    </h3>

                    <form class="space-y-4 md:space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1.5 md:mb-2">الاسم الأول *</label>
                                <input type="text" id="first_name" name="first_name" required
                                       class="w-full min-h-[44px] px-3 md:px-4 py-2.5 md:py-3 text-sm md:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            <div>
                                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1.5 md:mb-2">الاسم الأخير *</label>
                                <input type="text" id="last_name" name="last_name" required
                                       class="w-full min-h-[44px] px-3 md:px-4 py-2.5 md:py-3 text-sm md:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5 md:mb-2">البريد الإلكتروني *</label>
                            <input type="email" id="email" name="email" required
                                   class="w-full min-h-[44px] px-3 md:px-4 py-2.5 md:py-3 text-sm md:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1.5 md:mb-2">رقم الهاتف</label>
                            <input type="tel" id="phone" name="phone"
                                   class="w-full min-h-[44px] px-3 md:px-4 py-2.5 md:py-3 text-sm md:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <div>
                            <label for="subject" class="block text-sm font-medium text-gray-700 mb-1.5 md:mb-2">الموضوع *</label>
                            <select id="subject" name="subject" required
                                    class="w-full min-h-[44px] px-3 md:px-4 py-2.5 md:py-3 text-sm md:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">اختر الموضوع</option>
                                <option value="general">استفسار عام</option>
                                <option value="business">خدمات الأعمال</option>
                                <option value="support">الدعم الفني</option>
                                <option value="partnership">شراكة</option>
                                <option value="other">أخرى</option>
                            </select>
                        </div>

                        <div>
                            <label for="message" class="block text-sm font-medium text-gray-700 mb-1.5 md:mb-2">الرسالة *</label>
                            <textarea id="message" name="message" rows="5" required
                                      class="w-full px-3 md:px-4 py-2.5 md:py-3 text-sm md:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                      placeholder="اكتب رسالتك هنا..."></textarea>
                        </div>

                        <button type="submit"
                                class="min-h-[44px] w-full py-3 md:py-4 px-4 md:px-6 bg-blue-600 text-white text-sm md:text-base rounded-xl md:rounded-2xl font-semibold hover:bg-blue-700 transition-all transform hover:scale-105">
                            <i class="ri-send-plane-line ml-1.5 md:ml-2"></i>
                            إرسال الرسالة
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Section Bottom Border -->
        <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-green-100 via-teal-100 to-green-100"></div>
    </section>

    <!-- FAQ Section -->
    <section class="py-24 bg-gradient-to-br from-emerald-50 via-teal-50 to-cyan-50 relative overflow-hidden">
        <!-- Section Top Border -->
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-100 via-teal-100 to-cyan-100"></div>
        
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-5">
            <div class="absolute inset-0" style="background-image: url('/storage/app-design-assets/bg-pattern1.png'); background-size: 50px 50px; background-repeat: repeat;"></div>
        </div>
        
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center mb-16" data-aos="fade-up">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6 leading-relaxed">
                    <span class="flex items-center justify-center gap-4">
                        <i class="ri-question-line text-5xl text-emerald-600"></i>
                        <span class="bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 bg-clip-text text-transparent">
                            الأسئلة الشائعة
                        </span>
                    </span>
                </h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    إجابات على أكثر الأسئلة شيوعاً لمساعدتك في الحصول على المعلومات التي تحتاجها
                </p>
            </div>
            
            <div class="space-y-6">
                <div class="bg-white rounded-2xl p-6 shadow-xl border border-gray-200" data-aos="fade-up" data-aos-delay="100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3 flex items-center gap-3">
                        <i class="ri-question-answer-line text-emerald-600"></i>
                        كيف يمكنني طلب خدمة من منصة إتقان؟
                    </h3>
                    <p class="text-gray-600 leading-relaxed">يمكنك طلب الخدمة من خلال صفحة خدمات الأعمال، حيث ستجد نموذج طلب شامل لملء تفاصيل مشروعك.</p>
                </div>
                
                <div class="bg-white rounded-2xl p-6 shadow-xl border border-gray-200" data-aos="fade-up" data-aos-delay="200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3 flex items-center gap-3">
                        <i class="ri-service-line text-emerald-600"></i>
                        ما هي أنواع الخدمات التي تقدمونها؟
                    </h3>
                    <p class="text-gray-600 leading-relaxed">نقدم خدمات متنوعة تشمل التصميم، البرمجة، التسويق الرقمي، والاستشارات في مختلف المجالات.</p>
                </div>
                
                <div class="bg-white rounded-2xl p-6 shadow-xl border border-gray-200" data-aos="fade-up" data-aos-delay="300">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3 flex items-center gap-3">
                        <i class="ri-time-line text-emerald-600"></i>
                        كم تستغرق مدة تنفيذ المشروع؟
                    </h3>
                    <p class="text-gray-600 leading-relaxed">تختلف مدة التنفيذ حسب حجم وتعقيد المشروع، عادةً ما تستغرق المشاريع الصغيرة أسبوعين إلى شهر، والمشاريع الكبيرة من 2 إلى 6 أشهر.</p>
                </div>
                
                <div class="bg-white rounded-2xl p-6 shadow-xl border border-gray-200" data-aos="fade-up" data-aos-delay="400">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3 flex items-center gap-3">
                        <i class="ri-customer-service-2-line text-emerald-600"></i>
                        هل تقدمون خدمات الدعم بعد إنجاز المشروع؟
                    </h3>
                    <p class="text-gray-600 leading-relaxed">نعم، نقدم خدمات الدعم والصيانة بعد إنجاز المشروع، ويمكن توقيع عقود صيانة دورية حسب احتياجاتك.</p>
                </div>
            </div>
        </div>
        
        <!-- Section Bottom Border -->
        <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-100 via-teal-100 to-cyan-100"></div>
    </section>

    <!-- CTA Section -->
    <section class="py-24 bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 relative overflow-hidden">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-5">
            <div class="absolute inset-0" style="background-image: url('/storage/app-design-assets/bg-pattern1.png'); background-size: 100px 100px; background-repeat: repeat;"></div>
        </div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
            <h2 class="text-4xl md:text-5xl font-bold text-white mb-6 leading-relaxed" data-aos="fade-up">
                هل أنت مستعد لبدء مشروعك؟
            </h2>
            <p class="text-xl mb-12 text-blue-100 max-w-2xl mx-auto" data-aos="fade-up" data-aos-delay="200">
                دعنا نعمل معاً لتحقيق رؤيتك وأهدافك ونقل أعمالك إلى المستوى التالي
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center" data-aos="fade-up" data-aos-delay="400">
                <a href="{{ route('platform.business-services') }}" 
                   class="bg-green-600 text-white px-8 py-4 rounded-2xl font-semibold hover:bg-green-700 transition-all transform hover:scale-105">
                    <i class="ri-briefcase-line ml-2"></i>
                    اطلب خدمتك الآن
                </a>
                <a href="{{ route('platform.portfolio') }}" 
                   class="bg-sky-600 text-white px-8 py-4 rounded-2xl font-semibold hover:bg-sky-700 transition-all transform hover:scale-105">
                    <i class="ri-eye-line ml-2"></i>
                    شاهد أعمالنا
                </a>
            </div>
        </div>
    </section>
@endsection
