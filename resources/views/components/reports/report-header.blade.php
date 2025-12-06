@props([
    'title' => '',
    'subtitle' => '',
    'breadcrumbs' => [],
    'stats' => [], // Array of quick stats
])

<!-- Breadcrumb -->
@if(count($breadcrumbs) > 0)
<nav class="mb-8">
    <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
        @foreach($breadcrumbs as $breadcrumb)
            @if($loop->last)
                <li class="text-gray-900">{{ $breadcrumb['label'] }}</li>
            @else
                <li><a href="{{ $breadcrumb['url'] }}" class="hover:text-primary">{{ $breadcrumb['label'] }}</a></li>
                <li>/</li>
            @endif
        @endforeach
    </ol>
</nav>
@endif

<!-- Header -->
<div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl shadow-lg p-8 mb-6 text-white">
    <div class="flex items-center justify-between">
        <div class="flex-1">
            <h1 class="text-3xl font-bold">{{ $title }}</h1>
            @if($subtitle)
                <p class="mt-2 text-blue-100">{{ $subtitle }}</p>
            @endif

            @if(count($stats) > 0)
                <div class="mt-4 flex flex-wrap gap-6 text-sm text-blue-100">
                    @foreach($stats as $stat)
                        <div class="flex items-center gap-2">
                            @if(isset($stat['icon']))
                                <i class="{{ $stat['icon'] }}"></i>
                            @endif
                            <span>{{ $stat['label'] }}: {{ $stat['value'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center">
            <i class="ri-file-chart-line text-5xl"></i>
        </div>
    </div>
</div>
