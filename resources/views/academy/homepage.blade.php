<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $academy->name ?? 'أكاديمية إتقان' }} - منصة تعليمية شاملة</title>
  <meta name="description" content="{{ $academy->name ?? 'أكاديمية إتقان' }} - منصة تعليمية متميزة لتعلم القرآن الكريم والعلوم الأكاديمية مع أفضل المعلمين المؤهلين">
  <meta name="keywords" content="تعلم القرآن، دروس أكاديمية، معلمين مؤهلين، تعليم عربي، {{ $academy->name ?? 'أكاديمية إتقان' }}">
  <meta property="og:title" content="{{ $academy->name ?? 'أكاديمية إتقان' }} - منصة تعليمية شاملة">
  <meta property="og:description" content="منصة تعليمية متميزة لتعلم القرآن الكريم والعلوم الأكاديمية">
  <meta property="og:type" content="website">
  <meta name="twitter:card" content="summary_large_image">
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
            primary: "{{ $academy->primary_color ?? '#4169E1' }}",
            secondary: "{{ $academy->secondary_color ?? '#6495ED' }}",
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

    .hero-bg {
      background: linear-gradient(135deg, {{ $academy->primary_color ?? '#4169E1' }} 0%, {{ $academy->secondary_color ?? '#6495ED' }} 50%, #87CEEB 100%);
    }

    .quran-section-bg {
      background: linear-gradient(135deg, #f8faff 0%, #e6f0ff 50%, #d4e6ff 100%);
    }

    .academic-section-bg {
      background: linear-gradient(135deg, #fff8f0 0%, #fff0e6 50%, #ffe8d4 100%);
    }

    .card-hover {
      transition: all 0.3s ease;
    }

    .card-hover:hover {
      transform: translateY(-4px);
      box-shadow: 0 20px 40px rgba(65, 105, 225, 0.15);
    }

    .stats-counter {
      font-family: 'Cairo', sans-serif;
      font-weight: bold;
    }
    
    /* Accessibility Improvements */
    .skip-link {
      position: absolute;
      top: -40px;
      left: 6px;
      background: {{ $academy->primary_color ?? '#4169E1' }};
      color: white;
      padding: 8px;
      text-decoration: none;
      border-radius: 4px;
      z-index: 1000;
    }
    
    .skip-link:focus {
      top: 6px;
    }
    
    /* Focus indicators */
    .focus\:ring-custom:focus {
      outline: 2px solid {{ $academy->primary_color ?? '#4169E1' }};
      outline-offset: 2px;
    }
    
    /* Loading states */
    .loading {
      opacity: 0.6;
      pointer-events: none;
      position: relative;
    }
    
    .loading::after {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 20px;
      height: 20px;
      margin: -10px 0 0 -10px;
      border: 2px solid {{ $academy->primary_color ?? '#4169E1' }};
      border-radius: 50%;
      border-top-color: transparent;
      animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
    
    /* Carousel improvements */
    .carousel-dots {
      display: flex;
      justify-content: center;
      gap: 8px;
      margin-top: 16px;
    }
    
    .carousel-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #cbd5e1;
      cursor: pointer;
      transition: background 0.3s;
    }
    
    .carousel-dot.active {
      background: {{ $academy->primary_color ?? '#4169E1' }};
    }
    
    /* Trust indicators */
    .trust-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: #f0f9ff;
      border: 1px solid #e0f2fe;
      padding: 8px 12px;
      border-radius: 8px;
      font-size: 14px;
      color: #0369a1;
    }
  </style>
</head>

<body class="bg-gray-50 text-gray-900">
  <!-- Skip Navigation Links -->
  <a href="#main-content" class="skip-link">تخطي إلى المحتوى الرئيسي</a>
  <a href="#navigation" class="skip-link">تخطي إلى التنقل</a>
  
  <!-- Navigation -->
  @include('academy.components.navigation', ['academy' => $academy])
  
  <!-- Hero Section -->
  @include('academy.components.hero-section', ['academy' => $academy])
  
  <!-- Statistics Section -->
  @include('academy.components.statistics', ['academy' => $academy])
  
  <!-- Testimonials Section -->
  @include('academy.components.testimonials', ['academy' => $academy])
  
  <!-- Quran Section -->
  @if($academy->quran_enabled ?? true)
    @include('academy.components.quran-section', [
      'academy' => $academy,
      'quranCircles' => $quranCircles ?? collect(),
      'quranTeachers' => $quranTeachers ?? collect()
    ])
  @endif
  
  <!-- Academic Section -->
  @if($academy->academic_enabled ?? true)
    @include('academy.components.academic-section', [
      'academy' => $academy,
      'interactiveCourses' => $interactiveCourses ?? collect(),
      'academicTeachers' => $academicTeachers ?? collect()
    ])
  @endif
  
  <!-- Recorded Courses Section -->
  @if($academy->recorded_courses_enabled ?? true)
    @include('academy.components.recorded-courses', [
      'academy' => $academy,
      'recordedCourses' => $recordedCourses ?? collect()
    ])
  @endif
  
  <!-- Features Section -->
  @include('academy.components.features', ['academy' => $academy])
  
  <!-- Footer -->
  @include('academy.components.footer', ['academy' => $academy])

  <!-- Scripts -->
  @include('academy.components.scripts')
</body>

</html>