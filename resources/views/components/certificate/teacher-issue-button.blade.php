@props([
    'subscription',
    'type' => 'quran', // 'quran' or 'academic'
    'size' => 'default' // 'default', 'small', 'large'
])

@php
    $certificateIssued = $subscription->certificate_issued ?? false;
    $certificate = $subscription->certificate ?? null;
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $sizeClasses = [
        'small' => 'px-3 py-2 text-sm',
        'default' => 'px-5 py-3 text-sm',
        'large' => 'px-6 py-3.5 text-base'
    ];
@endphp

@if($certificateIssued && $certificate)
    <!-- Certificate Already Issued - View/Download Button -->
    <div class="space-y-2">
        <a href="{{ route('student.certificate.view', ['subdomain' => $subdomain, 'certificate' => $certificate->id]) }}"
           target="_blank"
           class="w-full inline-flex items-center justify-center {{ $sizeClasses[$size] }} bg-green-500 hover:bg-green-600 text-white font-medium rounded-xl transition-colors shadow-lg">
            <i class="ri-eye-line ml-2"></i>
            عرض الشهادة
        </a>
        <p class="text-xs text-center text-gray-500">
            تم الإصدار: {{ $certificate->issued_at->locale('ar')->translatedFormat('d F Y') }}
        </p>
    </div>
@else
    <!-- Issue Certificate Button -->
    <button
        type="button"
        onclick="openCertificateModal('{{ $type }}', {{ $subscription->id }})"
        class="w-full inline-flex items-center justify-center {{ $sizeClasses[$size] }} bg-gradient-to-r from-amber-500 to-yellow-500 hover:from-amber-600 hover:to-yellow-600 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl">
        <i class="ri-award-line ml-2 text-lg"></i>
        إصدار شهادة
    </button>
@endif

@once
@push('scripts')
<script>
function openCertificateModal(type, subscriptionId) {
    // Dispatch event to Livewire component
    if (typeof Livewire !== 'undefined') {
        Livewire.dispatch('openCertificateModal', { type: type, subscriptionId: subscriptionId });
    } else {
        // Fallback: redirect to Filament dashboard
        const baseUrl = type === 'quran'
            ? '{{ url("/teacher-panel/quran-subscriptions") }}'
            : '{{ url("/academic-teacher-panel/academic-subscriptions") }}';
        window.location.href = baseUrl;
    }
}
</script>
@endpush
@endonce
