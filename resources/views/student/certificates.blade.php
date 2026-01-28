@php
    $academy = auth()->user()->academy;
    $subdomain = request()->route('subdomain') ?? $academy->subdomain ?? 'itqan-academy';
    $isParent = ($layout ?? 'student') === 'parent';
    $routePrefix = $isParent ? 'parent.certificates' : 'student.certificates';
    $indexRoute = $isParent ? 'parent.certificates.index' : 'student.certificates';
@endphp

<x-layouts.authenticated
    :role="$layout ?? 'student'"
    title="{{ $academy->name ?? __('student.common.academy_default') }} - {{ $isParent ? __('student.certificates.parent_title') : __('student.certificates.title') }}">

    <!-- Header Section -->
    <x-student-page.header
        title="{{ $isParent ? __('student.certificates.parent_title') : __('student.certificates.title') }}"
        description="{{ $isParent ? __('student.certificates.parent_description') : __('student.certificates.description') }}"
        :count="$certificates->total()"
        countLabel="{{ __('student.certificates.total_count') }}"
        countColor="amber"
    />

    <!-- Filter Tabs -->
    <div class="mb-4 md:mb-6 bg-white rounded-xl shadow-sm border border-gray-200 p-2">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route($indexRoute, ['subdomain' => $subdomain]) }}"
               class="inline-flex items-center justify-center min-h-[40px] px-3 md:px-4 py-2 rounded-xl md:rounded-lg text-sm font-medium transition-colors {{ !request('type') ? 'bg-amber-500 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                {{ __('student.certificates.filter_all') }} @if(!request('type'))({{ $certificates->total() }})@endif
            </a>
            <a href="{{ route($indexRoute, ['subdomain' => $subdomain, 'type' => 'recorded_course']) }}"
               class="inline-flex items-center justify-center min-h-[40px] px-3 md:px-4 py-2 rounded-xl md:rounded-lg text-sm font-medium transition-colors {{ request('type') === 'recorded_course' ? 'bg-amber-500 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                <i class="ri-video-line ms-1"></i>
                <span class="hidden sm:inline">{{ __('student.certificates.filter_recorded_courses_prefix') }} </span>{{ __('student.certificates.filter_recorded_courses_suffix') }}
            </a>
            <a href="{{ route($indexRoute, ['subdomain' => $subdomain, 'type' => 'interactive_course']) }}"
               class="inline-flex items-center justify-center min-h-[40px] px-3 md:px-4 py-2 rounded-xl md:rounded-lg text-sm font-medium transition-colors {{ request('type') === 'interactive_course' ? 'bg-amber-500 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                <i class="ri-live-line ms-1"></i>
                <span class="hidden sm:inline">{{ __('student.certificates.filter_interactive_courses_prefix') }} </span>{{ __('student.certificates.filter_interactive_courses_suffix') }}
            </a>
            <a href="{{ route($indexRoute, ['subdomain' => $subdomain, 'type' => 'quran_subscription']) }}"
               class="inline-flex items-center justify-center min-h-[40px] px-3 md:px-4 py-2 rounded-xl md:rounded-lg text-sm font-medium transition-colors {{ request('type') === 'quran_subscription' ? 'bg-amber-500 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                <i class="ri-book-open-line ms-1"></i>
                <span class="hidden sm:inline">{{ __('student.certificates.filter_quran_prefix') }} </span>{{ __('student.certificates.filter_quran_suffix') }}
            </a>
            <a href="{{ route($indexRoute, ['subdomain' => $subdomain, 'type' => 'academic_subscription']) }}"
               class="inline-flex items-center justify-center min-h-[40px] px-3 md:px-4 py-2 rounded-xl md:rounded-lg text-sm font-medium transition-colors {{ request('type') === 'academic_subscription' ? 'bg-amber-500 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                <i class="ri-graduation-cap-line ms-1"></i>
                <span class="hidden sm:inline">{{ __('student.certificates.filter_academic_prefix') }} </span>{{ __('student.certificates.filter_academic_suffix') }}
            </a>
        </div>
    </div>

    <!-- Certificates Grid -->
    @if($certificates->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
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
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 md:p-12 text-center">
            <div class="max-w-md mx-auto">
                <div class="w-16 h-16 md:w-20 md:h-20 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="ri-award-line text-3xl md:text-4xl text-amber-500"></i>
                </div>
                <h3 class="text-lg md:text-xl font-bold text-gray-900 mb-2">{{ __('student.certificates.no_certificates_title') }}</h3>
                <p class="text-sm md:text-base text-gray-600 mb-6">
                    @if($isParent)
                        {{ __('student.certificates.no_certificates_parent') }}
                    @else
                        {{ __('student.certificates.no_certificates_student') }}
                    @endif
                </p>
                <a href="{{ route('courses.index', ['subdomain' => $subdomain]) }}"
                   class="inline-flex items-center justify-center min-h-[44px] gap-2 bg-amber-500 hover:bg-amber-600 text-white px-6 py-3 rounded-xl md:rounded-lg font-medium transition-colors">
                    <i class="ri-book-open-line"></i>
                    {{ __('student.certificates.browse_courses') }}
                </a>
            </div>
        </div>
    @endif
</x-layouts.authenticated>
