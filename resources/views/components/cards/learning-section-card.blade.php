<!-- Learning Section Card Component -->
@php
  // Map primary color to Tailwind classes
  $colorMap = [
    'green' => ['bg' => 'bg-green-600', 'hover' => 'hover:bg-green-700', 'text' => 'text-green-600', 'textHover' => 'hover:text-green-700'],
    'yellow' => ['bg' => 'bg-yellow-500', 'hover' => 'hover:bg-yellow-600', 'text' => 'text-yellow-600', 'textHover' => 'hover:text-yellow-700'],
    'blue' => ['bg' => 'bg-blue-600', 'hover' => 'hover:bg-blue-700', 'text' => 'text-blue-600', 'textHover' => 'hover:text-blue-700'],
    'violet' => ['bg' => 'bg-violet-600', 'hover' => 'hover:bg-violet-700', 'text' => 'text-violet-600', 'textHover' => 'hover:text-violet-700'],
    'cyan' => ['bg' => 'bg-cyan-600', 'hover' => 'hover:bg-cyan-700', 'text' => 'text-cyan-600', 'textHover' => 'hover:text-cyan-700'],
  ];
  $colors = $colorMap[$primaryColor ?? 'blue'] ?? $colorMap['blue'];
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow duration-300 flex flex-col h-full w-full">
  <!-- Card Header -->
  <div class="p-6 border-b border-gray-100">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 rounded-lg flex items-center justify-center {{ $iconBgColor ?? 'bg-primary' }}">
          <i class="{{ $icon ?? 'ri-book-open-line' }} text-xl text-white"></i>
        </div>
        <div>
          <h3 class="text-lg font-semibold text-gray-900">{{ $title ?? __('components.cards.learning_section.section_title') }}</h3>
          <p class="text-sm text-gray-500">{{ $subtitle ?? __('components.cards.learning_section.section_description') }}</p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        @if(!isset($hideDots) || !$hideDots)
          <button class="text-gray-400 hover:text-gray-600 transition-colors" aria-label="{{ __('components.cards.learning_section.additional_options') }}">
            <i class="ri-more-2-fill"></i>
          </button>
        @endif
      </div>
    </div>
  </div>

  <!-- Card Content -->
  <div class="p-4 md:p-6">
    @if(isset($items) && count($items) > 0)
      <div class="space-y-3">
        @foreach($items as $item)
          @if(isset($item['link']))
            <a href="{{ $item['link'] }}" class="block">
              <div class="flex items-center p-3 md:p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                @if(!isset($hideItemIcons) || !$hideItemIcons)
                  <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 {{ $item['iconBgColor'] ?? 'bg-blue-100' }} me-3">
                    <i class="{{ $item['icon'] ?? 'ri-book-line' }} text-base {{ isset($item['iconColor']) ? $item['iconColor'] : 'text-blue-600' }}"></i>
                  </div>
                @endif
                <div class="flex-1 min-w-0">
                  <h4 class="font-medium text-gray-900 truncate">{{ $item['title'] }}</h4>
                  <p class="text-sm text-gray-500 truncate">{{ $item['description'] }}</p>
                  @if(isset($item['progress']))
                    <div class="mt-2">
                      <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                        <span>{{ __('components.cards.learning_section.progress') }}</span>
                        <span>{{ number_format($item['progress'], 0) }}%</span>
                      </div>
                      <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="{{ $colors['bg'] }} h-2 rounded-full transition-all duration-300"
                             style="width: {{ number_format($item['progress'], 0) }}%"></div>
                      </div>
                    </div>
                  @endif
                </div>
                <div class="flex items-center gap-2 ms-3 flex-shrink-0">
                  @if(isset($item['status']))
                    @php
                      $statusObj = $item['status'];
                      // Handle enums and custom status objects that have label() and badgeClasses() methods
                      if (is_object($statusObj) && method_exists($statusObj, 'label') && method_exists($statusObj, 'badgeClasses')) {
                        $statusLabel = $statusObj->label();
                        $statusBadgeClasses = $statusObj->badgeClasses();
                      } elseif (is_object($statusObj) && isset($statusObj->label) && isset($statusObj->badgeClasses)) {
                        // Handle custom status objects with properties
                        $statusLabel = $statusObj->label;
                        $statusBadgeClasses = $statusObj->badgeClasses;
                      } else {
                        // Fallback for string statuses
                        $statusConfig = [
                          'active' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'label' => __('components.cards.learning_section.status_labels.active')],
                          'pending' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'label' => __('components.cards.learning_section.status_labels.pending')],
                          'cancelled' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => __('components.cards.learning_section.status_labels.cancelled')],
                          'expired' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'label' => __('components.cards.learning_section.status_labels.expired')],
                          'paused' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-800', 'label' => __('components.cards.learning_section.status_labels.paused')],
                          'completed' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'label' => __('components.cards.learning_section.status_labels.completed')],
                          'enrolled' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'label' => __('components.cards.learning_section.status_labels.enrolled')],
                          'draft' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'label' => __('components.cards.learning_section.status_labels.draft')],
                          'published' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'label' => __('components.cards.learning_section.status_labels.published')],
                        ];
                        $statusValue = is_object($statusObj) ? $statusObj->value : $statusObj;
                        $statusStyle = $statusConfig[$statusValue] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'label' => __('components.cards.learning_section.status_labels.inactive')];
                        $statusBadgeClasses = $statusStyle['bg'] . ' ' . $statusStyle['text'];
                        $statusLabel = $statusStyle['label'];
                      }
                    @endphp
                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusBadgeClasses }}">
                      {{ $statusLabel }}
                    </span>
                  @endif
                  <div class="{{ $colors['text'] }} {{ $colors['textHover'] }} transition-colors">
                    <i class="ri-arrow-left-s-line {{ app()->getLocale() !== 'ar' ? '-scale-x-100' : '' }}"></i>
                  </div>
                </div>
              </div>
            </a>
          @else
            <div class="flex items-center p-3 md:p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
              @if(!isset($hideItemIcons) || !$hideItemIcons)
                <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 {{ $item['iconBgColor'] ?? 'bg-blue-100' }} me-3">
                  <i class="{{ $item['icon'] ?? 'ri-book-line' }} text-base {{ isset($item['iconColor']) ? $item['iconColor'] : 'text-blue-600' }}"></i>
                </div>
              @endif
              <div class="flex-1 min-w-0">
                <h4 class="font-medium text-gray-900 truncate">{{ $item['title'] }}</h4>
                <p class="text-sm text-gray-500 truncate">{{ $item['description'] }}</p>
                @if(isset($item['progress']))
                  <div class="mt-2">
                    <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                      <span>{{ __('components.cards.learning_section.progress') }}</span>
                      <span>{{ number_format($item['progress'], 0) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                      <div class="{{ $colors['bg'] }} h-2 rounded-full transition-all duration-300"
                           style="width: {{ number_format($item['progress'], 0) }}%"></div>
                    </div>
                  </div>
                @endif
              </div>
              <div class="flex items-center gap-2 ms-3 flex-shrink-0">
                @if(isset($item['status']))
                  @php
                    $statusObj = $item['status'];
                    // Handle enums and custom status objects that have label() and badgeClasses() methods
                    if (is_object($statusObj) && method_exists($statusObj, 'label') && method_exists($statusObj, 'badgeClasses')) {
                      $statusLabel = $statusObj->label();
                      $statusBadgeClasses = $statusObj->badgeClasses();
                    } elseif (is_object($statusObj) && isset($statusObj->label) && isset($statusObj->badgeClasses)) {
                      // Handle custom status objects with properties
                      $statusLabel = $statusObj->label;
                      $statusBadgeClasses = $statusObj->badgeClasses;
                    } else {
                      // Fallback for string statuses
                      $statusConfig = [
                        'active' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'label' => __('components.cards.learning_section.status_labels.active')],
                        'pending' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'label' => __('components.cards.learning_section.status_labels.pending')],
                        'cancelled' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => __('components.cards.learning_section.status_labels.cancelled')],
                        'expired' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'label' => __('components.cards.learning_section.status_labels.expired')],
                        'paused' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-800', 'label' => __('components.cards.learning_section.status_labels.paused')],
                        'completed' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'label' => __('components.cards.learning_section.status_labels.completed')],
                        'enrolled' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'label' => __('components.cards.learning_section.status_labels.enrolled')],
                        'dropped' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => __('components.cards.learning_section.status_labels.dropped')],
                        'suspended' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-800', 'label' => __('components.cards.learning_section.status_labels.suspended')],
                        'draft' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'label' => __('components.cards.learning_section.status_labels.draft')],
                        'published' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'label' => __('components.cards.learning_section.status_labels.published')],
                      ];
                      $statusValue = is_object($statusObj) ? $statusObj->value : $statusObj;
                      $statusStyle = $statusConfig[$statusValue] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'label' => __('components.cards.learning_section.status_labels.inactive')];
                      $statusBadgeClasses = $statusStyle['bg'] . ' ' . $statusStyle['text'];
                      $statusLabel = $statusStyle['label'];
                    }
                  @endphp
                  <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusBadgeClasses }}">
                    {{ $statusLabel }}
                  </span>
                @endif
                <div class="{{ $colors['text'] }} {{ $colors['textHover'] }} transition-colors">
                  <i class="ri-arrow-left-s-line {{ app()->getLocale() !== 'ar' ? '-scale-x-100' : '' }}"></i>
                </div>
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
        <h4 class="text-lg font-medium text-gray-900 mb-2">{{ $emptyTitle ?? __('components.cards.learning_section.no_items_title') }}</h4>
        <p class="text-gray-500 mb-4">{{ $emptyDescription ?? __('components.cards.learning_section.no_items_description') }}</p>
        @if(!empty($emptyActionText))
          <a href="{{ $emptyActionLink ?? '#' }}" class="{{ $colors['bg'] }} text-white px-4 py-2 rounded-lg text-sm font-medium {{ $colors['hover'] }} transition-colors inline-block">
            {{ $emptyActionText }}
          </a>
        @endif
      </div>
    @endif
  </div>

  <!-- Card Footer -->
  @if(isset($footer))
    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 mt-auto">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4 text-sm text-gray-500">
          @if(isset($stats))
            @foreach($stats as $stat)
              @if(isset($stat['isActiveCount']) && $stat['isActiveCount'])
                <div class="flex items-center gap-1">
                  <i class="{{ $stat['icon'] ?? 'ri-user-line' }}"></i>
                  <span>{{ $stat['value'] }}</span>
                </div>
              @endif
            @endforeach
          @endif
        </div>
        <a href="{{ $footer['link'] ?? '#' }}"
           class="{{ $colors['text'] }} {{ $colors['textHover'] }} text-sm font-medium transition-colors">
          {{ $footer['text'] ?? __('components.cards.learning_section.view_all') }}
          <i class="ri-arrow-left-s-line ms-1 {{ app()->getLocale() !== 'ar' ? '-scale-x-100' : '' }} inline-block"></i>
        </a>
      </div>
    </div>
  @endif
</div>
