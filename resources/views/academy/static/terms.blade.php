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
  <title>الشروط والأحكام - {{ $academy->name ?? 'أكاديمية إتقان' }}</title>
  <meta name="description" content="الشروط والأحكام الخاصة بـ{{ $academy->name ?? 'أكاديمية إتقان' }}">
  <script src="https://cdn.tailwindcss.com/3.4.16"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: "{{ $academy->brand_color ?? $academy->primary_color ?? '#4169E1' }}",
            secondary: "{{ $academy->secondary_color ?? '#6495ED' }}",
          },
        },
      },
    };
  </script>
</head>
<body class="bg-gray-50 text-gray-900">
  <!-- Navigation -->
  @include('academy.components.topbar', ['academy' => $academy])

  <!-- Page Header -->
  <div class="bg-gradient-to-r from-{{ $gradientFrom }} to-{{ $gradientTo }} text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center">
        <h1 class="text-4xl font-bold mb-4">الشروط والأحكام</h1>
        <p class="text-xl opacity-90">تعرف على شروط وأحكام استخدام منصة {{ $academy->name ?? 'أكاديمية إتقان' }}</p>
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
            مرحباً بك في {{ $academy->name ?? 'أكاديمية إتقان' }}. باستخدامك لهذه المنصة، فإنك توافق على الالتزام بالشروط والأحكام التالية. يرجى قراءة هذه الشروط بعناية قبل استخدام خدماتنا.
          </p>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">2. التسجيل والحساب</h2>
          <ul class="list-disc list-inside text-gray-700 space-y-3 me-4">
            <li>يجب عليك تقديم معلومات دقيقة وكاملة عند التسجيل</li>
            <li>أنت مسؤول عن الحفاظ على سرية بيانات حسابك</li>
            <li>يجب عليك إخطارنا فوراً بأي استخدام غير مصرح به لحسابك</li>
            <li>يجب أن يكون عمر المستخدم 13 عاماً أو أكثر، أو بموافقة ولي الأمر</li>
          </ul>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">3. استخدام الخدمات</h2>
          <p class="text-gray-700 leading-relaxed mb-4">
            يحق لك استخدام خدماتنا للأغراض التعليمية الشخصية فقط. يُمنع منعاً باتاً:
          </p>
          <ul class="list-disc list-inside text-gray-700 space-y-3 me-4">
            <li>نسخ أو توزيع المحتوى التعليمي دون إذن كتابي</li>
            <li>استخدام الخدمات لأي أغراض غير قانونية أو غير أخلاقية</li>
            <li>محاولة اختراق أو إلحاق الضرر بالمنصة</li>
            <li>انتحال شخصية الآخرين أو تقديم معلومات مضللة</li>
            <li>مشاركة حسابك مع أشخاص آخرين</li>
          </ul>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">4. الرسوم والدفع</h2>
          <ul class="list-disc list-inside text-gray-700 space-y-3 me-4">
            <li>جميع الرسوم معلنة بوضوح على المنصة</li>
            <li>الدفع يتم من خلال وسائل الدفع الآمنة المتاحة</li>
            <li>الرسوم غير قابلة للاسترداد إلا وفقاً لسياسة الاسترجاع الخاصة بنا</li>
            <li>نحتفظ بالحق في تعديل الأسعار مع إشعار مسبق</li>
          </ul>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">5. حقوق الملكية الفكرية</h2>
          <p class="text-gray-700 leading-relaxed mb-4">
            جميع المواد التعليمية والمحتوى على هذه المنصة محمية بحقوق الملكية الفكرية. جميع الحقوق محفوظة لـ{{ $academy->name ?? 'أكاديمية إتقان' }}.
          </p>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">6. سلوك المستخدم</h2>
          <p class="text-gray-700 leading-relaxed mb-4">
            نتوقع من جميع المستخدمين الالتزام بالسلوك الأخلاقي والمهني:
          </p>
          <ul class="list-disc list-inside text-gray-700 space-y-3 me-4">
            <li>احترام المعلمين والطلاب الآخرين</li>
            <li>عدم استخدام لغة مسيئة أو غير لائقة</li>
            <li>الحضور في المواعيد المحددة للحصص</li>
            <li>المشاركة الإيجابية في العملية التعليمية</li>
          </ul>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">7. إلغاء الاشتراك</h2>
          <p class="text-gray-700 leading-relaxed mb-4">
            يمكنك إلغاء اشتراكك في أي وقت من خلال لوحة التحكم الخاصة بك. يُرجى مراجعة سياسة الاسترجاع لمعرفة شروط استرداد المدفوعات.
          </p>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">8. إخلاء المسؤولية</h2>
          <p class="text-gray-700 leading-relaxed mb-4">
            نبذل قصارى جهدنا لتقديم خدمة تعليمية عالية الجودة، لكننا لا نضمن:
          </p>
          <ul class="list-disc list-inside text-gray-700 space-y-3 me-4">
            <li>نتائج محددة من استخدام الخدمة</li>
            <li>عدم انقطاع الخدمة أو خلوها من الأخطاء</li>
            <li>توافر الخدمة في جميع الأوقات</li>
          </ul>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">9. التعديلات على الشروط</h2>
          <p class="text-gray-700 leading-relaxed mb-4">
            نحتفظ بالحق في تعديل هذه الشروط والأحكام في أي وقت. سيتم إخطار المستخدمين بأي تغييرات جوهرية عبر البريد الإلكتروني أو من خلال المنصة.
          </p>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">10. القانون الواجب التطبيق</h2>
          <p class="text-gray-700 leading-relaxed mb-4">
            تخضع هذه الشروط والأحكام لقوانين المملكة العربية السعودية، وتُحل أي نزاعات وفقاً لهذه القوانين.
          </p>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">11. التواصل معنا</h2>
          <p class="text-gray-700 leading-relaxed mb-4">
            إذا كان لديك أي أسئلة حول هذه الشروط والأحكام، يرجى التواصل معنا عبر:
          </p>
          <div class="bg-gray-50 p-6 rounded-lg">
            <p class="text-gray-700 mb-2"><strong>البريد الإلكتروني:</strong> {{ $academy->email ?? 'info@itqan-academy.com' }}</p>
            <p class="text-gray-700 mb-2"><strong>الهاتف:</strong> {{ $academy->phone ?? '+966 11 234 5678' }}</p>
            <p class="text-gray-700"><strong>العنوان:</strong> {{ $academy->address ?? 'الرياض، المملكة العربية السعودية' }}</p>
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
