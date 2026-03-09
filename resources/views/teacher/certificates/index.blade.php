<x-layouts.teacher :title="__('teacher.certificates.page_title') . ' - ' . config('app.name')">
@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $hasActiveFilters = request('certificate_type') || request('student_id') || request('date_from') || request('date_to');
@endphp

    {{-- Page Header --}}
    <div>
        <x-ui.breadcrumb :items="[['label' => __('teacher.certificates.breadcrumb')]]" view-type="teacher" />

        <div class="mb-6 md:mb-8">
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('teacher.certificates.page_title') }}</h1>
            <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('teacher.certificates.page_description') }}</p>
        </div>
    </div>

    {{-- List Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        {{-- List Header --}}
        <div class="px-4 md:px-6 py-3 md:py-4 border-b border-gray-200">
            <h2 class="text-base md:text-lg font-semibold text-gray-900">{{ __('teacher.certificates.list_title') }} ({{ $totalCertificates ?? 0 }})</h2>
        </div>

        {{-- Collapsible Filters --}}
        <div x-data="{ open: {{ $hasActiveFilters ? 'true' : 'false' }} }" class="border-b border-gray-200">
            <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-4 md:px-6 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                <span class="flex items-center gap-2">
                    <i class="ri-filter-3-line text-orange-500"></i>
                    {{ __('teacher.certificates.filter') }}
                    @if($hasActiveFilters)
                        <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-orange-500 rounded-full">{{ collect([request('student_id'), request('certificate_type'), request('date_from'), request('date_to')])->filter()->count() }}</span>
                    @endif
                </span>
                <i class="ri-arrow-down-s-line text-gray-400 transition-transform" :class="{ 'rotate-180': open }"></i>
            </button>
            <div x-show="open" x-collapse>
                <form method="GET" action="{{ route('teacher.certificates.index', ['subdomain' => $subdomain]) }}" class="px-4 md:px-6 pb-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
                        <div>
                            <label for="student_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.certificates.filter_student') }}</label>
                            <select name="student_id" id="student_id" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                <option value="">{{ __('teacher.certificates.all_students') }}</option>
                                @foreach($students ?? [] as $studentId => $studentName)
                                    <option value="{{ $studentId }}" {{ request('student_id') == $studentId ? 'selected' : '' }}>{{ $studentName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="certificate_type" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.certificates.filter_type') }}</label>
                            <select name="certificate_type" id="certificate_type" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                <option value="">{{ __('teacher.certificates.all_types') }}</option>
                                @foreach(\App\Enums\CertificateType::cases() as $type)
                                    <option value="{{ $type->value }}" {{ request('certificate_type') === $type->value ? 'selected' : '' }}>{{ $type->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.certificates.date_from') }}</label>
                            <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}"
                                   class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        </div>
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
        </div>

        {{-- Items --}}
        @if($certificates->count() > 0)
            <div class="divide-y divide-gray-200">
                @foreach($certificates as $certificate)
                    @php
                        $certType = $certificate->certificate_type;
                        $typeBadgeClass = $certType instanceof \App\Enums\CertificateType ? $certType->badgeClass() : 'bg-gray-100 text-gray-800';
                        $typeLabel = $certType instanceof \App\Enums\CertificateType ? $certType->label() : __('teacher.certificates.unknown_type');

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
            </div>

            @if($certificates->hasPages())
                <div class="px-4 md:px-6 py-4 border-t border-gray-200">
                    {{ $certificates->links() }}
                </div>
            @endif
        @else
            <div class="px-4 md:px-6 py-8 md:py-12 text-center">
                <div class="w-14 h-14 md:w-16 md:h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                    <i class="ri-award-line text-xl md:text-2xl text-gray-400"></i>
                </div>
                <h3 class="text-base md:text-lg font-medium text-gray-900 mb-1 md:mb-2">{{ __('teacher.certificates.empty_title') }}</h3>
                <p class="text-sm md:text-base text-gray-600">
                    @if($hasActiveFilters)
                        {{ __('teacher.certificates.empty_filter_description') }}
                    @else
                        {{ __('teacher.certificates.empty_description') }}
                    @endif
                </p>
                @if($hasActiveFilters)
                    <a href="{{ route('teacher.certificates.index', ['subdomain' => $subdomain]) }}"
                       class="min-h-[44px] inline-flex items-center justify-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium rounded-lg transition-colors mt-4">
                        {{ __('teacher.certificates.view_all') }}
                    </a>
                @endif
            </div>
        @endif
    </div>
</x-layouts.teacher>
