@props([
    'subscription' => null,
    'circle' => null,
    'type' => 'quran' // 'quran', 'academic', 'interactive', 'group_quran'
])

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $studentId = auth()->id();

    // For group circles, get certificates directly from the Certificate model
    if ($circle || $type === 'group_quran') {
        $circleModel = $circle ?? $subscription?->circle;
        if ($circleModel) {
            // Get all certificates for this student in this circle
            $certificates = \App\Models\Certificate::where('student_id', $studentId)
                ->where('certificateable_type', \App\Models\QuranCircle::class)
                ->where('certificateable_id', $circleModel->id)
                ->orderBy('issued_at', 'desc')
                ->get();
            $certificate = $certificates->first();
            $certificateIssued = $certificates->count() > 0;
            $totalCertificates = $certificates->count();
        } else {
            $certificate = null;
            $certificateIssued = false;
            $totalCertificates = 0;
        }
    } else {
        // For individual subscriptions
        $certificate = $subscription?->certificate ?? null;
        $certificateIssued = $subscription?->certificate_issued ?? false;
        $totalCertificates = $certificateIssued ? 1 : 0;
    }
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
        </svg>
        الشهادات
    </h3>

    @if($certificateIssued && $certificate)
        <!-- Certificate Issued -->
        <div class="bg-gradient-to-br from-amber-50 to-yellow-50 rounded-xl p-4 border-2 border-amber-200 mb-4">
            <div class="flex items-center justify-center mb-3">
                <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
            </div>

            <div class="text-center mb-4">
                <h4 class="text-lg font-bold text-amber-800 mb-1">مبارك! حصلت على شهادة تقدير</h4>
                <p class="text-sm text-amber-700">رقم الشهادة: {{ $certificate->certificate_number }}</p>
                <p class="text-xs text-amber-600 mt-1">
                    {{ $certificate->issued_at->locale('ar')->translatedFormat('d F Y') }}
                </p>
            </div>

            @if($totalCertificates > 1)
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-2 mb-2 text-center">
                    <p class="text-sm text-blue-700">
                        <svg class="w-4 h-4 inline ml-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        لديك {{ $totalCertificates }} شهادات في هذه الحلقة
                    </p>
                </div>
            @endif

            <div class="space-y-2">
                <a href="{{ route('student.certificate.view', ['subdomain' => $subdomain, 'certificate' => $certificate->id]) }}"
                   target="_blank"
                   class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors">
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    عرض الشهادة
                </a>
                <a href="{{ route('student.certificate.download', ['subdomain' => $subdomain, 'certificate' => $certificate->id]) }}"
                   class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors">
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    تحميل PDF
                </a>
            </div>
        </div>

        <!-- All Certificates Link -->
        <a href="{{ route('student.certificates', ['subdomain' => $subdomain]) }}"
           class="text-sm text-blue-600 hover:text-blue-700 flex items-center justify-center gap-1">
            <span>عرض جميع الشهادات</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </a>
    @else
        <!-- No Certificate Yet - Motivational -->
        <div class="text-center py-6">
            <div class="w-16 h-16 bg-amber-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                </svg>
            </div>
            <h4 class="text-gray-800 font-bold mb-2">شهادتك في انتظارك! 🌟</h4>
            <p class="text-sm text-gray-600 mb-4">
                @if($type === 'quran' || $type === 'group_quran')
                    واصل تقدمك في الحفظ والتلاوة وستحصل على شهادة تقدير
                @elseif($type === 'academic')
                    أكمل دروسك بتميز واحصل على شهادة إنجاز
                @else
                    أتم الدورة بنجاح واحصل على شهادتك
                @endif
            </p>

            <!-- Motivational Card -->
            <div class="bg-gradient-to-r from-amber-50 to-yellow-50 rounded-lg p-4 border border-amber-100">
                <p class="text-sm text-amber-800 font-medium">
                    <svg class="w-4 h-4 inline ml-1 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                    كل جهد تبذله يقربك من إنجازك!
                </p>
            </div>
        </div>
    @endif
</div>
