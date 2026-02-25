@php
    // Build a flat list of all searchable articles for this user
    $searchableArticles = [];
    foreach ($config['roles'] as $roleKey => $roleMeta) {
        // Non-admin users only see their own role's articles
        if (! $canSeeAll && $roleKey !== $userRole) {
            continue;
        }
        foreach ($roleMeta['articles'] as $slug => $art) {
            $searchableArticles[] = [
                'role'        => $roleKey,
                'roleLabel'   => $roleMeta['label'],
                'slug'        => $slug,
                'title'       => $art['title'],
                'description' => $art['description'] ?? '',
                'icon'        => $art['icon'] ?? 'ri-file-text-line',
                'keywords'    => implode(' ', $art['keywords'] ?? []),
                'url'         => route('help.article', ['role' => $roleKey, 'slug' => $slug]),
            ];
        }
    }
    foreach ($config['common']['articles'] ?? [] as $slug => $art) {
        $searchableArticles[] = [
            'role'        => 'common',
            'roleLabel'   => __('help.sections.common'),
            'slug'        => $slug,
            'title'       => $art['title'],
            'description' => $art['description'] ?? '',
            'icon'        => $art['icon'] ?? 'ri-file-text-line',
            'keywords'    => implode(' ', $art['keywords'] ?? []),
            'url'         => route('help.common', ['slug' => $slug]),
        ];
    }
@endphp

<x-layouts.help :title="__('help.search.title')" :role="$userRole">

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Back link --}}
        <a href="{{ route('help.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-primary transition-colors mb-6">
            <i class="ri-arrow-right-line"></i>
            {{ __('help.back_to_help') }}
        </a>

        {{-- Alpine.js search component --}}
        <div x-data="helpSearch({{ json_encode($searchableArticles) }})"
             x-init="init()">

            {{-- Search bar --}}
            <div class="relative mb-6">
                <i class="ri-search-line absolute top-1/2 -translate-y-1/2 right-4 text-gray-400 text-lg pointer-events-none"></i>
                <input type="text"
                       x-model="query"
                       @input="search()"
                       placeholder="{{ __('help.search.placeholder') }}"
                       autofocus
                       class="w-full pr-11 pl-4 py-3 rounded-xl border border-gray-300 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary bg-white">
            </div>

            {{-- Results count --}}
            <p class="text-sm text-gray-500 mb-4" x-show="query.length > 0">
                <span x-text="results.length"></span> {{ __('نتائج') }}
            </p>

            {{-- Results list --}}
            <div class="space-y-3">
                <template x-for="(item, i) in (query.length > 0 ? results : articles)" :key="i">
                    <a :href="item.url"
                       class="flex items-start gap-3 p-4 bg-white rounded-xl border border-gray-200 shadow-sm hover:border-primary/40 hover:shadow-md transition-all duration-200 group">
                        <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0 group-hover:bg-primary/20 transition-colors">
                            <i :class="item.icon + ' text-primary text-lg'"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-900 text-sm leading-snug group-hover:text-primary transition-colors" x-text="item.title"></p>
                            <p class="text-gray-500 text-xs mt-0.5 line-clamp-2" x-text="item.description" x-show="item.description"></p>
                            <p class="text-gray-400 text-xs mt-1">
                                <i class="ri-folder-line"></i>
                                <span x-text="item.roleLabel"></span>
                            </p>
                        </div>
                        <i class="ri-arrow-left-line text-gray-300 group-hover:text-primary text-sm flex-shrink-0 mt-0.5 transition-colors"></i>
                    </a>
                </template>
            </div>

            {{-- No results --}}
            <div x-show="query.length > 0 && results.length === 0" class="text-center py-12 text-gray-400">
                <i class="ri-search-line text-4xl mb-3 block"></i>
                <p class="text-base">{{ __('help.search.no_results', ['query' => '']) }}<span x-text="query"></span>"</p>
                <p class="text-sm mt-1">{{ __('help.search.hint') }}</p>
            </div>

        </div>

    </div>

</x-layouts.help>

@push('scripts')
<script>
function helpSearch(articles) {
    return {
        articles,
        query: '',
        results: [],
        init() {
            // Pre-fill query from URL param if present
            const params = new URLSearchParams(window.location.search);
            const q = params.get('q') || '';
            if (q) {
                this.query = q;
                this.search();
            }
        },
        search() {
            const q = this.query.trim().toLowerCase();
            if (!q) {
                this.results = [];
                return;
            }
            this.results = this.articles.filter(item => {
                return (
                    item.title.toLowerCase().includes(q) ||
                    item.description.toLowerCase().includes(q) ||
                    item.keywords.toLowerCase().includes(q)
                );
            });
        }
    };
}
</script>
@endpush
