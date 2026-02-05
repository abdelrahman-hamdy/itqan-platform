@php
    // Get gradient palette colors
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $colors = $gradientPalette->getColors();
    $gradientFrom = $colors['from'];
    $gradientTo = $colors['to'];

    // Parse Tailwind color to get hex value (e.g., "cyan-500" -> hex color)
    $gradientFromHex = $gradientPalette->getPreviewHex(); // Gets the 'from' color as hex
    // For 'to' color, we need to extract and convert it
    [$toColorName, $toShade] = explode('-', $gradientTo);
    try {
        $toTailwindColor = \App\Enums\TailwindColor::from($toColorName);
        $gradientToHex = $toTailwindColor->getHexValue((int)$toShade);
    } catch (\ValueError $e) {
        $gradientToHex = '#6366F1'; // fallback to indigo-500
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $academy->name ?? __('common.default_academy_name') }} - {{ __('academy.meta.title_suffix') }}</title>
  <meta name="description" content="{{ __('academy.meta.description', ['academy' => $academy->name ?? __('common.default_academy_name')]) }}">
  <meta name="keywords" content="{{ __('academy.meta.keywords') }}, {{ $academy->name ?? __('common.default_academy_name') }}">
  <meta property="og:title" content="{{ $academy->name ?? __('common.default_academy_name') }} - {{ __('academy.meta.title_suffix') }}">
  <meta property="og:description" content="{{ __('academy.meta.og_description') }}">
  <meta property="og:type" content="website">
  <meta name="twitter:card" content="summary_large_image">

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@200;300;400;500;600;700;800;900&family=Cairo:wght@300;400;500;700&family=Pacifico&display=swap" rel="stylesheet">

  <!-- Vite Assets (includes RemixIcon & Flag-icons) -->
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  @php
    // Generate CSS variables for primary color
    $primaryColor = $academy->brand_color ?? \App\Enums\TailwindColor::SKY;
    $primaryVars = $primaryColor->generateCssVariables('primary');
  @endphp

  <!-- Academy Colors CSS Variables -->
  <style>
    :root {
      @foreach($primaryVars as $varName => $varValue)
      {{ $varName }}: {{ $varValue }};
      @endforeach

      /* Gradient palette variables */
      --gradient-from: {{ $gradientFrom }};
      --gradient-to: {{ $gradientTo }};
      --gradient-from-hex: {{ $gradientFromHex }};
      --gradient-to-hex: {{ $gradientToHex }};
    }
  </style>
  <style>
    /* Force Tajawal font on all elements */
    body, html, * {
      font-family: 'Tajawal', 'Cairo', -apple-system, BlinkMacSystemFont, sans-serif !important;
    }

    .hero-bg {
      background: linear-gradient(135deg, {{ $gradientFromHex }} 0%, {{ $gradientToHex }} 50%, {{ $gradientFromHex }} 100%);
      position: relative;
    }

    .hero-bg::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(45deg,
        {{ $gradientFromHex }} 0%,
        {{ $gradientToHex }} 25%,
        {{ $gradientFromHex }} 50%,
        {{ $gradientToHex }} 75%, 
        {{ $gradientFromHex }} 100%);
      background-size: 400% 400%;
      animation: gradientShift 8s ease infinite;
      opacity: 0.9;
    }
    
    @keyframes gradientShift {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    .quran-section-bg {
      background: linear-gradient(135deg, #f8faff 0%, #e6f0ff 50%, #d4e6ff 100%);
    }

    .academic-section-bg {
      background: linear-gradient(135deg, #fff8f0 0%, #fff0e6 50%, #ffe8d4 100%);
    }


    /* Arrow Animation for See More Cards */
    .see-more-arrow {
      animation: arrow-slide 3s ease-in-out infinite;
    }

    @keyframes arrow-slide {
      0%, 100% {
        transform: translateX(0);
      }
      25% {
        transform: translateX(4px);
      }
      50% {
        transform: translateX(0);
      }
      75% {
        transform: translateX(-2px);
      }
    }

    .stats-counter {
      font-family: 'Tajawal', sans-serif;
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
    
    /* Enhanced trust badges for hero section */
    .trust-badge-enhanced {
      display: flex;
      align-items: center;
      gap: 12px;
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      padding: 16px;
      border-radius: 16px;
      transition: all 0.3s ease;
      cursor: pointer;
    }
    
    .trust-badge-enhanced:hover {
      background: rgba(255, 255, 255, 0.25);
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }
    
    .trust-icon {
      width: 40px;
      height: 40px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    
    .trust-icon i {
      font-size: 20px;
      color: white;
    }
    
    .trust-content {
      display: flex;
      flex-direction: column;
      gap: 2px;
    }
    
    .trust-title {
      font-size: 14px;
      font-weight: 600;
      color: white;
      line-height: 1.2;
    }
    
    .trust-subtitle {
      font-size: 12px;
      color: rgba(255, 255, 255, 0.8);
      line-height: 1.2;
    }
    
    /* Light trust badges for light background hero section */
    .trust-badge-light {
      display: flex;
      align-items: center;
      gap: 12px;
      background: rgba(255, 255, 255, 0.8);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(0, 0, 0, 0.1);
      padding: 16px;
      border-radius: 16px;
      transition: all 0.3s ease;
      cursor: pointer;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .trust-badge-light:hover {
      background: rgba(255, 255, 255, 0.95);
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }
    
    .trust-icon-light {
      width: 40px;
      height: 40px;
      background: rgba(59, 130, 246, 0.1);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    
    .trust-icon-light i {
      font-size: 20px;
      color: #3b82f6;
    }
    
    .trust-content-light {
      display: flex;
      flex-direction: column;
      gap: 2px;
    }
    
    .trust-title-light {
      font-size: 14px;
      font-weight: 600;
      color: #1f2937;
      line-height: 1.2;
    }
    
    .trust-subtitle-light {
      font-size: 12px;
      color: #6b7280;
      line-height: 1.2;
    }
    
    /* Modern Hero Section Styles */
    .hero-gradient-text {
      background: linear-gradient(135deg, #3B82F6, #10B981);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    
    .floating-card {
      animation: float 6s ease-in-out infinite;
    }
    
    .floating-card:nth-child(2) {
      animation-delay: 2s;
    }
    
    .floating-card:nth-child(3) {
      animation-delay: 4s;
    }
    
    @keyframes float {
      0%, 100% {
        transform: translateY(0px);
      }
      50% {
        transform: translateY(-10px);
      }
    }
    
    .pulse-dot {
      animation: pulse-dot 2s infinite;
    }
    
    @keyframes pulse-dot {
      0%, 100% {
        opacity: 1;
        transform: scale(1);
      }
      50% {
        opacity: 0.5;
        transform: scale(1.1);
      }
    }
    
    .gradient-border {
      position: relative;
      background: linear-gradient(135deg, #3B82F6, #10B981);
      padding: 2px;
      border-radius: 1rem;
    }
    
    .gradient-border::before {
      content: '';
      position: absolute;
      inset: 2px;
      background: white;
      border-radius: calc(1rem - 2px);
    }
    
    .modern-shadow {
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }
    
    .glass-effect {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    
    
    /* Testimonials Carousel Styles */
    .testimonials-carousel {
      padding: 0 60px;
      position: relative;
    }
    
    .carousel-container {
      position: relative;
      margin: 0 16px; /* Space for navigation buttons on all screens */
    }
    
    #testimonials-track {
      display: flex;
      transition: transform 0.3s ease-in-out;
    }
    
    .carousel-item {
      flex-shrink: 0;
    }
    
    /* Testimonial Card Design */
    .testimonial-card {
      background: #ffffff;
      border: 1px solid rgba(0, 0, 0, 0.08);
      border-radius: 20px;
      padding: 28px;
      height: 100%;
      position: relative;
      overflow: hidden;
      transition: all 0.3s ease;
      box-shadow: none;
      display: flex;
      flex-direction: column;
    }
    
    .testimonial-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: linear-gradient(90deg, var(--gradient-from-hex), var(--gradient-to-hex));
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    
    .testimonial-card:hover {
      transform: scale(1.02);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
    }
    
    .testimonial-card:hover::before {
      opacity: 1;
    }
    
    /* Testimonial Header */
    .testimonial-header {
      display: flex;
      align-items: center;
      margin-bottom: 16px;
    }
    
    .testimonial-avatar {
      width: 52px;
      height: 52px;
      border-radius: 16px;
      overflow: hidden;
      margin-inline-end: 12px;
      border: 2px solid var(--gradient-from-hex);
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }
    
    .testimonial-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    
    .testimonial-info {
      flex: 1;
    }
    
    .testimonial-name {
      font-size: 16px;
      font-weight: 600;
      color: #1f2937;
      margin: 0 0 2px 0;
      line-height: 1.4;
    }
    
    .testimonial-role {
      font-size: 13px;
      color: #6b7280;
      margin: 0;
      line-height: 1.3;
    }
    
    /* Rating Stars */
    .testimonial-rating {
      display: flex;
      gap: 2px;
      margin-bottom: 16px;
    }
    
    .testimonial-rating i {
      color: #fbbf24;
      font-size: 16px;
      filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.1));
    }
    
    /* Testimonial Content */
    .testimonial-content {
      font-size: 15px;
      line-height: 1.6;
      color: #4b5563;
      margin: 0;
      font-style: normal;
      position: relative;
      flex: 1;
    }
    
    .testimonial-content::before {
      content: '"';
      font-size: 32px;
      color: {{ $academy->brand_color ?? '#3B82F6' }};
      position: absolute;
      top: -4px;
      inset-inline-end: -4px;
      opacity: 0.2;
      font-family: serif;
      font-weight: bold;
    }
    
    /* Navigation Buttons */
    .carousel-nav-btn {
      width: 56px;
      height: 56px;
      background: white;
      border-radius: 50%;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
      color: {{ $academy->brand_color ?? '#3B82F6' }};
      font-weight: bold;
      transition: all 0.3s ease;
      border: none;
      cursor: pointer;
    }
    
    .carousel-nav-btn:hover {
      transform: scale(1.1);
      box-shadow: 0 12px 35px rgba(0, 0, 0, 0.2);
    }
    
    /* Pagination Dots */
    .carousel-dot {
      width: 14px;
      height: 14px;
      border-radius: 50%;
      background: #d1d5db;
      border: none;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .carousel-dot.bg-primary {
      background: {{ $academy->brand_color ?? '#3B82F6' }};
      transform: scale(1.3);
      box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
    }
    
    /* Responsive Design */
    @media (max-width: 1024px) {
      .testimonials-carousel {
        padding: 0 50px;
      }
      
      .carousel-container {
        margin: 0 12px; /* Space for buttons on medium screens */
      }
    }
    
    @media (max-width: 768px) {
      .testimonials-carousel {
        padding: 0 40px;
      }
      
      .carousel-container {
        margin: 0 8px; /* Space for buttons on tablet screens */
      }
      
      .testimonial-card {
        padding: 24px;
      }
      
      .testimonial-avatar {
        width: 48px;
        height: 48px;
        border-radius: 12px;
      }
      
      .testimonial-name {
        font-size: 15px;
      }
      
      .testimonial-role {
        font-size: 12px;
      }
      
      .testimonial-content {
        font-size: 14px;
      }
      
      .testimonial-rating i {
        font-size: 14px;
      }
      
      .carousel-nav-btn {
        width: 48px;
        height: 48px;
      }
    }
    
    @media (max-width: 480px) {
      .testimonials-carousel {
        padding: 0 30px;
      }
      
      .carousel-container {
        margin: 0 4px; /* Minimal space for mobile screens */
      }
      
      .testimonial-card {
        padding: 20px;
      }
      
      .testimonial-avatar {
        width: 44px;
        height: 44px;
        border-radius: 10px;
      }
      
      .testimonial-name {
        font-size: 14px;
      }
      
      .testimonial-role {
        font-size: 11px;
      }
      
      .testimonial-content {
        font-size: 13px;
      }
      
      .testimonial-rating i {
        font-size: 13px;
      }
    }
  </style>
  
  <!-- Global Styles -->
  @include('components.global-styles')
</head>

<body class="bg-gray-50 text-gray-900">
  <!-- Skip Navigation Links -->
  <a href="#hero-section" class="skip-link">تخطي إلى المحتوى الرئيسي</a>
  <a href="#navigation" class="skip-link">تخطي إلى التنقل</a>
  
  <!-- Navigation -->
  @include('academy.components.topbar', ['academy' => $academy])

  @php
    // Get sections order from academy settings
    $sectionsOrder = $academy->sections_order ?? ['hero', 'stats', 'reviews', 'quran', 'academic', 'courses', 'features'];

    // Section template mapping
    $sectionTemplates = [
      'hero' => 'academy.components.templates.hero.',
      'stats' => 'academy.components.templates.stats.',
      'reviews' => 'academy.components.templates.reviews.',
      'quran' => 'academy.components.templates.quran.',
      'academic' => 'academy.components.templates.academic.',
      'courses' => 'academy.components.templates.courses.',
      'features' => 'academy.components.templates.features.',
    ];

    // Section data mapping
    $sectionData = [
      'hero' => ['academy' => $academy],
      'stats' => ['academy' => $academy],
      'reviews' => ['academy' => $academy],
      'quran' => [
        'academy' => $academy,
        'quranCircles' => $quranCircles ?? collect(),
        'quranTeachers' => $quranTeachers ?? collect()
      ],
      'academic' => [
        'academy' => $academy,
        'interactiveCourses' => $interactiveCourses ?? collect(),
        'academicTeachers' => $academicTeachers ?? collect()
      ],
      'courses' => [
        'academy' => $academy,
        'recordedCourses' => $recordedCourses ?? collect()
      ],
      'features' => ['academy' => $academy],
    ];
  @endphp

  @foreach($sectionsOrder as $section)
    @php
      // Get section settings
      $isVisible = $academy->{$section . '_visible'} ?? true;
      $template = $academy->{$section . '_template'} ?? 'template_1';
      $heading = $academy->{$section . '_heading'} ?? null;
      $subheading = $academy->{$section . '_subheading'} ?? null;

      // Build template path
      $templatePath = $sectionTemplates[$section] . $template;

      // Add heading and subheading to section data if available
      $data = $sectionData[$section];
      if ($heading) {
        $data['heading'] = $heading;
      }
      if ($subheading) {
        $data['subheading'] = $subheading;
      }
    @endphp

    @if($isVisible)
      @include($templatePath, $data)
    @endif
  @endforeach
  
  <!-- Footer -->
  <x-academy-footer :academy="$academy" />

  <!-- Scripts -->
  @include('academy.components.scripts')
</body>

</html>