@props(['certificate'])

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $previewImageUrl = $certificate->template_style?->previewImageUrl() ?? asset('certificates/templates/template_images/template_1.png');
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-lg transition-shadow duration-300 overflow-hidden">
    <!-- Certificate Template Preview -->
    <div class="aspect-[297/210] relative overflow-hidden bg-gray-100">
        <!-- Template Preview Image -->
        <img src="{{ $previewImageUrl }}"
             alt="{{ __('components.certificate_card.preview_alt') }}"
             class="w-full h-full object-cover">

        <!-- Blur Overlay -->
        <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-black/20 to-black/10 backdrop-blur-sm"></div>

        <!-- Certificate Type Badge - Centered -->
        <div class="absolute inset-0 flex items-center justify-center">
            @php
                $typeIcon = match($certificate->certificate_type->value) {
                    'recorded_course' => 'ri-video-line',
                    'interactive_course' => 'ri-live-line',
                    'quran_subscription' => 'ri-book-open-line',
                    'academic_subscription' => 'ri-graduation-cap-line',
                    default => 'ri-award-line',
                };
            @endphp
            <span class="inline-flex items-center px-5 py-2.5 bg-white/95 backdrop-blur-sm rounded-xl text-base font-bold text-gray-800 shadow-lg">
                <i class="{{ $typeIcon }} ms-2 rtl:ms-2 ltr:me-2 text-xl text-amber-600"></i>
                {{ $certificate->certificate_type->label() }}
            </span>
        </div>
    </div>

    <!-- Certificate Details -->
    <div class="p-5">
        <!-- Certificate Number -->
        <div class="mb-4">
            <p class="text-xs text-gray-500 mb-1">{{ __('components.certificate_card.certificate_number') }}</p>
            <p class="text-sm font-mono text-gray-900 bg-gray-50 px-3 py-1.5 rounded-lg inline-block">
                {{ $certificate->certificate_number }}
            </p>
        </div>

        <!-- Meta Information -->
        <div class="space-y-2 mb-4 pb-4 border-b border-gray-100">
            <!-- Academy -->
            @if($certificate->academy)
            <div class="flex items-center text-sm text-gray-600">
                <i class="ri-building-line ms-2 rtl:ms-2 ltr:me-2 text-gray-400"></i>
                <span>{{ $certificate->academy->name }}</span>
            </div>
            @endif

            <!-- Teacher -->
            @if($certificate->teacher)
            <div class="flex items-center text-sm text-gray-600">
                <i class="ri-user-line ms-2 rtl:ms-2 ltr:me-2 text-gray-400"></i>
                <span>{{ $certificate->teacher->name }}</span>
            </div>
            @endif

            <!-- Issue Date -->
            <div class="flex items-center text-sm text-gray-600">
                <i class="ri-calendar-line ms-2 rtl:ms-2 ltr:me-2 text-gray-400"></i>
                <span>{{ $certificate->issued_at->locale(app()->getLocale())->translatedFormat('d F Y') }}</span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-2">
            <!-- View Button -->
            <a href="{{ route('student.certificate.view', ['subdomain' => $subdomain, 'certificate' => $certificate->id]) }}"
               target="_blank"
               class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition-colors font-medium">
                <i class="ri-eye-line ms-2 rtl:ms-2 ltr:me-2"></i>
                {{ __('components.certificate_card.view') }}
            </a>

            <!-- Download Button -->
            <a href="{{ route('student.certificate.download', ['subdomain' => $subdomain, 'certificate' => $certificate->id]) }}"
               class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors font-medium">
                <i class="ri-download-line ms-2 rtl:ms-2 ltr:me-2"></i>
                {{ __('components.certificate_card.download') }}
            </a>
        </div>
    </div>
</div>
