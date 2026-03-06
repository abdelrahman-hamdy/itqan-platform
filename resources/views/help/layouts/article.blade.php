{{--
    Article layout for the help center.
    Expects these variables (passed from controller + @extends child):
      $role        – config key (e.g. 'admin')
      $slug        – article slug (e.g. 'quran-packages')
      $article     – article metadata array from config
      $roleConfig  – full role config array (label, articles, …)
      $prevSlug    – previous article slug or null
      $nextSlug    – next article slug or null
      $prevArticle – previous article metadata or null
      $nextArticle – next article metadata or null
      $userRole    – current authenticated user's role
--}}
<x-layouts.help :title="$article['title'] ?? __('help.title')">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        {{-- Breadcrumb ──────────────────────────────────────────────────── --}}
        <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6" aria-label="breadcrumb">
            <a href="{{ route('help.index') }}"
               class="hover:text-primary transition-colors">
                {{ __('help.breadcrumb.home') }}
            </a>
            <i class="ri-arrow-left-s-line text-gray-400 rtl:rotate-180"></i>
            <a href="{{ route('help.role', ['role' => $role]) }}"
               class="hover:text-primary transition-colors">{{ $roleConfig['label'] ?? '' }}</a>
            <i class="ri-arrow-left-s-line text-gray-400 rtl:rotate-180"></i>
            <span class="text-gray-900 font-semibold truncate max-w-xs">{{ $article['title'] ?? '' }}</span>
        </nav>

        {{-- Main grid: article nav sidebar + content ────────────────────── --}}
        <div class="flex gap-6 items-start">

            {{-- Article navigation sidebar ────────────────────────────── --}}
            <aside class="hidden lg:block w-64 flex-shrink-0 sticky top-24 self-start">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4"
                     @if($role === 'developer') dir="ltr" @endif>
                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 px-1">
                        {{ __('help.navigation.articles') }}
                    </h3>
                    <nav class="space-y-1">
                        @foreach($roleConfig['articles'] ?? [] as $articleSlug => $articleMeta)
                            <a href="{{ route('help.article', ['role' => $role, 'slug' => $articleSlug]) }}"
                               class="help-nav-item {{ $articleSlug === $slug ? 'active' : '' }}">
                                <i class="{{ $articleMeta['icon'] ?? 'ri-file-text-line' }} text-base flex-shrink-0"></i>
                                <span class="leading-snug">{{ $articleMeta['title'] }}</span>
                            </a>
                        @endforeach
                    </nav>
                </div>
            </aside>

            {{-- Article content area ────────────────────────────────────── --}}
            <div class="flex-1 min-w-0">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm">

                    {{-- Article header --}}
                    <div class="px-6 pt-6 pb-4 border-b border-gray-100"
                         @if($role === 'developer') dir="ltr" @endif>
                        <div class="flex items-center gap-2 text-sm text-primary mb-2">
                            <i class="{{ $article['icon'] ?? 'ri-file-text-line' }}"></i>
                            <span>{{ $roleConfig['label'] ?? '' }}</span>
                        </div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $article['title'] ?? '' }}</h1>
                        @if(!empty($article['description']))
                            <p class="mt-1 text-gray-500 text-base">{{ $article['description'] }}</p>
                        @endif
                    </div>

                    {{-- Article body --}}
                    <div class="px-6 py-6">
                        <div class="help-content"
                             @if($role === 'developer') dir="ltr" @endif
                             x-data="helpArticleToc()"
                             x-init="buildToc()">

                            {{-- Table of Contents (auto-generated) --}}
                            <div x-show="headings.length > 1"
                                 class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6 text-sm">
                                <p class="font-semibold text-gray-700 mb-2 flex items-center gap-1.5">
                                    <i class="ri-list-check text-base"></i>
                                    {{ __('help.toc.title') }}
                                </p>
                                <nav>
                                    <template x-for="(h, i) in headings" :key="i">
                                        <a :href="'#' + h.id"
                                           class="help-toc-link block"
                                           x-text="h.text"></a>
                                    </template>
                                </nav>
                            </div>

                            @yield('content')
                        </div>
                    </div>

                    {{-- Prev / Next navigation --}}
                    @if($prevSlug || $nextSlug)
                        <div class="px-6 py-4 border-t border-gray-100 flex justify-between gap-4">
                            @if($prevSlug)
                                <a href="{{ route('help.article', ['role' => $role, 'slug' => $prevSlug]) }}"
                                   class="flex items-center gap-2 text-sm text-gray-600 hover:text-primary transition-colors group">
                                    <i class="ri-arrow-right-s-line text-lg group-hover:translate-x-0.5 transition-transform"></i>
                                    <span>
                                        <span class="block text-xs text-gray-400">{{ __('help.navigation.previous') }}</span>
                                        <span class="font-medium">{{ $prevArticle['title'] ?? '' }}</span>
                                    </span>
                                </a>
                            @else
                                <div></div>
                            @endif

                            @if($nextSlug)
                                <a href="{{ route('help.article', ['role' => $role, 'slug' => $nextSlug]) }}"
                                   class="flex items-center gap-2 text-sm text-gray-600 hover:text-primary transition-colors group text-end">
                                    <span>
                                        <span class="block text-xs text-gray-400">{{ __('help.navigation.next') }}</span>
                                        <span class="font-medium">{{ $nextArticle['title'] ?? '' }}</span>
                                    </span>
                                    <i class="ri-arrow-left-s-line text-lg group-hover:-translate-x-0.5 transition-transform"></i>
                                </a>
                            @endif
                        </div>
                    @endif

                </div>
            </div>

        </div>{{-- end flex grid --}}

    </div>

</x-layouts.help>

@push('scripts')
<script>
function helpArticleToc() {
    return {
        headings: [],
        buildToc() {
            const content = this.$el;
            const h2s = content.querySelectorAll('h2');
            this.headings = Array.from(h2s).map((el, i) => {
                if (!el.id) {
                    el.id = 'section-' + i;
                }
                return { id: el.id, text: el.textContent.trim() };
            });
        }
    };
}

function initDiagramFullscreen() {
    document.querySelectorAll('.help-mermaid, .mermaid-wrapper').forEach(function(container) {
        if (container.querySelector('.help-diagram-expand-btn')) return;

        var btn = document.createElement('button');
        btn.className = 'help-diagram-expand-btn';
        btn.type = 'button';
        btn.title = 'عرض بملء الشاشة';
        btn.innerHTML = '<i class="ri-fullscreen-line"></i>';
        container.appendChild(btn);

        btn.addEventListener('click', function() {
            var svg = container.querySelector('svg');
            if (!svg) return;

            var overlay = document.createElement('div');
            overlay.className = 'help-diagram-overlay';

            var closeBtn = document.createElement('button');
            closeBtn.className = 'help-diagram-overlay-close';
            closeBtn.type = 'button';
            closeBtn.innerHTML = '<i class="ri-close-line"></i>';

            var clone = svg.cloneNode(true);
            clone.removeAttribute('width');
            clone.removeAttribute('height');
            clone.style.width = 'auto';
            clone.style.maxWidth = '95vw';
            clone.style.maxHeight = '90vh';
            clone.style.height = 'auto';

            overlay.appendChild(closeBtn);
            overlay.appendChild(clone);
            document.body.appendChild(overlay);

            function close() {
                overlay.remove();
                document.removeEventListener('keydown', onEsc);
            }

            function onEsc(e) {
                if (e.key === 'Escape') close();
            }

            closeBtn.addEventListener('click', close);
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) close();
            });
            document.addEventListener('keydown', onEsc);
        });
    });
}

// Use MutationObserver to detect when mermaid renders SVGs into containers.
// The mermaid CDN script loads after this script, so a simple timeout is unreliable.
(function() {
    var containers = document.querySelectorAll('.help-mermaid, .mermaid-wrapper');
    if (!containers.length) return;

    // Run once immediately in case SVGs already exist
    initDiagramFullscreen();

    // Watch for mermaid rendering SVGs into containers
    var observer = new MutationObserver(function() {
        initDiagramFullscreen();
    });

    containers.forEach(function(el) {
        observer.observe(el, { childList: true, subtree: true });
    });

    // Safety fallback: stop observing after 10s
    setTimeout(function() { observer.disconnect(); }, 10000);
})();
</script>
@endpush
