@extends('layouts.app')

@section('title', 'شهاداتي - My Certificates')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-green-50 py-8">
    <div class="container mx-auto px-4 max-w-7xl">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">شهاداتي</h1>
            <p class="text-gray-600">عرض وتحميل جميع شهاداتك الدراسية</p>
        </div>

        <!-- Filter Tabs -->
        @php
            $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
        @endphp
        <div class="mb-6 bg-white rounded-lg shadow-sm p-2">
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('student.certificates', ['subdomain' => $subdomain]) }}"
                   class="px-4 py-2 rounded-lg {{ !request('type') ? 'bg-blue-500 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                    الكل ({{ $certificates->count() }})
                </a>
                <a href="{{ route('student.certificates', ['subdomain' => $subdomain, 'type' => 'recorded_course']) }}"
                   class="px-4 py-2 rounded-lg {{ request('type') === 'recorded_course' ? 'bg-blue-500 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                    الدورات المسجلة
                </a>
                <a href="{{ route('student.certificates', ['subdomain' => $subdomain, 'type' => 'interactive_course']) }}"
                   class="px-4 py-2 rounded-lg {{ request('type') === 'interactive_course' ? 'bg-blue-500 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                    الدورات التفاعلية
                </a>
                <a href="{{ route('student.certificates', ['subdomain' => $subdomain, 'type' => 'quran_subscription']) }}"
                   class="px-4 py-2 rounded-lg {{ request('type') === 'quran_subscription' ? 'bg-blue-500 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                    حلقات القرآن
                </a>
                <a href="{{ route('student.certificates', ['subdomain' => $subdomain, 'type' => 'academic_subscription']) }}"
                   class="px-4 py-2 rounded-lg {{ request('type') === 'academic_subscription' ? 'bg-blue-500 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                    الدروس الأكاديمية
                </a>
            </div>
        </div>

        <!-- Certificates Grid -->
        @if($certificates->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($certificates as $certificate)
                    <x-certificate-card :certificate="$certificate" />
                @endforeach
            </div>
        @else
            <!-- Empty State -->
            <div class="bg-white rounded-lg shadow-sm p-12 text-center">
                <div class="max-w-md mx-auto">
                    <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">لا توجد شهادات بعد</h3>
                    <p class="text-gray-600 mb-6">
                        ستظهر شهاداتك هنا عند إتمام الدورات أو استلام شهادات من المعلمين
                    </p>
                    <a href="{{ route('courses.index', ['subdomain' => $subdomain]) }}"
                       class="inline-flex items-center px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition">
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        تصفح الدورات
                    </a>
                </div>
            </div>
        @endif

        <!-- Statistics Cards (if has certificates) -->
        @if($certificates->count() > 0)
            <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 mb-1">إجمالي الشهادات</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $certificates->count() }}</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 mb-1">الدورات المسجلة</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $certificates->where('certificate_type', 'recorded_course')->count() }}</p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 mb-1">حلقات القرآن</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $certificates->where('certificate_type', 'quran_subscription')->count() }}</p>
                        </div>
                        <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 mb-1">الدروس الأكاديمية</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $certificates->where('certificate_type', 'academic_subscription')->count() }}</p>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
