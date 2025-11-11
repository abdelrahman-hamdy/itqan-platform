<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }} - تعديل الملف الشخصي</title>
  <meta name="description" content="تعديل الملف الشخصي للطالب - {{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }}">
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
  <main class="transition-all duration-300 pt-20 min-h-screen" id="main-content" style="margin-right: 320px;">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center space-x-4 space-x-reverse mb-4">
          <a href="{{ route('student.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="text-primary hover:text-secondary transition-colors">
            <i class="ri-arrow-right-line text-xl"></i>
          </a>
          <h1 class="text-3xl font-bold text-gray-900">تعديل الملف الشخصي</h1>
        </div>
        <p class="text-gray-600">قم بتحديث معلوماتك الشخصية</p>
      </div>

      <!-- Edit Profile Form -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-8">
                     <form action="{{ route('student.profile.update', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- First Name -->
              <div>
                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">الاسم الأول</label>
                <input type="text" 
                       id="first_name" 
                       name="first_name" 
                       value="{{ old('first_name', $studentProfile?->first_name ?? '') }}"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                       required>
                @error('first_name')
                  <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
              </div>

              <!-- Last Name -->
              <div>
                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">الاسم الأخير</label>
                <input type="text" 
                       id="last_name" 
                       name="last_name" 
                       value="{{ old('last_name', $studentProfile?->last_name ?? '') }}"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                       required>
                @error('last_name')
                  <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
              </div>

              <!-- Email (Non-editable) -->
              <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">البريد الإلكتروني</label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       value="{{ auth()->user()->email }}"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed"
                       readonly
                       disabled
                       tabindex="-1">
              </div>

              <!-- Student Code (Non-editable) -->
              <div>
                <label for="student_code" class="block text-sm font-medium text-gray-700 mb-2">رقم الطالب</label>
                <input type="text" 
                       id="student_code" 
                       name="student_code" 
                       value="{{ $studentProfile?->student_code ?? '' }}"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed"
                       readonly
                       disabled
                       tabindex="-1">
              </div>

              <!-- Phone -->
              <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">رقم الهاتف</label>
                <input type="tel" 
                       id="phone" 
                       name="phone" 
                       value="{{ old('phone', $studentProfile?->phone ?? '') }}"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                @error('phone')
                  <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
              </div>

              <!-- Birth Date -->
              <div>
                <label for="birth_date" class="block text-sm font-medium text-gray-700 mb-2">تاريخ الميلاد</label>
                <input type="date" 
                       id="birth_date" 
                       name="birth_date" 
                       value="{{ old('birth_date', $studentProfile?->birth_date?->format('Y-m-d')) }}"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                @error('birth_date')
                  <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
              </div>

              <!-- Gender -->
              <div>
                <label for="gender" class="block text-sm font-medium text-gray-700 mb-2">الجنس</label>
                <select id="gender" 
                        name="gender" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                  <option value="">اختر الجنس</option>
                  <option value="male" {{ old('gender', $studentProfile?->gender) === 'male' ? 'selected' : '' }}>ذكر</option>
                  <option value="female" {{ old('gender', $studentProfile?->gender) === 'female' ? 'selected' : '' }}>أنثى</option>
                </select>
                @error('gender')
                  <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
              </div>

              <!-- Nationality -->
              <div>
                <label for="nationality" class="block text-sm font-medium text-gray-700 mb-2">الجنسية</label>
                <select id="nationality" 
                        name="nationality" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                  <option value="">اختر الجنسية</option>
                  @foreach($countries as $code => $name)
                    <option value="{{ $code }}" {{ old('nationality', $studentProfile?->nationality) == $code ? 'selected' : '' }}>{{ $name }}</option>
                  @endforeach
                </select>
                @error('nationality')
                  <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
              </div>

              <!-- Address -->
              <div class="md:col-span-2">
                <label for="address" class="block text-sm font-medium text-gray-700 mb-2">العنوان</label>
                <textarea id="address" 
                          name="address" 
                          rows="3"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">{{ old('address', $studentProfile?->address ?? '') }}</textarea>
                @error('address')
                  <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
              </div>

              <!-- Emergency Contact -->
              <div>
                <label for="emergency_contact" class="block text-sm font-medium text-gray-700 mb-2">رقم الطوارئ</label>
                <input type="tel" 
                       id="emergency_contact" 
                       name="emergency_contact" 
                       value="{{ old('emergency_contact', $studentProfile?->emergency_contact ?? '') }}"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                @error('emergency_contact')
                  <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
              </div>

              <!-- Grade Level -->
              <div>
                <label for="grade_level_id" class="block text-sm font-medium text-gray-700 mb-2">المرحلة الدراسية</label>
                <select id="grade_level_id" 
                        name="grade_level_id" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                  <option value="">اختر المرحلة الدراسية</option>
                  @foreach($gradeLevels as $gradeLevel)
                    <option value="{{ $gradeLevel->id }}" {{ old('grade_level_id', $studentProfile?->grade_level_id) == $gradeLevel->id ? 'selected' : '' }}>
                      {{ $gradeLevel->name }}
                    </option>
                  @endforeach
                </select>
                @error('grade_level_id')
                  <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
              </div>

              <!-- Avatar -->
              <div>
                <label for="avatar" class="block text-sm font-medium text-gray-700 mb-2">صورة الملف الشخصي</label>
                <input type="file" 
                       id="avatar" 
                       name="avatar" 
                       accept="image/*"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-secondary">
                @if($studentProfile?->avatar)
                  <div class="mt-2">
                    <p class="text-sm text-gray-600">الصورة الحالية:</p>
                    <img src="{{ asset('storage/' . $studentProfile->avatar) }}" alt="الصورة الحالية" class="w-16 h-16 rounded-full object-cover mt-1">
                  </div>
                @endif
                @error('avatar')
                  <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
              </div>


            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 space-x-reverse mt-8 pt-6 border-t border-gray-200">
                             <a href="{{ route('student.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
                  class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                إلغاء
              </a>
              <button type="submit" 
                      class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-secondary transition-colors">
                حفظ التغييرات
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Success Message -->
      @if(session('success'))
        <div class="mt-6 bg-green-50 border border-green-200 rounded-lg p-4">
          <div class="flex items-center space-x-3 space-x-reverse">
            <div class="w-5 h-5 bg-green-500 rounded-full flex items-center justify-center">
              <i class="ri-check-line text-white text-sm"></i>
            </div>
            <p class="text-green-800">{{ session('success') }}</p>
          </div>
        </div>
      @endif

    </div>
  </main>

  <!-- Mobile Sidebar Toggle -->
  <button id="sidebar-toggle" class="fixed bottom-6 right-6 md:hidden bg-primary text-white p-3 rounded-full shadow-lg z-50">
    <i class="ri-menu-line text-xl"></i>
  </button>

</body>
</html> 