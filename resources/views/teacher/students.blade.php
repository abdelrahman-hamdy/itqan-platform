<x-layouts.teacher title="{{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }} - طلابي">
  <x-slot name="description">طلاب المعلم - {{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }}</x-slot>

  <div class="max-w-7xl mx-auto">
      
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
              <i class="ri-group-line text-blue-600 ml-2"></i>
              @if(auth()->user()->isQuranTeacher())
                طلاب حلقات القرآن
              @else
                طلاب الدورات
              @endif
            </h1>
            <p class="text-gray-600">
              @if(auth()->user()->isQuranTeacher())
                إدارة ومتابعة تقدم طلابك في حلقات القرآن المكلف بها
              @else
                إدارة ومتابعة تقدم طلابك في الدورات التي تدرسها
              @endif
            </p>
          </div>
          <div class="text-left">
            <p class="text-sm text-gray-500">إجمالي الطلاب</p>
            <p class="text-2xl font-bold text-primary">{{ $students->count() ?? 15 }}</p>
          </div>
        </div>
      </div>

      <!-- Students Statistics -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500">إجمالي الطلاب</p>
              <p class="text-2xl font-bold text-gray-900">{{ $students->count() ?? 15 }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
              <i class="ri-group-line text-xl text-blue-600"></i>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500">الطلاب النشطون</p>
              <p class="text-2xl font-bold text-gray-900">{{ ($students->count() ?? 15) - 2 }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
              <i class="ri-user-line text-xl text-green-600"></i>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500">
                @if(auth()->user()->isQuranTeacher())
                  معدل الحفظ
                @else
                  معدل الأداء
                @endif
              </p>
              <p class="text-2xl font-bold text-gray-900">78%</p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
              <i class="ri-bar-chart-line text-xl text-yellow-600"></i>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500">
                @if(auth()->user()->isQuranTeacher())
                  حلقات نشطة
                @else
                  دورات نشطة
                @endif
              </p>
              <p class="text-2xl font-bold text-gray-900">3</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
              <i class="ri-book-line text-xl text-purple-600"></i>
            </div>
          </div>
        </div>
      </div>

      @if(auth()->user()->isQuranTeacher())
        <!-- Quran Circles -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="ri-group-2-line text-purple-600 ml-2"></i>
            حلقات القرآن المكلف بها
          </h3>
          
          <!-- Sample circles -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="border border-gray-200 rounded-lg p-4">
              <div class="flex items-center justify-between mb-3">
                <h4 class="font-medium text-gray-900">دائرة الحفظ المسائية</h4>
                <span class="w-3 h-3 bg-green-400 rounded-full"></span>
              </div>
              <p class="text-sm text-gray-500 mb-2">8 طلاب</p>
              <p class="text-sm text-gray-600">الأحد - الثلاثاء - الخميس</p>
              <p class="text-sm text-gray-600">4:00 - 5:30 مساءً</p>
              <div class="mt-3 flex items-center space-x-2 space-x-reverse">
                <button class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">عرض الطلاب</button>
                <button class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">تقرير التقدم</button>
              </div>
            </div>
            
            <div class="border border-gray-200 rounded-lg p-4">
              <div class="flex items-center justify-between mb-3">
                <h4 class="font-medium text-gray-900">دائرة التجويد</h4>
                <span class="w-3 h-3 bg-green-400 rounded-full"></span>
              </div>
              <p class="text-sm text-gray-500 mb-2">5 طلاب</p>
              <p class="text-sm text-gray-600">السبت - الاثنين</p>
              <p class="text-sm text-gray-600">6:00 - 7:30 مساءً</p>
              <div class="mt-3 flex items-center space-x-2 space-x-reverse">
                <button class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">عرض الطلاب</button>
                <button class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">تقرير التقدم</button>
              </div>
            </div>
            
            <div class="border border-gray-200 rounded-lg p-4">
              <div class="flex items-center justify-between mb-3">
                <h4 class="font-medium text-gray-900">دائرة المراجعة</h4>
                <span class="w-3 h-3 bg-yellow-400 rounded-full"></span>
              </div>
              <p class="text-sm text-gray-500 mb-2">12 طالب</p>
              <p class="text-sm text-gray-600">الجمعة</p>
              <p class="text-sm text-gray-600">3:00 - 5:00 مساءً</p>
              <div class="mt-3 flex items-center space-x-2 space-x-reverse">
                <button class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">عرض الطلاب</button>
                <button class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">تحتاج متابعة</button>
              </div>
            </div>
          </div>
        </div>
      @else
        <!-- Academic Courses -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="ri-book-2-line text-blue-600 ml-2"></i>
            الدورات التي أدرسها
          </h3>
          
          <!-- Sample courses -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="border border-gray-200 rounded-lg p-4">
              <div class="flex items-center justify-between mb-3">
                <h4 class="font-medium text-gray-900">دورة الرياضيات المتقدمة</h4>
                <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">نشطة</span>
              </div>
              <p class="text-sm text-gray-500 mb-2">22 طالب مسجل</p>
              <p class="text-sm text-gray-600">دورة تفاعلية • أنشأتها بنفسي</p>
              <div class="mt-3 flex items-center space-x-2 space-x-reverse">
                <button class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">إدارة الطلاب</button>
                <button class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">عرض التقدم</button>
              </div>
            </div>
            
            <div class="border border-gray-200 rounded-lg p-4">
              <div class="flex items-center justify-between mb-3">
                <h4 class="font-medium text-gray-900">أساسيات الفيزياء</h4>
                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">مكلف بها</span>
              </div>
              <p class="text-sm text-gray-500 mb-2">18 طالب مسجل</p>
              <p class="text-sm text-gray-600">دورة مسجلة • مكلف من الإدارة</p>
              <div class="mt-3 flex items-center space-x-2 space-x-reverse">
                <button class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">إدارة الطلاب</button>
                <button class="text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded">عرض الواجبات</button>
              </div>
            </div>
          </div>
        </div>
      @endif

      <!-- Students List -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">
            <i class="ri-user-3-line text-green-600 ml-2"></i>
            قائمة الطلاب
          </h3>
          <div class="flex items-center space-x-4 space-x-reverse">
            <select class="border border-gray-300 rounded-md px-3 py-2 text-sm">
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
                   class="border border-gray-300 rounded-md px-3 py-2 text-sm">
          </div>
        </div>

        <div class="overflow-x-auto">
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
              <tr class="border-b border-gray-100">
                <td class="py-3 px-4">
                  <div class="flex items-center space-x-3 space-x-reverse">
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                      <span class="text-blue-600 font-medium text-sm">أ</span>
                    </div>
                    <div>
                      <p class="font-medium text-gray-900">أحمد محمد علي</p>
                      <p class="text-sm text-gray-500">ahmed@example.com</p>
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
                <td class="py-3 px-4">أمس</td>
                <td class="py-3 px-4">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    نشط
                  </span>
                </td>
                <td class="py-3 px-4">
                  <div class="flex items-center space-x-2 space-x-reverse">
                    <button class="text-blue-600 hover:text-blue-800">
                      <i class="ri-eye-line"></i>
                    </button>
                    <button class="text-green-600 hover:text-green-800">
                      <i class="ri-message-3-line"></i>
                    </button>
                    <button class="text-purple-600 hover:text-purple-800">
                      <i class="ri-bar-chart-line"></i>
                    </button>
                  </div>
                </td>
              </tr>

              <!-- More sample students -->
              <tr class="border-b border-gray-100">
                <td class="py-3 px-4">
                  <div class="flex items-center space-x-3 space-x-reverse">
                    <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                      <span class="text-purple-600 font-medium text-sm">س</span>
                    </div>
                    <div>
                      <p class="font-medium text-gray-900">سارة عبدالله</p>
                      <p class="text-sm text-gray-500">sara@example.com</p>
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
                <td class="py-3 px-4">3 أيام</td>
                <td class="py-3 px-4">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                    يحتاج متابعة
                  </span>
                </td>
                <td class="py-3 px-4">
                  <div class="flex items-center space-x-2 space-x-reverse">
                    <button class="text-blue-600 hover:text-blue-800">
                      <i class="ri-eye-line"></i>
                    </button>
                    <button class="text-green-600 hover:text-green-800">
                      <i class="ri-message-3-line"></i>
                    </button>
                    <button class="text-purple-600 hover:text-purple-800">
                      <i class="ri-bar-chart-line"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

  </div>
</x-layouts.teacher>