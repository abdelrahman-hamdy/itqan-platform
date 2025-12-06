@props([
    'type', // 'quran_group', 'quran_individual', 'academic', 'interactive'
    'entity', // The circle, subscription, or course object
])

@php
    // Determine subscription type and IDs based on type
    switch($type) {
        case 'quran_group':
            $subscriptionType = 'group_quran';
            $subscriptionId = null;
            $circleId = $entity->id;
            $buttonText = 'إصدار شهادات';
            $description = 'يمكنك إصدار شهادات للطلاب عند إتمام البرنامج';
            break;

        case 'quran_individual':
            $subscriptionType = 'quran';
            $subscriptionId = $entity->subscription->id ?? null;
            $circleId = null;
            $hasCertificate = ($entity->subscription?->certificate_issued && $entity->subscription?->certificate);
            $buttonText = 'إصدار شهادة';
            $description = 'يمكنك إصدار شهادة للطالب عند إتمام البرنامج';
            if ($hasCertificate) {
                $certificate = $entity->subscription->certificate;
            }
            break;

        case 'academic':
            $subscriptionType = 'academic';
            $subscriptionId = $entity->id;
            $circleId = null;
            $hasCertificate = ($entity->certificate_issued && $entity->certificate);
            $buttonText = 'إصدار شهادة';
            $description = 'يمكنك إصدار شهادة للطالب عند إتمام البرنامج';
            if ($hasCertificate) {
                $certificate = $entity->certificate;
            }
            break;

        case 'interactive':
            $subscriptionType = 'interactive';
            $subscriptionId = null;
            $circleId = $entity->id; // Pass course ID as circleId
            $buttonText = 'إصدار شهادة للطلاب';
            $description = 'يمكنك إصدار شهادات للطلاب عند إتمام الكورس أو تحقيق إنجاز معين';
            break;

        default:
            return;
    }

    // Check if it's an individual type (quran_individual or academic)
    $isIndividual = in_array($type, ['quran_individual', 'academic']);
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
        <i class="ri-award-line text-amber-500"></i>
        {{ $isIndividual ? 'الشهادة' : 'الشهادات' }}
    </h3>

    @if($isIndividual && isset($hasCertificate) && $hasCertificate && isset($certificate))
        <!-- Individual Certificate Status -->
        <div class="bg-green-50 rounded-lg p-3 mb-4 border border-green-200">
            <p class="text-sm text-green-800">
                <i class="ri-checkbox-circle-fill ml-1"></i>
                تم إصدار الشهادة
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('student.certificate.view', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'certificate' => $certificate->id]) }}"
               target="_blank"
               class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-lg transition-colors">
                <i class="ri-eye-line ml-1"></i>
                عرض
            </a>
            <a href="{{ route('student.certificate.download', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'certificate' => $certificate->id]) }}"
               class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-green-500 hover:bg-green-600 text-white text-xs font-medium rounded-lg transition-colors">
                <i class="ri-download-line ml-1"></i>
                تحميل
            </a>
        </div>
    @else
        <!-- Issue Certificate Button -->
        <p class="text-sm text-gray-600 mb-4">{{ $description }}</p>

        <button type="button"
                onclick="Livewire.dispatch('openModal', { subscriptionType: '{{ $subscriptionType }}', subscriptionId: {{ $subscriptionId ?? 'null' }}, circleId: {{ $circleId ?? 'null' }} })"
                class="w-full inline-flex items-center justify-center px-5 py-3 bg-gradient-to-r from-amber-500 to-yellow-500 hover:from-amber-600 hover:to-yellow-600 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl">
            <i class="ri-award-line ml-2 text-lg"></i>
            {{ $buttonText }}
        </button>
    @endif
</div>
