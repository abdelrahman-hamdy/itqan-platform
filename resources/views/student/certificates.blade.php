@php
    $academy = auth()->user()->academy;
    $subdomain = request()->route('subdomain') ?? $academy->subdomain ?? 'itqan-academy';
@endphp

<x-student title="{{ $academy->name ?? 'أكاديمية إتقان' }} - شهاداتي">
    <x-slot name="description">عرض وتحميل جميع شهاداتك الدراسية - {{ $academy->name ?? 'أكاديمية إتقان' }}</x-slot>

    <!-- Header Section -->
    <x-student-page.header
        title="شهاداتي"
        description="عرض وتحميل جميع شهاداتك الدراسية"
        :count="$certificates->count()"
        countLabel="إجمالي الشهادات"
        countColor="amber"
    />

    <!-- Filter Tabs -->
    <div class="mb-6 bg-white rounded-xl shadow-sm border border-gray-200 p-2">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('student.certificates', ['subdomain' => $subdomain]) }}"
               class="px-4 py-2 rounded-lg font-medium transition-colors {{ !request('type') ? 'bg-amber-500 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                الكل ({{ $certificates->count() }})
            </a>
            <a href="{{ route('student.certificates', ['subdomain' => $subdomain, 'type' => 'recorded_course']) }}"
               class="px-4 py-2 rounded-lg font-medium transition-colors {{ request('type') === 'recorded_course' ? 'bg-amber-500 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                <i class="ri-video-line ml-1"></i>
                الدورات المسجلة
            </a>
            <a href="{{ route('student.certificates', ['subdomain' => $subdomain, 'type' => 'interactive_course']) }}"
               class="px-4 py-2 rounded-lg font-medium transition-colors {{ request('type') === 'interactive_course' ? 'bg-amber-500 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                <i class="ri-live-line ml-1"></i>
                الدورات التفاعلية
            </a>
            <a href="{{ route('student.certificates', ['subdomain' => $subdomain, 'type' => 'quran_subscription']) }}"
               class="px-4 py-2 rounded-lg font-medium transition-colors {{ request('type') === 'quran_subscription' ? 'bg-amber-500 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                <i class="ri-book-open-line ml-1"></i>
                حلقات القرآن
            </a>
            <a href="{{ route('student.certificates', ['subdomain' => $subdomain, 'type' => 'academic_subscription']) }}"
               class="px-4 py-2 rounded-lg font-medium transition-colors {{ request('type') === 'academic_subscription' ? 'bg-amber-500 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                <i class="ri-graduation-cap-line ml-1"></i>
                الدروس الأكاديمية
            </a>
        </div>
    </div>

    <!-- Results Summary -->
    @if($certificates->count() > 0)
    <div class="mb-6 flex items-center justify-between">
        <p class="text-gray-600">
            <span class="font-semibold text-gray-900">{{ $certificates->count() }}</span>
            شهادة
        </p>
    </div>
    @endif

    <!-- Certificates Grid -->
    @if($certificates->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($certificates as $certificate)
                <x-certificate-card :certificate="$certificate" />
            @endforeach
        </div>
    @else
        <x-student-page.empty-state
            icon="ri-award-line"
            title="لا توجد شهادات بعد"
            description="ستظهر شهاداتك هنا عند إتمام الدورات أو استلام شهادات من المعلمين"
            :actionUrl="route('courses.index', ['subdomain' => $subdomain])"
            actionLabel="تصفح الدورات"
            actionIcon="ri-book-open-line"
            iconBgColor="amber"
        />
    @endif
</x-student>
