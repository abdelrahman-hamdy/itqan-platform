@props(['certificate'])

<div class="bg-white rounded-lg shadow-md hover:shadow-xl transition-shadow duration-300 overflow-hidden">
    <!-- Certificate Preview Background -->
    <div class="h-48 relative overflow-hidden {{ $certificate->template_style->value === 'modern' ? 'bg-gradient-to-br from-blue-500 to-green-500' : ($certificate->template_style->value === 'classic' ? 'bg-gradient-to-br from-gray-700 to-gray-900' : 'bg-gradient-to-br from-amber-500 to-yellow-600') }}">
        <!-- Template Style Indicator -->
        <div class="absolute top-4 left-4">
            <span class="px-3 py-1 bg-white/90 backdrop-blur-sm rounded-full text-sm font-semibold {{ $certificate->template_style->value === 'modern' ? 'text-blue-600' : ($certificate->template_style->value === 'classic' ? 'text-gray-800' : 'text-amber-700') }}">
                {{ $certificate->template_style->label() }}
            </span>
        </div>

        <!-- Certificate Icon -->
        <div class="absolute inset-0 flex items-center justify-center">
            <svg class="w-24 h-24 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
        </div>

        <!-- Certificate Type Badge -->
        <div class="absolute top-4 right-4">
            <span class="inline-flex items-center px-3 py-1 bg-white/90 backdrop-blur-sm rounded-full text-sm font-medium {{ $certificate->certificate_type->badgeClass() }}">
                <span class="ml-1.5">{{ $certificate->certificate_type->icon() }}</span>
                {{ $certificate->certificate_type->label() }}
            </span>
        </div>
    </div>

    <!-- Certificate Details -->
    <div class="p-6">
        <!-- Certificate Number -->
        <div class="mb-4">
            <p class="text-xs text-gray-500 mb-1">رقم الشهادة</p>
            <p class="text-sm font-mono text-gray-900 bg-gray-50 px-3 py-1 rounded inline-block">
                {{ $certificate->certificate_number }}
            </p>
        </div>

        <!-- Certificate Title/Text Preview -->
        <div class="mb-4">
            <p class="text-gray-700 leading-relaxed line-clamp-2">
                {{ Str::limit($certificate->certificate_text, 120) }}
            </p>
        </div>

        <!-- Meta Information -->
        <div class="space-y-2 mb-4 pb-4 border-b border-gray-200">
            <!-- Academy -->
            @if($certificate->academy)
            <div class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 ml-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <span>{{ $certificate->academy->name }}</span>
            </div>
            @endif

            <!-- Teacher -->
            @if($certificate->teacher)
            <div class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 ml-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span>{{ $certificate->teacher->name }}</span>
            </div>
            @endif

            <!-- Issue Date -->
            <div class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 ml-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>{{ $certificate->issued_at->locale('ar')->translatedFormat('d F Y') }}</span>
            </div>

            <!-- Manual Badge -->
            @if($certificate->is_manual)
            <div class="flex items-center text-sm">
                <span class="inline-flex items-center px-2 py-1 bg-purple-100 text-purple-700 rounded text-xs font-medium">
                    <svg class="w-3 h-3 ml-1" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm9.707 5.707a1 1 0 00-1.414-1.414L9 12.586l-1.293-1.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    شهادة مخصصة
                </span>
            </div>
            @endif
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-3">
            <!-- View Button -->
            <a href="{{ route('student.certificate.view', $certificate) }}"
               target="_blank"
               class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors duration-200">
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                عرض
            </a>

            <!-- Download Button -->
            <a href="{{ route('student.certificate.download', $certificate) }}"
               class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors duration-200">
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                تحميل PDF
            </a>
        </div>

        <!-- Share Hint -->
        <div class="mt-3 text-center">
            <p class="text-xs text-gray-500">
                <svg class="w-3 h-3 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                يمكنك مشاركة هذه الشهادة مع الآخرين
            </p>
        </div>
    </div>
</div>
