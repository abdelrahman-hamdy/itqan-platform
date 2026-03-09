<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div>
    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.sidebar.dashboard'), 'route' => route('manage.dashboard', ['subdomain' => $subdomain])],
            ['label' => __('supervisor.certificates.page_title')],
        ]"
        view-type="supervisor"
    />

    <div class="mb-6 md:mb-8">
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('supervisor.certificates.page_title') }}</h1>
        <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('supervisor.certificates.page_subtitle') }}</p>
    </div>

    <x-supervisor.teacher-filter :teachers="$teachers" :selected-teacher-id="request('teacher_id')" />

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4 flex items-center gap-3">
            <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="ri-award-line text-amber-600"></i>
            </div>
            <div>
                <p class="text-lg font-bold text-gray-900">{{ $totalCertificates }}</p>
                <p class="text-xs text-gray-600">{{ __('supervisor.certificates.total_certificates') }}</p>
            </div>
        </div>
    </div>

    <!-- Certificate List -->
    @if($certificates->isNotEmpty())
        <div class="space-y-3">
            @foreach($certificates as $certificate)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow md:flex md:items-center">
                    <!-- Student Info -->
                    <div class="bg-gradient-to-r from-amber-50 to-yellow-50 px-3 md:px-4 py-2.5 md:py-3 border-b md:border-b-0 md:border-e border-amber-100 md:min-w-[220px] md:self-stretch md:flex md:items-center">
                        <div class="flex items-center gap-2 md:gap-3">
                            <x-avatar :user="$certificate->student" size="sm" user-type="student" />
                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-gray-900 text-sm truncate">{{ $certificate->student?->name ?? '' }}</p>
                                <p class="text-xs text-gray-600 truncate">{{ $certificate->certificate_number }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Certificate Details -->
                    <div class="p-3 md:p-4 flex-1 md:flex md:items-center md:justify-between md:gap-4">
                        <div class="space-y-1 mb-2 md:mb-0">
                            <div class="flex items-center gap-1.5 text-xs md:text-sm text-gray-600">
                                <i class="ri-user-line text-gray-400"></i>
                                <span>{{ __('supervisor.certificates.issued_by', ['name' => $certificate->teacher?->name ?? '']) }}</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs md:text-sm text-gray-600">
                                <i class="ri-calendar-line text-amber-500"></i>
                                <span>{{ $certificate->issued_at?->locale(app()->getLocale())->translatedFormat('d F Y') ?? '' }}</span>
                            </div>
                            @if($certificate->certificate_type)
                                <span class="inline-flex text-xs px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700">
                                    {{ $certificate->certificate_type }}
                                </span>
                            @endif
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-2 md:shrink-0">
                            <a href="{{ route('student.certificate.view', ['subdomain' => $subdomain, 'certificate' => $certificate->id]) }}"
                               target="_blank"
                               class="min-h-[40px] md:min-h-[44px] flex-1 md:flex-initial inline-flex items-center justify-center gap-1 px-3 md:px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-lg transition-colors">
                                <i class="ri-eye-line"></i>
                                {{ __('supervisor.certificates.view_certificate') }}
                            </a>
                            <a href="{{ route('student.certificate.download', ['subdomain' => $subdomain, 'certificate' => $certificate->id]) }}"
                               class="min-h-[40px] md:min-h-[44px] flex-1 md:flex-initial inline-flex items-center justify-center gap-1 px-3 md:px-4 py-2 bg-green-500 hover:bg-green-600 text-white text-xs font-medium rounded-lg transition-colors">
                                <i class="ri-download-line"></i>
                                {{ __('supervisor.certificates.download_certificate') }}
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $certificates->links() }}
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 md:p-12 text-center">
            <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="ri-award-line text-2xl text-amber-400"></i>
            </div>
            <h3 class="text-base font-bold text-gray-900 mb-1">{{ __('supervisor.common.no_data') }}</h3>
            <p class="text-sm text-gray-500">{{ __('supervisor.certificates.page_subtitle') }}</p>
        </div>
    @endif
</div>

</x-layouts.supervisor>
