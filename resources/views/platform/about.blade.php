@extends('components.platform-layout')

@section('title', 'من نحن - منصة إتقان')

@section('content')
    <!-- Hero Section -->
    <div class="hero-gradient text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl md:text-6xl font-bold mb-6">من نحن</h1>
            <p class="text-xl md:text-2xl mb-8 text-green-100">تعرف على منصة إتقان ورحلتنا في خدمة المجتمع الإسلامي</p>
        </div>
    </div>

    <!-- About Section -->
    <div class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6">منصة إتقان</h2>
                    <p class="text-lg text-gray-600 mb-6">
                        منصة تقنية متكاملة تأسست بهدف خدمة المجتمع الإسلامي من خلال تقديم حلول تقنية مبتكرة 
                        تجمع بين خدمات الأعمال الاحترافية والتعليم الإلكتروني المتطور.
                    </p>
                    <p class="text-lg text-gray-600 mb-6">
                        نؤمن بأهمية الجودة والإتقان في كل ما نقدمه، ونسعى لتكون منصة إتقان الخيار الأمثل 
                        للمؤسسات والأفراد الذين يبحثون عن التميز والابتكار.
                    </p>
                    <div class="flex space-x-6 space-x-reverse">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-green-600">5+</div>
                            <div class="text-gray-600">سنوات خبرة</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-green-600">100+</div>
                            <div class="text-gray-600">مشروع مكتمل</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-green-600">50+</div>
                            <div class="text-gray-600">عميل راضٍ</div>
                        </div>
                    </div>
                </div>
                <div class="relative">
                    <div class="w-full h-96 bg-gradient-to-br from-green-100 to-blue-100 rounded-2xl flex items-center justify-center">
                        <svg class="w-32 h-32 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mission & Vision -->
    <div class="py-24 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <div class="bg-white p-8 rounded-2xl shadow-lg">
                    <div class="w-16 h-16 bg-green-100 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">رؤيتنا</h3>
                    <p class="text-gray-600">
                        نسعى لأن نكون المنصة الرائدة في مجال التقنية الإسلامية، ونطمح لتقديم حلول مبتكرة 
                        تساهم في تطوير المجتمع الإسلامي وتمكين المؤسسات التعليمية.
                    </p>
                </div>
                
                <div class="bg-white p-8 rounded-2xl shadow-lg">
                    <div class="w-16 h-16 bg-green-100 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">مهمتنا</h3>
                    <p class="text-gray-600">
                        تقديم خدمات تقنية عالية الجودة تجمع بين الابتكار والموثوقية، مع التركيز على 
                        احتياجات المجتمع الإسلامي وتطوير حلول مخصصة تلبي تطلعاته.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Values -->
    <div class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">قيمنا</h2>
                <p class="text-xl text-gray-600">المبادئ التي نؤمن بها ونعمل بها</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">الإتقان</h3>
                    <p class="text-gray-600">نؤمن بأهمية الإتقان في كل عمل نقوم به، ونطمح للتميز في جميع خدماتنا</p>
                </div>
                
                <div class="text-center">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">الابتكار</h3>
                    <p class="text-gray-600">نسعى دائماً لتطوير حلول مبتكرة ومتطورة تلبي احتياجات عملائنا</p>
                </div>
                
                <div class="text-center">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">الموثوقية</h3>
                    <p class="text-gray-600">نحرص على تقديم خدمات موثوقة وعالية الجودة لضمان رضا عملائنا</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Team -->
    <div class="py-24 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">فريقنا</h2>
                <p class="text-xl text-gray-600">فريق متخصص من الخبراء في مختلف المجالات التقنية</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white p-6 rounded-xl text-center shadow-lg">
                    <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">أحمد محمد</h3>
                    <p class="text-green-600 mb-2">مدير التطوير</p>
                    <p class="text-gray-600 text-sm">خبرة 8 سنوات في تطوير التطبيقات والمواقع الإلكترونية</p>
                </div>
                
                <div class="bg-white p-6 rounded-xl text-center shadow-lg">
                    <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">فاطمة علي</h3>
                    <p class="text-green-600 mb-2">مصممة UX/UI</p>
                    <p class="text-gray-600 text-sm">متخصصة في تصميم تجربة المستخدم والواجهات الجذابة</p>
                </div>
                
                <div class="bg-white p-6 rounded-xl text-center shadow-lg">
                    <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">محمد حسن</h3>
                    <p class="text-green-600 mb-2">خبير التسويق الرقمي</p>
                    <p class="text-gray-600 text-sm">متخصص في استراتيجيات التسويق الرقمي وبناء العلامات التجارية</p>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="py-24 bg-green-600 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl md:text-4xl font-bold mb-4">هل تريد العمل معنا؟</h2>
            <p class="text-xl mb-8 text-green-100">دعنا نتعاون لتحقيق مشاريعك وأهدافك</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('platform.contact') }}" class="bg-white text-green-600 px-8 py-4 rounded-xl font-semibold hover:bg-gray-100 transition-colors transform hover:scale-105">
                    اتصل بنا
                </a>
                <a href="{{ route('platform.business-services') }}" class="bg-transparent border-2 border-white text-white px-8 py-4 rounded-xl font-semibold hover:bg-white hover:text-green-600 transition-colors transform hover:scale-105">
                    خدماتنا
                </a>
            </div>
        </div>
    </div>
@endsection
