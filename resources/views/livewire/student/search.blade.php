<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4 max-w-7xl">
        <!-- Header with Search Bar -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        <i class="ri-search-line text-primary ms-2"></i>
                        {{ __('student.search.page_title') }}
                    </h1>
                    <p class="text-gray-600">
                        {{ __('student.search.page_description') }}
                    </p>
                </div>
            </div>

            <!-- Search Input -->
            <div class="relative">
                <div class="relative">
                    <input
                        type="text"
                        wire:model.live.debounce.500ms="query"
                        placeholder="{{ __('student.search.search_placeholder') }}"
                        class="w-full px-6 py-4 pr-12 text-lg border-2 border-gray-200 rounded-xl focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                        autocomplete="off"
                    >
                    <div class="absolute left-4 top-1/2 -translate-y-1/2">
                        @if($query)
                            <button
                                wire:click="clearSearch"
                                class="text-gray-400 hover:text-gray-600 transition-colors"
                            >
                                <i class="ri-close-circle-fill text-xl"></i>
                            </button>
                        @else
                            <i class="ri-search-line text-gray-400 text-xl"></i>
                        @endif
                    </div>
                </div>

                <!-- Quick Stats -->
                @if($query && $totalResults > 0)
                    <div class="mt-3 text-sm text-gray-600">
                        {{ __('student.search.found_results', ['count' => $totalResults]) }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Loading State -->
        <div wire:loading class="text-center py-8">
            <div class="inline-flex items-center gap-2 text-primary">
                <i class="ri-loader-4-line animate-spin text-2xl"></i>
                <span class="text-lg">{{ __('student.search.searching') }}</span>
            </div>
        </div>

        <!-- Results -->
        <div wire:loading.remove>
            @if($query)
                @if($totalResults > 0)
                    <!-- Tabs for filtering results by type -->
                    <div class="mb-6 border-b border-gray-200">
                        <nav class="flex gap-4 overflow-x-auto" aria-label="{{ __('common.aria.tabs') }}">
                            <button
                                wire:click="setActiveTab('all')"
                                class="px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap {{ $activeTab === 'all' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                            >
                                <i class="ri-apps-line ms-1"></i>
                                {{ __('student.search.tab_all', ['count' => $totalResults]) }}
                            </button>

                            @if($results['quran_circles']->count() > 0)
                                <button
                                    wire:click="setActiveTab('quran_circles')"
                                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap {{ $activeTab === 'quran_circles' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                                >
                                    <i class="ri-group-line ms-1"></i>
                                    {{ __('student.search.tab_quran_circles', ['count' => $results['quran_circles']->count()]) }}
                                </button>
                            @endif

                            @if($results['individual_circles']->count() > 0)
                                <button
                                    wire:click="setActiveTab('individual_circles')"
                                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap {{ $activeTab === 'individual_circles' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                                >
                                    <i class="ri-user-line ms-1"></i>
                                    {{ __('student.search.tab_individual_circles', ['count' => $results['individual_circles']->count()]) }}
                                </button>
                            @endif

                            @if($results['interactive_courses']->count() > 0)
                                <button
                                    wire:click="setActiveTab('interactive_courses')"
                                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap {{ $activeTab === 'interactive_courses' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                                >
                                    <i class="ri-book-open-line ms-1"></i>
                                    {{ __('student.search.tab_interactive_courses', ['count' => $results['interactive_courses']->count()]) }}
                                </button>
                            @endif

                            @if($results['academic_sessions']->count() > 0)
                                <button
                                    wire:click="setActiveTab('academic_sessions')"
                                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap {{ $activeTab === 'academic_sessions' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                                >
                                    <i class="ri-book-open-line ms-1"></i>
                                    {{ __('student.search.tab_academic_sessions', ['count' => $results['academic_sessions']->count()]) }}
                                </button>
                            @endif

                            @if($results['recorded_courses']->count() > 0)
                                <button
                                    wire:click="setActiveTab('recorded_courses')"
                                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap {{ $activeTab === 'recorded_courses' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                                >
                                    <i class="ri-video-line ms-1"></i>
                                    {{ __('student.search.tab_recorded_courses', ['count' => $results['recorded_courses']->count()]) }}
                                </button>
                            @endif

                            @if($results['quran_teachers']->count() > 0)
                                <button
                                    wire:click="setActiveTab('quran_teachers')"
                                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap {{ $activeTab === 'quran_teachers' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                                >
                                    <i class="ri-user-star-line ms-1"></i>
                                    {{ __('student.search.tab_quran_teachers', ['count' => $results['quran_teachers']->count()]) }}
                                </button>
                            @endif

                            @if($results['academic_teachers']->count() > 0)
                                <button
                                    wire:click="setActiveTab('academic_teachers')"
                                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap {{ $activeTab === 'academic_teachers' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                                >
                                    <i class="ri-graduation-cap-line ms-1"></i>
                                    {{ __('student.search.tab_academic_teachers', ['count' => $results['academic_teachers']->count()]) }}
                                </button>
                            @endif
                        </nav>
                    </div>

                    <!-- Results Grid -->
                    <div class="space-y-8">
                        @if($activeTab === 'all' || $activeTab === 'quran_circles')
                            @if($results['quran_circles']->count() > 0)
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                                        <i class="ri-group-line text-green-600 ms-2"></i>
                                        {{ __('student.search.section_quran_circles') }}
                                    </h2>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                        @foreach($results['quran_circles'] as $item)
                                            <x-search-result-card :item="$item" />
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif

                        @if($activeTab === 'all' || $activeTab === 'individual_circles')
                            @if($results['individual_circles']->count() > 0)
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                                        <i class="ri-user-line text-blue-600 ms-2"></i>
                                        {{ __('student.search.section_individual_circles') }}
                                    </h2>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                        @foreach($results['individual_circles'] as $item)
                                            <x-search-result-card :item="$item" />
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif

                        @if($activeTab === 'all' || $activeTab === 'interactive_courses')
                            @if($results['interactive_courses']->count() > 0)
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                                        <i class="ri-book-open-line text-blue-600 ms-2"></i>
                                        {{ __('student.search.section_interactive_courses') }}
                                    </h2>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                        @foreach($results['interactive_courses'] as $item)
                                            <x-search-result-card :item="$item" />
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif

                        @if($activeTab === 'all' || $activeTab === 'academic_sessions')
                            @if($results['academic_sessions']->count() > 0)
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                                        <i class="ri-book-open-line text-purple-600 ms-2"></i>
                                        {{ __('student.search.section_academic_sessions') }}
                                    </h2>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                        @foreach($results['academic_sessions'] as $item)
                                            <x-search-result-card :item="$item" />
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif

                        @if($activeTab === 'all' || $activeTab === 'recorded_courses')
                            @if($results['recorded_courses']->count() > 0)
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                                        <i class="ri-video-line text-red-600 ms-2"></i>
                                        {{ __('student.search.section_recorded_courses') }}
                                    </h2>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                        @foreach($results['recorded_courses'] as $item)
                                            <x-search-result-card :item="$item" />
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif

                        @if($activeTab === 'all' || $activeTab === 'quran_teachers')
                            @if($results['quran_teachers']->count() > 0)
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                                        <i class="ri-user-star-line text-green-600 ms-2"></i>
                                        {{ __('student.search.section_quran_teachers') }}
                                    </h2>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                        @foreach($results['quran_teachers'] as $item)
                                            <x-search-result-card :item="$item" />
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif

                        @if($activeTab === 'all' || $activeTab === 'academic_teachers')
                            @if($results['academic_teachers']->count() > 0)
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                                        <i class="ri-graduation-cap-line text-purple-600 ms-2"></i>
                                        {{ __('student.search.section_academic_teachers') }}
                                    </h2>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                        @foreach($results['academic_teachers'] as $item)
                                            <x-search-result-card :item="$item" />
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                @else
                    <!-- No Results -->
                    <div class="text-center py-16">
                        <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="ri-search-line text-gray-400 text-4xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ __('student.search.no_results') }}</h3>
                        <p class="text-gray-600 mb-6">{{ __('student.search.no_results_for', ['query' => $query]) }}</p>
                        <button
                            wire:click="clearSearch"
                            class="inline-flex items-center px-6 py-3 bg-primary text-white rounded-lg hover:bg-secondary transition-colors"
                        >
                            <i class="ri-refresh-line ms-2"></i>
                            {{ __('student.search.clear_search') }}
                        </button>
                    </div>
                @endif
            @else
                <!-- Empty State - Show suggestions -->
                <div class="text-center py-16">
                    <div class="w-24 h-24 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="ri-search-2-line text-primary text-4xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">{{ __('student.search.empty_title') }}</h3>
                    <p class="text-gray-600 mb-8 max-w-2xl mx-auto">
                        {{ __('student.search.empty_description') }}
                    </p>

                    <!-- Search Suggestions -->
                    <div class="max-w-3xl mx-auto">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">{{ __('student.search.suggestions_title') }}</h4>
                        <div class="flex flex-wrap gap-2 justify-center">
                            <button
                                wire:click="searchFor('{{ __('student.search.suggestion_math') }}')"
                                class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm text-gray-700 hover:border-primary hover:text-primary transition-colors"
                            >
                                <i class="ri-calculator-line ms-1"></i>
                                {{ __('student.search.suggestion_math') }}
                            </button>
                            <button
                                wire:click="searchFor('{{ __('student.search.suggestion_quran') }}')"
                                class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm text-gray-700 hover:border-primary hover:text-primary transition-colors"
                            >
                                <i class="ri-book-mark-line ms-1"></i>
                                {{ __('student.search.suggestion_quran') }}
                            </button>
                            <button
                                wire:click="searchFor('{{ __('student.search.suggestion_science') }}')"
                                class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm text-gray-700 hover:border-primary hover:text-primary transition-colors"
                            >
                                <i class="ri-flask-line ms-1"></i>
                                {{ __('student.search.suggestion_science') }}
                            </button>
                            <button
                                wire:click="searchFor('{{ __('student.search.suggestion_arabic') }}')"
                                class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm text-gray-700 hover:border-primary hover:text-primary transition-colors"
                            >
                                <i class="ri-translate-2 ms-1"></i>
                                {{ __('student.search.suggestion_arabic') }}
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sync navigation search input with Livewire query updates
    Livewire.on('queryUpdated', (query) => {
        const navSearchInput = document.getElementById('nav-search-input');
        if (navSearchInput) {
            navSearchInput.value = query;
        }
    });

    // Listen for Livewire updates to sync navigation input
    document.addEventListener('livewire:update', function() {
        const navSearchInput = document.getElementById('nav-search-input');
        const urlParams = new URLSearchParams(window.location.search);
        const queryParam = urlParams.get('q');

        if (navSearchInput && queryParam !== null) {
            navSearchInput.value = queryParam;
        }
    });
});
</script>
@endpush
