<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }} - أرباح المعلم</title>
  <meta name="description" content="أرباح المعلم - {{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }}">
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

    .card-hover {
      transition: all 0.3s ease;
    }

    .card-hover:hover {
      transform: translateY(-4px);
      box-shadow: 0 20px 40px rgba(65, 105, 225, 0.15);
    }

    .earnings-chart {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
  </style>
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
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
              <i class="ri-money-dollar-circle-line text-green-600 ml-2"></i>
              أرباحي الشهرية
            </h1>
            <p class="text-gray-600">
              تتبع أرباحك من الجلسات المكتملة والحصول على تقارير مفصلة
            </p>
          </div>
          <div class="text-left">
            <p class="text-sm text-gray-500">آخر تحديث</p>
            <p class="text-lg font-semibold text-gray-900">{{ now()->format('d/m/Y') }}</p>
          </div>
        </div>
      </div>

      <!-- Earnings Overview Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Current Month Earnings -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500">أرباح هذا الشهر</p>
              <p class="text-3xl font-bold text-gray-900">{{ number_format($earningsData['currentMonthEarnings'], 0) }}</p>
              <p class="text-sm text-gray-600 mt-1">{{ $earningsData['currency'] }}</p>
            </div>
            <div class="w-16 h-16 bg-green-100 rounded-lg flex items-center justify-center">
              <i class="ri-money-dollar-circle-line text-2xl text-green-600"></i>
            </div>
          </div>
          <div class="mt-4 flex items-center">
            @if($earningsData['earningsGrowth'] >= 0)
              <i class="ri-arrow-up-line text-green-600 ml-1"></i>
              <span class="text-green-600 text-sm font-medium">+{{ $earningsData['earningsGrowth'] }}%</span>
            @else
              <i class="ri-arrow-down-line text-red-600 ml-1"></i>
              <span class="text-red-600 text-sm font-medium">{{ $earningsData['earningsGrowth'] }}%</span>
            @endif
            <span class="text-gray-500 text-sm mr-2">مقارنة بالشهر الماضي</span>
          </div>
        </div>

        <!-- Total Sessions This Month -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500">جلسات هذا الشهر</p>
              <p class="text-3xl font-bold text-gray-900">{{ $earningsData['currentMonthSessions'] }}</p>
              <p class="text-sm text-gray-600 mt-1">جلسة مكتملة</p>
            </div>
            <div class="w-16 h-16 bg-blue-100 rounded-lg flex items-center justify-center">
              <i class="ri-calendar-check-line text-2xl text-blue-600"></i>
            </div>
          </div>
          <div class="mt-4">
            <p class="text-sm text-gray-500">
              {{ number_format($earningsData['sessionPrice'], 0) }} {{ $earningsData['currency'] }} لكل جلسة
            </p>
          </div>
        </div>

        <!-- Total Earnings -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500">إجمالي الأرباح</p>
              <p class="text-3xl font-bold text-gray-900">{{ number_format($earningsData['totalEarnings'], 0) }}</p>
              <p class="text-sm text-gray-600 mt-1">{{ $earningsData['currency'] }}</p>
            </div>
            <div class="w-16 h-16 bg-purple-100 rounded-lg flex items-center justify-center">
              <i class="ri-wallet-3-line text-2xl text-purple-600"></i>
            </div>
          </div>
          <div class="mt-4">
            <p class="text-sm text-gray-500">منذ بداية العمل</p>
          </div>
        </div>
      </div>

      <!-- Earnings Details -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Session Pricing -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="ri-price-tag-3-line text-blue-600 ml-2"></i>
            تسعيرة الجلسات
          </h3>
          
          <div class="space-y-4">
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
              <div>
                <p class="font-medium text-gray-900">سعر الجلسة الواحدة</p>
                <p class="text-sm text-gray-500">محدد من قبل الإدارة</p>
              </div>
              <div class="text-left">
                <p class="text-xl font-bold text-primary">{{ number_format($earningsData['sessionPrice'], 0) }}</p>
                <p class="text-sm text-gray-500">{{ $earningsData['currency'] }}</p>
              </div>
            </div>
            
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <div class="flex items-start">
                <i class="ri-information-line text-blue-600 text-lg ml-2 mt-0.5"></i>
                <div>
                  <p class="text-sm font-medium text-blue-900">كيف يتم حساب الأرباح؟</p>
                  <p class="text-sm text-blue-700 mt-1">
                    الأرباح = عدد الجلسات المكتملة × سعر الجلسة الواحدة
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Payment Schedule -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="ri-calendar-event-line text-green-600 ml-2"></i>
            جدول المدفوعات
          </h3>
          
          <div class="space-y-4">
            <div class="flex items-center justify-between p-4 bg-green-50 rounded-lg">
              <div>
                <p class="font-medium text-gray-900">موعد الدفع القادم</p>
                <p class="text-sm text-gray-500">نهاية الشهر الحالي</p>
              </div>
              <div class="text-left">
                <p class="text-lg font-bold text-green-600">{{ now()->endOfMonth()->format('d/m/Y') }}</p>
              </div>
            </div>
            
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
              <div>
                <p class="font-medium text-gray-900">المبلغ المتوقع</p>
                <p class="text-sm text-gray-500">بناءً على الجلسات الحالية</p>
              </div>
              <div class="text-left">
                <p class="text-lg font-bold text-gray-900">{{ number_format($earningsData['currentMonthEarnings'], 0) }}</p>
                <p class="text-sm text-gray-500">{{ $earningsData['currency'] }}</p>
              </div>
            </div>
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
              <div class="flex items-start">
                <i class="ri-time-line text-yellow-600 text-lg ml-2 mt-0.5"></i>
                <div>
                  <p class="text-sm font-medium text-yellow-900">موعد الاستلام</p>
                  <p class="text-sm text-yellow-700 mt-1">
                    يتم تحويل المبالغ خلال 3-5 أيام عمل من نهاية الشهر
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Payments History -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 lg:col-span-2">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="ri-history-line text-gray-600 ml-2"></i>
            سجل المدفوعات السابقة
          </h3>
          
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-gray-200">
                  <th class="text-right py-3 px-4 font-medium text-gray-700">الشهر</th>
                  <th class="text-right py-3 px-4 font-medium text-gray-700">عدد الجلسات</th>
                  <th class="text-right py-3 px-4 font-medium text-gray-700">المبلغ</th>
                  <th class="text-right py-3 px-4 font-medium text-gray-700">تاريخ الاستلام</th>
                  <th class="text-right py-3 px-4 font-medium text-gray-700">الحالة</th>
                </tr>
              </thead>
              <tbody>
                <!-- Sample data - would be dynamic -->
                <tr class="border-b border-gray-100">
                  <td class="py-3 px-4">{{ now()->subMonth()->format('F Y') }}</td>
                  <td class="py-3 px-4">{{ $earningsData['currentMonthSessions'] - 3 }}</td>
                  <td class="py-3 px-4">{{ number_format($earningsData['lastMonthEarnings'], 0) }} {{ $earningsData['currency'] }}</td>
                  <td class="py-3 px-4">{{ now()->subMonth()->endOfMonth()->addDays(3)->format('d/m/Y') }}</td>
                  <td class="py-3 px-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                      <i class="ri-check-line ml-1"></i>
                      مستلم
                    </span>
                  </td>
                </tr>
                <tr class="border-b border-gray-100">
                  <td class="py-3 px-4">{{ now()->subMonths(2)->format('F Y') }}</td>
                  <td class="py-3 px-4">{{ $earningsData['currentMonthSessions'] - 1 }}</td>
                  <td class="py-3 px-4">{{ number_format(($earningsData['currentMonthSessions'] - 1) * $earningsData['sessionPrice'], 0) }} {{ $earningsData['currency'] }}</td>
                  <td class="py-3 px-4">{{ now()->subMonths(2)->endOfMonth()->addDays(3)->format('d/m/Y') }}</td>
                  <td class="py-3 px-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                      <i class="ri-check-line ml-1"></i>
                      مستلم
                    </span>
                  </td>
                </tr>
                <tr class="border-b border-gray-100">
                  <td class="py-3 px-4">{{ now()->subMonths(3)->format('F Y') }}</td>
                  <td class="py-3 px-4">{{ $earningsData['currentMonthSessions'] + 2 }}</td>
                  <td class="py-3 px-4">{{ number_format(($earningsData['currentMonthSessions'] + 2) * $earningsData['sessionPrice'], 0) }} {{ $earningsData['currency'] }}</td>
                  <td class="py-3 px-4">{{ now()->subMonths(3)->endOfMonth()->addDays(3)->format('d/m/Y') }}</td>
                  <td class="py-3 px-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                      <i class="ri-check-line ml-1"></i>
                      مستلم
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </main>
</body>
</html>