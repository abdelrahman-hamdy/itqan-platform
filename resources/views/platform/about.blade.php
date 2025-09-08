@extends('components.platform-layout')

@section('title', 'من نحن - منصة إتقان')

@section('content')
    <!-- Hero Section -->
    <div class="relative text-white py-20 pt-40 overflow-hidden flex items-center justify-center" style="min-height: 70vh; background-image: url('/storage/app-design-assets/about-us-page-cover.png'); background-size: cover; background-position: center; background-repeat: no-repeat;">
        <!-- Dark Overlay -->
        <div class="absolute inset-0 bg-black/50"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
            <div class="mb-8" data-aos="fade-up">
                <span class="inline-flex items-center gap-3 px-6 py-3 bg-white/10 text-green-100 rounded-full text-sm font-semibold border border-white/30 backdrop-blur-sm">
                    <i class="ri-team-line text-lg"></i>
                    منصة إتقان
                </span>
            </div>
            <h1 class="text-4xl md:text-6xl font-bold mb-6 leading-relaxed text-white" data-aos="fade-up" data-aos-delay="200">
                من نحن
            </h1>
            <p class="text-xl md:text-2xl mb-8 text-green-100 max-w-3xl mx-auto" data-aos="fade-up" data-aos-delay="400">
                تعرف على منصة إتقان ورحلتنا في خدمة المجتمع الإسلامي وتطوير الحلول التقنية المبتكرة
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center" data-aos="fade-up" data-aos-delay="600">
                <a href="#about" class="bg-green-600 text-white px-8 py-4 rounded-xl font-semibold hover:bg-green-700 transition-all transform hover:scale-105">
                    <i class="ri-information-line ml-2"></i>
                    تعرف علينا أكثر
                </a>
                <a href="{{ route('platform.contact') }}" class="bg-sky-600 text-white px-8 py-4 rounded-xl font-semibold hover:bg-sky-700 transition-all transform hover:scale-105">
                    <i class="ri-phone-line ml-2"></i>
                    تواصل معنا
                </a>
            </div>
        </div>
    </div>

    <!-- Platform Overview Section -->
    <section id="about" class="py-24 bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 relative overflow-hidden">
        <!-- Section Top Border -->
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-100 via-indigo-100 to-purple-100"></div>
        
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-5">
            <div class="absolute inset-0" style="background-image: url('/storage/app-design-assets/bg-pattern1.png'); background-size: 50px 50px; background-repeat: repeat;"></div>
        </div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center mb-16" data-aos="fade-up">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6 leading-relaxed">
                    <span class="flex items-center justify-center gap-4">
                        <i class="ri-lightbulb-line text-5xl text-blue-600"></i>
                        <span class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 bg-clip-text text-transparent">
                            منصة إتقان الشاملة
                        </span>
                    </span>
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    منصة تقنية متكاملة تجمع بين خدمات الأعمال الاحترافية والأكاديمية التعليمية المتطورة
                </p>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div data-aos="fade-right">
                    <h3 class="text-3xl font-bold text-gray-900 mb-6">
                        <span class="flex items-center gap-3">
                            <i class="ri-briefcase-line text-3xl text-green-600"></i>
                            خدمات الأعمال الاحترافية
                        </span>
                    </h3>
                    <p class="text-lg text-gray-600 mb-6 leading-relaxed">
                        نقدم حلولاً تقنية متكاملة للشركات والمؤسسات تشمل تطوير المواقع، التطبيقات، 
                        التسويق الرقمي، وإدارة المشاريع التقنية بأعلى معايير الجودة.
                    </p>
                    <ul class="space-y-3 text-gray-600">
                        <li class="flex items-center gap-3">
                            <i class="ri-check-line text-green-600"></i>
                            تطوير المواقع والتطبيقات
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="ri-check-line text-green-600"></i>
                            التسويق الرقمي والحلول التسويقية
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="ri-check-line text-green-600"></i>
                            استشارات تقنية متخصصة
                        </li>
                    </ul>
                </div>
                
                <div data-aos="fade-left">
                    <h3 class="text-3xl font-bold text-gray-900 mb-6">
                        <span class="flex items-center gap-3">
                            <i class="ri-graduation-cap-line text-3xl text-blue-600"></i>
                            الأكاديمية التعليمية
                        </span>
                    </h3>
                    <p class="text-lg text-gray-600 mb-6 leading-relaxed">
                        أكاديمية تعليمية متطورة تقدم برامج تعليمية شاملة تغطي مختلف المجالات 
                        التعليمية مع التركيز على الجودة والتميز الأكاديمي.
                    </p>
                    <ul class="space-y-3 text-gray-600">
                        <li class="flex items-center gap-3">
                            <i class="ri-check-line text-blue-600"></i>
                            حلقات القرآن الكريم
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="ri-check-line text-blue-600"></i>
                            الدورات الأكاديمية المتخصصة
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="ri-check-line text-blue-600"></i>
                            الدروس الخصوصية والدورات التدريبية
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Section Bottom Border -->
        <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-100 via-indigo-100 to-purple-100"></div>
    </section>

    <!-- Unique Features Section -->
    <section class="py-24 bg-gradient-to-br from-emerald-50 via-teal-50 to-cyan-50 relative overflow-hidden">
        <!-- Section Top Border -->
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-100 via-teal-100 to-cyan-100"></div>
        
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-5">
            <div class="absolute inset-0" style="background-image: url('/storage/app-design-assets/bg-pattern1.png'); background-size: 50px 50px; background-repeat: repeat;"></div>
        </div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center mb-16" data-aos="fade-up">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6 leading-relaxed">
                    <span class="flex items-center justify-center gap-4">
                        <i class="ri-star-line text-5xl text-emerald-600"></i>
                        <span class="bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 bg-clip-text text-transparent">
                            مميزاتنا الفريدة
                        </span>
                    </span>
                </h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    ما يميز منصة إتقان عن غيرها من المنصات التقنية والتعليمية
                </p>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                <div class="space-y-8">
                    <div class="bg-white/70 backdrop-blur-sm p-8 rounded-2xl shadow-xl border border-emerald-200" data-aos="fade-right">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-emerald-100 to-teal-100 rounded-xl flex items-center justify-center border border-emerald-200 flex-shrink-0">
                                <i class="ri-shield-check-line text-xl text-emerald-600"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 mb-3">التكامل الشامل</h3>
                                <p class="text-gray-600 leading-relaxed">
                                    نقدم حلولاً متكاملة تجمع بين الخدمات التقنية والتعليمية تحت سقف واحد، 
                                    مما يوفر تجربة موحدة ومتماسكة للعملاء.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white/70 backdrop-blur-sm p-8 rounded-2xl shadow-xl border border-teal-200" data-aos="fade-right" data-aos-delay="100">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-teal-100 to-cyan-100 rounded-xl flex items-center justify-center border border-teal-200 flex-shrink-0">
                                <i class="ri-user-heart-line text-xl text-teal-600"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 mb-3">التركيز على المجتمع الإسلامي</h3>
                                <p class="text-gray-600 leading-relaxed">
                                    نحن متخصصون في فهم احتياجات المجتمع الإسلامي وتقديم حلول مخصصة 
                                    تلبي متطلباته الفريدة وتتماشى مع قيمه ومبادئه.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-8">
                    <div class="bg-white/70 backdrop-blur-sm p-8 rounded-2xl shadow-xl border border-cyan-200" data-aos="fade-left">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-cyan-100 to-blue-100 rounded-xl flex items-center justify-center border border-cyan-200 flex-shrink-0">
                                <i class="ri-rocket-line text-xl text-cyan-600"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 mb-3">التقنيات المتطورة</h3>
                                <p class="text-gray-600 leading-relaxed">
                                    نستخدم أحدث التقنيات والأدوات في تطوير حلولنا، مع التركيز على 
                                    الأداء العالي والأمان والموثوقية في جميع خدماتنا.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white/70 backdrop-blur-sm p-8 rounded-2xl shadow-xl border border-emerald-200" data-aos="fade-left" data-aos-delay="100">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-emerald-100 to-green-100 rounded-xl flex items-center justify-center border border-emerald-200 flex-shrink-0">
                                <i class="ri-customer-service-line text-xl text-emerald-600"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 mb-3">الدعم المستمر</h3>
                                <p class="text-gray-600 leading-relaxed">
                                    نقدم دعم فني متواصل وخدمة عملاء متميزة، مع متابعة مستمرة 
                                    لضمان نجاح مشاريع عملائنا وتحقيق أهدافهم.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Section Bottom Border -->
        <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-100 via-teal-100 to-cyan-100"></div>
    </section>

    <!-- Platform Objectives Section -->
    <section class="py-24 bg-gradient-to-br from-rose-50 via-pink-50 to-purple-50 relative overflow-hidden">
        <!-- Section Top Border -->
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-rose-100 via-pink-100 to-purple-100"></div>
        
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-5">
            <div class="absolute inset-0" style="background-image: url('/storage/app-design-assets/bg-pattern1.png'); background-size: 50px 50px; background-repeat: repeat;"></div>
        </div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center mb-16" data-aos="fade-up">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6 leading-relaxed">
                    <span class="flex items-center justify-center gap-4">
                        <i class="ri-target-line text-5xl text-rose-600"></i>
                        <span class="bg-gradient-to-r from-rose-600 via-pink-600 to-purple-600 bg-clip-text text-transparent">
                            أهدافنا الرئيسية
                        </span>
                    </span>
                </h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    الأهداف التي نسعى لتحقيقها من خلال خدماتنا المتنوعة
                </p>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                <div class="space-y-8">
                    <div class="bg-white/70 backdrop-blur-sm p-8 rounded-2xl shadow-xl border border-rose-200" data-aos="fade-right">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-rose-100 to-pink-100 rounded-xl flex items-center justify-center border border-rose-200 flex-shrink-0">
                                <i class="ri-briefcase-line text-xl text-rose-600"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 mb-3">تطوير الأعمال والمؤسسات</h3>
                                <p class="text-gray-600 leading-relaxed">
                                    نساعد الشركات والمؤسسات على النمو والتطور من خلال تقديم حلول تقنية 
                                    متطورة تساهم في تحسين الأداء وزيادة الإنتاجية.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white/70 backdrop-blur-sm p-8 rounded-2xl shadow-xl border border-pink-200" data-aos="fade-right" data-aos-delay="100">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-pink-100 to-purple-100 rounded-xl flex items-center justify-center border border-pink-200 flex-shrink-0">
                                <i class="ri-graduation-cap-line text-xl text-pink-600"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 mb-3">التميز التعليمي</h3>
                                <p class="text-gray-600 leading-relaxed">
                                    نهدف لتقديم تعليم عالي الجودة يلبي احتياجات المتعلمين في مختلف المراحل 
                                    العمرية والمستويات الأكاديمية.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-8">
                    <div class="bg-white/70 backdrop-blur-sm p-8 rounded-2xl shadow-xl border border-purple-200" data-aos="fade-left">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-purple-100 to-indigo-100 rounded-xl flex items-center justify-center border border-purple-200 flex-shrink-0">
                                <i class="ri-community-line text-xl text-purple-600"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 mb-3">خدمة المجتمع الإسلامي</h3>
                                <p class="text-gray-600 leading-relaxed">
                                    نعمل على تطوير المجتمع الإسلامي من خلال تقديم حلول تقنية وتعليمية 
                                    مخصصة تلبي احتياجاته الفريدة وتتماشى مع قيمه.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white/70 backdrop-blur-sm p-8 rounded-2xl shadow-xl border border-rose-200" data-aos="fade-left" data-aos-delay="100">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-rose-100 to-pink-100 rounded-xl flex items-center justify-center border border-rose-200 flex-shrink-0">
                                <i class="ri-lightbulb-line text-xl text-rose-600"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 mb-3">الابتكار والتطوير</h3>
                                <p class="text-gray-600 leading-relaxed">
                                    نسعى ليكون منصة إتقان رائدة في مجال الابتكار التقني والتعليمي، 
                                    مع التركيز على تطوير حلول مبتكرة ومتطورة.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Section Bottom Border -->
        <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-rose-100 via-pink-100 to-purple-100"></div>
    </section>


    <!-- CTA Section -->
    <section class="py-24 bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 relative overflow-hidden">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-5">
            <div class="absolute inset-0" style="background-image: url('/storage/app-design-assets/bg-pattern1.png'); background-size: 100px 100px; background-repeat: repeat;"></div>
        </div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
            <h2 class="text-4xl md:text-5xl font-bold text-white mb-6 leading-relaxed" data-aos="fade-up">
                هل تريد العمل معنا؟
            </h2>
            <p class="text-xl mb-12 text-blue-100 max-w-2xl mx-auto" data-aos="fade-up" data-aos-delay="200">
                دعنا نتعاون لتحقيق مشاريعك وأهدافك ونقل أعمالك إلى المستوى التالي
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center" data-aos="fade-up" data-aos-delay="400">
                <a href="{{ route('platform.contact') }}" 
                   class="bg-green-600 text-white px-8 py-4 rounded-2xl font-semibold hover:bg-green-700 transition-all transform hover:scale-105">
                    <i class="ri-phone-line ml-2"></i>
                    اتصل بنا
                </a>
                <a href="{{ route('platform.business-services') }}" 
                   class="bg-sky-600 text-white px-8 py-4 rounded-2xl font-semibold hover:bg-sky-700 transition-all transform hover:scale-105">
                    <i class="ri-briefcase-line ml-2"></i>
                    خدماتنا
                </a>
            </div>
        </div>
    </section>
@endsection

