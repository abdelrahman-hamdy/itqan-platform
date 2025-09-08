<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>المعلمون الأكاديميون - {{ $academy->name ?? 'أكاديمية إتقان' }}</title>
  <meta name="description" content="تصفح المعلمين الأكاديميين المؤهلين والمعتمدين في {{ $academy->name ?? 'أكاديمية إتقان' }}">
  <script src="https://cdn.tailwindcss.com/3.4.16"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: "{{ $academy->brand_color ?? $academy->primary_color ?? '#4169E1' }}",
            secondary: "{{ $academy->secondary_color ?? '#6495ED' }}",
          }
        }
      }
    };
  </script>
  <!-- Global Styles -->
  @include('components.global-styles')
</head>

<body class="bg-gray-50 font-sans">

  <!-- Public Navigation -->
  @include('components.public-navigation', ['academy' => $academy])

  <!-- Hero Section -->
  <x-public-hero-section 
    :academy="$academy"
    title="المعلمون الأكاديميون"
    subtitle="معلمون مؤهلون ومعتمدون لتدريس المواد الأكاديمية"
    icon="ri-user-star-line"
    :stats="[
      ['value' => $teachers->total(), 'label' => 'معلم متاح'],
      ['value' => $teachers->sum('total_students'), 'label' => 'طالب مسجل'],
      ['value' => $teachers->sum('total_sessions'), 'label' => 'جلسة مكتملة']
    ]"
  />

  <!-- Teachers Grid -->
  <section class="py-16">
    <div class="container mx-auto px-4">
      
      @if($teachers->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          @foreach($teachers as $teacher)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden card-hover">
              <!-- Teacher Avatar -->
              <div class="p-6 text-center">
                <div class="flex justify-center mb-4">
                  <x-teacher-avatar :teacher="$teacher" size="lg" :show-badge="true" />
                </div>
                
                <!-- Teacher Name -->
                <h3 class="text-lg font-bold text-gray-900 mb-1">{{ $teacher->full_name }}</h3>
                
                <!-- Rating -->
                @if($teacher->rating > 0)
                  <div class="flex items-center justify-center mb-3">
                    <div class="flex text-yellow-400">
                      @for($i = 1; $i <= 5; $i++)
                        <i class="ri-star-{{ $i <= $teacher->rating ? 'fill' : 'line' }} text-sm"></i>
                      @endfor
                    </div>
                    <span class="text-sm text-gray-600 mr-2">({{ $teacher->rating }})</span>
                  </div>
                @endif
              </div>

              <!-- Teacher Info -->
              <div class="px-6 pb-4 space-y-3">
                <!-- Experience -->
                <div class="flex items-center text-sm text-gray-600">
                  <i class="ri-time-line ml-2 text-primary"></i>
                  <span>{{ $teacher->teaching_experience_years ?? 0 }} سنوات خبرة</span>
                </div>

                <!-- Students Count -->
                <div class="flex items-center text-sm text-gray-600">
                  <i class="ri-group-line ml-2 text-primary"></i>
                  <span>{{ $teacher->total_students ?? 0 }} طالب</span>
                </div>

                <!-- Specialization -->
                @if($teacher->specialization)
                  <div class="flex items-center text-sm text-gray-600">
                    <i class="ri-book-line ml-2 text-primary"></i>
                    <span>{{ $teacher->specialization }}</span>
                  </div>
                @endif

                <!-- Education -->
                @if($teacher->education_level)
                  <div class="flex items-center text-sm text-gray-600">
                    <i class="ri-graduation-cap-line ml-2 text-primary"></i>
                    <span>{{ $teacher->education_level_in_arabic ?? $teacher->education_level }}</span>
                  </div>
                @endif

                <!-- Certifications -->
                @if($teacher->formatted_certifications && count($teacher->formatted_certifications) > 0)
                  <div class="flex items-start text-sm text-gray-600">
                    <i class="ri-award-line ml-2 text-primary mt-0.5"></i>
                    <div class="flex-1">
                      <span class="font-medium">الشهادات:</span>
                      <div class="mt-1 space-y-1">
                        @foreach($teacher->formatted_certifications as $cert)
                          <div class="text-xs">
                            @php
                              // Convert certification names to Arabic
                              $certNames = [
                                'teaching_certificate' => 'شهادة تدريس',
                                'education_degree' => 'درجة تعليمية',
                                'subject_specialization' => 'تخصص في المادة',
                                'pedagogical_training' => 'تدريب تربوي',
                                'technology_integration' => 'تكامل التكنولوجيا',
                                'classroom_management' => 'إدارة الفصل',
                                'assessment_methods' => 'طرق التقييم',
                                'special_needs' => 'الاحتياجات الخاصة',
                                'language_proficiency' => 'الكفاءة اللغوية',
                                'professional_development' => 'التطوير المهني'
                              ];
                              $certName = $certNames[$cert['name']] ?? $cert['name'];
                            @endphp
                            {{ $certName }}
                            @if($cert['issuer'])
                              - {{ $cert['issuer'] }}
                            @endif
                            @if($cert['year'])
                              ({{ $cert['year'] }})
                            @endif
                          </div>
                        @endforeach
                      </div>
                    </div>
                  </div>
                @endif
              </div>

              <!-- Action Button -->
              <div class="px-6 pb-6">
                <a href="{{ route('public.academic-teachers.show', ['subdomain' => $academy->subdomain, 'teacher' => $teacher->id]) }}" 
                   class="w-full bg-primary text-white py-3 px-4 rounded-lg text-center font-medium hover:bg-secondary transition-colors block">
                  عرض الملف الشخصي
                </a>
              </div>
            </div>
          @endforeach
        </div>

        <!-- Pagination -->
        @if($teachers->hasPages())
          <div class="mt-12">
            {{ $teachers->links() }}
          </div>
        @endif

      @else
        <!-- No Teachers -->
        <div class="text-center py-16">
          <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gray-100 flex items-center justify-center">
            <i class="ri-user-line text-4xl text-gray-400"></i>
          </div>
          <h3 class="text-xl font-bold text-gray-900 mb-2">لا توجد معلمين متاحين حالياً</h3>
          <p class="text-gray-600 mb-6">نعمل على إضافة معلمين جدد قريباً</p>
          <a href="{{ route('academy.home', ['subdomain' => $academy->subdomain]) }}" 
             class="bg-primary text-white px-6 py-3 rounded-lg hover:bg-secondary transition-colors">
            العودة للرئيسية
          </a>
        </div>
      @endif
    </div>
  </section>

  <!-- Footer -->
  @include('academy.components.footer', ['academy' => $academy])

</body>
</html>