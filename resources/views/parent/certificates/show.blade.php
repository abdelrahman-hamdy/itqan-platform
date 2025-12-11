@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy?->subdomain ?? 'itqan-academy';
@endphp

<x-layouts.parent-layout title="عرض الشهادة">
    <div class="space-y-4 md:space-y-6">
        <!-- Back Button -->
        <div>
            <a href="{{ route('parent.certificates.index', ['subdomain' => $subdomain]) }}" class="min-h-[44px] inline-flex items-center text-blue-600 hover:text-blue-700 font-bold text-sm md:text-base">
                <i class="ri-arrow-right-line ml-1.5 md:ml-2"></i>
                العودة إلى الشهادات
            </a>
        </div>

        <!-- Certificate Preview -->
        <div class="bg-white rounded-lg md:rounded-xl shadow-xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r {{ $certificate->certificate_type === 'quran' ? 'from-green-500 to-green-600' : ($certificate->certificate_type === 'academic' ? 'from-blue-500 to-blue-600' : 'from-purple-500 to-purple-600') }} p-4 md:p-6 text-white">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 md:gap-4">
                    <div class="flex items-center gap-3 md:gap-4">
                        <i class="ri-award-line text-3xl md:text-5xl flex-shrink-0"></i>
                        <div class="min-w-0">
                            <h1 class="text-xl md:text-3xl font-bold">{{ $certificate->title }}</h1>
                            @if($certificate->subtitle)
                                <p class="text-sm md:text-lg mt-0.5 md:mt-1 opacity-90">{{ $certificate->subtitle }}</p>
                            @endif
                        </div>
                    </div>
                    <span class="self-start px-3 md:px-4 py-1.5 md:py-2 bg-white bg-opacity-20 rounded-lg text-xs md:text-sm font-bold flex-shrink-0">
                        {{ $certificate->certificate_type === 'quran' ? 'شهادة قرآن' : ($certificate->certificate_type === 'academic' ? 'شهادة أكاديمية' : 'شهادة دورة') }}
                    </span>
                </div>
            </div>

            <!-- Certificate Visual Preview -->
            <div class="p-4 md:p-8 bg-gradient-to-br {{ $certificate->certificate_type === 'quran' ? 'from-green-50 to-white' : ($certificate->certificate_type === 'academic' ? 'from-blue-50 to-white' : 'from-purple-50 to-white') }}">
                <div class="max-w-4xl mx-auto">
                    <!-- Certificate Border -->
                    <div class="border-4 md:border-8 {{ $certificate->certificate_type === 'quran' ? 'border-green-600' : ($certificate->certificate_type === 'academic' ? 'border-blue-600' : 'border-purple-600') }} rounded-lg p-4 sm:p-8 md:p-12 bg-white shadow-2xl">
                        <!-- Certificate Content -->
                        <div class="text-center space-y-4 md:space-y-6">
                            <!-- Award Icon -->
                            <div class="flex justify-center">
                                <div class="bg-gradient-to-br {{ $certificate->certificate_type === 'quran' ? 'from-green-100 to-green-200' : ($certificate->certificate_type === 'academic' ? 'from-blue-100 to-blue-200' : 'from-purple-100 to-purple-200') }} rounded-full w-20 h-20 md:w-32 md:h-32 flex items-center justify-center">
                                    <i class="ri-award-fill text-4xl md:text-6xl {{ $certificate->certificate_type === 'quran' ? 'text-green-600' : ($certificate->certificate_type === 'academic' ? 'text-blue-600' : 'text-purple-600') }}"></i>
                                </div>
                            </div>

                            <!-- Certificate Title -->
                            <div>
                                <h2 class="text-xl sm:text-2xl md:text-4xl font-bold text-gray-900 mb-1 md:mb-2">{{ $certificate->title }}</h2>
                                @if($certificate->subtitle)
                                    <p class="text-sm md:text-xl text-gray-600">{{ $certificate->subtitle }}</p>
                                @endif
                            </div>

                            <!-- Awarded To -->
                            <div class="py-3 md:py-6">
                                <p class="text-sm md:text-lg text-gray-600 mb-1 md:mb-2">هذه الشهادة تمنح إلى</p>
                                <p class="text-xl sm:text-2xl md:text-4xl font-bold {{ $certificate->certificate_type === 'quran' ? 'text-green-600' : ($certificate->certificate_type === 'academic' ? 'text-blue-600' : 'text-purple-600') }} mb-1 md:mb-2">
                                    {{ $certificate->student->name ?? '-' }}
                                </p>
                                <div class="h-0.5 md:h-1 w-32 md:w-64 mx-auto {{ $certificate->certificate_type === 'quran' ? 'bg-green-600' : ($certificate->certificate_type === 'academic' ? 'bg-blue-600' : 'bg-purple-600') }}"></div>
                            </div>

                            <!-- Description -->
                            @if($certificate->description)
                                <div class="max-w-2xl mx-auto">
                                    <p class="text-sm md:text-lg text-gray-700 leading-relaxed">{{ $certificate->description }}</p>
                                </div>
                            @endif

                            <!-- Issue Date & Teacher -->
                            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 md:gap-4 pt-4 md:pt-8 border-t-2 border-gray-200 max-w-2xl mx-auto">
                                <div class="text-center sm:text-right">
                                    <p class="text-xs md:text-sm text-gray-500">تاريخ الإصدار</p>
                                    <p class="font-bold text-sm md:text-base text-gray-900">{{ $certificate->issued_at->format('Y/m/d') }}</p>
                                </div>
                                @if($certificate->issued_by)
                                    <div class="text-center sm:text-left">
                                        <p class="text-xs md:text-sm text-gray-500">صادرة من</p>
                                        <p class="font-bold text-sm md:text-base text-gray-900">{{ $certificate->issuedBy->name ?? 'الأكاديمية' }}</p>
                                    </div>
                                @endif
                            </div>

                            <!-- Verification Code -->
                            @if($certificate->verification_code)
                                <div class="pt-3 md:pt-4">
                                    <p class="text-[10px] md:text-xs text-gray-500">رمز التحقق</p>
                                    <p class="font-mono text-xs md:text-sm text-gray-700 break-all">{{ $certificate->verification_code }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="p-4 md:p-6 bg-gray-50 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-center gap-2 md:gap-4">
                <a href="{{ route('parent.certificates.download', ['subdomain' => $subdomain, 'id' => $certificate->id]) }}"
                   class="min-h-[44px] w-full sm:w-auto inline-flex items-center justify-center px-4 md:px-6 py-2.5 md:py-3 bg-gradient-to-r {{ $certificate->certificate_type === 'quran' ? 'from-green-600 to-green-700 hover:from-green-700 hover:to-green-800' : ($certificate->certificate_type === 'academic' ? 'from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800' : 'from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800') }} text-white font-bold text-sm md:text-base rounded-lg transition-all shadow-lg hover:shadow-xl">
                    <i class="ri-download-line text-lg md:text-xl ml-1.5 md:ml-2"></i>
                    تحميل الشهادة بصيغة PDF
                </a>
                <button onclick="window.print()"
                   class="min-h-[44px] w-full sm:w-auto inline-flex items-center justify-center px-4 md:px-6 py-2.5 md:py-3 bg-gray-600 hover:bg-gray-700 text-white font-bold text-sm md:text-base rounded-lg transition-colors">
                    <i class="ri-printer-line text-lg md:text-xl ml-1.5 md:ml-2"></i>
                    طباعة
                </button>
            </div>
        </div>

        <!-- Certificate Details -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
            <!-- Certificate Information -->
            <div class="lg:col-span-2 bg-white rounded-lg md:rounded-xl shadow">
                <div class="p-4 md:p-6 border-b border-gray-200">
                    <h2 class="text-base md:text-xl font-bold text-gray-900">معلومات الشهادة</h2>
                </div>
                <div class="p-4 md:p-6 space-y-3 md:space-y-4">
                    <!-- Student -->
                    <div class="flex items-center gap-2.5 md:gap-3">
                        <div class="bg-purple-100 rounded-lg p-2.5 md:p-3 flex-shrink-0">
                            <i class="ri-user-smile-line text-lg md:text-xl text-purple-600"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs md:text-sm text-gray-500">الطالب</p>
                            <p class="font-bold text-sm md:text-base text-gray-900 truncate">{{ $certificate->student->name ?? '-' }}</p>
                        </div>
                    </div>

                    <!-- Issue Date -->
                    <div class="flex items-center gap-2.5 md:gap-3">
                        <div class="bg-blue-100 rounded-lg p-2.5 md:p-3 flex-shrink-0">
                            <i class="ri-calendar-line text-lg md:text-xl text-blue-600"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs md:text-sm text-gray-500">تاريخ الإصدار</p>
                            <p class="font-bold text-sm md:text-base text-gray-900">{{ $certificate->issued_at->format('l، Y/m/d') }}</p>
                        </div>
                    </div>

                    <!-- Issued By -->
                    @if($certificate->issued_by)
                        <div class="flex items-center gap-2.5 md:gap-3">
                            <div class="bg-green-100 rounded-lg p-2.5 md:p-3 flex-shrink-0">
                                <i class="ri-user-star-line text-lg md:text-xl text-green-600"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs md:text-sm text-gray-500">صادرة من</p>
                                <p class="font-bold text-sm md:text-base text-gray-900 truncate">{{ $certificate->issuedBy->name ?? 'الأكاديمية' }}</p>
                            </div>
                        </div>
                    @endif

                    <!-- Certificate Type -->
                    <div class="flex items-center gap-2.5 md:gap-3">
                        <div class="bg-yellow-100 rounded-lg p-2.5 md:p-3 flex-shrink-0">
                            <i class="ri-bookmark-line text-lg md:text-xl text-yellow-600"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs md:text-sm text-gray-500">نوع الشهادة</p>
                            <p class="font-bold text-sm md:text-base text-gray-900">
                                {{ $certificate->certificate_type === 'quran' ? 'شهادة قرآن كريم' : ($certificate->certificate_type === 'academic' ? 'شهادة أكاديمية' : 'شهادة دورة تعليمية') }}
                            </p>
                        </div>
                    </div>

                    <!-- Verification Code -->
                    @if($certificate->verification_code)
                        <div class="flex items-center gap-2.5 md:gap-3">
                            <div class="bg-gray-100 rounded-lg p-2.5 md:p-3 flex-shrink-0">
                                <i class="ri-shield-check-line text-lg md:text-xl text-gray-600"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs md:text-sm text-gray-500">رمز التحقق</p>
                                <p class="font-mono text-xs md:text-sm text-gray-900 break-all">{{ $certificate->verification_code }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-4 md:space-y-6">
                <!-- Quick Actions -->
                <div class="bg-white rounded-lg md:rounded-xl shadow p-4 md:p-6">
                    <h3 class="text-sm md:text-lg font-bold text-gray-900 mb-3 md:mb-4">إجراءات سريعة</h3>
                    <div class="space-y-2">
                        <a href="{{ route('parent.certificates.download', ['subdomain' => $subdomain, 'id' => $certificate->id]) }}"
                           class="min-h-[44px] flex items-center justify-between p-3 bg-green-50 hover:bg-green-100 rounded-lg transition-colors">
                            <div class="flex items-center gap-2">
                                <i class="ri-download-line text-green-600"></i>
                                <span class="text-sm md:text-base text-gray-900 font-bold">تحميل PDF</span>
                            </div>
                            <i class="ri-arrow-left-line text-gray-400"></i>
                        </a>
                        <button onclick="window.print()"
                           class="min-h-[44px] w-full flex items-center justify-between p-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                            <div class="flex items-center gap-2">
                                <i class="ri-printer-line text-blue-600"></i>
                                <span class="text-sm md:text-base text-gray-900 font-bold">طباعة</span>
                            </div>
                            <i class="ri-arrow-left-line text-gray-400"></i>
                        </button>
                    </div>
                </div>

                <!-- Related Links -->
                <div class="bg-white rounded-lg md:rounded-xl shadow p-4 md:p-6">
                    <h3 class="text-sm md:text-lg font-bold text-gray-900 mb-3 md:mb-4">روابط ذات صلة</h3>
                    <div class="space-y-2">
                        <a href="{{ route('parent.certificates.index', ['subdomain' => $subdomain]) }}" class="min-h-[44px] flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                            <div class="flex items-center gap-2">
                                <i class="ri-award-line text-gray-600"></i>
                                <span class="text-sm md:text-base text-gray-900 font-bold">جميع الشهادات</span>
                            </div>
                            <i class="ri-arrow-left-line text-gray-400"></i>
                        </a>
                        <a href="{{ route('parent.dashboard', ['subdomain' => $subdomain]) }}" class="min-h-[44px] flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                            <div class="flex items-center gap-2">
                                <i class="ri-dashboard-line text-gray-600"></i>
                                <span class="text-sm md:text-base text-gray-900 font-bold">الصفحة الرئيسية</span>
                            </div>
                            <i class="ri-arrow-left-line text-gray-400"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.parent-layout>

<style>
    @media print {
        .no-print {
            display: none !important;
        }
        body {
            background: white;
        }
    }
</style>
