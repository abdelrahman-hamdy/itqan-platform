<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }} - الإعدادات</title>
  <meta name="description" content="إعدادات الطالب - {{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }}">
  <script src="https://cdn.tailwindcss.com/3.4.16"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: "{{ auth()->user()->academy->primary_color ?? '#4169E1' }}",
            secondary: "{{ auth()->user()->academy->secondary_color ?? '#6495ED' }}",
          },
          borderRadius: {
            none: "0px",
            sm: "4px",
            DEFAULT: "8px",
            md: "12px",
            lg: "16px",
            xl: "20px",
            "2xl": "24px",
            "3xl": "32px",
            full: "9999px",
            button: "8px",
          },
        },
      },
    };
  </script>
  <style>
    :where([class^="ri-"])::before {
      content: "\f3c2";
    }

    .focus\:ring-custom:focus {
      outline: 2px solid {{ auth()->user()->academy->primary_color ?? '#4169E1' }};
      outline-offset: 2px;
    }
  </style>
</head>

<body class="bg-gray-50 text-gray-900">
  <!-- Navigation -->
  @include('components.navigation.student-nav')
  
  <!-- Sidebar -->
  @include('components.sidebar.student-sidebar')

  <!-- Main Content -->
  <main class="mr-80 pt-20 min-h-screen" id="main-content">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center space-x-4 space-x-reverse mb-4">
          <a href="{{ route('student.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="text-primary hover:text-secondary transition-colors">
            <i class="ri-arrow-right-line text-xl"></i>
          </a>
          <h1 class="text-3xl font-bold text-gray-900">الإعدادات</h1>
        </div>
        <p class="text-gray-600">إدارة إعدادات حسابك وتفضيلاتك</p>
      </div>

      <!-- Settings Sections -->
      <div class="space-y-6">
        
        <!-- Account Settings -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="p-6 border-b border-gray-100">
            <h2 class="text-xl font-semibold text-gray-900">إعدادات الحساب</h2>
            <p class="text-sm text-gray-500 mt-1">إدارة معلومات الحساب والأمان</p>
          </div>
          <div class="p-6 space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="font-medium text-gray-900">البريد الإلكتروني</h3>
                <p class="text-sm text-gray-500">{{ auth()->user()->email }}</p>
              </div>
              <button class="text-primary hover:text-secondary text-sm font-medium">تغيير</button>
            </div>
            
            <div class="flex items-center justify-between">
              <div>
                <h3 class="font-medium text-gray-900">كلمة المرور</h3>
                <p class="text-sm text-gray-500">آخر تحديث منذ 30 يوماً</p>
              </div>
              <button class="text-primary hover:text-secondary text-sm font-medium">تغيير</button>
            </div>
            
            <div class="flex items-center justify-between">
              <div>
                <h3 class="font-medium text-gray-900">المصادقة الثنائية</h3>
                <p class="text-sm text-gray-500">غير مفعلة</p>
              </div>
              <button class="text-primary hover:text-secondary text-sm font-medium">تفعيل</button>
            </div>
          </div>
        </div>

        <!-- Notification Settings -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="p-6 border-b border-gray-100">
            <h2 class="text-xl font-semibold text-gray-900">إعدادات الإشعارات</h2>
            <p class="text-sm text-gray-500 mt-1">إدارة الإشعارات والتنبيهات</p>
          </div>
          <div class="p-6 space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="font-medium text-gray-900">إشعارات البريد الإلكتروني</h3>
                <p class="text-sm text-gray-500">استلام إشعارات عبر البريد الإلكتروني</p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" class="sr-only peer" checked>
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:right-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
              </label>
            </div>
            
            <div class="flex items-center justify-between">
              <div>
                <h3 class="font-medium text-gray-900">إشعارات الدروس</h3>
                <p class="text-sm text-gray-500">تذكير قبل بدء الدروس</p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" class="sr-only peer" checked>
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:right-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
              </label>
            </div>
            
            <div class="flex items-center justify-between">
              <div>
                <h3 class="font-medium text-gray-900">إشعارات التقدم</h3>
                <p class="text-sm text-gray-500">إشعارات عند إكمال الدروس</p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:right-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
              </label>
            </div>
          </div>
        </div>

        <!-- Privacy Settings -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="p-6 border-b border-gray-100">
            <h2 class="text-xl font-semibold text-gray-900">إعدادات الخصوصية</h2>
            <p class="text-sm text-gray-500 mt-1">إدارة خصوصية حسابك</p>
          </div>
          <div class="p-6 space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="font-medium text-gray-900">الملف الشخصي العام</h3>
                <p class="text-sm text-gray-500">إظهار معلوماتك للمعلمين</p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" class="sr-only peer" checked>
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:right-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
              </label>
            </div>
            
            <div class="flex items-center justify-between">
              <div>
                <h3 class="font-medium text-gray-900">مشاركة التقدم</h3>
                <p class="text-sm text-gray-500">مشاركة تقدمك مع الوالدين</p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" class="sr-only peer" checked>
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:right-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
              </label>
            </div>
          </div>
        </div>

        <!-- Language Settings -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="p-6 border-b border-gray-100">
            <h2 class="text-xl font-semibold text-gray-900">إعدادات اللغة</h2>
            <p class="text-sm text-gray-500 mt-1">اختر لغة الواجهة</p>
          </div>
          <div class="p-6">
            <div class="space-y-3">
              <label class="flex items-center space-x-3 space-x-reverse">
                <input type="radio" name="language" value="ar" checked class="text-primary focus:ring-primary">
                <span class="text-gray-900">العربية</span>
              </label>
              <label class="flex items-center space-x-3 space-x-reverse">
                <input type="radio" name="language" value="en" class="text-primary focus:ring-primary">
                <span class="text-gray-900">English</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Danger Zone -->
        <div class="bg-white rounded-xl shadow-sm border border-red-200 overflow-hidden">
          <div class="p-6 border-b border-red-100">
            <h2 class="text-xl font-semibold text-red-900">منطقة الخطر</h2>
            <p class="text-sm text-red-500 mt-1">إجراءات لا يمكن التراجع عنها</p>
          </div>
          <div class="p-6 space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="font-medium text-gray-900">حذف الحساب</h3>
                <p class="text-sm text-gray-500">حذف حسابك نهائياً وجميع البيانات المرتبطة به</p>
              </div>
              <button class="text-red-600 hover:text-red-700 text-sm font-medium">حذف الحساب</button>
            </div>
          </div>
        </div>

      </div>

    </div>
  </main>

  <!-- Mobile Sidebar Toggle -->
  <button id="sidebar-toggle" class="fixed bottom-6 right-6 md:hidden bg-primary text-white p-3 rounded-full shadow-lg z-50">
    <i class="ri-menu-line text-xl"></i>
  </button>

</body>
</html> 