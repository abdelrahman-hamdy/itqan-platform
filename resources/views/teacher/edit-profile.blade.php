<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }} - تعديل ملف المعلم</title>
  <meta name="description" content="تعديل ملف المعلم - {{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }}">
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
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
          <i class="ri-edit-line text-blue-600 ml-2"></i>
          تعديل الملف الشخصي
        </h1>
        <p class="text-gray-600">تحديث بياناتك الشخصية ومعلومات التدريس</p>
      </div>

      <!-- Success Message -->
      @if(session('success'))
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
          {{ session('success') }}
        </div>
      @endif

      <!-- Edit Form -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form method="POST" action="{{ route('teacher.profile.update', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}">
          @csrf
          @method('PUT')
          
          <!-- Personal Information -->
          <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">المعلومات الشخصية</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- First Name -->
              <div>
                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">الاسم الأول</label>
                <input type="text" 
                       id="first_name" 
                       name="first_name" 
                       value="{{ old('first_name', $teacherProfile->first_name ?? auth()->user()->first_name) }}"
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary">
                @error('first_name')
                  <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
              </div>

              <!-- Last Name -->
              <div>
                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">الاسم الأخير</label>
                <input type="text" 
                       id="last_name" 
                       name="last_name" 
                       value="{{ old('last_name', $teacherProfile->last_name ?? auth()->user()->last_name) }}"
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary">
                @error('last_name')
                  <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
              </div>

              <!-- Phone -->
              <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">رقم الهاتف</label>
                <input type="tel" 
                       id="phone" 
                       name="phone" 
                       value="{{ old('phone', $teacherProfile->phone ?? auth()->user()->phone) }}"
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary">
                @error('phone')
                  <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
              </div>

              <!-- Email (readonly) -->
              <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">البريد الإلكتروني</label>
                <input type="email" 
                       id="email" 
                       value="{{ $teacherProfile->email ?? auth()->user()->email }}"
                       readonly
                       class="block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 text-gray-500">
                <p class="mt-1 text-xs text-gray-500">لا يمكن تغيير البريد الإلكتروني</p>
              </div>
            </div>
          </div>

          <!-- Bio -->
          <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">السيرة الذاتية</h3>
            
            <div>
              <label for="bio_arabic" class="block text-sm font-medium text-gray-700 mb-2">نبذة عن المعلم</label>
              <textarea id="bio_arabic" 
                        name="bio_arabic" 
                        rows="4"
                        placeholder="اكتب نبذة مختصرة عنك وخبرتك في التدريس..."
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary">{{ old('bio_arabic', $teacherProfile->bio_arabic ?? '') }}</textarea>
              @error('bio_arabic')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
              @enderror
            </div>
          </div>

          <!-- Availability -->
          <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">أوقات التدريس</h3>
            
            <!-- Available Days -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-3">الأيام المتاحة</label>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                @php
                  $days = [
                    'sunday' => 'الأحد',
                    'monday' => 'الاثنين', 
                    'tuesday' => 'الثلاثاء',
                    'wednesday' => 'الأربعاء',
                    'thursday' => 'الخميس',
                    'friday' => 'الجمعة',
                    'saturday' => 'السبت'
                  ];
                  $availableDays = old('available_days', $teacherProfile->available_days ?? []);
                @endphp
                
                @foreach($days as $key => $day)
                  <label class="flex items-center">
                    <input type="checkbox" 
                           name="available_days[]" 
                           value="{{ $key }}"
                           {{ in_array($key, $availableDays) ? 'checked' : '' }}
                           class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                    <span class="mr-2 text-sm text-gray-700">{{ $day }}</span>
                  </label>
                @endforeach
              </div>
            </div>

            <!-- Available Hours -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="available_time_start" class="block text-sm font-medium text-gray-700 mb-2">من الساعة</label>
                <input type="time" 
                       id="available_time_start" 
                       name="available_time_start" 
                       value="{{ old('available_time_start', $teacherProfile->available_time_start ? \Carbon\Carbon::parse($teacherProfile->available_time_start)->format('H:i') : '08:00') }}"
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary">
                @error('available_time_start')
                  <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
              </div>

              <div>
                <label for="available_time_end" class="block text-sm font-medium text-gray-700 mb-2">إلى الساعة</label>
                <input type="time" 
                       id="available_time_end" 
                       name="available_time_end" 
                       value="{{ old('available_time_end', $teacherProfile->available_time_end ? \Carbon\Carbon::parse($teacherProfile->available_time_end)->format('H:i') : '18:00') }}"
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary">
                @error('available_time_end')
                  <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-between pt-6 border-t border-gray-200">
            <a href="{{ route('teacher.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
               class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg text-sm font-medium hover:bg-gray-400 transition-colors">
              إلغاء
            </a>
            
            <button type="submit" 
                    class="bg-primary text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-secondary transition-colors">
              <i class="ri-save-line ml-2"></i>
              حفظ التغييرات
            </button>
          </div>
        </form>
      </div>

    </div>
  </main>
</body>
</html>