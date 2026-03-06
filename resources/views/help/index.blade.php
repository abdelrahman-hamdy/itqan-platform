@php
    // Build the list of category cards visible to this user
    $categories = [];

    if ($userRole === 'developer') {
        // super_admin sees developer docs + admin guide
        if (!empty($allRoles['developer']['articles'])) {
            $categories[] = ['key' => 'developer', 'config' => $allRoles['developer']];
        }
        if (!empty($allRoles['admin']['articles'])) {
            $categories[] = ['key' => 'admin', 'config' => $allRoles['admin']];
        }
    } elseif ($userRole === 'admin') {
        // admin sees admin guide + can browse others
        if (!empty($allRoles['admin']['articles'])) {
            $categories[] = ['key' => 'admin', 'config' => $allRoles['admin']];
        }
        if (!empty($allRoles['developer']['articles'])) {
            $categories[] = ['key' => 'developer', 'config' => $allRoles['developer']];
        }
    } else {
        // Other roles see their own category
        if (!empty($roleConfig['articles'])) {
            $categories[] = ['key' => $userRole, 'config' => $roleConfig];
        }
    }

    $categoryDescriptions = [
        'developer' => 'الوثائق التقنية للمطورين: البنية التحتية، قواعد البيانات، الخدمات، والنشر',
        'admin'     => 'أدلة إدارة المنصة: القرآن، الأكاديمي، المستخدمون، والمالية',
        'student'   => 'دليل استخدام المنصة للطلاب',
        'parent'    => 'دليل متابعة أبنائك على المنصة',
        'quran_teacher'    => 'دليل معلم القرآن الكريم',
        'academic_teacher' => 'دليل المعلم الأكاديمي',
        'supervisor'       => 'دليل المشرف على المعلمين والطلاب',
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

        {{-- Category cards ────────────────────────────────────────────────────── --}}
        @if(count($categories) > 0)
            <div class="grid grid-cols-1 {{ count($categories) > 1 ? 'md:grid-cols-2' : '' }} gap-6 max-w-4xl mx-auto">
                @foreach($categories as $cat)
                    @php
                        $catKey    = $cat['key'];
                        $catConfig = $cat['config'];
                        $count     = count($catConfig['articles'] ?? []);
                    @endphp
                    <a href="{{ route('help.role', ['role' => $catKey]) }}"
                       class="group block p-6 bg-white rounded-2xl border border-gray-200 shadow-sm hover:border-primary/40 hover:shadow-lg transition-all duration-200">
                        <div class="flex items-start gap-4">
                            <div class="w-14 h-14 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0 group-hover:bg-primary/20 transition-colors">
                                <i class="{{ $catConfig['icon'] ?? 'ri-book-line' }} text-primary text-2xl"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h2 class="text-lg font-bold text-gray-900 group-hover:text-primary transition-colors">
                                    {{ $catConfig['label'] ?? '' }}
                                </h2>
                                <p class="text-gray-500 text-sm mt-1 leading-relaxed">
                                    {{ $categoryDescriptions[$catKey] ?? '' }}
                                </p>
                                <div class="flex items-center gap-3 mt-4">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-medium">
                                        <i class="ri-article-line"></i>
                                        {{ __('help.landing.articles_count', ['count' => $count]) }}
                                    </span>
                                    <span class="text-sm text-primary font-medium flex items-center gap-1 group-hover:gap-2 transition-all">
                                        {{ __('help.landing.browse') }}
                                        <i class="ri-arrow-left-line text-xs"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="text-center py-12 text-gray-400">
                <i class="ri-book-line text-4xl mb-3 block"></i>
                <p>{{ __('help.landing.empty_section') }}</p>
            </div>
        @endif

    </div>

</x-layouts.help>
