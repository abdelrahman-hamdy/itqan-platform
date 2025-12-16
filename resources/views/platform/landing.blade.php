@extends('components.platform-layout')

@section('title', 'منصة إتقان - الرئيسية')

@section('content')
<!-- Hero Section with Dark Gradient Background -->
<section data-hero class="relative flex items-center justify-center overflow-hidden bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 py-20" style="min-height: 100vh; padding-top: 7rem;">
    <!-- Background Pattern Layer -->
    <div class="absolute inset-0 opacity-20">
        <div class="absolute inset-0" style="background-image: url('/assets/images/bg-pattern1.png'); background-size: 100px 100px; background-repeat: repeat;"></div>
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
    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center pt-16">
        <!-- Animated Label -->
        <div class="mb-2 md:mb-4" data-aos="fade-down" data-aos-delay="100">
            <span class="inline-flex items-center gap-2 md:gap-3 px-4 md:px-6 py-2 md:py-3 bg-white/10 text-blue-100 rounded-full text-xs md:text-sm font-semibold border border-blue-300/30 backdrop-blur-sm animate-bounce">
                <span class="text-base md:text-lg">🚀</span>
                منصة رائدة في التكنولوجيا والتعليم
            </span>
        </div>
        
        <!-- Main Heading with Enhanced Styling -->
        <h1 class="py-8 leading-relaxed" data-aos="fade-up" data-aos-delay="200">
            <span class="block text-4xl md:text-6xl lg:text-7xl font-bold bg-gradient-to-r from-white via-blue-100 to-indigo-100 bg-clip-text text-transparent py-4">
             منصة إتقان للأعمال
            </span>
        </h1>
        
        <!-- Enhanced Subtitle -->
        <p class="text-lg md:text-xl lg:text-2xl mb-16 text-blue-100 max-w-5xl mx-auto leading-relaxed" data-aos="fade-up" data-aos-delay="400">
            نقدم حلولاً متكاملة تجمع بين 
            <span class="text-green-300 font-bold">خدمات الأعمال المتطورة</span> 
            و 
            <span class="text-blue-300 font-bold">الأكاديمية التعليمية الرائدة</span>
            <br class="hidden md:block">
            <span class="text-blue-200 text-base md:text-lg mt-2 block">لتحقيق أهدافك وطموحاتك في عالم التكنولوجيا والتعليم</span>
        </p>
        
        <!-- Modern Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 md:gap-6 justify-center items-center mb-16" data-aos="fade-up" data-aos-delay="800">
            <!-- Business Solutions Button -->
            <a href="{{ route('platform.business-services') }}"
               class="group relative w-full sm:w-auto px-8 md:px-10 py-4 md:py-5 bg-gradient-to-r from-green-600 to-teal-600 text-white rounded-2xl font-bold text-base md:text-lg transition-all duration-300 transform hover:scale-105 hover:shadow-2xl overflow-hidden text-center">
                <div class="absolute inset-0 bg-gradient-to-r from-green-500 to-teal-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="absolute -inset-1 bg-gradient-to-r from-green-500 to-teal-500 rounded-2xl blur opacity-30 group-hover:opacity-60 transition-opacity duration-300"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white to-transparent opacity-0 group-hover:opacity-20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                <span class="relative z-10 flex items-center justify-center gap-3">
                    <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                خدمات الأعمال
                </span>
            </a>

            <!-- Education Button -->
            <a href="http://itqan-academy.{{ config('app.domain') }}"
               class="group relative w-full sm:w-auto px-8 md:px-10 py-4 md:py-5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-2xl font-bold text-base md:text-lg transition-all duration-300 transform hover:scale-105 hover:shadow-2xl overflow-hidden text-center">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-500 to-indigo-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="absolute -inset-1 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-2xl blur opacity-30 group-hover:opacity-60 transition-opacity duration-300"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white to-transparent opacity-0 group-hover:opacity-20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                <span class="relative z-10 flex items-center justify-center gap-3">
                    <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    الأكاديمية التعليمية
                </span>
            </a>
        </div>
        
        <!-- Customer Reviews & Testimonials -->
        <div class="flex flex-wrap justify-center gap-6 text-blue-100" data-aos="fade-up" data-aos-delay="1000">
            <!-- Review 1 -->
            <div class="bg-white/10 backdrop-blur-md px-6 py-6 rounded-2xl border border-white/30 max-w-sm shadow-2xl" style="box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(255, 255, 255, 0.1);">
                <!-- Rating Section -->
                <div class="flex items-center justify-between mb-4">
                    <div class="flex">
                        <svg class="w-4 h-4 text-yellow-400 animate-pulse" fill="currentColor" viewBox="0 0 20 20" style="animation-delay: 0.1s;">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        <svg class="w-4 h-4 text-yellow-400 animate-pulse" fill="currentColor" viewBox="0 0 20 20" style="animation-delay: 0.2s;">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        <svg class="w-4 h-4 text-yellow-400 animate-pulse" fill="currentColor" viewBox="0 0 20 20" style="animation-delay: 0.3s;">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        <svg class="w-4 h-4 text-yellow-400 animate-pulse" fill="currentColor" viewBox="0 0 20 20" style="animation-delay: 0.4s;">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        <svg class="w-4 h-4 text-yellow-400 animate-pulse" fill="currentColor" viewBox="0 0 20 20" style="animation-delay: 0.5s;">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                    </div>
                    <span class="text-xs text-blue-200 font-medium">5.0</span>
                </div>
                
                <!-- Review Text -->
                <p class="text-sm text-white mb-5 leading-relaxed">"خدمة ممتازة وجودة عالية جداً في التنفيذ والدعم الفني المستمر، فريق العمل محترف ومتفهم لاحتياجات العملاء"</p>
                
                <!-- User Info -->
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full overflow-hidden">
                        <img src="/assets/images/user-avatar2.png" alt="أحمد محمد" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <p class="text-sm font-medium text-white">أحمد محمد</p>
                        <p class="text-xs text-blue-200">مدير تقني</p>
                    </div>
                </div>
            </div>

            <!-- Review 2 -->
            <div class="bg-white/10 backdrop-blur-md px-6 py-6 rounded-2xl border border-white/30 max-w-sm shadow-2xl" style="animation-delay: 0.5s; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(255, 255, 255, 0.1);">
                <!-- Rating Section -->
                <div class="flex items-center justify-between mb-4">
                    <div class="flex">
                        <svg class="w-4 h-4 text-yellow-400 animate-pulse" fill="currentColor" viewBox="0 0 20 20" style="animation-delay: 0.6s;">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        <svg class="w-4 h-4 text-yellow-400 animate-pulse" fill="currentColor" viewBox="0 0 20 20" style="animation-delay: 0.7s;">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        <svg class="w-4 h-4 text-yellow-400 animate-pulse" fill="currentColor" viewBox="0 0 20 20" style="animation-delay: 0.8s;">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        <svg class="w-4 h-4 text-yellow-400 animate-pulse" fill="currentColor" viewBox="0 0 20 20" style="animation-delay: 0.9s;">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        <svg class="w-4 h-4 text-yellow-400 animate-pulse" fill="currentColor" viewBox="0 0 20 20" style="animation-delay: 1.0s;">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                    </div>
                    <span class="text-xs text-blue-200 font-medium">5.0</span>
                </div>
                
                <!-- Review Text -->
                <p class="text-sm text-white mb-5 leading-relaxed">"دعم فني ممتاز وسرعة في التنفيذ مع جودة عالية في الخدمة، تجربة رائعة مع فريق متخصص ومتفهم"</p>
                
                <!-- User Info -->
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full overflow-hidden">
                        <img src="/assets/images/user-avatar1.png" alt="فاطمة علي" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <p class="text-sm font-medium text-white">فاطمة علي</p>
                        <p class="text-xs text-blue-200">مديرة تسويق</p>
                    </div>
                </div>
            </div>

            <!-- Review 3 -->
            <div class="bg-white/10 backdrop-blur-md px-6 py-6 rounded-2xl border border-white/30 max-w-sm shadow-2xl" style="animation-delay: 1s; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(255, 255, 255, 0.1);">
                <!-- Rating Section -->
                <div class="flex items-center justify-between mb-4">
                    <div class="flex">
                        <svg class="w-4 h-4 text-yellow-400 animate-pulse" fill="currentColor" viewBox="0 0 20 20" style="animation-delay: 1.1s;">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        <svg class="w-4 h-4 text-yellow-400 animate-pulse" fill="currentColor" viewBox="0 0 20 20" style="animation-delay: 1.2s;">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        <svg class="w-4 h-4 text-yellow-400 animate-pulse" fill="currentColor" viewBox="0 0 20 20" style="animation-delay: 1.3s;">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        <svg class="w-4 h-4 text-yellow-400 animate-pulse" fill="currentColor" viewBox="0 0 20 20" style="animation-delay: 1.4s;">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        <svg class="w-4 h-4 text-yellow-400 animate-pulse" fill="currentColor" viewBox="0 0 20 20" style="animation-delay: 1.5s;">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                    </div>
                    <span class="text-xs text-blue-200 font-medium">5.0</span>
                </div>
                
                <!-- Review Text -->
                <p class="text-sm text-white mb-5 leading-relaxed">"تجربة تعليمية رائعة ومفيدة جداً، المنصة سهلت علي التعلم وزادت من فهمي للمواد الدراسية بشكل كبير"</p>
                
                <!-- User Info -->
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full overflow-hidden">
                        <img src="/assets/images/user-avatar3.png" alt="عمر حسن" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <p class="text-sm font-medium text-white">عمر حسن</p>
                        <p class="text-xs text-blue-200">معلم</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Academic & Educational Section -->
<section class="py-24 relative overflow-hidden" style="background: linear-gradient(135deg, #ffffff 30%, #00ff511a 50%, #ffffff 80%);">
    <!-- Section Top Border -->
    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-100 via-indigo-100 to-blue-100"></div>
    
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-5">
        <div class="absolute inset-0" style="background-image: url('/assets/images/bg-pattern1.png'); background-size: 50px 50px; background-repeat: repeat;"></div>
</div>

    <!-- Subtle Background Elements -->
    <div class="absolute top-10 left-10 w-32 h-32 bg-blue-50 rounded-full blur-2xl opacity-30"></div>
    <div class="absolute bottom-10 right-10 w-40 h-40 bg-indigo-50 rounded-full blur-2xl opacity-25"></div>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center mb-12 md:mb-16" data-aos="fade-up">
            <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-black text-gray-900 mb-4 md:mb-6 leading-relaxed">
                <span class="flex flex-col sm:flex-row items-center justify-center gap-2 sm:gap-4">
                    <svg class="w-8 h-8 sm:w-10 sm:h-10 md:w-12 md:h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    <span class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                        الأكاديمية التعليمية
                    </span>
                </span>
            </h2>
            <p class="text-base md:text-xl text-gray-600 max-w-3xl mx-auto px-4">
                منصة تعليمية متكاملة تقدم دورات في القرآن الكريم والعلوم الإسلامية
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            <!-- Left Column - Features Grid -->
            <div class="grid grid-cols-2 gap-6" data-aos="fade-right">
                <!-- Feature 1 - Quran -->
                <div class="relative bg-gradient-to-br from-indigo-600 via-blue-600 to-indigo-700 rounded-2xl p-6 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 overflow-hidden">
                    <!-- Background Pattern Layer -->
                    <div class="absolute inset-0 opacity-30 bg-pattern"></div>
                    <div class="relative z-10">
                        <div class="w-14 h-14 bg-blue-500/20 rounded-xl flex items-center justify-center mb-4">
                            <i class="ri-book-open-line text-3xl text-blue-400"></i>
                        </div>
                        <h4 class="font-bold text-white mb-2">تحفيظ القرآن</h4>
                        <p class="text-sm text-blue-100 leading-relaxed">حلقات تفاعلية مع معلمين متخصصين<br>في بيئة محفزة ومريحة للتعلم</p>
                    </div>
                </div>

                <!-- Feature 2 - Academic Subjects -->
                <div class="relative bg-gradient-to-br from-indigo-600 via-blue-600 to-indigo-700 rounded-2xl p-6 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 overflow-hidden">
                    <!-- Background Pattern Layer -->
                    <div class="absolute inset-0 opacity-30 bg-pattern"></div>
                    <div class="relative z-10">
                        <div class="w-14 h-14 bg-indigo-500/20 rounded-xl flex items-center justify-center mb-4">
                            <i class="ri-checkbox-circle-line text-3xl text-indigo-400"></i>
                        </div>
                        <h4 class="font-bold text-white mb-2">المواد الأكاديمية</h4>
                        <p class="text-sm text-indigo-100 leading-relaxed">جميع المراحل الدراسية مع مناهج<br>محدثة وطرق تدريس متطورة</p>
                    </div>
                </div>

                <!-- Feature 3 - Live Sessions -->
                <div class="relative bg-gradient-to-br from-indigo-600 via-blue-600 to-indigo-700 rounded-2xl p-6 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 overflow-hidden">
                    <!-- Background Pattern Layer -->
                    <div class="absolute inset-0 opacity-30 bg-pattern"></div>
                    <div class="relative z-10">
                        <div class="w-14 h-14 bg-cyan-500/20 rounded-xl flex items-center justify-center mb-4">
                            <i class="ri-video-line text-3xl text-cyan-400"></i>
                        </div>
                        <h4 class="font-bold text-white mb-2">جلسات مباشرة</h4>
                        <p class="text-sm text-cyan-100 leading-relaxed">تفاعل مباشر مع المعلمين في<br>جلسات حية تفاعلية ومحفزة</p>
                    </div>
                </div>

                <!-- Feature 4 - Progress Tracking -->
                <div class="relative bg-gradient-to-br from-indigo-600 via-blue-600 to-indigo-700 rounded-2xl p-6 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 overflow-hidden">
                    <!-- Background Pattern Layer -->
                    <div class="absolute inset-0 opacity-30 bg-pattern"></div>
                    <div class="relative z-10">
                        <div class="w-14 h-14 bg-purple-500/20 rounded-xl flex items-center justify-center mb-4">
                            <i class="ri-bar-chart-line text-3xl text-purple-400"></i>
                        </div>
                        <h4 class="font-bold text-white mb-2">متابعة التقدم</h4>
                        <p class="text-sm text-purple-100 leading-relaxed">تقارير مفصلة عن الأداء مع<br>نصائح للتطوير والتحسين</p>
                    </div>
                </div>
            </div>

            <!-- Right Column - Text Content and Button -->
            <div class="space-y-8" data-aos="fade-left">
                <div class="space-y-4 md:space-y-6 text-center lg:text-right">
                    <h3 class="text-xl sm:text-2xl md:text-3xl lg:text-4xl font-bold text-gray-900 section-heading">
                        تعلم مع أفضل المعلمين المتخصصين
                    </h3>
                    <p class="text-base md:text-lg text-gray-600 leading-relaxed">
                        انضم إلى آلاف الطلاب الذين يثقون في منصة إتقان لتطوير مهاراتهم التعليمية والدينية. نقدم بيئة تعليمية تفاعلية ومحفزة مع معلمين مؤهلين وطرق تدريس حديثة.
                    </p>
                </div>
                
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="text-gray-700 font-medium">معلمون متخصصون ومؤهلون</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="text-gray-700 font-medium">دورات تفاعلية ومحفزة</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="text-gray-700 font-medium">متابعة مستمرة وتقييمات</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="text-gray-700 font-medium">شهادات معتمدة ومعترف بها</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="text-gray-700 font-medium">دعم فني متوفر على مدار الساعة</span>
                    </div>
                </div>
                
                <div class="pt-4">
                    <a href="http://itqan-academy.{{ config('app.domain') }}" 
                       class="group relative inline-flex items-center gap-3 px-8 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-2xl font-bold text-lg transition-all duration-300 transform hover:scale-105 hover:shadow-2xl overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-500 to-indigo-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <div class="absolute -inset-1 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-2xl blur opacity-30 group-hover:opacity-60 transition-opacity duration-300"></div>
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white to-transparent opacity-0 group-hover:opacity-20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                        <span class="relative z-10 flex items-center gap-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                            ابدأ رحلتك التعليمية
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Section Bottom Border -->
    <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-100 via-indigo-100 to-blue-100"></div>
</section>

<!-- Business Solutions Section -->
<section class="py-24 relative overflow-hidden" style="background: linear-gradient(135deg, #ffffff 30%, #64d2ff33 50%, #ffffff 80%);">
    <!-- Section Top Border -->
    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-green-100 via-teal-100 to-green-100"></div>
    
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-5">
        <div class="absolute inset-0" style="background-image: url('/assets/images/bg-pattern1.png'); background-size: 50px 50px; background-repeat: repeat;"></div>
</div>

    <!-- Subtle Background Elements -->
    <div class="absolute top-10 right-10 w-32 h-32 bg-green-50 rounded-full blur-2xl opacity-30"></div>
    <div class="absolute bottom-10 left-10 w-40 h-40 bg-teal-50 rounded-full blur-2xl opacity-25"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center mb-12 md:mb-16" data-aos="fade-up">
            <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-black text-gray-900 mb-4 md:mb-6 leading-relaxed">
                <span class="flex flex-col sm:flex-row items-center justify-center gap-2 sm:gap-4">
                    <svg class="w-8 h-8 sm:w-10 sm:h-10 md:w-12 md:h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    <span class="bg-gradient-to-r from-green-600 to-teal-600 bg-clip-text text-transparent">
                خدمات الأعمال
                    </span>
                </span>
            </h2>
            <p class="text-base md:text-xl text-gray-600 max-w-3xl mx-auto px-4">
                نقدم مجموعة متنوعة من الخدمات الاحترافية لمساعدة شركتك على النمو والتطور
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            <!-- Left Column - Text Content and Button -->
            <div class="space-y-8" data-aos="fade-right">
                <div class="space-y-4 md:space-y-6 text-center lg:text-right">
                    <h3 class="text-xl sm:text-2xl md:text-3xl lg:text-4xl font-bold text-gray-900 section-heading">
                        حلول تقنية متكاملة لأعمالك
                    </h3>
                    <p class="text-base md:text-lg text-gray-600 leading-relaxed">
                        نقدم خدمات تقنية متطورة وحلول إبداعية لمساعدة شركتك على النمو والتميز في السوق. فريقنا المتخصص يضمن لك الحصول على أفضل النتائج بأعلى معايير الجودة.
                    </p>
                </div>
                
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="text-gray-700 font-medium">فريق متخصص ومؤهل</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="text-gray-700 font-medium">حلول مخصصة لاحتياجاتك</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="text-gray-700 font-medium">دعم فني مستمر</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="text-gray-700 font-medium">أسعار تنافسية ومناسبة</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="text-gray-700 font-medium">تسليم في الوقت المحدد</span>
                    </div>
                </div>

                <div class="pt-4 flex flex-col sm:flex-row gap-4">
                    <a href="http://itqan-business.{{ config('app.domain') }}" 
                       class="group relative inline-flex items-center justify-center gap-3 px-8 py-4 bg-gradient-to-r from-green-600 to-teal-600 text-white rounded-2xl font-bold text-lg transition-all duration-300 transform hover:scale-105 hover:shadow-2xl overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-r from-green-500 to-teal-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <div class="absolute -inset-1 bg-gradient-to-r from-green-500 to-teal-500 rounded-2xl blur opacity-30 group-hover:opacity-60 transition-opacity duration-300"></div>
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white to-transparent opacity-0 group-hover:opacity-20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                        <span class="relative z-10 flex items-center gap-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            ابدأ مشروعك الآن
                        </span>
                    </a>
                </div>
            </div>

            <!-- Right Column - Features Grid -->
            <div class="grid grid-cols-2 gap-6" data-aos="fade-left">
                <!-- Feature 1 - Web Development -->
                <div class="relative bg-gradient-to-br from-teal-600 via-cyan-600 to-blue-600 rounded-2xl p-6 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 overflow-hidden">
                    <!-- Background Pattern Layer -->
                    <div class="absolute inset-0 opacity-30 bg-pattern"></div>
                    <div class="relative z-10">
                        <div class="w-14 h-14 bg-emerald-500/20 rounded-xl flex items-center justify-center mb-4">
                            <i class="ri-code-line text-3xl text-emerald-400"></i>
                        </div>
                        <h4 class="font-bold text-white mb-2">تطوير المواقع</h4>
                        <p class="text-sm text-emerald-100 leading-relaxed">مواقع احترافية متجاوبة<br>مع أحدث التقنيات</p>
                    </div>
                </div>

                <!-- Feature 2 - Mobile Apps -->
                <div class="relative bg-gradient-to-br from-teal-600 via-cyan-600 to-blue-600 rounded-2xl p-6 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 overflow-hidden">
                    <!-- Background Pattern Layer -->
                    <div class="absolute inset-0 opacity-30 bg-pattern"></div>
                    <div class="relative z-10">
                        <div class="w-14 h-14 bg-teal-500/20 rounded-xl flex items-center justify-center mb-4">
                            <i class="ri-smartphone-line text-3xl text-teal-400"></i>
                        </div>
                        <h4 class="font-bold text-white mb-2">تطبيقات الجوال</h4>
                        <p class="text-sm text-teal-100 leading-relaxed">تطبيقات ذكية متطورة<br>لجميع المنصات</p>
                    </div>
                </div>

                <!-- Feature 3 - Digital Marketing -->
                <div class="relative bg-gradient-to-br from-teal-600 via-cyan-600 to-blue-600 rounded-2xl p-6 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 overflow-hidden">
                    <!-- Background Pattern Layer -->
                    <div class="absolute inset-0 opacity-30 bg-pattern"></div>
                    <div class="relative z-10">
                        <div class="w-14 h-14 bg-green-500/20 rounded-xl flex items-center justify-center mb-4">
                            <i class="ri-line-chart-line text-3xl text-green-400"></i>
                        </div>
                        <h4 class="font-bold text-white mb-2">التسويق الرقمي</h4>
                        <p class="text-sm text-green-100 leading-relaxed">استراتيجيات تسويقية متقدمة<br>لزيادة المبيعات</p>
                    </div>
                </div>

                <!-- Feature 4 - Business Consulting -->
                <div class="relative bg-gradient-to-br from-teal-600 via-cyan-600 to-blue-600 rounded-2xl p-6 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 overflow-hidden">
                    <!-- Background Pattern Layer -->
                    <div class="absolute inset-0 opacity-30 bg-pattern"></div>
                    <div class="relative z-10">
                        <div class="w-14 h-14 bg-cyan-500/20 rounded-xl flex items-center justify-center mb-4">
                            <i class="ri-customer-service-line text-3xl text-cyan-400"></i>
                        </div>
                        <h4 class="font-bold text-white mb-2">الاستشارات</h4>
                        <p class="text-sm text-cyan-100 leading-relaxed">استشارات متخصصة لتطوير<br>وتنمية الأعمال</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
    
    <!-- Section Bottom Border -->
    <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-green-100 via-teal-100 to-green-100"></div>
</section>

<!-- Available Services Section -->
<section class="py-24 bg-white relative overflow-hidden">
    <!-- Section Top Border -->
    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-gray-200 via-slate-200 to-gray-200"></div>
    
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-5">
        <div class="absolute inset-0" style="background-image: url('/assets/images/bg-pattern1.png'); background-size: 50px 50px; background-repeat: repeat;"></div>
    </div>
    
    <!-- Subtle Background Elements -->
    <div class="absolute top-10 right-10 w-32 h-32 bg-gray-100 rounded-full blur-2xl opacity-30"></div>
    <div class="absolute bottom-10 left-10 w-40 h-40 bg-slate-100 rounded-full blur-2xl opacity-25"></div>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center mb-12 md:mb-16" data-aos="fade-up">
            <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold mb-4 md:mb-6 leading-relaxed">
                <span class="bg-gradient-to-r from-gray-700 to-slate-700 bg-clip-text text-transparent">
                    خدماتنا المتاحة
                </span>
            </h2>
            <p class="text-base md:text-xl text-gray-600 max-w-3xl mx-auto px-4">
                مجموعة شاملة من الخدمات الاحترافية المصممة خصيصاً لتنمية أعمالك وتحقيق أهدافك
            </p>
        </div>

        <!-- Services Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12" id="services-grid">
            @foreach($services as $index => $service)
                <div class="service-card" data-aos="fade-up" data-aos-delay="{{ ($index + 1) * 100 }}">
                    <x-service-card :service="$service" />
                </div>
            @endforeach
        </div>

        <!-- Show More Button -->
        <div class="text-center" data-aos="fade-up" data-aos-delay="800">
            <button id="show-more-btn" 
                    class="group relative inline-flex items-center gap-3 px-8 py-4 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-2xl font-bold text-lg transition-all duration-300 transform hover:scale-105 hover:shadow-2xl overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-green-500 to-emerald-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="absolute -inset-1 bg-gradient-to-r from-green-500 to-emerald-500 rounded-2xl blur opacity-30 group-hover:opacity-60 transition-opacity duration-300"></div>
                <span class="relative z-10 flex items-center gap-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                    عرض المزيد من الخدمات
                </span>
            </button>
        </div>
    </div>
    
    <!-- Section Bottom Border -->
    <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-gray-200 via-slate-200 to-gray-200"></div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const showMoreBtn = document.getElementById('show-more-btn');
    const servicesGrid = document.getElementById('services-grid');
    
    showMoreBtn.addEventListener('click', function() {
        // Redirect to business services page
        window.location.href = '{{ route("platform.business-services") }}';
    });
});
</script>

<!-- Stats Section with Animation -->
<section class="py-24 relative overflow-hidden bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900">
    <div class="absolute inset-0 opacity-20">
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 100 100\"><defs><pattern id=\"stars\" width=\"20\" height=\"20\" patternUnits=\"userSpaceOnUse\"><circle cx=\"10\" cy=\"10\" r=\"1\" fill=\"%23ffffff\" opacity=\"0.3\"/></pattern></defs><rect width=\"100\" height=\"100\" fill=\"url(%23stars)\"/></svg>'); background-size: 30px 30px;"></div>
</div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 text-center">
            <div class="group" data-aos="fade-up" data-aos-delay="100">
                <div class="text-5xl font-bold bg-gradient-to-r from-green-400 to-emerald-500 bg-clip-text text-transparent mb-2 group-hover:scale-110 transition-transform duration-300 counter-number" data-count="50">
                    <span class="counter-display">0</span>+
                </div>
                <div class="text-gray-300 text-lg">مشروع مكتمل</div>
            </div>
            <div class="group" data-aos="fade-up" data-aos-delay="200">
                <div class="text-5xl font-bold bg-gradient-to-r from-blue-400 to-cyan-500 bg-clip-text text-transparent mb-2 group-hover:scale-110 transition-transform duration-300 counter-number" data-count="100">
                    <span class="counter-display">0</span>+
                </div>
                <div class="text-gray-300 text-lg">عميل راضٍ</div>
            </div>
            <div class="group" data-aos="fade-up" data-aos-delay="300">
                <div class="text-5xl font-bold bg-gradient-to-r from-purple-400 to-pink-500 bg-clip-text text-transparent mb-2 group-hover:scale-110 transition-transform duration-300 counter-number" data-count="5">
                    <span class="counter-display">0</span>+
                </div>
                <div class="text-gray-300 text-lg">سنوات خبرة</div>
            </div>
            <div class="group" data-aos="fade-up" data-aos-delay="400">
                <div class="text-5xl font-bold bg-gradient-to-r from-yellow-400 to-orange-500 bg-clip-text text-transparent mb-2 group-hover:scale-110 transition-transform duration-300">
                    24/7
                </div>
                <div class="text-gray-300 text-lg">دعم متواصل</div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-24 bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-50 relative overflow-hidden">
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-gray-900 mb-4 md:mb-6" data-aos="fade-up">
            هل أنت مستعد لبدء مشروعك؟
        </h2>
        <p class="text-base md:text-xl mb-8 md:mb-12 text-gray-600 max-w-2xl mx-auto px-4" data-aos="fade-up" data-aos-delay="200">
            دعنا نعمل معاً لتحقيق رؤيتك ونقل أعمالك إلى المستوى التالي
        </p>
        <a href="{{ route('platform.business-services') }}" 
           class="group relative inline-block px-12 py-6 bg-gradient-to-r from-green-600 via-emerald-600 to-teal-600 text-white rounded-2xl font-bold text-xl hover:from-green-700 hover:via-emerald-700 hover:to-teal-700 transition-all duration-300 transform hover:scale-105 hover:shadow-2xl" 
           data-aos="fade-up" data-aos-delay="400">
            <span class="relative z-10 flex items-center gap-3">
                <span class="text-2xl">🚀</span>
                أنجز مشروعك الآن
            </span>
            <div class="absolute inset-0 bg-gradient-to-r from-green-500 to-emerald-500 rounded-2xl opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
            <div class="absolute -inset-1 bg-gradient-to-r from-green-500 to-emerald-500 rounded-2xl blur opacity-30 group-hover:opacity-50 transition-opacity duration-300"></div>
        </a>
    </div>
</section>

<!-- Custom CSS for Animations -->
<style>
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(180deg); }
    }
    
    /* Counter Animation */
    .counter {
        transition: all 0.3s ease;
    }
    
    .animate-float {
        animation: float 6s ease-in-out infinite;
    }
    
    .hover\:shadow-3xl:hover {
        box-shadow: 0 35px 60px -12px rgba(0, 0, 0, 0.25);
    }
    
    /* Smooth scrolling */
    html {
        scroll-behavior: smooth;
    }
    
    /* Custom gradient text */
    .gradient-text {
        background: linear-gradient(45deg, #3b82f6, #8b5cf6, #ec4899);
        background-size: 200% 200%;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        animation: gradient-shift 3s ease infinite;
    }
    
    @keyframes gradient-shift {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
</style>

<!-- JavaScript for Enhanced Animations -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Simple CSS-based animations (no GSAP dependency)
        // All animations are now handled by AOS (Animate On Scroll) library
        
        // SIMPLE COUNTER ANIMATION - NO GSAP DEPENDENCY
        let countersStarted = false;
        
        function animateCounter(element, target) {
            let current = 0;
            const increment = target / 60; // 60 steps for smooth animation
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    element.textContent = target;
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(current);
                }
            }, 33); // ~30fps
        }
        
        function startCounters() {
            if (countersStarted) return;
            countersStarted = true;
            
            const counters = document.querySelectorAll('.counter-number');
            counters.forEach((counter, index) => {
                const target = parseInt(counter.getAttribute('data-count'));
                const display = counter.querySelector('.counter-display');
                
                if (!isNaN(target) && display) {
                    display.textContent = '0';
                    setTimeout(() => {
                        animateCounter(display, target);
                    }, index * 200);
                }
            });
        }
        
        // Intersection Observer for scroll trigger
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !countersStarted) {
                    startCounters();
                }
            });
        }, { threshold: 0.5 });
        
        // Start observing the stats section
        const statsSection = document.querySelector('.counter-number')?.closest('section');
        if (statsSection) {
            observer.observe(statsSection);
        }
        
        // Fallback - immediate trigger if section is visible
        setTimeout(() => {
            if (!countersStarted) {
                const statsSection = document.querySelector('.counter-number')?.closest('section');
                if (statsSection) {
                    const rect = statsSection.getBoundingClientRect();
                    if (rect.top < window.innerHeight && rect.bottom > 0) {
                        startCounters();
                    }
                }
            }
        }, 1000);
    });
</script>
@endsection