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
  <title>سياسة الخصوصية - {{ $academy->name ?? 'أكاديمية إتقان' }}</title>
  <meta name="description" content="سياسة الخصوصية وحماية البيانات في {{ $academy->name ?? 'أكاديمية إتقان' }}">
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
        <h1 class="text-4xl font-bold mb-4">سياسة الخصوصية</h1>
        <p class="text-xl opacity-90">حماية بياناتك وخصوصيتك هي أولويتنا في {{ $academy->name ?? 'أكاديمية إتقان' }}</p>
      </div>
    </div>
  </div>

  <!-- Content -->
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="bg-white rounded-2xl shadow-lg p-8 md:p-12">

      <div class="prose prose-lg max-w-none text-right" dir="rtl">

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">1. المقدمة</h2>
          <p class="text-gray-700 leading-relaxed mb-4">
            في {{ $academy->name ?? 'أكاديمية إتقان' }}، نحن ملتزمون بحماية خصوصيتك وبياناتك الشخصية. توضح هذه السياسة كيفية جمع واستخدام وحماية معلوماتك عند استخدامك لخدماتنا التعليمية.
          </p>
          <div class="bg-blue-50 border-r-4 border-primary p-6 rounded-lg">
            <p class="text-gray-800">
              <i class="ri-shield-check-line text-primary text-xl ms-2"></i>
              نلتزم بأعلى معايير حماية البيانات ونحترم خصوصيتك بشكل كامل
            </p>
          </div>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">2. البيانات التي نجمعها</h2>

          <h3 class="text-xl font-bold text-gray-800 mb-3 mt-6">2.1 المعلومات الشخصية</h3>
          <ul class="list-disc list-inside text-gray-700 space-y-2 mr-4 mb-6">
            <li>الاسم الكامل</li>
            <li>عنوان البريد الإلكتروني</li>
            <li>رقم الهاتف</li>
            <li>تاريخ الميلاد (للطلاب)</li>
            <li>معلومات الدفع (معالجة عبر بوابات آمنة)</li>
            <li>الصورة الشخصية (اختياري)</li>
          </ul>

          <h3 class="text-xl font-bold text-gray-800 mb-3 mt-6">2.2 المعلومات الأكاديمية</h3>
          <ul class="list-disc list-inside text-gray-700 space-y-2 mr-4 mb-6">
            <li>المستوى التعليمي</li>
            <li>الدورات والحلقات المسجلة</li>
            <li>سجل الحضور والغياب</li>
            <li>النتائج والتقييمات</li>
            <li>التقدم الأكاديمي</li>
          </ul>

          <h3 class="text-xl font-bold text-gray-800 mb-3 mt-6">2.3 معلومات الاستخدام التقني</h3>
          <ul class="list-disc list-inside text-gray-700 space-y-2 me-4">
            <li>عنوان IP</li>
            <li>نوع المتصفح والجهاز</li>
            <li>نظام التشغيل</li>
            <li>سجلات الدخول والنشاط</li>
            <li>ملفات تعريف الارتباط (Cookies)</li>
          </ul>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">3. كيفية استخدام بياناتك</h2>
          <p class="text-gray-700 leading-relaxed mb-4">نستخدم معلوماتك للأغراض التالية:</p>

          <div class="space-y-4">
            <div class="bg-gray-50 p-4 rounded-lg">
              <h4 class="font-bold text-gray-800 mb-2 flex items-center">
                <i class="ri-user-line text-primary ms-2"></i>
                إدارة الحساب
              </h4>
              <p class="text-gray-700 text-sm">إنشاء وإدارة حسابك وتوفير الوصول إلى الخدمات التعليمية</p>
            </div>

            <div class="bg-gray-50 p-4 rounded-lg">
              <h4 class="font-bold text-gray-800 mb-2 flex items-center">
                <i class="ri-book-open-line text-primary ms-2"></i>
                تقديم الخدمات التعليمية
              </h4>
              <p class="text-gray-700 text-sm">تنظيم الحصص، تتبع التقدم، وتوفير المواد التعليمية</p>
            </div>

            <div class="bg-gray-50 p-4 rounded-lg">
              <h4 class="font-bold text-gray-800 mb-2 flex items-center">
                <i class="ri-mail-line text-primary ms-2"></i>
                التواصل
              </h4>
              <p class="text-gray-700 text-sm">إرسال التحديثات، الإشعارات، والمعلومات المهمة عن الدورات</p>
            </div>

            <div class="bg-gray-50 p-4 rounded-lg">
              <h4 class="font-bold text-gray-800 mb-2 flex items-center">
                <i class="ri-bank-card-line text-primary ms-2"></i>
                معالجة المدفوعات
              </h4>
              <p class="text-gray-700 text-sm">إتمام المعاملات المالية وإصدار الفواتير</p>
            </div>

            <div class="bg-gray-50 p-4 rounded-lg">
              <h4 class="font-bold text-gray-800 mb-2 flex items-center">
                <i class="ri-bar-chart-line text-primary ms-2"></i>
                تحسين الخدمات
              </h4>
              <p class="text-gray-700 text-sm">تحليل الاستخدام لتطوير وتحسين جودة الخدمات التعليمية</p>
            </div>

            <div class="bg-gray-50 p-4 rounded-lg">
              <h4 class="font-bold text-gray-800 mb-2 flex items-center">
                <i class="ri-shield-check-line text-primary ms-2"></i>
                الأمان والامتثال
              </h4>
              <p class="text-gray-700 text-sm">ضمان أمان المنصة والامتثال للمتطلبات القانونية</p>
            </div>
          </div>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">4. مشاركة البيانات</h2>
          <p class="text-gray-700 leading-relaxed mb-4">
            نحن لا نبيع أو نؤجر معلوماتك الشخصية لأطراف ثالثة. قد نشارك بياناتك فقط في الحالات التالية:
          </p>

          <ul class="list-disc list-inside text-gray-700 space-y-3 me-4">
            <li><strong>المعلمون:</strong> نشارك المعلومات الضرورية مع المعلمين لتقديم الخدمة التعليمية</li>
            <li><strong>مزودو الخدمات:</strong> شركات معالجة الدفع، الاستضافة، والخدمات التقنية الموثوقة</li>
            <li><strong>المتطلبات القانونية:</strong> عند الطلب من الجهات الرسمية وفقاً للقانون</li>
            <li><strong>ولي الأمر:</strong> للطلاب القُصَّر، نشارك المعلومات مع أولياء الأمور</li>
          </ul>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">5. حماية البيانات</h2>
          <p class="text-gray-700 leading-relaxed mb-4">
            نتخذ إجراءات أمنية صارمة لحماية معلوماتك:
          </p>

          <div class="grid md:grid-cols-2 gap-4">
            <div class="bg-green-50 p-5 rounded-lg">
              <i class="ri-lock-line text-green-600 text-2xl mb-2"></i>
              <h4 class="font-bold text-gray-800 mb-1">التشفير</h4>
              <p class="text-gray-700 text-sm">جميع البيانات الحساسة مشفرة باستخدام SSL/TLS</p>
            </div>

            <div class="bg-blue-50 p-5 rounded-lg">
              <i class="ri-server-line text-blue-600 text-2xl mb-2"></i>
              <h4 class="font-bold text-gray-800 mb-1">خوادم آمنة</h4>
              <p class="text-gray-700 text-sm">نستخدم خوادم محمية بجدران نارية متقدمة</p>
            </div>

            <div class="bg-purple-50 p-5 rounded-lg">
              <i class="ri-key-line text-purple-600 text-2xl mb-2"></i>
              <h4 class="font-bold text-gray-800 mb-1">المصادقة الآمنة</h4>
              <p class="text-gray-700 text-sm">كلمات مرور قوية ونظام مصادقة متعدد العوامل</p>
            </div>

            <div class="bg-orange-50 p-5 rounded-lg">
              <i class="ri-eye-line text-orange-600 text-2xl mb-2"></i>
              <h4 class="font-bold text-gray-800 mb-1">المراقبة المستمرة</h4>
              <p class="text-gray-700 text-sm">مراقبة دائمة للأنشطة المشبوهة والتهديدات</p>
            </div>
          </div>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">6. حقوقك</h2>
          <p class="text-gray-700 leading-relaxed mb-4">لديك الحقوق التالية فيما يتعلق ببياناتك:</p>

          <div class="space-y-3">
            <div class="flex items-start">
              <i class="ri-checkbox-circle-fill text-primary text-xl ms-3 mt-1"></i>
              <div>
                <h4 class="font-bold text-gray-800">حق الوصول</h4>
                <p class="text-gray-700 text-sm">يمكنك طلب نسخة من بياناتك الشخصية</p>
              </div>
            </div>

            <div class="flex items-start">
              <i class="ri-checkbox-circle-fill text-primary text-xl ms-3 mt-1"></i>
              <div>
                <h4 class="font-bold text-gray-800">حق التصحيح</h4>
                <p class="text-gray-700 text-sm">يمكنك تصحيح أي معلومات غير دقيقة</p>
              </div>
            </div>

            <div class="flex items-start">
              <i class="ri-checkbox-circle-fill text-primary text-xl ms-3 mt-1"></i>
              <div>
                <h4 class="font-bold text-gray-800">حق الحذف</h4>
                <p class="text-gray-700 text-sm">يمكنك طلب حذف بياناتك (مع مراعاة الالتزامات القانونية)</p>
              </div>
            </div>

            <div class="flex items-start">
              <i class="ri-checkbox-circle-fill text-primary text-xl ms-3 mt-1"></i>
              <div>
                <h4 class="font-bold text-gray-800">حق الاعتراض</h4>
                <p class="text-gray-700 text-sm">يمكنك الاعتراض على معالجة بياناتك لأغراض معينة</p>
              </div>
            </div>

            <div class="flex items-start">
              <i class="ri-checkbox-circle-fill text-primary text-xl ms-3 mt-1"></i>
              <div>
                <h4 class="font-bold text-gray-800">حق نقل البيانات</h4>
                <p class="text-gray-700 text-sm">يمكنك الحصول على بياناتك بتنسيق قابل للنقل</p>
              </div>
            </div>
          </div>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">7. ملفات تعريف الارتباط (Cookies)</h2>
          <p class="text-gray-700 leading-relaxed mb-4">
            نستخدم ملفات تعريف الارتباط لتحسين تجربتك على المنصة:
          </p>
          <ul class="list-disc list-inside text-gray-700 space-y-2 me-4">
            <li><strong>ملفات ضرورية:</strong> للحفاظ على تسجيل دخولك وإعدادات الأمان</li>
            <li><strong>ملفات التفضيلات:</strong> لحفظ اختياراتك وإعداداتك</li>
            <li><strong>ملفات التحليل:</strong> لفهم كيفية استخدامك للمنصة وتحسينها</li>
          </ul>
          <p class="text-gray-700 leading-relaxed mt-4">
            يمكنك التحكم في ملفات تعريف الارتباط من خلال إعدادات المتصفح الخاص بك.
          </p>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">8. الاحتفاظ بالبيانات</h2>
          <div class="bg-gray-50 p-6 rounded-lg">
            <p class="text-gray-700 leading-relaxed mb-3">
              نحتفظ بمعلوماتك طالما كان حسابك نشطاً أو حسب الحاجة لتقديم الخدمات.
            </p>
            <ul class="list-disc list-inside text-gray-700 space-y-2 me-4">
              <li>البيانات الأكاديمية: لمدة 5 سنوات بعد انتهاء الدراسة</li>
              <li>السجلات المالية: وفقاً للمتطلبات القانونية (عادة 7 سنوات)</li>
              <li>معلومات الحساب: حتى طلب الحذف أو إلغاء الحساب</li>
            </ul>
          </div>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">9. خصوصية الأطفال</h2>
          <div class="bg-yellow-50 border-r-4 border-yellow-500 p-6 rounded-lg">
            <p class="text-gray-800 mb-3">
              <i class="ri-parent-line text-yellow-600 text-xl ms-2"></i>
              نحن ملتزمون بحماية خصوصية الأطفال القُصَّر (أقل من 18 عاماً)
            </p>
            <ul class="list-disc list-inside text-gray-700 space-y-2 me-4">
              <li>نطلب موافقة ولي الأمر قبل جمع بيانات الطفل</li>
              <li>يمكن لولي الأمر الوصول إلى بيانات الطفل وإدارتها</li>
              <li>نجمع الحد الأدنى من البيانات الضرورية</li>
            </ul>
          </div>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">10. التعديلات على السياسة</h2>
          <p class="text-gray-700 leading-relaxed mb-4">
            قد نقوم بتحديث سياسة الخصوصية من وقت لآخر. سنخطرك بأي تغييرات جوهرية عبر:
          </p>
          <ul class="list-disc list-inside text-gray-700 space-y-2 me-4">
            <li>إشعار على المنصة</li>
            <li>رسالة بريد إلكتروني</li>
            <li>تحديث تاريخ "آخر تحديث" في أسفل هذه الصفحة</li>
          </ul>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">11. التواصل معنا</h2>
          <p class="text-gray-700 leading-relaxed mb-4">
            لأي استفسارات حول سياسة الخصوصية أو لممارسة حقوقك، تواصل معنا:
          </p>
          <div class="bg-gray-50 p-6 rounded-lg">
            <p class="text-gray-700 mb-2"><strong>مسؤول حماية البيانات:</strong></p>
            <p class="text-gray-700 mb-2"><strong>البريد الإلكتروني:</strong> privacy@{{ $academy->email ?? 'itqan-academy.com' }}</p>
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
