<x-layouts.teacher :title="__('teacher.certificates.page_title') . ' - ' . config('app.name')">
@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $filterOptions = [
        '' => __('teacher.certificates.all_types'),
    ];
    foreach (\App\Enums\CertificateType::cases() as $type) {
        $filterOptions[$type->value] = $type->label();
    }

    $stats = [
        [
            'icon' => 'ri-award-line',
            'bgColor' => 'bg-orange-100',
            'iconColor' => 'text-orange-600',
            'value' => $totalCertificates ?? 0,
            'label' => __('teacher.certificates.total_certificates'),
        ],
    ];
@endphp

    <x-teacher.entity-list-page
        :title="__('teacher.certificates.page_title')"
        :subtitle="__('teacher.certificates.page_description')"
        :items="$certificates"
        :stats="$stats"
        :filter-options="$filterOptions"
        filter-param="certificate_type"
        :breadcrumbs="[['label' => __('teacher.certificates.breadcrumb')]]"
        theme-color="orange"
        :list-title="__('teacher.certificates.list_title')"
        empty-icon="ri-award-line"
        :empty-title="__('teacher.certificates.empty_title')"
        :empty-description="__('teacher.certificates.empty_description')"
        :empty-filter-description="__('teacher.certificates.empty_filter_description')"
        :clear-filter-route="route('teacher.certificates.index', ['subdomain' => $subdomain])"
        :clear-filter-text="__('teacher.certificates.view_all')"
    >
        @foreach($certificates as $certificate)
            @php
                $certType = $certificate->certificate_type;
                $typeBadgeClass = $certType instanceof \App\Enums\CertificateType ? $certType->badgeClass() : 'bg-gray-100 text-gray-800';
                $typeLabel = $certType instanceof \App\Enums\CertificateType ? $certType->label() : __('teacher.certificates.unknown_type');

                // Determine entity name
                $entityName = '';
                if ($certificate->certificateable) {
                    $entity = $certificate->certificateable;
                    $entityName = $entity->title ?? $entity->name ?? '';
                }

                $metadata = [
                    ['icon' => 'ri-user-line', 'text' => $certificate->student?->name ?? __('teacher.certificates.unknown_student')],
                ];

                if ($entityName) {
                    $certTypeIcon = $certType instanceof \App\Enums\CertificateType ? $certType->icon() : 'ri-file-line';
                    $metadata[] = ['icon' => $certTypeIcon, 'text' => $entityName];
                }

                if ($certificate->certificate_number) {
                    $metadata[] = ['icon' => 'ri-hashtag', 'text' => $certificate->certificate_number];
                }

                if ($certificate->issued_at) {
                    $metadata[] = ['icon' => 'ri-calendar-line', 'text' => $certificate->issued_at->format('Y/m/d')];
                }

                $actions = [];
                if ($certificate->file_path && $certificate->fileExists()) {
                    $actions[] = [
                        'href' => $certificate->view_url,
                        'icon' => 'ri-eye-line',
                        'label' => __('teacher.certificates.view_pdf'),
                        'shortLabel' => __('teacher.certificates.view_pdf'),
                        'class' => 'bg-orange-600 hover:bg-orange-700 text-white',
                    ];
                    $actions[] = [
                        'href' => $certificate->download_url,
                        'icon' => 'ri-download-line',
                        'label' => __('teacher.certificates.download_pdf'),
                        'shortLabel' => __('teacher.certificates.download_pdf'),
                        'class' => 'bg-gray-100 hover:bg-gray-200 text-gray-700',
                    ];
                }
            @endphp

            <x-teacher.entity-list-item
                :title="$certificate->student?->name ?? __('teacher.certificates.unknown_student')"
                :status-badge="$typeLabel"
                :status-class="$typeBadgeClass"
                :metadata="$metadata"
                :actions="$actions"
                icon="ri-award-line"
                icon-bg-class="bg-gradient-to-br from-orange-500 to-orange-600"
            />
        @endforeach
    </x-teacher.entity-list-page>
</x-layouts.teacher>
