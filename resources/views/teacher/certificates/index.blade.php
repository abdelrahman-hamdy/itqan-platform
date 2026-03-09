<x-layouts.teacher :title="__('teacher.certificates.page_title') . ' - ' . config('app.name')">
@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $hasActiveFilters = request('certificate_type') || request('template_style') || request('is_manual') || request('student_id') || request('date_from') || request('date_to');
@endphp

    {{-- Filters Section --}}
    <div class="mb-4 md:mb-6">
        <form method="GET" action="{{ route('teacher.certificates.index', ['subdomain' => $subdomain]) }}" class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4">
                {{-- Student Filter --}}
                <div>
                    <label for="student_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.certificates.filter_student') }}</label>
                    <select name="student_id" id="student_id" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        <option value="">{{ __('teacher.certificates.all_students') }}</option>
                        @foreach($students ?? [] as $studentId => $studentName)
                            <option value="{{ $studentId }}" {{ request('student_id') == $studentId ? 'selected' : '' }}>{{ $studentName }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Certificate Type Filter --}}
                <div>
                    <label for="certificate_type" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.certificates.filter_type') }}</label>
                    <select name="certificate_type" id="certificate_type" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        <option value="">{{ __('teacher.certificates.all_types') }}</option>
                        @foreach(\App\Enums\CertificateType::cases() as $type)
                            <option value="{{ $type->value }}" {{ request('certificate_type') === $type->value ? 'selected' : '' }}>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Template Style Filter --}}
                <div>
                    <label for="template_style" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.certificates.filter_template') }}</label>
                    <select name="template_style" id="template_style" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        <option value="">{{ __('teacher.certificates.all_templates') }}</option>
                        @foreach(\App\Enums\CertificateTemplateStyle::cases() as $style)
                            <option value="{{ $style->value }}" {{ request('template_style') === $style->value ? 'selected' : '' }}>{{ $style->label() }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Issue Type Filter --}}
                <div>
                    <label for="is_manual" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.certificates.filter_issue_type') }}</label>
                    <select name="is_manual" id="is_manual" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        <option value="">{{ __('teacher.certificates.all_issue_types') }}</option>
                        <option value="1" {{ request('is_manual') === '1' ? 'selected' : '' }}>{{ __('teacher.certificates.manual') }}</option>
                        <option value="0" {{ request('is_manual') === '0' ? 'selected' : '' }}>{{ __('teacher.certificates.automatic') }}</option>
                    </select>
                </div>

                {{-- Date From --}}
                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.certificates.date_from') }}</label>
                    <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}"
                           class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                </div>

                {{-- Date To --}}
                <div>
                    <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.certificates.date_to') }}</label>
                    <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}"
                           class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 mt-4">
                <button type="submit" class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors text-sm font-medium">
                    <i class="ri-filter-line"></i>
                    {{ __('teacher.certificates.filter') }}
                </button>
                @if($hasActiveFilters)
                    <a href="{{ route('teacher.certificates.index', ['subdomain' => $subdomain]) }}"
                       class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                        <i class="ri-close-line"></i>
                        {{ __('teacher.certificates.clear_filters') }}
                    </a>
                @endif
            </div>
        </form>
    </div>

    <x-teacher.entity-list-page
        :title="__('teacher.certificates.page_title') . ' (' . ($totalCertificates ?? 0) . ')'"
        :subtitle="__('teacher.certificates.page_description')"
        :items="$certificates"
        :stats="[]"
        :filter-options="[]"
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

                $metadata = [];

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
                :avatar="$certificate->student"
            />
        @endforeach
    </x-teacher.entity-list-page>
</x-layouts.teacher>
