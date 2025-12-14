@props([
    'title',
    'statusBadge' => null,
    'statusClass' => 'bg-gray-100 text-gray-800',
    'metadata' => [],
    'description' => null,
    'actions' => [],
    'avatar' => null,
    'icon' => null,
    'iconBgClass' => 'bg-gradient-to-br from-blue-500 to-blue-600',
])

<div class="px-4 md:px-6 py-4 hover:bg-gray-50 transition-colors">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div class="flex items-start gap-3 md:gap-4 flex-1 min-w-0 lg:max-w-[65%]">
            <!-- Avatar or Icon -->
            @if($avatar)
                <x-avatar :user="$avatar" size="sm" />
            @elseif($icon)
                <div class="w-10 h-10 md:w-12 md:h-12 {{ $iconBgClass }} rounded-full flex items-center justify-center text-white font-bold text-base md:text-lg flex-shrink-0">
                    <i class="{{ $icon }}"></i>
                </div>
            @endif

            <!-- Info -->
            <div class="flex-1 min-w-0 overflow-hidden">
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <h3 class="text-base md:text-lg font-semibold text-gray-900 truncate">
                        {{ $title }}
                    </h3>

                    <!-- Status Badge -->
                    @if($statusBadge)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                            {{ $statusBadge }}
                        </span>
                    @endif
                </div>

                <!-- Metadata -->
                @if(count($metadata) > 0)
                    <div class="flex flex-wrap items-center gap-2 md:gap-4 text-xs md:text-sm text-gray-600">
                        @foreach($metadata as $item)
                            <span class="flex items-center {{ $item['class'] ?? '' }}">
                                @if(isset($item['icon']))
                                    <i class="{{ $item['icon'] }} ml-1"></i>
                                @endif
                                {{ $item['text'] }}
                            </span>
                        @endforeach
                    </div>
                @endif

                <!-- Description -->
                @if($description)
                    <p class="mt-1 text-xs md:text-sm text-gray-500 line-clamp-1">{{ $description }}</p>
                @endif
            </div>
        </div>

        <!-- Actions -->
        @if(count($actions) > 0)
            <div class="flex flex-wrap items-center gap-2 flex-shrink-0">
                @foreach($actions as $action)
                    @if(isset($action['onclick']))
                        <button onclick="{{ $action['onclick'] }}"
                               class="min-h-[44px] inline-flex items-center justify-center px-3 py-2 {{ $action['class'] ?? 'bg-blue-600 hover:bg-blue-700 text-white' }} text-sm font-medium rounded-lg transition-colors flex-1 sm:flex-none">
                            @if(isset($action['icon']))
                                <i class="{{ $action['icon'] }} ml-1"></i>
                            @endif
                            <span class="hidden sm:inline">{{ $action['label'] }}</span>
                            @if(isset($action['shortLabel']))
                                <span class="sm:hidden">{{ $action['shortLabel'] }}</span>
                            @endif
                        </button>
                    @else
                        <a href="{{ $action['href'] }}"
                           class="min-h-[44px] inline-flex items-center justify-center px-3 py-2 {{ $action['class'] ?? 'bg-blue-600 hover:bg-blue-700 text-white' }} text-sm font-medium rounded-lg transition-colors flex-1 sm:flex-none"
                           @if(isset($action['title'])) title="{{ $action['title'] }}" @endif>
                            @if(isset($action['icon']))
                                <i class="{{ $action['icon'] }} ml-1"></i>
                            @endif
                            <span class="hidden sm:inline">{{ $action['label'] }}</span>
                            @if(isset($action['shortLabel']))
                                <span class="sm:hidden">{{ $action['shortLabel'] }}</span>
                            @endif
                        </a>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</div>
