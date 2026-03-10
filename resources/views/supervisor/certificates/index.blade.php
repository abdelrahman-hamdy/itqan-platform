<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div>
    <x-ui.breadcrumb
        :items="[['label' => __('supervisor.certificates.page_title')]]"
        view-type="supervisor"
    />

    <div class="mb-6 md:mb-8">
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('supervisor.certificates.page_title') }}</h1>
        <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('supervisor.certificates.page_subtitle') }}</p>
    </div>

    <x-certificate.certificate-list
        :certificates="$certificates"
        :totalCertificates="$totalCertificates"
        :filterRoute="route('manage.certificates.index', ['subdomain' => $subdomain])"
        :subdomain="$subdomain"
        :students="$students ?? []"
        :teachers="$teachers"
        :selectedTeacherId="request('teacher_id')"
        accentColor="indigo"
    />
</div>

</x-layouts.supervisor>
