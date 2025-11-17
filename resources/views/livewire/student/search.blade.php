<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4 max-w-7xl">
        <!-- Header with Search Bar -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        <i class="ri-search-line text-primary ml-2"></i>
                        البحث في الموارد التعليمية
                    </h1>
                    <p class="text-gray-600">
                        ابحث في الكورسات، الحلقات، المعلمين وجميع الموارد المتاحة
                    </p>
                </div>
            </div>

            <!-- Search Input -->
            <div class="relative">
                <div class="relative">
                    <input
                        type="text"
                        wire:model.live.debounce.500ms="query"
                        placeholder="ابحث عن كورس، معلم، أو مادة دراسية..."
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
                        تم العثور على <span class="font-semibold text-primary">{{ $totalResults }}</span> نتيجة
                    </div>
                @endif
            </div>
        </div>

        <!-- Loading State -->
        <div wire:loading class="text-center py-8">
            <div class="inline-flex items-center space-x-2 space-x-reverse text-primary">
                <i class="ri-loader-4-line animate-spin text-2xl"></i>
                <span class="text-lg">جاري البحث...</span>
            </div>
        </div>

        <!-- Results -->
        <div wire:loading.remove>
            @if($query)
                @if($totalResults > 0)
                    <!-- Tabs for filtering results by type -->
                    <div class="mb-6 border-b border-gray-200">
                        <nav class="flex space-x-4 space-x-reverse overflow-x-auto" aria-label="Tabs">
                            <button
                                wire:click="setActiveTab('all')"
                                class="px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap {{ $activeTab === 'all' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                            >
                                <i class="ri-apps-line ml-1"></i>
                                الكل ({{ $totalResults }})
                            </button>

                            @if($results['quran_circles']->count() > 0)
                                <button
                                    wire:click="setActiveTab('quran_circles')"
                                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap {{ $activeTab === 'quran_circles' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                                >
                                    <i class="ri-group-line ml-1"></i>
                                    حلقات القرآن ({{ $results['quran_circles']->count() }})
                                </button>
                            @endif

                            @if($results['individual_circles']->count() > 0)
                                <button
                                    wire:click="setActiveTab('individual_circles')"
                                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap {{ $activeTab === 'individual_circles' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                                >
                                    <i class="ri-user-line ml-1"></i>
                                    حلقاتي الفردية ({{ $results['individual_circles']->count() }})
                                </button>
                            @endif

                            @if($results['interactive_courses']->count() > 0)
                                <button
                                    wire:click="setActiveTab('interactive_courses')"
                                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap {{ $activeTab === 'interactive_courses' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                                >
                                    <i class="ri-book-open-line ml-1"></i>
                                    الكورسات التفاعلية ({{ $results['interactive_courses']->count() }})
                                </button>
                            @endif

                            @if($results['academic_sessions']->count() > 0)
                                <button
                                    wire:click="setActiveTab('academic_sessions')"
                                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap {{ $activeTab === 'academic_sessions' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                                >
                                    <i class="ri-book-open-line ml-1"></i>
                                    دروسي الخاصة ({{ $results['academic_sessions']->count() }})
                                </button>
                            @endif

                            @if($results['recorded_courses']->count() > 0)
                                <button
                                    wire:click="setActiveTab('recorded_courses')"
                                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap {{ $activeTab === 'recorded_courses' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                                >
                                    <i class="ri-video-line ml-1"></i>
                                    الكورسات المسجلة ({{ $results['recorded_courses']->count() }})
                                </button>
                            @endif

                            @if($results['quran_teachers']->count() > 0)
                                <button
                                    wire:click="setActiveTab('quran_teachers')"
                                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap {{ $activeTab === 'quran_teachers' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                                >
                                    <i class="ri-user-star-line ml-1"></i>
                                    معلمو القرآن ({{ $results['quran_teachers']->count() }})
                                </button>
                            @endif

                            @if($results['academic_teachers']->count() > 0)
                                <button
                                    wire:click="setActiveTab('academic_teachers')"
                                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap {{ $activeTab === 'academic_teachers' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                                >
                                    <i class="ri-graduation-cap-line ml-1"></i>
                                    المعلمون الأكاديميون ({{ $results['academic_teachers']->count() }})
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
                                        <i class="ri-group-line text-green-600 ml-2"></i>
                                        حلقات القرآن الجماعية
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
                                        <i class="ri-user-line text-blue-600 ml-2"></i>
                                        حلقاتي الفردية
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
                                        <i class="ri-book-open-line text-blue-600 ml-2"></i>
                                        الكورسات التفاعلية
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
                                        <i class="ri-book-open-line text-purple-600 ml-2"></i>
                                        دروسي الخاصة
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
                                        <i class="ri-video-line text-red-600 ml-2"></i>
                                        الكورسات المسجلة
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
                                        <i class="ri-user-star-line text-green-600 ml-2"></i>
                                        معلمو القرآن
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
                                        <i class="ri-graduation-cap-line text-purple-600 ml-2"></i>
                                        المعلمون الأكاديميون
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
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">لا توجد نتائج</h3>
                        <p class="text-gray-600 mb-6">لم نتمكن من العثور على أي نتائج لـ "{{ $query }}"</p>
                        <button
                            wire:click="clearSearch"
                            class="inline-flex items-center px-6 py-3 bg-primary text-white rounded-lg hover:bg-secondary transition-colors"
                        >
                            <i class="ri-refresh-line ml-2"></i>
                            مسح البحث
                        </button>
                    </div>
                @endif
            @else
                <!-- Empty State - Show suggestions -->
                <div class="text-center py-16">
                    <div class="w-24 h-24 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="ri-search-2-line text-primary text-4xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">ابحث في جميع الموارد التعليمية</h3>
                    <p class="text-gray-600 mb-8 max-w-2xl mx-auto">
                        ابحث عن الكورسات، الحلقات، المعلمين، والمواد الدراسية المتاحة. استخدم مربع البحث أعلاه للبدء.
                    </p>

                    <!-- Search Suggestions -->
                    <div class="max-w-3xl mx-auto">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">أمثلة للبحث:</h4>
                        <div class="flex flex-wrap gap-2 justify-center">
                            <button
                                wire:click="searchFor('رياضيات')"
                                class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm text-gray-700 hover:border-primary hover:text-primary transition-colors"
                            >
                                <i class="ri-calculator-line ml-1"></i>
                                رياضيات
                            </button>
                            <button
                                wire:click="searchFor('قرآن')"
                                class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm text-gray-700 hover:border-primary hover:text-primary transition-colors"
                            >
                                <i class="ri-book-mark-line ml-1"></i>
                                قرآن
                            </button>
                            <button
                                wire:click="searchFor('علوم')"
                                class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm text-gray-700 hover:border-primary hover:text-primary transition-colors"
                            >
                                <i class="ri-flask-line ml-1"></i>
                                علوم
                            </button>
                            <button
                                wire:click="searchFor('لغة عربية')"
                                class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm text-gray-700 hover:border-primary hover:text-primary transition-colors"
                            >
                                <i class="ri-translate-2 ml-1"></i>
                                لغة عربية
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
