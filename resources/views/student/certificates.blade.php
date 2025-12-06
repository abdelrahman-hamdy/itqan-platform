@php
    $academy = auth()->user()->academy;
    $subdomain = request()->route('subdomain') ?? $academy->subdomain ?? 'itqan-academy';
    $isParent = ($layout ?? 'student') === 'parent';
    $routePrefix = $isParent ? 'parent.certificates' : 'student.certificates';
    $indexRoute = $isParent ? 'parent.certificates.index' : 'student.certificates';
@endphp

<x-layouts.authenticated
    :role="$layout ?? 'student'"
    title="{{ $academy->name ?? 'أكاديمية إتقان' }} - {{ $isParent ? 'شهادات الأبناء' : 'شهاداتي' }}">

    <!-- Header Section -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 flex items-center">
                    <i class="ri-award-line text-amber-600 ml-3"></i>
                    {{ $isParent ? 'شهادات الأبناء' : 'شهاداتي' }}
                </h1>
                <p class="text-gray-600 mt-1">
                    {{ $isParent ? 'عرض جميع الشهادات التي حصل عليها أبناؤك' : 'عرض وتحميل جميع شهاداتك الدراسية' }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <div class="bg-amber-100 text-amber-800 px-4 py-2 rounded-lg">
                    <span class="font-bold text-lg">{{ $certificates->total() }}</span>
                    <span class="text-sm mr-1">إجمالي الشهادات</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="mb-6 bg-white rounded-xl shadow-sm border border-gray-200 p-2">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route($indexRoute, ['subdomain' => $subdomain]) }}"
               class="px-4 py-2 rounded-lg font-medium transition-colors {{ !request('type') ? 'bg-amber-500 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                الكل @if(!request('type'))({{ $certificates->total() }})@endif
            </a>
            <a href="{{ route($indexRoute, ['subdomain' => $subdomain, 'type' => 'recorded_course']) }}"
               class="px-4 py-2 rounded-lg font-medium transition-colors {{ request('type') === 'recorded_course' ? 'bg-amber-500 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                <i class="ri-video-line ml-1"></i>
                الدورات المسجلة
            </a>
            <a href="{{ route($indexRoute, ['subdomain' => $subdomain, 'type' => 'interactive_course']) }}"
               class="px-4 py-2 rounded-lg font-medium transition-colors {{ request('type') === 'interactive_course' ? 'bg-amber-500 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                <i class="ri-live-line ml-1"></i>
                الدورات التفاعلية
            </a>
            <a href="{{ route($indexRoute, ['subdomain' => $subdomain, 'type' => 'quran_subscription']) }}"
               class="px-4 py-2 rounded-lg font-medium transition-colors {{ request('type') === 'quran_subscription' ? 'bg-amber-500 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                <i class="ri-book-open-line ml-1"></i>
                حلقات القرآن
            </a>
            <a href="{{ route($indexRoute, ['subdomain' => $subdomain, 'type' => 'academic_subscription']) }}"
               class="px-4 py-2 rounded-lg font-medium transition-colors {{ request('type') === 'academic_subscription' ? 'bg-amber-500 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                <i class="ri-graduation-cap-line ml-1"></i>
                الدروس الأكاديمية
            </a>
        </div>
    </div>

    <!-- Certificates Grid -->
    @if($certificates->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($certificates as $certificate)
                <x-certificate-card :certificate="$certificate" :showStudent="$isParent" />
            @endforeach
        </div>

        <!-- Pagination -->
        @if($certificates->hasPages())
        <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            {{ $certificates->appends(request()->query())->links('vendor.pagination.custom-tailwind') }}
        </div>
        @endif
    @else
        <!-- Empty State -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
            <div class="max-w-md mx-auto">
                <div class="w-20 h-20 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="ri-award-line text-4xl text-amber-500"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">لا توجد شهادات بعد</h3>
                <p class="text-gray-600 mb-6">
                    @if($isParent)
                        ستظهر شهادات أبنائك هنا عند حصولهم على شهادات من المعلمين أو إتمام الدورات
                    @else
                        ستظهر شهاداتك هنا عند إتمام الدورات أو استلام شهادات من المعلمين
                    @endif
                </p>
                <a href="{{ route('courses.index', ['subdomain' => $subdomain]) }}"
                   class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                    <i class="ri-book-open-line"></i>
                    تصفح الدورات
                </a>
            </div>
        </div>
    @endif
</x-layouts.authenticated>
