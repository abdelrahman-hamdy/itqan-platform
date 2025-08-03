<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }} - إعدادات المعلم</title>
  <meta name="description" content="إعدادات المعلم - {{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }}">
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
        },
      },
    };
  </script>
</head>

<body class="bg-gray-50 text-gray-900">
  <!-- Navigation -->
  @include('components.navigation.teacher-nav')
  
  <!-- Sidebar -->
  @include('components.sidebar.teacher-sidebar')

  <!-- Main Content -->
  <main class="mr-80 pt-20 min-h-screen" id="main-content">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
          <i class="ri-settings-3-line text-gray-600 ml-2"></i>
          إعدادات الحساب
        </h1>
        <p class="text-gray-600">إدارة إعدادات حسابك والتفضيلات الشخصية</p>
      </div>

      <!-- Settings Content -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="text-center py-16">
          <i class="ri-settings-3-line text-6xl text-gray-300 mb-4"></i>
          <h3 class="text-xl font-semibold text-gray-900 mb-2">صفحة الإعدادات</h3>
          <p class="text-gray-600 mb-6">سيتم تطوير هذه الصفحة قريباً لتتضمن جميع إعدادات الحساب</p>
          
          <div class="max-w-md mx-auto space-y-4">
            <a href="{{ route('teacher.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
               class="w-full bg-primary text-white px-6 py-3 rounded-lg text-sm font-medium hover:bg-secondary transition-colors flex items-center justify-center">
              <i class="ri-user-line ml-2"></i>
              العودة للملف الشخصي
            </a>
            
            <a href="{{ route('teacher.profile.edit', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
               class="w-full bg-gray-600 text-white px-6 py-3 rounded-lg text-sm font-medium hover:bg-gray-700 transition-colors flex items-center justify-center">
              <i class="ri-edit-line ml-2"></i>
              تعديل الملف الشخصي
            </a>
          </div>
        </div>
      </div>

    </div>
  </main>
</body>
</html>