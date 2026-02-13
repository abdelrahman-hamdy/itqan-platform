@extends('components.platform-layout')

@section('title', 'سياسة الخصوصية - منصة إتقان')

@section('content')
    <!-- Hero Section -->
    <div class="relative flex items-center justify-center overflow-hidden bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 py-20 pt-40" style="min-height: 50vh;">
        <!-- Background Pattern Layer -->
        <div class="absolute inset-0 opacity-20">
            <div class="absolute inset-0" style="background-image: url('/assets/images/bg-pattern1.png'); background-size: 100px 100px; background-repeat: repeat;"></div>
        </div>

        <!-- Background Elements -->
        <div class="absolute top-0 right-0 w-[40rem] h-[40rem] bg-gradient-to-br from-blue-500/20 via-blue-600/30 to-indigo-600/25 rounded-full blur-3xl opacity-60"></div>
        <div class="absolute bottom-0 left-0 w-[45rem] h-[45rem] bg-gradient-to-tr from-emerald-500/20 via-green-600/25 to-teal-600/20 rounded-full blur-3xl opacity-55"></div>
        <div class="absolute top-1/2 right-0 transform translate-x-1/2 -translate-y-1/2 w-[35rem] h-[35rem] bg-gradient-to-l from-cyan-500/20 via-teal-600/25 to-blue-600/20 rounded-full blur-3xl opacity-50"></div>

        <!-- Content -->
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="mb-8">
                <span class="inline-flex items-center gap-2 md:gap-3 px-4 md:px-6 py-2 md:py-3 bg-white/10 text-blue-100 rounded-full text-xs md:text-sm font-semibold border border-blue-300/30 backdrop-blur-sm">
                    <i class="ri-shield-check-line text-base md:text-lg"></i>
                    حماية بياناتك أولويتنا
                </span>
            </div>

            <h1 class="text-4xl md:text-6xl font-bold mb-6 leading-relaxed text-white">
                <span class="bg-gradient-to-r from-white via-blue-100 to-indigo-100 bg-clip-text text-transparent">
                    سياسة الخصوصية
                </span>
            </h1>

            <p class="text-xl md:text-2xl mb-8 text-blue-100 max-w-3xl mx-auto">
                منصة إتقان — التعليم القرآني والأكاديمي
            </p>
        </div>
    </div>

    <!-- Privacy Policy Content -->
    <section class="py-16 md:py-24 bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 relative overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-100 via-indigo-100 to-purple-100"></div>
        <div class="absolute inset-0 opacity-5">
            <div class="absolute inset-0" style="background-image: url('/assets/images/bg-pattern1.png'); background-size: 50px 50px; background-repeat: repeat;"></div>
        </div>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">

            <!-- Introduction -->
            <div class="bg-white rounded-2xl p-6 md:p-8 shadow-xl border border-gray-200 mb-6">
                <h2 class="text-xl md:text-2xl font-bold text-gray-900 mb-4 flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="ri-file-list-3-line text-xl text-blue-600"></i>
                    </div>
                    مقدمة
                </h2>
                <p class="text-gray-600 leading-relaxed mb-4">
                    مرحباً بك في <strong>منصة إتقان</strong>. نحن نلتزم بحماية خصوصيتك وأمان بياناتك الشخصية.
                    تصف هذه السياسة كيفية جمع معلوماتك واستخدامها وحمايتها عند استخدام تطبيقنا أو موقعنا الإلكتروني.
                </p>
                <div class="bg-blue-50 border-r-4 border-blue-600 rounded-lg p-4 text-blue-900">
                    باستخدامك لمنصة إتقان، فإنك توافق على ممارسات الخصوصية المبيّنة في هذه السياسة.
                </div>
            </div>

            <!-- Data We Collect -->
            <div class="bg-white rounded-2xl p-6 md:p-8 shadow-xl border border-gray-200 mb-6">
                <h2 class="text-xl md:text-2xl font-bold text-gray-900 mb-4 flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="ri-bar-chart-box-line text-xl text-indigo-600"></i>
                    </div>
                    البيانات التي نجمعها
                </h2>
                <p class="text-gray-600 leading-relaxed mb-4">نجمع المعلومات التالية لتقديم خدماتنا التعليمية بشكل فعّال:</p>
                <ul class="space-y-3 text-gray-600">
                    <li class="flex items-start gap-3"><i class="ri-check-line text-green-600 mt-1 flex-shrink-0"></i> معلومات الحساب: الاسم، البريد الإلكتروني، رقم الهاتف، وصورة الملف الشخصي</li>
                    <li class="flex items-start gap-3"><i class="ri-check-line text-green-600 mt-1 flex-shrink-0"></i> بيانات التعلم: سجل الجلسات، التقدم في الحفظ، الواجبات، والتقييمات</li>
                    <li class="flex items-start gap-3"><i class="ri-check-line text-green-600 mt-1 flex-shrink-0"></i> بيانات الجلسات المباشرة: سجلات الحضور وأوقات الاتصال (بدون تسجيل المحادثات)</li>
                    <li class="flex items-start gap-3"><i class="ri-check-line text-green-600 mt-1 flex-shrink-0"></i> بيانات الإشعارات: رمز الجهاز (FCM Token) لإرسال الإشعارات الفورية</li>
                    <li class="flex items-start gap-3"><i class="ri-check-line text-green-600 mt-1 flex-shrink-0"></i> بيانات الدفع: معلومات الاشتراكات (لا نخزن بيانات البطاقة الائتمانية مباشرةً)</li>
                    <li class="flex items-start gap-3"><i class="ri-check-line text-green-600 mt-1 flex-shrink-0"></i> بيانات تقنية: نوع الجهاز، نظام التشغيل، وسجلات الأخطاء لتحسين الأداء</li>
                </ul>
            </div>

            <!-- How We Use Data -->
            <div class="bg-white rounded-2xl p-6 md:p-8 shadow-xl border border-gray-200 mb-6">
                <h2 class="text-xl md:text-2xl font-bold text-gray-900 mb-4 flex items-center gap-3">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="ri-focus-3-line text-xl text-purple-600"></i>
                    </div>
                    كيف نستخدم بياناتك
                </h2>
                <ul class="space-y-3 text-gray-600">
                    <li class="flex items-start gap-3"><i class="ri-check-line text-green-600 mt-1 flex-shrink-0"></i> تقديم وإدارة الجلسات التعليمية القرآنية والأكاديمية</li>
                    <li class="flex items-start gap-3"><i class="ri-check-line text-green-600 mt-1 flex-shrink-0"></i> تتبع التقدم الدراسي وإعداد التقارير للطلاب وأولياء الأمور</li>
                    <li class="flex items-start gap-3"><i class="ri-check-line text-green-600 mt-1 flex-shrink-0"></i> إرسال إشعارات فورية بمواعيد الجلسات والواجبات والتحديثات</li>
                    <li class="flex items-start gap-3"><i class="ri-check-line text-green-600 mt-1 flex-shrink-0"></i> معالجة مدفوعات الاشتراكات والتحقق من صلاحيتها</li>
                    <li class="flex items-start gap-3"><i class="ri-check-line text-green-600 mt-1 flex-shrink-0"></i> التواصل بين الطلاب والمعلمين وإدارة المنصة</li>
                    <li class="flex items-start gap-3"><i class="ri-check-line text-green-600 mt-1 flex-shrink-0"></i> تحسين جودة الخدمة وإصلاح الأخطاء التقنية</li>
                    <li class="flex items-start gap-3"><i class="ri-check-line text-green-600 mt-1 flex-shrink-0"></i> الامتثال للمتطلبات القانونية والتنظيمية</li>
                </ul>
            </div>

            <!-- Camera & Microphone -->
            <div class="bg-white rounded-2xl p-6 md:p-8 shadow-xl border border-gray-200 mb-6">
                <h2 class="text-xl md:text-2xl font-bold text-gray-900 mb-4 flex items-center gap-3">
                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="ri-camera-line text-xl text-red-600"></i>
                    </div>
                    الكاميرا والميكروفون
                </h2>
                <p class="text-gray-600 leading-relaxed">
                    يطلب التطبيق الوصول إلى الكاميرا والميكروفون <strong>فقط</strong> أثناء الجلسات المباشرة عبر الفيديو.
                    لا يتم تسجيل أي جلسات أو تخزين بيانات الصوت أو الصورة على خوادمنا.
                    يمكنك رفض هذه الصلاحيات أو سحبها في أي وقت من إعدادات هاتفك.
                </p>
            </div>

            <!-- Photos & Files -->
            <div class="bg-white rounded-2xl p-6 md:p-8 shadow-xl border border-gray-200 mb-6">
                <h2 class="text-xl md:text-2xl font-bold text-gray-900 mb-4 flex items-center gap-3">
                    <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="ri-image-line text-xl text-amber-600"></i>
                    </div>
                    مكتبة الصور والملفات
                </h2>
                <p class="text-gray-600 leading-relaxed mb-4">
                    يطلب التطبيق الوصول إلى مكتبة الصور والملفات لأغراض محدودة فقط:
                </p>
                <ul class="space-y-3 text-gray-600">
                    <li class="flex items-start gap-3"><i class="ri-check-line text-green-600 mt-1 flex-shrink-0"></i> رفع صورة الملف الشخصي</li>
                    <li class="flex items-start gap-3"><i class="ri-check-line text-green-600 mt-1 flex-shrink-0"></i> إرسال الواجبات المنزلية والمستندات للمعلم</li>
                </ul>
                <p class="text-gray-600 leading-relaxed mt-4">لا يتم الوصول إلى ملفاتك أو صورك بدون إذنك الصريح.</p>
            </div>

            <!-- Push Notifications -->
            <div class="bg-white rounded-2xl p-6 md:p-8 shadow-xl border border-gray-200 mb-6">
                <h2 class="text-xl md:text-2xl font-bold text-gray-900 mb-4 flex items-center gap-3">
                    <div class="w-10 h-10 bg-teal-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="ri-notification-3-line text-xl text-teal-600"></i>
                    </div>
                    الإشعارات الفورية
                </h2>
                <p class="text-gray-600 leading-relaxed">
                    نستخدم <strong>Firebase Cloud Messaging (FCM)</strong> من Google لإرسال إشعارات فورية
                    تتعلق بمواعيد جلساتك، الواجبات، والتحديثات المهمة.
                    يمكنك إيقاف الإشعارات في أي وقت من إعدادات هاتفك دون أن يؤثر ذلك على استخدام التطبيق.
                </p>
            </div>

            <!-- Third Party Sharing -->
            <div class="bg-white rounded-2xl p-6 md:p-8 shadow-xl border border-gray-200 mb-6">
                <h2 class="text-xl md:text-2xl font-bold text-gray-900 mb-4 flex items-center gap-3">
                    <div class="w-10 h-10 bg-cyan-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="ri-team-line text-xl text-cyan-600"></i>
                    </div>
                    مشاركة البيانات مع أطراف ثالثة
                </h2>
                <p class="text-gray-600 leading-relaxed mb-4">نشارك بياناتك مع الأطراف التالية بشكل محدود وضروري فقط:</p>
                <ul class="space-y-3 text-gray-600">
                    <li class="flex items-start gap-3"><i class="ri-check-line text-green-600 mt-1 flex-shrink-0"></i> <span><strong>Google Firebase:</strong> لإدارة الإشعارات الفورية — <a href="https://policies.google.com/privacy" target="_blank" class="text-blue-600 hover:underline">سياسة خصوصية Google</a></span></li>
                    <li class="flex items-start gap-3"><i class="ri-check-line text-green-600 mt-1 flex-shrink-0"></i> <strong>LiveKit:</strong> لتشغيل الجلسات المباشرة عبر الفيديو</li>
                    <li class="flex items-start gap-3"><i class="ri-check-line text-green-600 mt-1 flex-shrink-0"></i> <strong>Paymob / EasyKash:</strong> لمعالجة المدفوعات بأمان</li>
                    <li class="flex items-start gap-3"><i class="ri-check-line text-green-600 mt-1 flex-shrink-0"></i> <strong>Sentry:</strong> لمراقبة الأخطاء التقنية وتحسين الاستقرار</li>
                </ul>
                <p class="text-gray-600 leading-relaxed mt-4">لا نبيع بياناتك الشخصية لأي طرف ثالث لأغراض تسويقية.</p>
            </div>

            <!-- Data Security -->
            <div class="bg-white rounded-2xl p-6 md:p-8 shadow-xl border border-gray-200 mb-6">
                <h2 class="text-xl md:text-2xl font-bold text-gray-900 mb-4 flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="ri-lock-line text-xl text-green-600"></i>
                    </div>
                    أمان البيانات
                </h2>
                <ul class="space-y-3 text-gray-600">
                    <li class="flex items-start gap-3"><i class="ri-shield-check-line text-green-600 mt-1 flex-shrink-0"></i> جميع البيانات مشفّرة أثناء النقل باستخدام بروتوكول HTTPS/TLS</li>
                    <li class="flex items-start gap-3"><i class="ri-shield-check-line text-green-600 mt-1 flex-shrink-0"></i> بيانات الجلسات والمحادثات محمية بنظام عزل متعدد المستأجرين</li>
                    <li class="flex items-start gap-3"><i class="ri-shield-check-line text-green-600 mt-1 flex-shrink-0"></i> كلمات المرور مشفّرة ولا يمكن لأحد الاطلاع عليها بما فيهم فريقنا</li>
                    <li class="flex items-start gap-3"><i class="ri-shield-check-line text-green-600 mt-1 flex-shrink-0"></i> الوصول إلى البيانات مقيّد بحسب الأدوار والصلاحيات</li>
                    <li class="flex items-start gap-3"><i class="ri-shield-check-line text-green-600 mt-1 flex-shrink-0"></i> خوادمنا محمية بجدران حماية وأنظمة مراقبة مستمرة</li>
                </ul>
            </div>

            <!-- User Rights -->
            <div class="bg-white rounded-2xl p-6 md:p-8 shadow-xl border border-gray-200 mb-6">
                <h2 class="text-xl md:text-2xl font-bold text-gray-900 mb-4 flex items-center gap-3">
                    <div class="w-10 h-10 bg-violet-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="ri-user-heart-line text-xl text-violet-600"></i>
                    </div>
                    حقوقك
                </h2>
                <p class="text-gray-600 leading-relaxed mb-4">لديك الحقوق التالية فيما يتعلق ببياناتك الشخصية:</p>
                <ul class="space-y-3 text-gray-600">
                    <li class="flex items-start gap-3"><i class="ri-check-line text-green-600 mt-1 flex-shrink-0"></i> الاطلاع على بياناتك الشخصية المحفوظة لدينا</li>
                    <li class="flex items-start gap-3"><i class="ri-check-line text-green-600 mt-1 flex-shrink-0"></i> تصحيح أي بيانات غير دقيقة</li>
                    <li class="flex items-start gap-3"><i class="ri-check-line text-green-600 mt-1 flex-shrink-0"></i> طلب حذف حسابك وجميع بياناتك</li>
                    <li class="flex items-start gap-3"><i class="ri-check-line text-green-600 mt-1 flex-shrink-0"></i> الاعتراض على معالجة بياناتك لأغراض معينة</li>
                    <li class="flex items-start gap-3"><i class="ri-check-line text-green-600 mt-1 flex-shrink-0"></i> سحب موافقتك في أي وقت</li>
                </ul>
                <p class="text-gray-600 leading-relaxed mt-4">لممارسة أي من هذه الحقوق، تواصل معنا عبر البريد الإلكتروني أدناه.</p>
            </div>

            <!-- Children's Privacy -->
            <div class="bg-white rounded-2xl p-6 md:p-8 shadow-xl border border-gray-200 mb-6">
                <h2 class="text-xl md:text-2xl font-bold text-gray-900 mb-4 flex items-center gap-3">
                    <div class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="ri-parent-line text-xl text-pink-600"></i>
                    </div>
                    خصوصية الأطفال
                </h2>
                <p class="text-gray-600 leading-relaxed mb-4">
                    منصة إتقان موجّهة للطلاب من جميع الأعمار بما فيهم الأطفال. نلتزم بما يلي:
                </p>
                <ul class="space-y-3 text-gray-600">
                    <li class="flex items-start gap-3"><i class="ri-check-line text-green-600 mt-1 flex-shrink-0"></i> يتطلب تسجيل الأطفال دون 13 عاماً موافقة ولي الأمر</li>
                    <li class="flex items-start gap-3"><i class="ri-check-line text-green-600 mt-1 flex-shrink-0"></i> لا نجمع بيانات تسويقية من الأطفال</li>
                    <li class="flex items-start gap-3"><i class="ri-check-line text-green-600 mt-1 flex-shrink-0"></i> يملك أولياء الأمور صلاحية الاطلاع على بيانات أبنائهم وطلب حذفها</li>
                </ul>
            </div>

            <!-- Policy Updates -->
            <div class="bg-white rounded-2xl p-6 md:p-8 shadow-xl border border-gray-200 mb-6">
                <h2 class="text-xl md:text-2xl font-bold text-gray-900 mb-4 flex items-center gap-3">
                    <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="ri-refresh-line text-xl text-orange-600"></i>
                    </div>
                    التحديثات على هذه السياسة
                </h2>
                <p class="text-gray-600 leading-relaxed">
                    قد نحدّث سياسة الخصوصية هذه من وقت لآخر. سيتم إخطارك بأي تغييرات جوهرية
                    عبر إشعار داخل التطبيق أو بالبريد الإلكتروني. تاريخ آخر تحديث موضّح أدناه.
                </p>
            </div>

            <!-- Contact Us -->
            <div class="bg-white rounded-2xl p-6 md:p-8 shadow-xl border border-gray-200 mb-6">
                <h2 class="text-xl md:text-2xl font-bold text-gray-900 mb-4 flex items-center gap-3">
                    <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="ri-mail-send-line text-xl text-emerald-600"></i>
                    </div>
                    تواصل معنا
                </h2>
                <p class="text-gray-600 leading-relaxed mb-4">لأي استفسارات أو طلبات تتعلق بخصوصيتك:</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                        <strong class="block text-blue-600 mb-2 text-sm">البريد الإلكتروني</strong>
                        <a href="mailto:support@itqanway.com" class="text-gray-600 hover:text-blue-600 transition-colors text-sm">support@itqanway.com</a>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                        <strong class="block text-blue-600 mb-2 text-sm">الموقع الإلكتروني</strong>
                        <a href="https://itqanway.com" class="text-gray-600 hover:text-blue-600 transition-colors text-sm">itqanway.com</a>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                        <strong class="block text-blue-600 mb-2 text-sm">الشركة</strong>
                        <span class="text-gray-600 text-sm">منصة إتقان — المملكة العربية السعودية</span>
                    </div>
                </div>
            </div>

            <!-- Last Updated -->
            <p class="text-center text-gray-400 text-sm mt-8">
                آخر تحديث: {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}
            </p>

        </div>

        <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-green-100 via-teal-100 to-green-100"></div>
    </section>
@endsection
