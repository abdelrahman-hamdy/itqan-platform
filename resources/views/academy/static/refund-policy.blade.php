@php
    // Get gradient palette for this academy
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $colors = $gradientPalette->getColors();
    $gradientFrom = $colors['from'];
    $gradientTo = $colors['to'];
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>سياسة الاسترجاع - {{ $academy->name ?? 'أكاديمية إتقان' }}</title>
  <meta name="description" content="سياسة الاسترجاع والاستبدال الخاصة بـ{{ $academy->name ?? 'أكاديمية إتقان' }}">
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
        <h1 class="text-4xl font-bold mb-4">سياسة الاسترجاع</h1>
        <p class="text-xl opacity-90">تعرف على سياسة استرداد المدفوعات في {{ $academy->name ?? 'أكاديمية إتقان' }}</p>
      </div>
    </div>
  </div>

  <!-- Content -->
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="bg-white rounded-2xl shadow-lg p-8 md:p-12">

      <div class="prose prose-lg max-w-none text-right" dir="rtl">

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">1. نظرة عامة</h2>
          <p class="text-gray-700 leading-relaxed mb-4">
            في {{ $academy->name ?? 'أكاديمية إتقان' }}، نلتزم بتقديم تجربة تعليمية متميزة. نتفهم أن الظروف قد تتغير، ولذلك وضعنا سياسة واضحة للاسترجاع والاستبدال.
          </p>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">2. فترة الاسترجاع</h2>
          <div class="bg-blue-50 border-r-4 border-primary p-6 rounded-lg mb-4">
            <p class="text-gray-800 font-semibold mb-2">
              <i class="ri-time-line text-primary ml-2"></i>
              يمكنك طلب استرداد كامل المبلغ خلال 7 أيام من تاريخ الاشتراك
            </p>
            <p class="text-gray-600 text-sm">
              شريطة عدم حضور أكثر من حصتين تعليميتين
            </p>
          </div>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">3. شروط الاسترجاع</h2>

          <h3 class="text-xl font-bold text-gray-800 mb-3 mt-6">3.1 الاسترجاع الكامل (100%)</h3>
          <p class="text-gray-700 leading-relaxed mb-3">يمكن الحصول على استرداد كامل في الحالات التالية:</p>
          <ul class="list-disc list-inside text-gray-700 space-y-2 mr-4">
            <li>طلب الإلغاء خلال 7 أيام من تاريخ الاشتراك</li>
            <li>عدم حضور أكثر من حصتين</li>
            <li>وجود مشكلة تقنية من جانبنا تمنع تقديم الخدمة</li>
            <li>إلغاء الدورة من قبل الأكاديمية</li>
          </ul>

          <h3 class="text-xl font-bold text-gray-800 mb-3 mt-6">3.2 الاسترجاع الجزئي (50%)</h3>
          <p class="text-gray-700 leading-relaxed mb-3">يمكن الحصول على استرداد 50% في الحالات التالية:</p>
          <ul class="list-disc list-inside text-gray-700 space-y-2 mr-4">
            <li>طلب الإلغاء بعد 7 أيام وقبل 14 يوماً من تاريخ الاشتراك</li>
            <li>حضور ما لا يزيد عن 25% من إجمالي الحصص المدفوعة</li>
          </ul>

          <h3 class="text-xl font-bold text-gray-800 mb-3 mt-6">3.3 لا يحق الاسترجاع في الحالات التالية:</h3>
          <ul class="list-disc list-inside text-gray-700 space-y-2 mr-4">
            <li>مرور أكثر من 14 يوماً على الاشتراك</li>
            <li>حضور أكثر من 25% من الحصص</li>
            <li>إتمام الدورة أو الكورس بالكامل</li>
            <li>مخالفة شروط وأحكام الاستخدام</li>
          </ul>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">4. سياسة استرجاع الكورسات المسجلة</h2>
          <div class="bg-yellow-50 border-r-4 border-yellow-500 p-6 rounded-lg mb-4">
            <p class="text-gray-800 mb-2">
              <strong>الكورسات المسجلة:</strong> يمكن استرداد المبلغ كاملاً خلال 3 أيام من تاريخ الشراء، شريطة عدم مشاهدة أكثر من 10% من محتوى الكورس.
            </p>
          </div>
          <p class="text-gray-700 leading-relaxed">
            بعد مرور 3 أيام أو مشاهدة أكثر من 10% من المحتوى، لا يمكن استرداد المبلغ.
          </p>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">5. كيفية طلب الاسترجاع</h2>
          <div class="space-y-4">
            <div class="flex items-start">
              <div class="flex-shrink-0 w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center font-bold ml-4">
                1
              </div>
              <div>
                <h4 class="font-bold text-gray-800 mb-1">تسجيل الدخول</h4>
                <p class="text-gray-700">قم بتسجيل الدخول إلى حسابك على المنصة</p>
              </div>
            </div>

            <div class="flex items-start">
              <div class="flex-shrink-0 w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center font-bold ml-4">
                2
              </div>
              <div>
                <h4 class="font-bold text-gray-800 mb-1">التواصل مع الدعم</h4>
                <p class="text-gray-700">تواصل مع فريق الدعم عبر البريد الإلكتروني أو نموذج الاتصال</p>
              </div>
            </div>

            <div class="flex items-start">
              <div class="flex-shrink-0 w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center font-bold ml-4">
                3
              </div>
              <div>
                <h4 class="font-bold text-gray-800 mb-1">تقديم التفاصيل</h4>
                <p class="text-gray-700">قدم رقم الاشتراك وسبب طلب الاسترجاع</p>
              </div>
            </div>

            <div class="flex items-start">
              <div class="flex-shrink-0 w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center font-bold ml-4">
                4
              </div>
              <div>
                <h4 class="font-bold text-gray-800 mb-1">المراجعة والموافقة</h4>
                <p class="text-gray-700">سيتم مراجعة طلبك خلال 3-5 أيام عمل</p>
              </div>
            </div>

            <div class="flex items-start">
              <div class="flex-shrink-0 w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center font-bold ml-4">
                5
              </div>
              <div>
                <h4 class="font-bold text-gray-800 mb-1">استرداد المبلغ</h4>
                <p class="text-gray-700">سيتم استرداد المبلغ خلال 7-14 يوم عمل إلى نفس وسيلة الدفع</p>
              </div>
            </div>
          </div>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">6. الحالات الاستثنائية</h2>
          <p class="text-gray-700 leading-relaxed mb-4">
            نحن نتفهم أن هناك ظروفاً استثنائية قد تحدث. في الحالات التالية، قد ننظر في طلبات الاسترجاع خارج السياسة المعتادة:
          </p>
          <ul class="list-disc list-inside text-gray-700 space-y-2 mr-4">
            <li>الحالات الطبية الطارئة (مع تقديم ما يثبت ذلك)</li>
            <li>الظروف العائلية القاهرة</li>
            <li>مشاكل تقنية مستمرة من جانبنا</li>
            <li>عدم توافق مستوى الطالب مع المنهج (بناءً على تقييم المعلم)</li>
          </ul>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">7. سياسة التحويل بين الدورات</h2>
          <p class="text-gray-700 leading-relaxed mb-4">
            بدلاً من طلب الاسترجاع، يمكنك تحويل اشتراكك إلى دورة أخرى في الأكاديمية:
          </p>
          <ul class="list-disc list-inside text-gray-700 space-y-2 mr-4">
            <li>مجاناً خلال أول 7 أيام من الاشتراك</li>
            <li>مع رسوم إدارية بسيطة (50 ريال) بعد 7 أيام</li>
            <li>يمكن التحويل مرة واحدة فقط لكل اشتراك</li>
          </ul>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">8. مدة معالجة الاسترجاع</h2>
          <div class="bg-gray-50 p-6 rounded-lg">
            <ul class="space-y-3 text-gray-700">
              <li class="flex items-center">
                <i class="ri-checkbox-circle-fill text-green-500 ml-3"></i>
                <span><strong>مراجعة الطلب:</strong> 3-5 أيام عمل</span>
              </li>
              <li class="flex items-center">
                <i class="ri-checkbox-circle-fill text-green-500 ml-3"></i>
                <span><strong>استرداد المبلغ:</strong> 7-14 يوم عمل بعد الموافقة</span>
              </li>
              <li class="flex items-center">
                <i class="ri-checkbox-circle-fill text-green-500 ml-3"></i>
                <span><strong>وصول المبلغ للبنك:</strong> قد يستغرق 3-5 أيام إضافية حسب البنك</span>
              </li>
            </ul>
          </div>
        </section>

        <section class="mb-10">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">9. التواصل معنا</h2>
          <p class="text-gray-700 leading-relaxed mb-4">
            لأي استفسارات حول سياسة الاسترجاع أو لتقديم طلب استرداد، يرجى التواصل معنا:
          </p>
          <div class="bg-gray-50 p-6 rounded-lg">
            <p class="text-gray-700 mb-2"><strong>البريد الإلكتروني:</strong> {{ $academy->email ?? 'info@itqan-academy.com' }}</p>
            <p class="text-gray-700 mb-2"><strong>الهاتف:</strong> {{ $academy->phone ?? '+966 11 234 5678' }}</p>
            <p class="text-gray-700"><strong>ساعات العمل:</strong> الأحد - الخميس، 9 صباحاً - 5 مساءً</p>
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
        <i class="ri-arrow-right-line ml-2"></i>
        العودة للصفحة الرئيسية
      </a>
    </div>
  </div>

  <!-- Footer -->
  <x-academy-footer :academy="$academy" />

</body>
</html>
