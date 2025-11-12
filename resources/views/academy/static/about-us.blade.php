<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>من نحن - {{ $academy->name ?? 'أكاديمية إتقان' }}</title>
  <meta name="description" content="تعرف على {{ $academy->name ?? 'أكاديمية إتقان' }} ورؤيتنا ورسالتنا في مجال التعليم">
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
  <div class="bg-gradient-to-r from-primary to-secondary text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center">
        <h1 class="text-4xl font-bold mb-4">من نحن</h1>
        <p class="text-xl opacity-90">رحلتنا في تقديم تعليم متميز وشامل</p>
      </div>
    </div>
  </div>

  <!-- Content -->
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

    <!-- Introduction -->
    <div class="bg-white rounded-2xl shadow-lg p-8 md:p-12 mb-12">
      <div class="text-center mb-12">
        <div class="w-24 h-24 bg-gradient-to-br from-primary to-secondary rounded-2xl flex items-center justify-center mx-auto mb-6">
          <i class="ri-book-open-line text-4xl text-white"></i>
        </div>
        <h2 class="text-3xl font-bold text-gray-900 mb-4">{{ $academy->name ?? 'أكاديمية إتقان' }}</h2>
        <p class="text-xl text-gray-700 leading-relaxed max-w-3xl mx-auto">
          {{ $academy->description ?? 'منصة تعليمية شاملة تهدف إلى تقديم أفضل تجربة تعليمية في القرآن الكريم والمواد الأكاديمية من خلال معلمين مؤهلين وتقنيات تعليمية حديثة' }}
        </p>
      </div>
    </div>

    <!-- Vision and Mission -->
    <div class="grid md:grid-cols-2 gap-8 mb-12">
      <!-- Vision -->
      <div class="bg-white rounded-2xl shadow-lg p-8">
        <div class="w-16 h-16 bg-blue-100 rounded-xl flex items-center justify-center mb-6">
          <i class="ri-eye-line text-3xl text-primary"></i>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 mb-4">رؤيتنا</h3>
        <p class="text-gray-700 leading-relaxed">
          أن نكون المنصة التعليمية الرائدة في المنطقة، نجمع بين التعليم القرآني الأصيل والتعليم الأكاديمي المتميز، مع الاستفادة من أحدث التقنيات التعليمية لتوفير تجربة تعليمية شاملة ومتطورة تلبي احتياجات جميع الطلاب.
        </p>
      </div>

      <!-- Mission -->
      <div class="bg-white rounded-2xl shadow-lg p-8">
        <div class="w-16 h-16 bg-green-100 rounded-xl flex items-center justify-center mb-6">
          <i class="ri-compass-line text-3xl text-green-600"></i>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 mb-4">رسالتنا</h3>
        <p class="text-gray-700 leading-relaxed">
          تمكين الطلاب من تحقيق أهدافهم التعليمية من خلال توفير تعليم عالي الجودة، معلمين متميزين، ومنهجيات تدريس حديثة. نسعى لبناء جيل متعلم ومتميز، يحفظ القرآن ويتقن العلوم الأكاديمية، في بيئة تعليمية آمنة ومحفزة.
        </p>
      </div>
    </div>

    <!-- Our Values -->
    <div class="bg-white rounded-2xl shadow-lg p-8 md:p-12 mb-12">
      <h3 class="text-3xl font-bold text-gray-900 mb-8 text-center">قيمنا</h3>
      <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="text-center">
          <div class="w-16 h-16 bg-purple-100 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="ri-star-line text-3xl text-purple-600"></i>
          </div>
          <h4 class="text-lg font-bold text-gray-900 mb-2">التميز</h4>
          <p class="text-gray-600 text-sm">نسعى دائماً للتميز في كل ما نقدمه</p>
        </div>

        <div class="text-center">
          <div class="w-16 h-16 bg-blue-100 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="ri-shield-check-line text-3xl text-blue-600"></i>
          </div>
          <h4 class="text-lg font-bold text-gray-900 mb-2">الأمانة</h4>
          <p class="text-gray-600 text-sm">نلتزم بالأمانة والمصداقية في التعامل</p>
        </div>

        <div class="text-center">
          <div class="w-16 h-16 bg-green-100 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="ri-heart-line text-3xl text-green-600"></i>
          </div>
          <h4 class="text-lg font-bold text-gray-900 mb-2">الشغف</h4>
          <p class="text-gray-600 text-sm">شغف حقيقي بالتعليم وتطوير الطلاب</p>
        </div>

        <div class="text-center">
          <div class="w-16 h-16 bg-orange-100 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="ri-rocket-line text-3xl text-orange-600"></i>
          </div>
          <h4 class="text-lg font-bold text-gray-900 mb-2">الابتكار</h4>
          <p class="text-gray-600 text-sm">نستخدم أحدث التقنيات التعليمية</p>
        </div>
      </div>
    </div>

    <!-- What We Offer -->
    <div class="bg-white rounded-2xl shadow-lg p-8 md:p-12 mb-12">
      <h3 class="text-3xl font-bold text-gray-900 mb-8 text-center">ما نقدمه</h3>
      <div class="grid md:grid-cols-3 gap-8">

        <!-- Quran Education -->
        <div class="text-center">
          <div class="w-20 h-20 bg-gradient-to-br from-green-400 to-green-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg">
            <i class="ri-book-2-line text-4xl text-white"></i>
          </div>
          <h4 class="text-xl font-bold text-gray-900 mb-3">تعليم القرآن الكريم</h4>
          <p class="text-gray-700 leading-relaxed mb-4">
            حلقات قرآنية بإشراف معلمين مؤهلين ومتخصصين في تحفيظ القرآن الكريم وتجويده، مع متابعة دقيقة لتقدم كل طالب.
          </p>
          <ul class="text-right text-gray-600 space-y-2 text-sm">
            <li class="flex items-center justify-center">
              <i class="ri-checkbox-circle-fill text-green-500 ml-2"></i>
              تحفيظ القرآن الكريم
            </li>
            <li class="flex items-center justify-center">
              <i class="ri-checkbox-circle-fill text-green-500 ml-2"></i>
              أحكام التجويد
            </li>
            <li class="flex items-center justify-center">
              <i class="ri-checkbox-circle-fill text-green-500 ml-2"></i>
              القراءات القرآنية
            </li>
          </ul>
        </div>

        <!-- Academic Education -->
        <div class="text-center">
          <div class="w-20 h-20 bg-gradient-to-br from-blue-400 to-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg">
            <i class="ri-school-line text-4xl text-white"></i>
          </div>
          <h4 class="text-xl font-bold text-gray-900 mb-3">التعليم الأكاديمي</h4>
          <p class="text-gray-700 leading-relaxed mb-4">
            دروس أكاديمية شاملة في مختلف المواد الدراسية، مع معلمين متخصصين يستخدمون أحدث طرق التدريس.
          </p>
          <ul class="text-right text-gray-600 space-y-2 text-sm">
            <li class="flex items-center justify-center">
              <i class="ri-checkbox-circle-fill text-blue-500 ml-2"></i>
              دروس تفاعلية مباشرة
            </li>
            <li class="flex items-center justify-center">
              <i class="ri-checkbox-circle-fill text-blue-500 ml-2"></i>
              متابعة فردية
            </li>
            <li class="flex items-center justify-center">
              <i class="ri-checkbox-circle-fill text-blue-500 ml-2"></i>
              تقييمات دورية
            </li>
          </ul>
        </div>

        <!-- Recorded Courses -->
        <div class="text-center">
          <div class="w-20 h-20 bg-gradient-to-br from-purple-400 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg">
            <i class="ri-video-line text-4xl text-white"></i>
          </div>
          <h4 class="text-xl font-bold text-gray-900 mb-3">الكورسات المسجلة</h4>
          <p class="text-gray-700 leading-relaxed mb-4">
            مكتبة شاملة من الكورسات المسجلة عالية الجودة في مختلف المجالات، يمكن الوصول إليها في أي وقت.
          </p>
          <ul class="text-right text-gray-600 space-y-2 text-sm">
            <li class="flex items-center justify-center">
              <i class="ri-checkbox-circle-fill text-purple-500 ml-2"></i>
              محتوى عالي الجودة
            </li>
            <li class="flex items-center justify-center">
              <i class="ri-checkbox-circle-fill text-purple-500 ml-2"></i>
              مرونة في التعلم
            </li>
            <li class="flex items-center justify-center">
              <i class="ri-checkbox-circle-fill text-purple-500 ml-2"></i>
              شهادات إتمام
            </li>
          </ul>
        </div>

      </div>
    </div>

    <!-- Why Choose Us -->
    <div class="bg-white rounded-2xl shadow-lg p-8 md:p-12 mb-12">
      <h3 class="text-3xl font-bold text-gray-900 mb-8 text-center">لماذا تختار {{ $academy->name ?? 'أكاديمية إتقان' }}؟</h3>
      <div class="grid md:grid-cols-2 gap-6">

        <div class="flex items-start">
          <div class="flex-shrink-0 w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center ml-4">
            <i class="ri-user-star-line text-2xl text-primary"></i>
          </div>
          <div>
            <h4 class="text-lg font-bold text-gray-900 mb-2">معلمون مؤهلون</h4>
            <p class="text-gray-700 text-sm">معلمون حاصلون على أعلى المؤهلات وخبرات واسعة في التدريس</p>
          </div>
        </div>

        <div class="flex items-start">
          <div class="flex-shrink-0 w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center ml-4">
            <i class="ri-calendar-check-line text-2xl text-primary"></i>
          </div>
          <div>
            <h4 class="text-lg font-bold text-gray-900 mb-2">جداول مرنة</h4>
            <p class="text-gray-700 text-sm">مرونة في اختيار الأوقات المناسبة للدراسة</p>
          </div>
        </div>

        <div class="flex items-start">
          <div class="flex-shrink-0 w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center ml-4">
            <i class="ri-laptop-line text-2xl text-primary"></i>
          </div>
          <div>
            <h4 class="text-lg font-bold text-gray-900 mb-2">تقنية حديثة</h4>
            <p class="text-gray-700 text-sm">منصة تعليمية متطورة وسهلة الاستخدام</p>
          </div>
        </div>

        <div class="flex items-start">
          <div class="flex-shrink-0 w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center ml-4">
            <i class="ri-line-chart-line text-2xl text-primary"></i>
          </div>
          <div>
            <h4 class="text-lg font-bold text-gray-900 mb-2">متابعة التقدم</h4>
            <p class="text-gray-700 text-sm">تقارير دورية مفصلة عن تقدم الطالب</p>
          </div>
        </div>

        <div class="flex items-start">
          <div class="flex-shrink-0 w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center ml-4">
            <i class="ri-customer-service-line text-2xl text-primary"></i>
          </div>
          <div>
            <h4 class="text-lg font-bold text-gray-900 mb-2">دعم فني متواصل</h4>
            <p class="text-gray-700 text-sm">فريق دعم جاهز لمساعدتك في أي وقت</p>
          </div>
        </div>

        <div class="flex items-start">
          <div class="flex-shrink-0 w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center ml-4">
            <i class="ri-medal-line text-2xl text-primary"></i>
          </div>
          <div>
            <h4 class="text-lg font-bold text-gray-900 mb-2">شهادات معتمدة</h4>
            <p class="text-gray-700 text-sm">شهادات إتمام معتمدة لجميع الدورات</p>
          </div>
        </div>

      </div>
    </div>

    <!-- Contact CTA -->
    <div class="bg-gradient-to-r from-primary to-secondary rounded-2xl shadow-lg p-8 md:p-12 text-center text-white">
      <h3 class="text-3xl font-bold mb-4">انضم إلينا اليوم</h3>
      <p class="text-xl mb-8 opacity-90">
        ابدأ رحلتك التعليمية معنا وكن جزءاً من مجتمعنا التعليمي المتميز
      </p>
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <a href="{{ route('academy.home', ['subdomain' => $academy->subdomain]) }}" class="inline-flex items-center justify-center px-8 py-4 bg-white text-primary rounded-lg font-bold hover:bg-gray-100 transition-colors">
          <i class="ri-home-line ml-2"></i>
          العودة للصفحة الرئيسية
        </a>
        <a href="#contact" class="inline-flex items-center justify-center px-8 py-4 bg-white/20 text-white rounded-lg font-bold hover:bg-white/30 transition-colors backdrop-blur-sm">
          <i class="ri-mail-line ml-2"></i>
          تواصل معنا
        </a>
      </div>
    </div>

  </div>

  <!-- Footer -->
  @include('academy.components.footer', ['academy' => $academy])

</body>
</html>
