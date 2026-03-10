<x-layouts.teacher :title="__('teacher.certificates.page_title') . ' - ' . config('app.name')">
@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

    {{-- Page Header --}}
    <div>
        <x-ui.breadcrumb :items="[['label' => __('teacher.certificates.breadcrumb')]]" view-type="teacher" />

        <div class="mb-6 md:mb-8">
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('teacher.certificates.page_title') }}</h1>
            <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('teacher.certificates.page_description') }}</p>
        </div>
    </div>

    <x-certificate.certificate-list
        :certificates="$certificates"
        :totalCertificates="$totalCertificates"
        :filterRoute="route('teacher.certificates.index', ['subdomain' => $subdomain])"
        :subdomain="$subdomain"
        :students="$students ?? []"
        accentColor="orange"
    />
</x-layouts.teacher>
