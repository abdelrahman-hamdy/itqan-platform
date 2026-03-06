@php
    // Group articles by their 'section' key
    $grouped = [];
    foreach ($roleConfig['articles'] as $slug => $art) {
        $section = $art['section'] ?? 'general';
        $grouped[$section][] = ['slug' => $slug, 'article' => $art];
    }
@endphp

<x-layouts.help :title="($roleConfig['label'] ?? '') . ' — ' . __('help.title')">

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Breadcrumb ──────────────────────────────────────────────────────── --}}
        <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6" aria-label="breadcrumb">
            <a href="{{ route('help.index') }}"
               class="hover:text-primary transition-colors">
                {{ __('help.breadcrumb.home') }}
            </a>
            <i class="ri-arrow-left-s-line text-gray-400 rtl:rotate-180"></i>
            <span class="text-gray-900 font-semibold">{{ $roleConfig['label'] ?? '' }}</span>
        </nav>

        {{-- Role heading ────────────────────────────────────────────────────── --}}
        <div class="flex items-center gap-3 mb-8">
            <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                <i class="{{ $roleConfig['icon'] ?? 'ri-book-line' }} text-primary text-xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $roleConfig['label'] ?? '' }}</h1>
                <p class="text-gray-500 text-sm">
                    {{ __('help.landing.articles_count', ['count' => count($roleConfig['articles'])]) }}
                </p>
            </div>
        </div>

        {{-- Articles grouped by section ─────────────────────────────────────── --}}
        @foreach($grouped as $sectionKey => $sectionArticles)
            @if(count($grouped) > 1)
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3 mt-6">
                    {{ $sectionLabels[$sectionKey] ?? $sectionKey }}
                </h3>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                @foreach($sectionArticles as $item)
                    <a href="{{ route('help.article', ['role' => $role, 'slug' => $item['slug']]) }}"
                       class="group flex items-start gap-3 p-4 bg-white rounded-xl border border-gray-200 shadow-sm hover:border-primary/40 hover:shadow-md transition-all duration-200">
                        <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0 group-hover:bg-primary/20 transition-colors">
                            <i class="{{ $item['article']['icon'] ?? 'ri-file-text-line' }} text-primary text-lg"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-900 text-sm leading-snug group-hover:text-primary transition-colors">
                                {{ $item['article']['title'] }}
                            </p>
                            @if(!empty($item['article']['description']))
                                <p class="text-gray-500 text-xs mt-0.5 line-clamp-2">
                                    {{ $item['article']['description'] }}
                                </p>
                            @endif
                        </div>
                        <i class="ri-arrow-left-line text-gray-300 group-hover:text-primary text-sm flex-shrink-0 mt-0.5 transition-colors"></i>
                    </a>
                @endforeach
            </div>
        @endforeach

    </div>

</x-layouts.help>
