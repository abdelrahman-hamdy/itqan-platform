@php
    // Group articles by their 'section' key for display
    $sectionLabels = [
        'overview' => __('help.sections.overview'),
        'quran'    => __('help.sections.quran'),
        'academic' => __('help.sections.academic'),
        'courses'  => __('help.sections.courses'),
        'users'    => __('help.sections.users'),
        'finance'  => __('help.sections.finance'),
        'settings' => __('help.sections.settings'),
    ];
@endphp

<x-layouts.help :title="__('help.title')">

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Page header + search ────────────────────────────────────────────── --}}
        <div class="text-center mb-10">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ __('help.title') }}</h1>
            <p class="text-gray-500 text-lg">{{ __('help.subtitle') }}</p>

            {{-- Search bar --}}
            <div class="mt-6 max-w-xl mx-auto">
                <form action="{{ route('help.search') }}" method="GET">
                    <div class="relative">
                        <i class="ri-search-line absolute top-1/2 -translate-y-1/2 right-4 text-gray-400 text-lg pointer-events-none"></i>
                        <input type="text"
                               name="q"
                               placeholder="{{ __('help.search.placeholder') }}"
                               class="w-full pr-11 pl-4 py-3 rounded-xl border border-gray-300 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary bg-white"
                               autocomplete="off">
                        <button type="submit"
                                class="absolute left-2 top-1/2 -translate-y-1/2 px-3 py-1.5 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary/90 transition-colors">
                            بحث
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Current user's role section (prominently shown) ─────────────────── --}}
        @if(!empty($roleConfig['articles']))
            <section class="mb-10">
                <div class="flex items-center gap-2 mb-5">
                    <i class="{{ $roleConfig['icon'] ?? 'ri-book-line' }} text-xl text-primary"></i>
                    <h2 class="text-xl font-bold text-gray-900">{{ $roleConfig['label'] ?? '' }}</h2>
                </div>

                @php
                    // Group articles by their 'section' key
                    $grouped = [];
                    foreach ($roleConfig['articles'] as $slug => $art) {
                        $section = $art['section'] ?? 'general';
                        $grouped[$section][] = ['slug' => $slug, 'article' => $art];
                    }
                @endphp

                @foreach($grouped as $sectionKey => $sectionArticles)
                    @if(count($grouped) > 1)
                        <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3 mt-5">
                            {{ $sectionLabels[$sectionKey] ?? $sectionKey }}
                        </h3>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($sectionArticles as $item)
                            <a href="{{ route('help.article', ['role' => $userRole, 'slug' => $item['slug']]) }}"
                               class="group flex items-start gap-3 p-4 bg-white rounded-xl border border-gray-200 shadow-sm hover:border-primary/40 hover:shadow-md transition-all duration-200 card-hover">
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
            </section>
        @endif

    </div>

</x-layouts.help>
