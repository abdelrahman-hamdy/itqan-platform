@extends('components.platform-layout')

@section('title', 'منصة إتقان - الرئيسية')

@section('content')
<!-- Hero Section with White Background and Soft Colored Spots -->
<section class="relative h-screen flex items-center justify-center overflow-hidden bg-white" style="height: calc(100vh - 7rem);">
    <!-- Background Pattern Layer -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: url('/storage/app-design-assets/bg-pattern1.png'); background-size: 100px 100px; background-repeat: repeat;"></div>
    </div>
    
    <!-- Enhanced Background Elements -->
    <div class="absolute top-0 right-0 w-[40rem] h-[40rem] bg-gradient-to-br from-blue-100 via-blue-200 to-indigo-200 rounded-full blur-3xl opacity-60 animate-pulse" style="animation-duration: 6s;"></div>
    <div class="absolute bottom-0 left-0 w-[45rem] h-[45rem] bg-gradient-to-tr from-emerald-100 via-green-200 to-teal-200 rounded-full blur-3xl opacity-55 animate-bounce" style="animation-duration: 8s;"></div>
    <div class="absolute top-1/2 right-0 transform translate-x-1/2 -translate-y-1/2 w-[35rem] h-[35rem] bg-gradient-to-l from-cyan-100 via-teal-200 to-blue-200 rounded-full blur-3xl opacity-50 animate-ping" style="animation-duration: 10s;"></div>
    <div class="absolute top-0 left-0 w-[38rem] h-[38rem] bg-gradient-to-br from-purple-100 via-violet-200 to-indigo-200 rounded-full blur-3xl opacity-45 animate-pulse" style="animation-duration: 7s; animation-delay: 2s;"></div>
    <div class="absolute bottom-0 right-0 w-[42rem] h-[42rem] bg-gradient-to-tl from-indigo-100 via-blue-200 to-purple-200 rounded-full blur-3xl opacity-50 animate-bounce" style="animation-duration: 9s; animation-delay: 3s;"></div>
    
    <!-- Additional subtle elements -->
    <div class="absolute top-1/4 left-1/4 w-[20rem] h-[20rem] bg-gradient-to-r from-rose-100 to-pink-200 rounded-full blur-2xl opacity-30 animate-pulse" style="animation-duration: 12s; animation-delay: 1s;"></div>
    <div class="absolute bottom-1/4 right-1/4 w-[25rem] h-[25rem] bg-gradient-to-r from-amber-100 to-yellow-200 rounded-full blur-2xl opacity-25 animate-bounce" style="animation-duration: 11s; animation-delay: 4s;"></div>
    
    <!-- Content -->
    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <!-- Animated Label -->
        <div class="mb-4" data-aos="fade-down" data-aos-delay="100">
            <span class="inline-flex items-center gap-3 px-6 py-3 bg-transparent text-gray-700 rounded-full text-sm font-semibold border border-gray-300 backdrop-blur-sm animate-bounce">
                <span class="text-lg">🚀</span>
                منصة رائدة في التكنولوجيا والتعليم
            </span>
        </div>
        
        <!-- Main Heading with Enhanced Styling -->
        <h1 class="py-8 leading-relaxed" data-aos="fade-up" data-aos-delay="200">
            <span class="block text-4xl md:text-6xl lg:text-7xl font-bold bg-gradient-to-r from-gray-900 via-gray-800 to-gray-700 bg-clip-text text-transparent py-4">
             منصة إتقان للأعمال
            </span>
        </h1>
        
        <!-- Enhanced Subtitle -->
        <p class="text-lg md:text-xl lg:text-2xl mb-16 text-gray-600 max-w-5xl mx-auto leading-relaxed" data-aos="fade-up" data-aos-delay="400">
            نقدم حلولاً متكاملة تجمع بين 
            <span class="text-green-600 font-bold">خدمات الأعمال المتطورة</span> 
            و 
            <span class="text-blue-600 font-bold">الأكاديمية التعليمية الرائدة</span>
            <br class="hidden md:block">
            <span class="text-gray-500 text-base md:text-lg mt-2 block">لتحقيق أهدافك وطموحاتك في عالم التكنولوجيا والتعليم</span>
        </p>
        
        <!-- Modern Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-6 justify-center mb-16" data-aos="fade-up" data-aos-delay="800">
            <!-- Business Solutions Button -->
            <a href="{{ route('platform.business-services') }}" 
               class="group relative px-10 py-5 bg-gradient-to-r from-green-600 to-teal-600 text-white rounded-2xl font-bold text-lg transition-all duration-300 transform hover:scale-105 hover:shadow-2xl overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-green-500 to-teal-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="absolute -inset-1 bg-gradient-to-r from-green-500 to-teal-500 rounded-2xl blur opacity-30 group-hover:opacity-60 transition-opacity duration-300"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white to-transparent opacity-0 group-hover:opacity-20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                <span class="relative z-10 flex items-center gap-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    خدمات الأعمال
                </span>
            </a>
            
            <!-- Education Button -->
            <a href="http://itqan-academy.{{ config('app.domain') }}" 
               class="group relative px-10 py-5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-2xl font-bold text-lg transition-all duration-300 transform hover:scale-105 hover:shadow-2xl overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-500 to-indigo-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="absolute -inset-1 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-2xl blur opacity-30 group-hover:opacity-60 transition-opacity duration-300"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white to-transparent opacity-0 group-hover:opacity-20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                <span class="relative z-10 flex items-center gap-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    الأكاديمية التعليمية
                </span>
            </a>
        </div>
        
        <!-- Customer Reviews & Testimonials -->
        <div class="flex flex-wrap justify-center gap-6 text-gray-600" data-aos="fade-up" data-aos-delay="1000">
            <!-- Review 1 -->
            <div class="bg-white/20 backdrop-blur-md px-6 py-5 rounded-2xl border border-white/30 max-w-xs shadow-2xl" style="box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(255, 255, 255, 0.1);">
                <div class="flex items-center gap-2 mb-3">
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
                    <span class="text-xs text-gray-500">5.0</span>
                </div>
                <p class="text-sm text-gray-700 mb-4">"خدمة ممتازة وجودة عالية"</p>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-blue-400 to-blue-500 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                        أ.م
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-800">أحمد محمد</p>
                        <p class="text-xs text-gray-500">مدير تقني</p>
                    </div>
                </div>
            </div>

            <!-- Review 2 -->
            <div class="bg-white/20 backdrop-blur-md px-6 py-5 rounded-2xl border border-white/30 max-w-xs shadow-2xl" style="animation-delay: 0.5s; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(255, 255, 255, 0.1);">
                <div class="flex items-center gap-2 mb-3">
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
                    <span class="text-xs text-gray-500">5.0</span>
                </div>
                <p class="text-sm text-gray-700 mb-4">"دعم فني ممتاز وسرعة في التنفيذ"</p>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-green-400 to-emerald-500 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                        ف.ع
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-800">فاطمة علي</p>
                        <p class="text-xs text-gray-500">مديرة تسويق</p>
                    </div>
                </div>
            </div>

            <!-- Review 3 -->
            <div class="bg-white/20 backdrop-blur-md px-6 py-5 rounded-2xl border border-white/30 max-w-xs shadow-2xl" style="animation-delay: 1s; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(255, 255, 255, 0.1);">
                <div class="flex items-center gap-2 mb-3">
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
                    <span class="text-xs text-gray-500">5.0</span>
                </div>
                <p class="text-sm text-gray-700 mb-4">"تجربة تعليمية رائعة ومفيدة"</p>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-purple-400 to-indigo-500 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                        ع.ح
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-800">عمر حسن</p>
                        <p class="text-xs text-gray-500">معلم</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Academic & Educational Section -->
<section class="py-24 bg-white relative overflow-hidden">
    <!-- Section Top Border -->
    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-100 via-indigo-100 to-blue-100"></div>
    
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-5">
        <div class="absolute inset-0" style="background-image: url('/storage/app-design-assets/bg-pattern1.png'); background-size: 50px 50px; background-repeat: repeat;"></div>
    </div>
    
    <!-- Subtle Background Elements -->
    <div class="absolute top-10 left-10 w-32 h-32 bg-blue-50 rounded-full blur-2xl opacity-30"></div>
    <div class="absolute bottom-10 right-10 w-40 h-40 bg-indigo-50 rounded-full blur-2xl opacity-25"></div>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center mb-16" data-aos="fade-up">
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">
                <span class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                    الأكاديمية التعليمية
                </span>
            </h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                منصة تعليمية متكاملة تقدم دورات في القرآن الكريم والعلوم الإسلامية
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            <!-- Left Column - Text Content and Button -->
            <div class="space-y-8" data-aos="fade-right">
                <div class="space-y-6">
                                    <h3 class="text-3xl md:text-4xl font-bold text-gray-900 leading-tight">
                    تعلم مع أفضل المعلمين المتخصصين
                </h3>
                    <p class="text-lg text-gray-600 leading-relaxed">
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

            <!-- Right Column - Features Grid -->
            <div class="grid grid-cols-2 gap-6" data-aos="fade-left">
                <!-- Feature 1 - Quran -->
                <div class="bg-gradient-to-br from-emerald-100 via-green-50 to-emerald-50 rounded-2xl p-6 border border-emerald-200 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                    <div class="w-14 h-14 bg-emerald-100 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                    <h4 class="font-bold text-gray-900 mb-2">تحفيظ القرآن</h4>
                    <p class="text-sm text-gray-600 leading-relaxed">حلقات تفاعلية مع معلمين متخصصين<br>في بيئة محفزة ومريحة للتعلم</p>
                </div>

                <!-- Feature 2 - Academic Subjects -->
                <div class="bg-gradient-to-br from-blue-100 via-cyan-50 to-blue-50 rounded-2xl p-6 border border-blue-200 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                    <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                        </svg>
                    </div>
                    <h4 class="font-bold text-gray-900 mb-2">المواد الأكاديمية</h4>
                    <p class="text-sm text-gray-600 leading-relaxed">جميع المراحل الدراسية مع مناهج<br>محدثة وطرق تدريس متطورة</p>
                </div>

                <!-- Feature 3 - Live Sessions -->
                <div class="bg-gradient-to-br from-purple-100 via-violet-50 to-purple-50 rounded-2xl p-6 border border-purple-200 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                    <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h4 class="font-bold text-gray-900 mb-2">جلسات مباشرة</h4>
                    <p class="text-sm text-gray-600 leading-relaxed">تفاعل مباشر مع المعلمين في<br>جلسات حية تفاعلية ومحفزة</p>
                </div>

                <!-- Feature 4 - Progress Tracking -->
                <div class="bg-gradient-to-br from-orange-100 via-amber-50 to-orange-50 rounded-2xl p-6 border border-orange-200 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                    <div class="w-14 h-14 bg-orange-100 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h4 class="font-bold text-gray-900 mb-2">متابعة التقدم</h4>
                    <p class="text-sm text-gray-600 leading-relaxed">تقارير مفصلة عن الأداء مع<br>نصائح للتطوير والتحسين</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Section Bottom Border -->
    <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-100 via-indigo-100 to-blue-100"></div>
</section>

<!-- Business Solutions Section -->
<section class="py-24 bg-white relative overflow-hidden">
    <!-- Section Top Border -->
    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-green-100 via-teal-100 to-green-100"></div>
    
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-5">
        <div class="absolute inset-0" style="background-image: url('/storage/app-design-assets/bg-pattern1.png'); background-size: 50px 50px; background-repeat: repeat;"></div>
    </div>
    
    <!-- Subtle Background Elements -->
    <div class="absolute top-10 right-10 w-32 h-32 bg-green-50 rounded-full blur-2xl opacity-30"></div>
    <div class="absolute bottom-10 left-10 w-40 h-40 bg-teal-50 rounded-full blur-2xl opacity-25"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center mb-16" data-aos="fade-up">
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">
                <span class="bg-gradient-to-r from-green-600 to-teal-600 bg-clip-text text-transparent">
                    خدمات الأعمال
                </span>
            </h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                نقدم مجموعة متنوعة من الخدمات الاحترافية لمساعدة شركتك على النمو والتطور
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            <!-- Left Column - Features Grid (Swapped from education section) -->
            <div class="grid grid-cols-2 gap-6" data-aos="fade-right">
                <!-- Feature 1 - Web Development -->
                <div class="bg-gradient-to-br from-green-100 via-emerald-50 to-green-50 rounded-2xl p-6 border border-green-200 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                    <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                        </svg>
                    </div>
                    <h4 class="font-bold text-gray-900 mb-2">تطوير المواقع</h4>
                    <p class="text-sm text-gray-600 leading-relaxed">مواقع احترافية متجاوبة<br>مع أحدث التقنيات</p>
                </div>

                <!-- Feature 2 - Mobile Apps -->
                <div class="bg-gradient-to-br from-teal-100 via-cyan-50 to-teal-50 rounded-2xl p-6 border border-teal-200 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                    <div class="w-14 h-14 bg-teal-100 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h4 class="font-bold text-gray-900 mb-2">تطبيقات الجوال</h4>
                    <p class="text-sm text-gray-600 leading-relaxed">تطبيقات ذكية متطورة<br>لجميع المنصات</p>
                </div>

                <!-- Feature 3 - Digital Marketing -->
                <div class="bg-gradient-to-br from-emerald-100 via-green-50 to-emerald-50 rounded-2xl p-6 border border-emerald-200 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                    <div class="w-14 h-14 bg-emerald-100 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <h4 class="font-bold text-gray-900 mb-2">التسويق الرقمي</h4>
                    <p class="text-sm text-gray-600 leading-relaxed">استراتيجيات تسويقية متقدمة<br>لزيادة المبيعات</p>
                </div>

                <!-- Feature 4 - Business Consulting -->
                <div class="bg-gradient-to-br from-cyan-100 via-teal-50 to-cyan-50 rounded-2xl p-6 border border-cyan-200 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                    <div class="w-14 h-14 bg-cyan-100 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h4 class="font-bold text-gray-900 mb-2">الاستشارات</h4>
                    <p class="text-sm text-gray-600 leading-relaxed">استشارات متخصصة لتطوير<br>وتنمية الأعمال</p>
                </div>
            </div>

            <!-- Right Column - Text Content and Button (Swapped from education section) -->
            <div class="space-y-8" data-aos="fade-left">
                <div class="space-y-6">
                    <h3 class="text-3xl md:text-4xl font-bold text-gray-900 leading-tight">
                        حلول تقنية متكاملة لأعمالك
                    </h3>
                    <p class="text-lg text-gray-600 leading-relaxed">
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
                
                <div class="pt-4">
                    <a href="http://itqan-business.{{ config('app.domain') }}" 
                       class="group relative inline-flex items-center gap-3 px-8 py-4 bg-gradient-to-r from-green-600 to-teal-600 text-white rounded-2xl font-bold text-lg transition-all duration-300 transform hover:scale-105 hover:shadow-2xl overflow-hidden">
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
        </div>
    </div>
    
    <!-- Section Bottom Border -->
    <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-green-100 via-teal-100 to-green-100"></div>
</section>

<!-- Why Choose Itqan Section - Modern Design -->
<section class="py-24 bg-gradient-to-br from-emerald-50 via-teal-50 to-cyan-50 relative overflow-hidden">
    <!-- Animated Background Elements -->
    <div class="absolute top-10 right-10 w-32 h-32 bg-gradient-to-r from-emerald-400 to-teal-400 rounded-full blur-2xl opacity-20 animate-pulse"></div>
    <div class="absolute bottom-10 left-10 w-40 h-40 bg-gradient-to-r from-cyan-400 to-blue-400 rounded-full blur-2xl opacity-20 animate-bounce"></div>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center mb-16" data-aos="fade-up">
            <h2 class="text-4xl md:text-5xl font-bold mb-6">
                <span class="bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent">
                    لماذا تختار منصة إتقان؟
                </span>
            </h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                نتميز بتقديم حلول متكاملة تجمع بين الخبرة التقنية والرؤية الإسلامية
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Feature 1 -->
            <div class="group bg-white rounded-3xl p-8 shadow-lg hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-3 border border-emerald-100" data-aos="fade-up" data-aos-delay="100">
                <div class="w-16 h-16 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-4">جودة عالية</h3>
                <p class="text-gray-600 leading-relaxed">
                    نلتزم بأعلى معايير الجودة في جميع خدماتنا، من التصميم إلى التطوير والتنفيذ
                </p>
            </div>

            <!-- Feature 2 -->
            <div class="group bg-white rounded-3xl p-8 shadow-lg hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-3 border border-emerald-100" data-aos="fade-up" data-aos-delay="200">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-4">سرعة في التنفيذ</h3>
                <p class="text-gray-600 leading-relaxed">
                    فريق عمل محترف يضمن تسليم مشاريعك في الوقت المحدد وبأعلى جودة
                </p>
            </div>

            <!-- Feature 3 -->
            <div class="group bg-white rounded-3xl p-8 shadow-lg hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-3 border border-emerald-100" data-aos="fade-up" data-aos-delay="300">
                <div class="w-16 h-16 bg-gradient-to-br from-purple-400 to-pink-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-4">فريق متخصص</h3>
                <p class="text-gray-600 leading-relaxed">
                    نخبة من الخبراء والمتخصصين في مختلف المجالات التقنية والتسويقية
                </p>
            </div>

            <!-- Feature 4 -->
            <div class="group bg-white rounded-3xl p-8 shadow-lg hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-3 border border-emerald-100" data-aos="fade-up" data-aos-delay="400">
                <div class="w-16 h-16 bg-gradient-to-br from-orange-400 to-red-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M12 2.25a9.75 9.75 0 100 19.5 9.75 9.75 0 000-19.5z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-4">دعم مستمر</h3>
                <p class="text-gray-600 leading-relaxed">
                    نقدم دعم فني مستمر وخدمة ما بعد البيع لضمان نجاح مشروعك
                </p>
            </div>

            <!-- Feature 5 -->
            <div class="group bg-white rounded-3xl p-8 shadow-lg hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-3 border border-emerald-100" data-aos="fade-up" data-aos-delay="500">
                <div class="w-16 h-16 bg-gradient-to-br from-teal-400 to-cyan-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-4">أمان وخصوصية</h3>
                <p class="text-gray-600 leading-relaxed">
                    نضمن أمان بياناتك وخصوصية معلوماتك بأحدث تقنيات الحماية
                </p>
            </div>

            <!-- Feature 6 -->
            <div class="group bg-white rounded-3xl p-8 shadow-lg hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-3 border border-emerald-100" data-aos="fade-up" data-aos-delay="600">
                <div class="w-16 h-16 bg-gradient-to-br from-green-400 to-emerald-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-4">رؤية إسلامية</h3>
                <p class="text-gray-600 leading-relaxed">
                    نعمل وفقاً للقيم الإسلامية ونقدم حلول تناسب المجتمع المسلم
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section with Animation -->
<section class="py-24 bg-gradient-to-r from-gray-900 via-slate-800 to-gray-900 relative overflow-hidden">
    <div class="absolute inset-0 opacity-20">
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 100 100\"><defs><pattern id=\"stars\" width=\"20\" height=\"20\" patternUnits=\"userSpaceOnUse\"><circle cx=\"10\" cy=\"10\" r=\"1\" fill=\"%23ffffff\" opacity=\"0.3\"/></pattern></defs><rect width=\"100\" height=\"100\" fill=\"url(%23stars)\"/></svg>'); background-size: 30px 30px;"></div>
    </div>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 text-center">
            <div class="group" data-aos="fade-up" data-aos-delay="100">
                <div class="text-5xl font-bold bg-gradient-to-r from-green-400 to-emerald-500 bg-clip-text text-transparent mb-2 group-hover:scale-110 transition-transform duration-300">
                    50+
                </div>
                <div class="text-gray-300 text-lg">مشروع مكتمل</div>
            </div>
            <div class="group" data-aos="fade-up" data-aos-delay="200">
                <div class="text-5xl font-bold bg-gradient-to-r from-blue-400 to-cyan-500 bg-clip-text text-transparent mb-2 group-hover:scale-110 transition-transform duration-300">
                    100+
                </div>
                <div class="text-gray-300 text-lg">عميل راضٍ</div>
            </div>
            <div class="group" data-aos="fade-up" data-aos-delay="300">
                <div class="text-5xl font-bold bg-gradient-to-r from-purple-400 to-pink-500 bg-clip-text text-transparent mb-2 group-hover:scale-110 transition-transform duration-300">
                    5+
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
<section class="py-24 bg-gradient-to-br from-blue-600 via-purple-600 to-pink-600 relative overflow-hidden">
    <!-- Animated Background -->
    <div class="absolute inset-0">
        <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-r from-blue-600/50 to-purple-600/50 animate-pulse"></div>
        <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-l from-pink-600/30 to-purple-600/30 animate-bounce"></div>
    </div>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <h2 class="text-4xl md:text-5xl font-bold text-white mb-6" data-aos="fade-up">
            هل أنت مستعد لبدء مشروعك؟
        </h2>
        <p class="text-xl mb-12 text-blue-100 max-w-2xl mx-auto" data-aos="fade-up" data-aos-delay="200">
            دعنا نعمل معاً لتحقيق رؤيتك ونقل أعمالك إلى المستوى التالي
        </p>
        <a href="{{ route('platform.contact') }}" 
           class="group relative inline-block px-12 py-6 bg-white text-gray-900 rounded-2xl font-bold text-xl hover:bg-gray-50 transition-all duration-300 transform hover:scale-105 hover:shadow-2xl" 
           data-aos="fade-up" data-aos-delay="400">
            <span class="relative z-10">اتصل بنا الآن</span>
            <div class="absolute inset-0 bg-gradient-to-r from-blue-500 to-purple-500 rounded-2xl opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
            <div class="absolute -inset-1 bg-gradient-to-r from-blue-500 to-purple-500 rounded-2xl blur opacity-30 group-hover:opacity-50 transition-opacity duration-300"></div>
        </a>
    </div>
</section>

<!-- Custom CSS for Animations -->
<style>
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(180deg); }
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
        // Initialize GSAP animations
        gsap.registerPlugin(ScrollTrigger);
        
        // Hero section animations
        gsap.from('.hero-gradient', {
            duration: 2,
            opacity: 0,
            y: 50,
            ease: "power2.out"
        });
        
        // Stagger animation for feature cards
        gsap.from('.feature-card', {
            duration: 1,
            y: 100,
            opacity: 0,
            stagger: 0.2,
            ease: "power2.out",
            scrollTrigger: {
                trigger: '.feature-card',
                start: "top 80%",
                end: "bottom 20%",
                toggleActions: "play none none reverse"
            }
        });
        
        // Counter animation for stats
        gsap.utils.toArray('.stat-number').forEach(stat => {
            const endValue = parseInt(stat.textContent);
            gsap.fromTo(stat, {
                textContent: 0
            }, {
                textContent: endValue,
                duration: 2,
                ease: "power2.out",
                snap: { textContent: 1 },
                scrollTrigger: {
                    trigger: stat,
                    start: "top 80%",
                    toggleActions: "play none none reverse"
                }
            });
        });
        
        // Parallax effect for background elements
        gsap.utils.toArray('.parallax-bg').forEach(bg => {
            gsap.to(bg, {
                yPercent: -50,
                ease: "none",
                scrollTrigger: {
                    trigger: bg,
                    start: "top bottom",
                    end: "bottom top",
                    scrub: true
                }
            });
        });
    });
</script>
@endsection