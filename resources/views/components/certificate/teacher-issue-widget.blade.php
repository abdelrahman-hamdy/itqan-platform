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
            $buttonText = __('components.certificate.teacher.issue_certificates');
            $description = __('components.certificate.teacher.can_issue_group');
            break;

        case 'quran_individual':
            $subscriptionType = 'quran';
            $subscriptionId = $entity->subscription->id ?? null;
            $circleId = null;
            $buttonText = __('components.certificate.teacher.issue_certificate');
            $description = __('components.certificate.teacher.can_issue_individual');
            break;

        case 'academic':
            $subscriptionType = 'academic';
            $subscriptionId = $entity->id;
            $circleId = null;
            $buttonText = __('components.certificate.teacher.issue_certificate');
            $description = __('components.certificate.teacher.can_issue_individual');
            break;

        case 'interactive':
            $subscriptionType = 'interactive';
            $subscriptionId = null;
            $circleId = $entity->id; // Pass course ID as circleId
            $buttonText = __('components.certificate.teacher.issue_certificate_for_students');
            $description = __('components.certificate.teacher.can_issue_course');
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
        {{ $isIndividual ? __('components.certificate.teacher.title_singular') : __('components.certificate.teacher.title_plural') }}
    </h3>

    <p class="text-sm text-gray-600 mb-4">{{ $description }}</p>

    <button type="button"
            onclick="Livewire.dispatch('openModal', { subscriptionType: '{{ $subscriptionType }}', subscriptionId: {{ $subscriptionId ?? 'null' }}, circleId: {{ $circleId ?? 'null' }} })"
            class="w-full inline-flex items-center justify-center px-5 py-3 bg-gradient-to-r from-amber-500 to-yellow-500 hover:from-amber-600 hover:to-yellow-600 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl">
        <i class="ri-award-line ms-2 rtl:ms-2 ltr:me-2 text-lg"></i>
        {{ $buttonText }}
    </button>
</div>
