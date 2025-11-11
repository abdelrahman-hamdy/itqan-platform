<!-- Learning Section Card Component -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow duration-300">
  <!-- Card Header -->
  <div class="p-6 border-b border-gray-100">
    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-3 space-x-reverse">
        <div class="w-12 h-12 rounded-lg flex items-center justify-center {{ $iconBgColor ?? 'bg-primary' }}">
          <i class="{{ $icon ?? 'ri-book-open-line' }} text-xl text-white"></i>
        </div>
        <div>
          <h3 class="text-lg font-semibold text-gray-900">{{ $title ?? 'عنوان القسم' }}</h3>
          <p class="text-sm text-gray-500">{{ $subtitle ?? 'وصف القسم' }}</p>
        </div>
      </div>
      <div class="flex items-center space-x-2 space-x-reverse">
        @if(!isset($hideDots) || !$hideDots)
          <button class="text-gray-400 hover:text-gray-600 transition-colors" aria-label="خيارات إضافية">
            <i class="ri-more-2-fill"></i>
          </button>
        @endif
      </div>
    </div>
  </div>

  <!-- Card Content -->
  <div class="p-6">
    @if(isset($items) && count($items) > 0)
      <div class="space-y-4">
        @foreach($items as $item)
          @if(isset($item['link']))
            <a href="{{ $item['link'] }}" class="block">
              <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                @if(!isset($hideItemIcons) || !$hideItemIcons)
                  <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ $item['iconBgColor'] ?? 'bg-blue-100' }} ml-3">
                    <i class="{{ $item['icon'] ?? 'ri-book-line' }} text-sm {{ isset($item['iconColor']) ? $item['iconColor'] : 'text-blue-600' }}"></i>
                  </div>
                @endif
                <div class="flex-1">
                  <h4 class="font-medium text-gray-900">{{ $item['title'] }}</h4>
                  <p class="text-sm text-gray-500">{{ $item['description'] }}</p>
                  @if(isset($item['progress']))
                    <div class="mt-2">
                      <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                        <span>التقدم</span>
                        <span>{{ $item['progress'] }}%</span>
                      </div>
                      <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-primary h-2 rounded-full transition-all duration-300" 
                             style="width: {{ $item['progress'] }}%"></div>
                      </div>
                    </div>
                  @endif
                </div>
                <div class="flex items-center space-x-2 space-x-reverse mr-3">
                  @if(isset($item['status']))
                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                               {{ $item['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                  ($item['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                   'bg-gray-100 text-gray-800') }}">
                      {{ $item['status'] === 'active' ? 'نشط' : 
                         ($item['status'] === 'pending' ? 'قيد الانتظار' : 'غير نشط') }}
                    </span>
                  @endif
                  <div class="text-primary hover:text-secondary transition-colors">
                    <i class="ri-arrow-left-s-line"></i>
                  </div>
                </div>
              </div>
            </a>
          @else
            <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
              @if(!isset($hideItemIcons) || !$hideItemIcons)
                <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ $item['iconBgColor'] ?? 'bg-blue-100' }} ml-3">
                  <i class="{{ $item['icon'] ?? 'ri-book-line' }} text-sm {{ isset($item['iconColor']) ? $item['iconColor'] : 'text-blue-600' }}"></i>
                </div>
              @endif
              <div class="flex-1">
                <h4 class="font-medium text-gray-900">{{ $item['title'] }}</h4>
                <p class="text-sm text-gray-500">{{ $item['description'] }}</p>
                @if(isset($item['progress']))
                  <div class="mt-2">
                    <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                      <span>التقدم</span>
                      <span>{{ $item['progress'] }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                      <div class="bg-primary h-2 rounded-full transition-all duration-300" 
                           style="width: {{ $item['progress'] }}%"></div>
                    </div>
                  </div>
                @endif
              </div>
              <div class="flex items-center space-x-2 space-x-reverse mr-3">
                @if(isset($item['status']))
                  <span class="px-2 py-1 text-xs font-medium rounded-full 
                             {{ $item['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                ($item['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                 'bg-gray-100 text-gray-800') }}">
                    {{ $item['status'] === 'active' ? 'نشط' : 
                       ($item['status'] === 'pending' ? 'قيد الانتظار' : 'غير نشط') }}
                  </span>
                @endif
                <button class="text-primary hover:text-secondary transition-colors" aria-label="عرض التفاصيل">
                  <i class="ri-arrow-left-s-line"></i>
                </button>
              </div>
            </div>
          @endif
        @endforeach
      </div>
    @else
      <div class="text-center py-8">
        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <i class="ri-inbox-line text-2xl text-gray-400"></i>
        </div>
        <h4 class="text-lg font-medium text-gray-900 mb-2">{{ $emptyTitle ?? 'لا توجد عناصر' }}</h4>
        <p class="text-gray-500 mb-4">{{ $emptyDescription ?? 'لم يتم العثور على أي عناصر في هذا القسم' }}</p>
        <button class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-secondary transition-colors">
          {{ $emptyActionText ?? 'إضافة عنصر جديد' }}
        </button>
      </div>
    @endif
  </div>

  <!-- Card Footer -->
  @if(isset($footer))
    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4 space-x-reverse text-sm text-gray-500">
          @if(isset($stats))
            @foreach($stats as $stat)
              <div class="flex items-center space-x-1 space-x-reverse">
                <i class="{{ $stat['icon'] ?? 'ri-user-line' }}"></i>
                <span>{{ $stat['value'] }}</span>
              </div>
            @endforeach
          @endif
        </div>
        <a href="{{ $footer['link'] ?? '#' }}" 
           class="text-primary hover:text-secondary text-sm font-medium transition-colors">
          {{ $footer['text'] ?? 'عرض الكل' }}
          <i class="ri-arrow-left-s-line mr-1"></i>
        </a>
      </div>
    </div>
  @endif
</div> 