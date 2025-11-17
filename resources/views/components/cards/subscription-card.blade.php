@props([
    'subscription',
    'viewType' => 'student', // 'student' or 'teacher'
    'showProgress' => true,
    'showActions' => true,
    'compact' => false
])

@php
    $user = $viewType === 'teacher' ? $subscription->student : $subscription->quranTeacher;
    $userDisplayName = $viewType === 'teacher' ? 
        ($subscription->student->name ?? 'طالب') : 
        ($subscription->quranTeacher?->full_name ?? 'معلم غير محدد');
    
    $routeName = 'individual-circles.show'; // Unified route for both teachers and students
    $routeParam = 'circle'; // Unified parameter name
    
    $statusColors = [
        'active' => 'bg-green-100 text-green-800',
        'pending' => 'bg-yellow-100 text-yellow-800',
        'expired' => 'bg-red-100 text-red-800',
        'cancelled' => 'bg-gray-100 text-gray-800',
        'completed' => 'bg-blue-100 text-blue-800'
    ];
    
    $statusLabels = [
        'active' => 'نشط',
        'pending' => 'في الانتظار',
        'expired' => 'منتهي الصلاحية',
        'cancelled' => 'ملغي',
        'completed' => 'مكتمل'
    ];
    
    $canAccess = $subscription->individualCircle && $subscription->individualCircle->id;
    $href = $canAccess ? 
        route($routeName, [
            'subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 
            $routeParam => $subscription->individualCircle->id
        ]) : '#';
@endphp

<div class="subscription-card {{ $compact ? 'p-4' : 'p-6' }} bg-white rounded-lg border border-gray-200 hover:border-primary-300 transition-all duration-200 {{ $canAccess ? 'hover:shadow-md cursor-pointer' : 'opacity-60 cursor-not-allowed' }}">
    @if($canAccess)
        <a href="{{ $href }}" class="block h-full">
    @endif
    
    <div class="flex items-start justify-between {{ $compact ? 'mb-3' : 'mb-4' }}">
        <div class="flex items-center space-x-3 space-x-reverse flex-1">
            <!-- User Avatar -->
            @if($user && $user->avatar)
                <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $userDisplayName }}" 
                     class="{{ $compact ? 'w-10 h-10' : 'w-12 h-12' }} rounded-full object-cover">
            @else
                <div class="{{ $compact ? 'w-10 h-10' : 'w-12 h-12' }} rounded-full bg-primary-100 flex items-center justify-center">
                    <span class="{{ $compact ? 'text-sm' : 'text-lg' }} font-bold text-primary-600">
                        {{ substr($userDisplayName, 0, 1) }}
                    </span>
                </div>
            @endif
            
            <!-- User Info -->
            <div class="flex-1 min-w-0">
                <h4 class="{{ $compact ? 'text-sm' : 'text-base' }} font-medium text-gray-900 truncate">
                    {{ $userDisplayName }}
                </h4>
                <p class="{{ $compact ? 'text-xs' : 'text-sm' }} text-gray-600 truncate">
                    {{ $subscription->package->name ?? 'اشتراك مخصص' }}
                </p>
                @if(!$compact)
                    <p class="text-xs text-gray-500 mt-1">
                        {{ $subscription->created_at->diffForHumans() }}
                    </p>
                @endif
            </div>
        </div>
        
        <!-- Status Badge -->
        <div class="flex flex-col items-end space-y-2">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$subscription->subscription_status] ?? 'bg-gray-100 text-gray-800' }}">
                {{ $statusLabels[$subscription->subscription_status] ?? $subscription->subscription_status }}
            </span>
            
            @if($canAccess)
                <i class="ri-arrow-left-s-line text-gray-400 {{ $compact ? 'text-sm' : '' }}"></i>
            @endif
        </div>
    </div>

    <!-- Progress Section -->
    @if($showProgress && $subscription->individualCircle)
        <div class="{{ $compact ? 'space-y-2' : 'space-y-3' }}">
            <!-- Session Stats -->
            <div class="flex items-center justify-between {{ $compact ? 'text-xs' : 'text-sm' }} text-gray-600">
                <span>{{ $subscription->sessions_completed ?? 0 }}/{{ $subscription->total_sessions ?? 0 }} جلسة</span>
                @if($subscription->progress_percentage)
                    <span class="font-medium">{{ number_format($subscription->progress_percentage, 1) }}%</span>
                @endif
            </div>
            
            <!-- Progress Bar -->
            @if($subscription->progress_percentage > 0)
                <div class="w-full bg-gray-200 rounded-full {{ $compact ? 'h-1.5' : 'h-2' }}">
                    <div class="bg-primary-600 {{ $compact ? 'h-1.5' : 'h-2' }} rounded-full transition-all duration-300" 
                         style="width: {{ min(100, max(0, $subscription->progress_percentage)) }}%"></div>
                </div>
            @endif
            
            <!-- Additional Info -->
            @if(!$compact && $subscription->individualCircle)
                <div class="flex items-center justify-between text-xs text-gray-500">
                    @if($subscription->individualCircle->last_session_at)
                        <span>
                            <i class="ri-time-line ml-1"></i>
                            آخر جلسة {{ $subscription->individualCircle->last_session_at->diffForHumans() }}
                        </span>
                    @endif
                    
                    @if($subscription->sessions_remaining > 0)
                        <span class="text-blue-600 font-medium">
                            {{ $subscription->sessions_remaining }} جلسة متبقية
                        </span>
                    @endif
                </div>
            @endif
        </div>
    @endif

    <!-- Warning Messages -->
    @if(!$canAccess)
        <div class="mt-3 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs text-yellow-800">
            <i class="ri-alert-line ml-1"></i>
            لم يتم إنشاء الحلقة الفردية بعد
        </div>
    @endif

    <!-- Quick Actions (for non-compact view) -->
    @if($showActions && !$compact && $canAccess)
        <div class="mt-4 pt-3 border-t border-gray-100">
            <div class="flex items-center space-x-2 space-x-reverse">
                @if($viewType === 'teacher')
                    <!-- Teacher Actions -->
                    <button type="button" 
                            onclick="event.preventDefault(); event.stopPropagation(); openProgress({{ $subscription->individualCircle->id ?? 0 }})"
                            class="inline-flex items-center px-2 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded hover:bg-gray-200 transition-colors">
                        <i class="ri-line-chart-line ml-1"></i>
                        التقدم
                    </button>
                @else
                    <!-- Student Actions -->
                    @php
                        $nextSession = $subscription->sessions()
                            ->where('scheduled_at', '>', now())
                            ->where('status', 'scheduled')
                            ->orderBy('scheduled_at')
                            ->first();
                    @endphp
                    
                    @if($nextSession && $nextSession->scheduled_at->diffInMinutes(now()) <= 30)
                        <a href="{{ route('student.sessions.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'sessionId' => $nextSession->id]) }}"
                           onclick="event.stopPropagation()"
                           class="inline-flex items-center px-2 py-1 bg-green-600 text-white text-xs font-medium rounded hover:bg-green-700 transition-colors">
                            <i class="ri-video-line ml-1"></i>
                            انضمام للجلسة
                        </a>
                    @endif
                @endif
            </div>
        </div>
    @endif

    @if($canAccess)
        </a>
    @endif
</div>

@if($viewType === 'teacher')
<script>
    function openProgress(circleId) {
        if (circleId > 0) {
            window.open(`/teacher/individual-circles/${circleId}/progress`, '_blank');
        }
    }
</script>
@endif
