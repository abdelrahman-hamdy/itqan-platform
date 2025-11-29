@props(['certificate'])

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-lg transition-shadow duration-300 overflow-hidden">
    <!-- Certificate Preview Background -->
    <div class="h-40 relative overflow-hidden bg-gradient-to-br from-amber-400 to-yellow-500">
        <!-- Certificate Icon -->
        <div class="absolute inset-0 flex items-center justify-center">
            <i class="ri-award-fill text-6xl text-white/40"></i>
        </div>

        <!-- Certificate Type Badge -->
        <div class="absolute top-4 right-4">
            @php
                $typeIcon = match($certificate->certificate_type->value) {
                    'recorded_course' => 'ri-video-line',
                    'interactive_course' => 'ri-live-line',
                    'quran_subscription' => 'ri-book-open-line',
                    'academic_subscription' => 'ri-graduation-cap-line',
                    default => 'ri-award-line',
                };
            @endphp
            <span class="inline-flex items-center px-3 py-1.5 bg-white/95 backdrop-blur-sm rounded-full text-sm font-medium text-gray-800">
                <i class="{{ $typeIcon }} ml-1.5 text-amber-600"></i>
                {{ $certificate->certificate_type->label() }}
            </span>
        </div>
    </div>

    <!-- Certificate Details -->
    <div class="p-5">
        <!-- Certificate Number -->
        <div class="mb-4">
            <p class="text-xs text-gray-500 mb-1">رقم الشهادة</p>
            <p class="text-sm font-mono text-gray-900 bg-gray-50 px-3 py-1.5 rounded-lg inline-block">
                {{ $certificate->certificate_number }}
            </p>
        </div>

        <!-- Meta Information -->
        <div class="space-y-2 mb-4 pb-4 border-b border-gray-100">
            <!-- Academy -->
            @if($certificate->academy)
            <div class="flex items-center text-sm text-gray-600">
                <i class="ri-building-line ml-2 text-gray-400"></i>
                <span>{{ $certificate->academy->name }}</span>
            </div>
            @endif

            <!-- Teacher -->
            @if($certificate->teacher)
            <div class="flex items-center text-sm text-gray-600">
                <i class="ri-user-line ml-2 text-gray-400"></i>
                <span>{{ $certificate->teacher->name }}</span>
            </div>
            @endif

            <!-- Issue Date -->
            <div class="flex items-center text-sm text-gray-600">
                <i class="ri-calendar-line ml-2 text-gray-400"></i>
                <span>{{ $certificate->issued_at->locale('ar')->translatedFormat('d F Y') }}</span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-2">
            <!-- View Button -->
            <a href="{{ route('student.certificate.view', ['subdomain' => $subdomain, 'certificate' => $certificate->id]) }}"
               target="_blank"
               class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors font-medium">
                <i class="ri-eye-line ml-2"></i>
                عرض
            </a>

            <!-- Download Button -->
            <a href="{{ route('student.certificate.download', ['subdomain' => $subdomain, 'certificate' => $certificate->id]) }}"
               class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors font-medium">
                <i class="ri-download-line ml-2"></i>
                تحميل
            </a>
        </div>
    </div>
</div>
