@props(['child'])

@php
    $subdomain = auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-all duration-200 card-hover">
    <!-- Child Header with Gradient -->
    <div class="bg-gradient-to-l from-purple-500 to-purple-600 p-4">
        <div class="flex items-center gap-4">
            <x-avatar
                :user="$child->user"
                size="md"
                userType="student"
                :gender="$child->gender ?? 'male'"
                class="border-2 border-white/30" />
            <div class="flex-1 text-white">
                <h3 class="font-bold text-lg">{{ $child->user->name ?? $child->first_name }}</h3>
                <p class="text-purple-100 text-sm">{{ $child->student_code ?? 'طالب' }}</p>
            </div>
            <a href="{{ route('parent.profile', ['subdomain' => $subdomain, 'child_id' => $child->id]) }}"
               class="bg-white/20 hover:bg-white/30 text-white px-3 py-1.5 rounded-lg text-sm font-medium transition-colors">
                <i class="ri-eye-line ml-1"></i>
                عرض
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="p-4">
        <div class="grid grid-cols-3 gap-4 text-center">
            <div class="p-3 bg-green-50 rounded-lg">
                <p class="text-2xl font-bold text-green-600">{{ $child->stats['active_subscriptions'] ?? 0 }}</p>
                <p class="text-xs text-gray-600 mt-1">اشتراك نشط</p>
            </div>
            <div class="p-3 bg-blue-50 rounded-lg">
                <p class="text-2xl font-bold text-blue-600">{{ $child->stats['upcoming_sessions'] ?? 0 }}</p>
                <p class="text-xs text-gray-600 mt-1">جلسة قادمة</p>
            </div>
            <div class="p-3 bg-amber-50 rounded-lg">
                <p class="text-2xl font-bold text-amber-600">{{ $child->stats['certificates'] ?? 0 }}</p>
                <p class="text-xs text-gray-600 mt-1">شهادة</p>
            </div>
        </div>
    </div>
</div>
