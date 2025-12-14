@props([
    'title',
    'subtitle',
    'items',
    'stats' => [],
    'filterOptions' => [],
    'filterParam' => 'status',
    'listTitle',
    'emptyIcon' => 'ri-inbox-line',
    'emptyTitle' => 'لا توجد عناصر',
    'emptyDescription' => 'لم يتم العثور على أي عناصر',
    'emptyFilterDescription' => 'لا توجد عناصر بالحالة المحددة',
    'clearFilterRoute' => null,
    'clearFilterText' => 'عرض الكل',
    'breadcrumbs' => [],
    'themeColor' => 'blue', // blue, green, yellow, indigo, purple, violet, orange
])

@php
    $colorConfig = match($themeColor) {
        'green' => [
            'button' => 'bg-green-600 hover:bg-green-700',
            'text' => 'text-green-600',
            'ring' => 'focus:ring-green-500 focus:border-green-500',
        ],
        'yellow' => [
            'button' => 'bg-yellow-600 hover:bg-yellow-700',
            'text' => 'text-yellow-600',
            'ring' => 'focus:ring-yellow-500 focus:border-yellow-500',
        ],
        'indigo' => [
            'button' => 'bg-indigo-600 hover:bg-indigo-700',
            'text' => 'text-indigo-600',
            'ring' => 'focus:ring-indigo-500 focus:border-indigo-500',
        ],
        'purple' => [
            'button' => 'bg-purple-600 hover:bg-purple-700',
            'text' => 'text-purple-600',
            'ring' => 'focus:ring-purple-500 focus:border-purple-500',
        ],
        'violet' => [
            'button' => 'bg-violet-600 hover:bg-violet-700',
            'text' => 'text-violet-600',
            'ring' => 'focus:ring-violet-500 focus:border-violet-500',
        ],
        'orange' => [
            'button' => 'bg-orange-600 hover:bg-orange-700',
            'text' => 'text-orange-600',
            'ring' => 'focus:ring-orange-500 focus:border-orange-500',
        ],
        default => [
            'button' => 'bg-blue-600 hover:bg-blue-700',
            'text' => 'text-blue-600',
            'ring' => 'focus:ring-blue-500 focus:border-blue-500',
        ],
    };
@endphp

<div class="min-h-screen bg-gray-50 py-4 md:py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumbs -->
        @if(count($breadcrumbs) > 0)
            <nav class="mb-4 md:mb-6 overflow-x-auto">
                <ol class="flex items-center gap-2 text-xs md:text-sm text-gray-600 whitespace-nowrap">
                    @foreach($breadcrumbs as $index => $crumb)
                        @if($index > 0)
                            <li>/</li>
                        @endif
                        @if(isset($crumb['href']))
                            <li><a href="{{ $crumb['href'] }}" class="min-h-[44px] inline-flex items-center hover:{{ $colorConfig['text'] }} transition-colors">{{ $crumb['label'] }}</a></li>
                        @else
                            <li class="text-gray-900 font-medium truncate max-w-[150px] md:max-w-none">{{ $crumb['label'] }}</li>
                        @endif
                    @endforeach
                </ol>
            </nav>
        @endif

        <!-- Page Header -->
        <div class="mb-6 md:mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ $title }}</h1>
                    <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ $subtitle }}</p>
                </div>
                @if(count($filterOptions) > 0)
                    <div class="flex items-center">
                        <select id="statusFilter" class="min-h-[44px] w-full sm:w-auto px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                            @foreach($filterOptions as $value => $label)
                                <option value="{{ $value }}" {{ request($filterParam) === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
        </div>

        <!-- Statistics Cards -->
        @if(count($stats) > 0)
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-6 mb-6 md:mb-8">
                @foreach($stats as $stat)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 md:w-12 md:h-12 {{ $stat['bgColor'] ?? 'bg-blue-100' }} rounded-lg flex items-center justify-center flex-shrink-0 hidden sm:flex">
                                <i class="{{ $stat['icon'] }} {{ $stat['iconColor'] ?? 'text-blue-600' }} text-lg md:text-xl"></i>
                            </div>
                            <div>
                                <div class="text-xl md:text-2xl font-bold text-gray-900">{{ $stat['value'] }}</div>
                                <div class="text-xs md:text-sm text-gray-600">{{ $stat['label'] }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Items List -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-4 md:px-6 py-3 md:py-4 border-b border-gray-200">
                <h2 class="text-base md:text-lg font-semibold text-gray-900">{{ $listTitle }}</h2>
            </div>

            @if($items->count() > 0)
                <div class="divide-y divide-gray-200">
                    {{ $slot }}
                </div>

                <!-- Pagination -->
                @if($items->hasPages())
                    <div class="px-4 md:px-6 py-4 border-t border-gray-200">
                        {{ $items->links() }}
                    </div>
                @endif
            @else
                <div class="px-4 md:px-6 py-8 md:py-12 text-center">
                    <div class="w-14 h-14 md:w-16 md:h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                        <i class="{{ $emptyIcon }} text-xl md:text-2xl text-gray-400"></i>
                    </div>
                    <h3 class="text-base md:text-lg font-medium text-gray-900 mb-1 md:mb-2">{{ $emptyTitle }}</h3>
                    <p class="text-sm md:text-base text-gray-600">
                        @if(request($filterParam))
                            {{ $emptyFilterDescription }}
                        @else
                            {{ $emptyDescription }}
                        @endif
                    </p>
                    @if(request($filterParam) && $clearFilterRoute)
                        <a href="{{ $clearFilterRoute }}"
                           class="min-h-[44px] inline-flex items-center justify-center px-4 py-2 {{ $colorConfig['button'] }} text-white text-sm font-medium rounded-lg transition-colors mt-4">
                            {{ $clearFilterText }}
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

@if(count($filterOptions) > 0)
<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            const currentUrl = new URL(window.location.href);
            if (this.value) {
                currentUrl.searchParams.set('{{ $filterParam }}', this.value);
            } else {
                currentUrl.searchParams.delete('{{ $filterParam }}');
            }
            window.location.href = currentUrl.toString();
        });
    }
});
</script>
@endif
