<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $certType = $certificate->certificate_type;
    $certTypeLabel = $certType instanceof \App\Enums\CertificateType ? $certType->label() : $certType;
    $certTypeBadge = $certType instanceof \App\Enums\CertificateType ? $certType->badgeClass() : 'bg-gray-100 text-gray-800';
    $certTypeIcon = $certType instanceof \App\Enums\CertificateType ? $certType->icon() : 'ri-award-line';
@endphp

<div>
    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.certificates.page_title'), 'route' => route('manage.certificates.index', ['subdomain' => $subdomain])],
            ['label' => $certificate->certificate_number ?? __('supervisor.certificates.certificate_details')],
        ]"
        view-type="supervisor"
    />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6 lg:gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-4 md:space-y-6">
            <!-- Certificate Header -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <div class="flex items-start gap-4 mb-4">
                    <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="ri-award-fill text-2xl text-amber-600"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h2 class="text-lg md:text-xl font-bold text-gray-900">{{ __('supervisor.certificates.certificate_details') }}</h2>
                        @if($certificate->certificate_number)
                            <p class="text-sm text-gray-500">{{ $certificate->certificate_number }}</p>
                        @endif
                    </div>
                    <span class="text-xs px-2.5 py-1 rounded-full {{ $certTypeBadge }}">
                        <i class="{{ $certTypeIcon }} ml-1"></i> {{ $certTypeLabel }}
                    </span>
                </div>

                @if($certificate->certificate_text)
                    <div class="bg-gray-50 rounded-lg p-4 mb-4">
                        <p class="text-sm text-gray-700 leading-relaxed">{{ $certificate->certificate_text }}</p>
                    </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="flex items-center gap-3 p-3 bg-blue-50 rounded-lg">
                        <div class="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="ri-user-line text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">{{ __('supervisor.certificates.student') }}</p>
                            <p class="text-sm font-medium text-gray-900">{{ $certificate->student?->name ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 p-3 bg-green-50 rounded-lg">
                        <div class="w-9 h-9 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="ri-user-star-line text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">{{ __('supervisor.certificates.teacher') }}</p>
                            <p class="text-sm font-medium text-gray-900">{{ $certificate->teacher?->name ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 p-3 bg-amber-50 rounded-lg">
                        <div class="w-9 h-9 bg-amber-100 rounded-lg flex items-center justify-center">
                            <i class="ri-calendar-line text-amber-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">{{ __('supervisor.certificates.issued_at') }}</p>
                            <p class="text-sm font-medium text-gray-900">{{ $certificate->issued_at?->format('Y/m/d') ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 p-3 bg-purple-50 rounded-lg">
                        <div class="w-9 h-9 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="ri-shield-user-line text-purple-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">{{ __('supervisor.certificates.issued_by') }}</p>
                            <p class="text-sm font-medium text-gray-900">{{ $certificate->issuedBy?->name ?? __('supervisor.certificates.system') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Related Entity -->
            @if($certificate->certificateable)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                    <h3 class="text-sm font-bold text-gray-900 mb-3">{{ __('supervisor.certificates.related_entity') }}</h3>
                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                        <div class="w-9 h-9 bg-gray-200 rounded-lg flex items-center justify-center">
                            <i class="{{ $certTypeIcon }} text-gray-600"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $certificate->certificateable->title ?? $certificate->certificateable->name ?? '-' }}</p>
                            <p class="text-xs text-gray-500">{{ class_basename($certificate->certificateable_type) }}</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-4 md:space-y-6">
            <!-- Actions -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-5">
                <h3 class="text-sm font-bold text-gray-900 mb-3">{{ __('supervisor.common.actions') }}</h3>
                <div class="space-y-2">
                    @if($certificate->file_path && $certificate->fileExists())
                        <a href="{{ $certificate->download_url }}" target="_blank"
                           class="flex items-center gap-2 w-full px-4 py-2.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="ri-download-line"></i> {{ __('supervisor.certificates.download') }}
                        </a>
                    @endif
                    <a href="{{ $certificate->view_url }}" target="_blank"
                       class="flex items-center gap-2 w-full px-4 py-2.5 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200 transition-colors">
                        <i class="ri-external-link-line"></i> {{ __('supervisor.certificates.view_certificate') }}
                    </a>
                    <a href="{{ route('manage.certificates.index', ['subdomain' => $subdomain]) }}"
                       class="flex items-center gap-2 w-full px-4 py-2.5 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200 transition-colors">
                        <i class="ri-arrow-right-line"></i> {{ __('supervisor.common.back_to_list') }}
                    </a>
                </div>
            </div>

            <!-- Certificate Meta -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-5">
                <h3 class="text-sm font-bold text-gray-900 mb-3">{{ __('supervisor.certificates.details') }}</h3>
                <div class="space-y-3 text-sm text-gray-600">
                    <div class="flex items-center gap-2">
                        <i class="ri-hashtag text-gray-400"></i>
                        <span>{{ $certificate->certificate_number ?? '-' }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="ri-award-line text-gray-400"></i>
                        <span>{{ $certTypeLabel }}</span>
                    </div>
                    @if($certificate->template_style)
                        <div class="flex items-center gap-2">
                            <i class="ri-palette-line text-gray-400"></i>
                            <span>{{ $certificate->template_style instanceof \App\Enums\CertificateTemplateStyle ? $certificate->template_style->getLabel() : $certificate->template_style }}</span>
                        </div>
                    @endif
                    <div class="flex items-center gap-2">
                        <i class="ri-settings-line text-gray-400"></i>
                        <span>{{ $certificate->is_manual ? __('supervisor.certificates.manual') : __('supervisor.certificates.automatic') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="ri-time-line text-gray-400"></i>
                        <span>{{ $certificate->created_at?->format('Y/m/d H:i') }}</span>
                    </div>
                </div>
            </div>

            @if($certificate->metadata && count($certificate->metadata) > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-5">
                    <h3 class="text-sm font-bold text-gray-900 mb-3">{{ __('supervisor.certificates.metadata') }}</h3>
                    <div class="space-y-2 text-sm text-gray-600">
                        @foreach($certificate->metadata as $key => $value)
                            <div class="flex justify-between gap-2">
                                <span class="text-gray-500">{{ $key }}</span>
                                <span class="font-medium text-gray-700">{{ is_array($value) ? json_encode($value) : $value }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

</x-layouts.supervisor>
