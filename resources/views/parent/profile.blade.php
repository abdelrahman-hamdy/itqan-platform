@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy?->subdomain ?? 'itqan-academy';
@endphp

<x-layouts.parent-layout title="الملف الشخصي">
    <div class="space-y-6">

        <!-- Welcome Section -->
        <div class="mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    مرحباً، {{ $parent->first_name ?? $user->name }}! 👋
                </h1>
                <p class="text-gray-600">
                    @if($selectedChild ?? false)
                        تتابع الآن بيانات {{ $selectedChild->user->name ?? $selectedChild->first_name }}
                    @else
                        متابعة شاملة لتقدم جميع أبنائك في رحلة التعلم
                    @endif
                </p>
            </div>
        </div>

        <!-- Quick Stats -->
        @include('components.stats.parent-quick-stats', ['stats' => $stats, 'selectedChild' => $selectedChild ?? null])

        <!-- Children Overview Cards -->
        @if($children->count() > 0)
            <div class="mb-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">
                        <i class="ri-team-line text-purple-600 ml-2"></i>
                        أبنائك المسجلون
                    </h2>
                    <span class="text-sm text-gray-500">{{ $children->count() }} {{ $children->count() > 1 ? 'أبناء' : 'ابن' }}</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($children as $child)
                        <x-parent.child-overview-card :child="$child" />
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Upcoming Sessions Section -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900">
                    <i class="ri-calendar-event-line text-blue-600 ml-2"></i>
                    الجلسات القادمة
                </h2>
                <a href="{{ route('parent.calendar.index', ['subdomain' => $subdomain]) }}"
                   class="text-blue-600 hover:text-blue-700 text-sm font-medium transition-colors">
                    عرض التقويم الكامل
                    <i class="ri-arrow-left-s-line mr-1"></i>
                </a>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                @if(!empty($upcomingSessions) && count($upcomingSessions) > 0)
                    <div class="divide-y divide-gray-100">
                        @foreach(collect($upcomingSessions)->take(5) as $session)
                            @php
                                // Determine status badge
                                $statusText = match($session['status'] ?? 'scheduled') {
                                    'ready' => 'جاهزة للبدء',
                                    'live' => 'جارية الآن',
                                    'scheduled' => 'مجدولة',
                                    'pending' => 'قيد الانتظار',
                                    default => 'مجدولة'
                                };

                                $statusColor = match($session['status'] ?? 'scheduled') {
                                    'ready' => 'bg-green-100 text-green-700',
                                    'live' => 'bg-red-100 text-red-700',
                                    'scheduled' => 'bg-blue-100 text-blue-700',
                                    'pending' => 'bg-yellow-100 text-yellow-700',
                                    default => 'bg-gray-100 text-gray-700'
                                };
                            @endphp
                            <div class="flex items-center gap-4 p-4">
                                <div class="flex-shrink-0 w-12 h-12 rounded-xl {{ $session['type'] === 'quran' ? 'bg-green-100' : 'bg-violet-100' }} flex items-center justify-center">
                                    <i class="{{ $session['type'] === 'quran' ? 'ri-book-read-line text-green-600' : 'ri-book-line text-violet-600' }} text-xl"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <p class="font-medium text-gray-900">{{ $session['title'] }}</p>
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">{{ $statusText }}</span>
                                    </div>
                                    <p class="text-sm text-gray-500">
                                        <i class="ri-user-line ml-1"></i>{{ $session['teacher_name'] }}
                                        •
                                        <i class="ri-team-line ml-1"></i>{{ $session['child_name'] }}
                                    </p>
                                </div>
                                <div class="text-left flex-shrink-0">
                                    <p class="text-sm font-medium text-gray-600">{{ $session['scheduled_at']->format('d/m/Y') }}</p>
                                    <p class="text-xs text-gray-500">
                                        <i class="ri-time-line ml-1"></i>{{ $session['scheduled_at']->format('h:i A') }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="ri-calendar-line text-gray-400 text-2xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-1">لا توجد جلسات قادمة</h3>
                        <p class="text-sm text-gray-500">عندما يتم جدولة جلسات للأبناء ستظهر هنا</p>
                    </div>
                @endif
            </div>
        </div>

    </div>
</x-layouts.parent-layout>
