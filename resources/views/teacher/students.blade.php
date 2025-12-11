<x-layouts.teacher title="{{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }} - طلابي">
  <x-slot name="description">طلاب المعلم - {{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }}</x-slot>

  <div class="max-w-7xl mx-auto">

      <!-- Header -->
      <div class="mb-6 md:mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 mb-1 md:mb-2">
              <i class="ri-group-line text-blue-600 ml-2"></i>
              @if(auth()->user()->isQuranTeacher())
                طلاب حلقات القرآن
              @else
                طلاب الدورات
              @endif
            </h1>
            <p class="text-sm md:text-base text-gray-600">
              @if(auth()->user()->isQuranTeacher())
                إدارة ومتابعة تقدم طلابك في حلقات القرآن المكلف بها
              @else
                إدارة ومتابعة تقدم طلابك في الدورات التي تدرسها
              @endif
            </p>
          </div>
          <div class="text-right sm:text-left bg-white rounded-xl px-4 py-2.5 border border-gray-200 shadow-sm">
            <p class="text-xs md:text-sm text-gray-500">إجمالي الطلاب</p>
            <p class="text-xl md:text-2xl font-bold text-primary">{{ $students->count() ?? 15 }}</p>
          </div>
        </div>
      </div>

      <!-- Students Statistics -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-6 mb-6 md:mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs md:text-sm font-medium text-gray-500">إجمالي الطلاب</p>
              <p class="text-lg md:text-2xl font-bold text-gray-900">{{ $students->count() ?? 15 }}</p>
            </div>
            <div class="w-10 h-10 md:w-12 md:h-12 bg-blue-100 rounded-lg flex items-center justify-center hidden sm:flex">
              <i class="ri-group-line text-lg md:text-xl text-blue-600"></i>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs md:text-sm font-medium text-gray-500">الطلاب النشطون</p>
              <p class="text-lg md:text-2xl font-bold text-gray-900">{{ ($students->count() ?? 15) - 2 }}</p>
            </div>
            <div class="w-10 h-10 md:w-12 md:h-12 bg-green-100 rounded-lg flex items-center justify-center hidden sm:flex">
              <i class="ri-user-line text-lg md:text-xl text-green-600"></i>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs md:text-sm font-medium text-gray-500">
                @if(auth()->user()->isQuranTeacher())
                  معدل الحفظ
                @else
                  معدل الأداء
                @endif
              </p>
              <p class="text-lg md:text-2xl font-bold text-gray-900">78%</p>
            </div>
            <div class="w-10 h-10 md:w-12 md:h-12 bg-yellow-100 rounded-lg flex items-center justify-center hidden sm:flex">
              <i class="ri-bar-chart-line text-lg md:text-xl text-yellow-600"></i>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs md:text-sm font-medium text-gray-500">
                @if(auth()->user()->isQuranTeacher())
                  حلقات نشطة
                @else
                  دورات نشطة
                @endif
              </p>
              <p class="text-lg md:text-2xl font-bold text-gray-900">3</p>
            </div>
            <div class="w-10 h-10 md:w-12 md:h-12 bg-purple-100 rounded-lg flex items-center justify-center hidden sm:flex">
              <i class="ri-book-line text-lg md:text-xl text-purple-600"></i>
            </div>
          </div>
        </div>
      </div>

      @if(auth()->user()->isQuranTeacher())
        <!-- Quran Circles -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-6 md:mb-8">
          <h3 class="text-base md:text-lg font-semibold text-gray-900 mb-3 md:mb-4">
            <i class="ri-group-2-line text-purple-600 ml-2"></i>
            حلقات القرآن المكلف بها
          </h3>

          <!-- Sample circles -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-6">
            <div class="border border-gray-200 rounded-lg p-3 md:p-4">
              <div class="flex items-center justify-between mb-2 md:mb-3">
                <h4 class="font-medium text-gray-900 text-sm md:text-base">دائرة الحفظ المسائية</h4>
                <span class="w-2.5 h-2.5 md:w-3 md:h-3 bg-green-400 rounded-full"></span>
              </div>
              <p class="text-xs md:text-sm text-gray-500 mb-1 md:mb-2">8 طلاب</p>
              <p class="text-xs md:text-sm text-gray-600">الأحد - الثلاثاء - الخميس</p>
              <p class="text-xs md:text-sm text-gray-600">4:00 - 5:30 مساءً</p>
              <div class="mt-2 md:mt-3 flex items-center gap-2">
                <button class="min-h-[32px] text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">عرض الطلاب</button>
                <button class="min-h-[32px] text-xs bg-green-100 text-green-800 px-2 py-1 rounded">تقرير التقدم</button>
              </div>
            </div>

            <div class="border border-gray-200 rounded-lg p-3 md:p-4">
              <div class="flex items-center justify-between mb-2 md:mb-3">
                <h4 class="font-medium text-gray-900 text-sm md:text-base">دائرة التجويد</h4>
                <span class="w-2.5 h-2.5 md:w-3 md:h-3 bg-green-400 rounded-full"></span>
              </div>
              <p class="text-xs md:text-sm text-gray-500 mb-1 md:mb-2">5 طلاب</p>
              <p class="text-xs md:text-sm text-gray-600">السبت - الاثنين</p>
              <p class="text-xs md:text-sm text-gray-600">6:00 - 7:30 مساءً</p>
              <div class="mt-2 md:mt-3 flex items-center gap-2">
                <button class="min-h-[32px] text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">عرض الطلاب</button>
                <button class="min-h-[32px] text-xs bg-green-100 text-green-800 px-2 py-1 rounded">تقرير التقدم</button>
              </div>
            </div>

            <div class="border border-gray-200 rounded-lg p-3 md:p-4">
              <div class="flex items-center justify-between mb-2 md:mb-3">
                <h4 class="font-medium text-gray-900 text-sm md:text-base">دائرة المراجعة</h4>
                <span class="w-2.5 h-2.5 md:w-3 md:h-3 bg-yellow-400 rounded-full"></span>
              </div>
              <p class="text-xs md:text-sm text-gray-500 mb-1 md:mb-2">12 طالب</p>
              <p class="text-xs md:text-sm text-gray-600">الجمعة</p>
              <p class="text-xs md:text-sm text-gray-600">3:00 - 5:00 مساءً</p>
              <div class="mt-2 md:mt-3 flex items-center gap-2">
                <button class="min-h-[32px] text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">عرض الطلاب</button>
                <button class="min-h-[32px] text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">تحتاج متابعة</button>
              </div>
            </div>
          </div>
        </div>
      @else
        <!-- Academic Courses -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-6 md:mb-8">
          <h3 class="text-base md:text-lg font-semibold text-gray-900 mb-3 md:mb-4">
            <i class="ri-book-2-line text-blue-600 ml-2"></i>
            الدورات التي أدرسها
          </h3>

          <!-- Sample courses -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-6">
            <div class="border border-gray-200 rounded-lg p-3 md:p-4">
              <div class="flex items-center justify-between mb-2 md:mb-3">
                <h4 class="font-medium text-gray-900 text-sm md:text-base">دورة الرياضيات المتقدمة</h4>
                <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">نشطة</span>
              </div>
              <p class="text-xs md:text-sm text-gray-500 mb-1 md:mb-2">22 طالب مسجل</p>
              <p class="text-xs md:text-sm text-gray-600">دورة تفاعلية • أنشأتها بنفسي</p>
              <div class="mt-2 md:mt-3 flex items-center gap-2">
                <button class="min-h-[32px] text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">إدارة الطلاب</button>
                <button class="min-h-[32px] text-xs bg-green-100 text-green-800 px-2 py-1 rounded">عرض التقدم</button>
              </div>
            </div>

            <div class="border border-gray-200 rounded-lg p-3 md:p-4">
              <div class="flex items-center justify-between mb-2 md:mb-3">
                <h4 class="font-medium text-gray-900 text-sm md:text-base">أساسيات الفيزياء</h4>
                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">مكلف بها</span>
              </div>
              <p class="text-xs md:text-sm text-gray-500 mb-1 md:mb-2">18 طالب مسجل</p>
              <p class="text-xs md:text-sm text-gray-600">دورة مسجلة • مكلف من الإدارة</p>
              <div class="mt-2 md:mt-3 flex items-center gap-2">
                <button class="min-h-[32px] text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">إدارة الطلاب</button>
                <button class="min-h-[32px] text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded">عرض الواجبات</button>
              </div>
            </div>
          </div>
        </div>
      @endif

      <!-- Students List -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 md:gap-4 mb-4">
          <h3 class="text-base md:text-lg font-semibold text-gray-900">
            <i class="ri-user-3-line text-green-600 ml-2"></i>
            قائمة الطلاب
          </h3>
          <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3">
            <select class="min-h-[44px] border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
              <option>جميع الطلاب</option>
              @if(auth()->user()->isQuranTeacher())
                <option>طلاب الحفظ</option>
                <option>طلاب التجويد</option>
                <option>طلاب المراجعة</option>
              @else
                <option>طلاب الرياضيات</option>
                <option>طلاب الفيزياء</option>
                <option>الطلاب النشطون</option>
              @endif
            </select>
            <input type="search" placeholder="البحث عن طالب..."
                   class="min-h-[44px] border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
          </div>
        </div>

        <!-- Desktop Table View -->
        <div class="hidden md:block overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="text-right py-3 px-4 font-medium text-gray-700">الطالب</th>
                <th class="text-right py-3 px-4 font-medium text-gray-700">
                  @if(auth()->user()->isQuranTeacher())
                    الدائرة
                  @else
                    الدورة
                  @endif
                </th>
                <th class="text-right py-3 px-4 font-medium text-gray-700">التقدم</th>
                <th class="text-right py-3 px-4 font-medium text-gray-700">آخر جلسة</th>
                <th class="text-right py-3 px-4 font-medium text-gray-700">الحالة</th>
                <th class="text-right py-3 px-4 font-medium text-gray-700">الإجراءات</th>
              </tr>
            </thead>
            <tbody>
              <!-- Sample students -->
              <tr class="border-b border-gray-100 hover:bg-gray-50">
                <td class="py-3 px-4">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                      <span class="text-blue-600 font-medium text-sm">أ</span>
                    </div>
                    <div class="min-w-0">
                      <p class="font-medium text-gray-900 truncate">أحمد محمد علي</p>
                      <p class="text-sm text-gray-500 truncate">ahmed@example.com</p>
                    </div>
                  </div>
                </td>
                <td class="py-3 px-4">
                  @if(auth()->user()->isQuranTeacher())
                    دائرة الحفظ المسائية
                  @else
                    دورة الرياضيات المتقدمة
                  @endif
                </td>
                <td class="py-3 px-4">
                  <div class="flex items-center">
                    <div class="w-16 bg-gray-200 rounded-full h-2 ml-2">
                      <div class="bg-green-600 h-2 rounded-full" style="width: 75%"></div>
                    </div>
                    <span class="text-sm text-gray-600">75%</span>
                  </div>
                </td>
                <td class="py-3 px-4 whitespace-nowrap">أمس</td>
                <td class="py-3 px-4">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    نشط
                  </span>
                </td>
                <td class="py-3 px-4">
                  <div class="flex items-center gap-1">
                    <button class="min-h-[36px] min-w-[36px] p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors">
                      <i class="ri-eye-line text-lg"></i>
                    </button>
                    <button class="min-h-[36px] min-w-[36px] p-2 text-green-600 hover:text-green-800 hover:bg-green-50 rounded-lg transition-colors">
                      <i class="ri-message-3-line text-lg"></i>
                    </button>
                    <button class="min-h-[36px] min-w-[36px] p-2 text-purple-600 hover:text-purple-800 hover:bg-purple-50 rounded-lg transition-colors">
                      <i class="ri-bar-chart-line text-lg"></i>
                    </button>
                  </div>
                </td>
              </tr>

              <!-- More sample students -->
              <tr class="border-b border-gray-100 hover:bg-gray-50">
                <td class="py-3 px-4">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0">
                      <span class="text-purple-600 font-medium text-sm">س</span>
                    </div>
                    <div class="min-w-0">
                      <p class="font-medium text-gray-900 truncate">سارة عبدالله</p>
                      <p class="text-sm text-gray-500 truncate">sara@example.com</p>
                    </div>
                  </div>
                </td>
                <td class="py-3 px-4">
                  @if(auth()->user()->isQuranTeacher())
                    دائرة التجويد
                  @else
                    أساسيات الفيزياء
                  @endif
                </td>
                <td class="py-3 px-4">
                  <div class="flex items-center">
                    <div class="w-16 bg-gray-200 rounded-full h-2 ml-2">
                      <div class="bg-blue-600 h-2 rounded-full" style="width: 60%"></div>
                    </div>
                    <span class="text-sm text-gray-600">60%</span>
                  </div>
                </td>
                <td class="py-3 px-4 whitespace-nowrap">3 أيام</td>
                <td class="py-3 px-4">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                    يحتاج متابعة
                  </span>
                </td>
                <td class="py-3 px-4">
                  <div class="flex items-center gap-1">
                    <button class="min-h-[36px] min-w-[36px] p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors">
                      <i class="ri-eye-line text-lg"></i>
                    </button>
                    <button class="min-h-[36px] min-w-[36px] p-2 text-green-600 hover:text-green-800 hover:bg-green-50 rounded-lg transition-colors">
                      <i class="ri-message-3-line text-lg"></i>
                    </button>
                    <button class="min-h-[36px] min-w-[36px] p-2 text-purple-600 hover:text-purple-800 hover:bg-purple-50 rounded-lg transition-colors">
                      <i class="ri-bar-chart-line text-lg"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile Card View -->
        <div class="md:hidden space-y-3">
          <!-- Sample Student Card 1 -->
          <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
            <div class="flex items-start gap-3">
              <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                <span class="text-blue-600 font-medium">أ</span>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2">
                  <div class="min-w-0">
                    <p class="font-medium text-gray-900 truncate">أحمد محمد علي</p>
                    <p class="text-xs text-gray-500 truncate">ahmed@example.com</p>
                  </div>
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 flex-shrink-0">
                    نشط
                  </span>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-gray-600">
                  <span class="bg-gray-100 px-2 py-1 rounded">
                    @if(auth()->user()->isQuranTeacher())
                      دائرة الحفظ المسائية
                    @else
                      دورة الرياضيات المتقدمة
                    @endif
                  </span>
                  <span>آخر جلسة: أمس</span>
                </div>

                <div class="mt-3 flex items-center gap-2">
                  <div class="flex-1 bg-gray-200 rounded-full h-2">
                    <div class="bg-green-600 h-2 rounded-full" style="width: 75%"></div>
                  </div>
                  <span class="text-xs text-gray-600 font-medium">75%</span>
                </div>

                <div class="mt-3 flex items-center justify-end gap-2 border-t border-gray-100 pt-3">
                  <button class="min-h-[44px] min-w-[44px] p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors flex items-center justify-center">
                    <i class="ri-eye-line text-xl"></i>
                  </button>
                  <button class="min-h-[44px] min-w-[44px] p-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors flex items-center justify-center">
                    <i class="ri-message-3-line text-xl"></i>
                  </button>
                  <button class="min-h-[44px] min-w-[44px] p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition-colors flex items-center justify-center">
                    <i class="ri-bar-chart-line text-xl"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Sample Student Card 2 -->
          <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
            <div class="flex items-start gap-3">
              <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0">
                <span class="text-purple-600 font-medium">س</span>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2">
                  <div class="min-w-0">
                    <p class="font-medium text-gray-900 truncate">سارة عبدالله</p>
                    <p class="text-xs text-gray-500 truncate">sara@example.com</p>
                  </div>
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 flex-shrink-0">
                    يحتاج متابعة
                  </span>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-gray-600">
                  <span class="bg-gray-100 px-2 py-1 rounded">
                    @if(auth()->user()->isQuranTeacher())
                      دائرة التجويد
                    @else
                      أساسيات الفيزياء
                    @endif
                  </span>
                  <span>آخر جلسة: 3 أيام</span>
                </div>

                <div class="mt-3 flex items-center gap-2">
                  <div class="flex-1 bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: 60%"></div>
                  </div>
                  <span class="text-xs text-gray-600 font-medium">60%</span>
                </div>

                <div class="mt-3 flex items-center justify-end gap-2 border-t border-gray-100 pt-3">
                  <button class="min-h-[44px] min-w-[44px] p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors flex items-center justify-center">
                    <i class="ri-eye-line text-xl"></i>
                  </button>
                  <button class="min-h-[44px] min-w-[44px] p-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors flex items-center justify-center">
                    <i class="ri-message-3-line text-xl"></i>
                  </button>
                  <button class="min-h-[44px] min-w-[44px] p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition-colors flex items-center justify-center">
                    <i class="ri-bar-chart-line text-xl"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

  </div>
</x-layouts.teacher>