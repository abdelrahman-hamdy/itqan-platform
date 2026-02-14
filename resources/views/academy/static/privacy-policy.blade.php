@php
    // Get gradient palette for this academy
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $colors = $gradientPalette->getColors();
    $gradientFrom = $colors['from'];
    $gradientTo = $colors['to'];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>سياسة الخصوصية - {{ $academy->name ?? 'أكاديمية مَعِين' }}</title>
  <meta name="description" content="سياسة الخصوصية الخاصة بـ{{ $academy->name ?? 'أكاديمية مَعِين' }}">

  <!-- Fonts -->
  @include('partials.fonts')

  <!-- Vite Assets (includes RemixIcon & Flag-icons) -->
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  <style>
    :root {
      --color-primary-500: {{ $academy->brand_color?->getHexValue(500) ?? '#4169E1' }};
      --color-secondary-500: {{ $academy->secondary_color?->getHexValue(500) ?? '#6495ED' }};
    }
  </style>
</head>
<body class="bg-gray-50 text-gray-900">
  <!-- Navigation -->
  @include('academy.components.topbar', ['academy' => $academy])

  <!-- Page Header -->
  <div class="bg-gradient-to-r from-{{ $gradientFrom }} to-{{ $gradientTo }} text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center">
        <h1 class="text-4xl font-bold mb-4">سياسة الخصوصية</h1>
        <p class="text-xl opacity-90">تعرف على كيفية حماية بياناتك في {{ $academy->name ?? 'أكاديمية مَعِين' }}</p>
      </div>
    </div>
  </div>

  <!-- Content -->
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="bg-white rounded-2xl shadow-lg p-8 md:p-12">

      <div class="prose prose-lg max-w-none text-right" dir="rtl">

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">1. مقدمة</h2>
          <p class="text-gray-700 leading-relaxed mb-4">
            نحن في {{ $academy->name ?? 'أكاديمية مَعِين' }} نلتزم بحماية خصوصيتك وبياناتك الشخصية. توضح هذه السياسة كيفية جمع واستخدام وحماية المعلومات التي تقدمها لنا عند استخدام منصتنا التعليمية.
          </p>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">2. المعلومات التي نجمعها</h2>
          <p class="text-gray-700 leading-relaxed mb-4">نقوم بجمع الأنواع التالية من المعلومات:</p>
          <ul class="list-disc list-inside text-gray-700 space-y-3 me-4">
            <li>معلومات التسجيل: الاسم، البريد الإلكتروني، رقم الهاتف</li>
            <li>معلومات الملف الشخصي: الصورة الشخصية، تاريخ الميلاد، الجنس</li>
            <li>معلومات الدفع: بيانات البطاقة الائتمانية (تُعالج عبر بوابات دفع آمنة)</li>
            <li>بيانات الاستخدام: سجل الحضور، التقدم الدراسي، تفاعلات المنصة</li>
            <li>معلومات تقنية: عنوان IP، نوع المتصفح، نظام التشغيل</li>
          </ul>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">3. كيفية استخدام المعلومات</h2>
          <p class="text-gray-700 leading-relaxed mb-4">نستخدم المعلومات المجمعة للأغراض التالية:</p>
          <ul class="list-disc list-inside text-gray-700 space-y-3 me-4">
            <li>تقديم الخدمات التعليمية وإدارة حسابك</li>
            <li>تحسين تجربة المستخدم وتطوير المنصة</li>
            <li>إرسال إشعارات متعلقة بالحصص والدروس</li>
            <li>معالجة المدفوعات والاشتراكات</li>
            <li>التواصل معك بشأن تحديثات الخدمة</li>
            <li>ضمان أمان المنصة ومنع الاحتيال</li>
          </ul>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">4. حماية البيانات</h2>
          <p class="text-gray-700 leading-relaxed mb-4">
            نتخذ إجراءات أمنية صارمة لحماية بياناتك:
          </p>
          <ul class="list-disc list-inside text-gray-700 space-y-3 me-4">
            <li>تشفير البيانات أثناء النقل والتخزين باستخدام بروتوكولات أمان متقدمة</li>
            <li>حماية الوصول إلى البيانات بأنظمة مصادقة متعددة</li>
            <li>مراقبة مستمرة للأنظمة لكشف أي محاولات اختراق</li>
            <li>نسخ احتياطي منتظم للبيانات</li>
          </ul>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">5. مشاركة البيانات</h2>
          <p class="text-gray-700 leading-relaxed mb-4">
            لا نبيع أو نؤجر بياناتك الشخصية لأطراف ثالثة. قد نشارك بياناتك فقط في الحالات التالية:
          </p>
          <ul class="list-disc list-inside text-gray-700 space-y-3 me-4">
            <li>مع المعلمين المعنيين لتقديم الخدمة التعليمية</li>
            <li>مع أولياء الأمور فيما يخص بيانات أبنائهم</li>
            <li>مع مزودي خدمات الدفع لمعالجة المدفوعات</li>
            <li>عند الاقتضاء بموجب القانون أو بأمر قضائي</li>
          </ul>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">6. ملفات تعريف الارتباط (Cookies)</h2>
          <p class="text-gray-700 leading-relaxed mb-4">
            نستخدم ملفات تعريف الارتباط لتحسين تجربة التصفح وتذكر تفضيلاتك. يمكنك التحكم في إعدادات ملفات تعريف الارتباط من خلال متصفحك.
          </p>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">7. حقوقك</h2>
          <p class="text-gray-700 leading-relaxed mb-4">لديك الحقوق التالية فيما يتعلق ببياناتك الشخصية:</p>
          <ul class="list-disc list-inside text-gray-700 space-y-3 me-4">
            <li>الحق في الوصول إلى بياناتك الشخصية</li>
            <li>الحق في تصحيح البيانات غير الدقيقة</li>
            <li>الحق في طلب حذف بياناتك</li>
            <li>الحق في الاعتراض على معالجة بياناتك</li>
            <li>الحق في نقل بياناتك إلى خدمة أخرى</li>
          </ul>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">8. حماية بيانات الأطفال</h2>
          <p class="text-gray-700 leading-relaxed mb-4">
            نولي اهتماماً خاصاً بحماية بيانات المستخدمين دون سن 13 عاماً. لا يمكن لهؤلاء المستخدمين التسجيل إلا بموافقة ولي الأمر، ونقوم بجمع الحد الأدنى من البيانات اللازمة لتقديم الخدمة.
          </p>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">9. التعديلات على السياسة</h2>
          <p class="text-gray-700 leading-relaxed mb-4">
            نحتفظ بالحق في تعديل سياسة الخصوصية هذه في أي وقت. سيتم إخطارك بأي تغييرات جوهرية عبر البريد الإلكتروني أو من خلال إشعار على المنصة.
          </p>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">10. التواصل معنا</h2>
          <p class="text-gray-700 leading-relaxed mb-4">
            إذا كان لديك أي أسئلة حول سياسة الخصوصية، يرجى التواصل معنا عبر:
          </p>
          <div class="bg-gray-50 p-6 rounded-lg">
            <p class="text-gray-700 mb-2"><strong>البريد الإلكتروني:</strong> {{ $academy->email ?? config('app.contact_email', 'info@itqanway.com') }}</p>
            <p class="text-gray-700 mb-2"><strong>الهاتف:</strong> {{ $academy->phone ?? config('app.contact_phone', '+966 50 123 4567') }}</p>
            <p class="text-gray-700"><strong>العنوان:</strong> {{ $academy->address ?? config('app.contact_address', 'الرياض، المملكة العربية السعودية') }}</p>
          </div>
        </section>

        <div class="mt-12 pt-8 border-t border-gray-200">
          <p class="text-gray-600 text-sm">
            تاريخ آخر تحديث: {{ date('Y/m/d') }}
          </p>
        </div>

      </div>

    </div>

    <!-- Back to Home Button -->
    <div class="mt-8 text-center">
      <a href="{{ route('academy.home', ['subdomain' => $academy->subdomain]) }}" class="inline-flex items-center px-6 py-3 bg-primary text-white rounded-lg hover:opacity-90 transition-opacity">
        <i class="ri-arrow-right-line ms-2"></i>
        العودة للصفحة الرئيسية
      </a>
    </div>
  </div>

  <!-- Footer -->
  <x-academy-footer :academy="$academy" />

</body>
</html>
