@extends('components.layouts.teacher')

@section('title', 'الحلقات الفردية - ' . config('app.name', 'منصة إتقان'))

@section('content')
<div class="min-h-screen bg-gray-50 py-4 md:py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-6 md:mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">الحلقات الفردية</h1>
                    <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">إدارة ومتابعة جلسات القرآن الفردية مع الطلاب</p>
                </div>
                <div class="flex items-center">
                    <!-- Filter by Status -->
                    <select id="statusFilter" class="min-h-[44px] w-full sm:w-auto px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="">جميع الحلقات</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>نشطة</option>
                        <option value="paused" {{ request('status') === 'paused' ? 'selected' : '' }}>متوقفة</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>مكتملة</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-6 mb-6 md:mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 md:w-12 md:h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0 hidden sm:flex">
                        <i class="ri-group-line text-blue-600 text-lg md:text-xl"></i>
                    </div>
                    <div>
                        <div class="text-xl md:text-2xl font-bold text-gray-900">{{ $circles->total() }}</div>
                        <div class="text-xs md:text-sm text-gray-600">إجمالي الحلقات</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 md:w-12 md:h-12 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0 hidden sm:flex">
                        <i class="ri-play-circle-line text-green-600 text-lg md:text-xl"></i>
                    </div>
                    <div>
                        <div class="text-xl md:text-2xl font-bold text-gray-900">{{ $circles->where('status', 'active')->count() }}</div>
                        <div class="text-xs md:text-sm text-gray-600">حلقات نشطة</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 md:w-12 md:h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0 hidden sm:flex">
                        <i class="ri-pause-circle-line text-yellow-600 text-lg md:text-xl"></i>
                    </div>
                    <div>
                        <div class="text-xl md:text-2xl font-bold text-gray-900">{{ $circles->where('status', 'paused')->count() }}</div>
                        <div class="text-xs md:text-sm text-gray-600">حلقات متوقفة</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 md:w-12 md:h-12 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0 hidden sm:flex">
                        <i class="ri-check-circle-line text-purple-600 text-lg md:text-xl"></i>
                    </div>
                    <div>
                        <div class="text-xl md:text-2xl font-bold text-gray-900">{{ $circles->where('status', 'completed')->count() }}</div>
                        <div class="text-xs md:text-sm text-gray-600">حلقات مكتملة</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Circles List -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-4 md:px-6 py-3 md:py-4 border-b border-gray-200">
                <h2 class="text-base md:text-lg font-semibold text-gray-900">قائمة الحلقات الفردية</h2>
            </div>

            @if($circles->count() > 0)
                <div class="divide-y divide-gray-200">
                    @foreach($circles as $circle)
                        <div class="px-4 md:px-6 py-4 hover:bg-gray-50 transition-colors">
                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                                <div class="flex items-start gap-3 md:gap-4">
                                    <!-- Student Avatar -->
                                    <x-avatar :user="$circle->student" size="sm" />

                                    <!-- Circle Info -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-wrap items-center gap-2 mb-1">
                                            <h3 class="text-base md:text-lg font-semibold text-gray-900 truncate">
                                                {{ $circle->student->name ?? 'طالب غير محدد' }}
                                            </h3>

                                            <!-- Status Badge -->
                                            @php
                                                $statusConfig = match($circle->status) {
                                                    'active' => ['class' => 'bg-green-100 text-green-800', 'text' => 'نشطة'],
                                                    'paused' => ['class' => 'bg-yellow-100 text-yellow-800', 'text' => 'متوقفة'],
                                                    'completed' => ['class' => 'bg-purple-100 text-purple-800', 'text' => 'مكتملة'],
                                                    default => ['class' => 'bg-gray-100 text-gray-800', 'text' => $circle->status]
                                                };
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusConfig['class'] }}">
                                                {{ $statusConfig['text'] }}
                                            </span>
                                        </div>

                                        <div class="flex flex-wrap items-center gap-2 md:gap-4 text-xs md:text-sm text-gray-600">
                                            @if($circle->subscription && $circle->subscription->package)
                                                <span class="flex items-center">
                                                    <i class="ri-bookmark-line ml-1"></i>
                                                    {{ $circle->subscription->package->name }}
                                                </span>
                                            @endif

                                            <span class="flex items-center">
                                                <i class="ri-calendar-line ml-1"></i>
                                                {{ $circle->created_at->format('Y/m/d') }}
                                            </span>

                                            @if($circle->sessions_count)
                                                <span class="flex items-center">
                                                    <i class="ri-play-list-line ml-1"></i>
                                                    {{ $circle->sessions_count }} جلسة
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('individual-circles.show', ['subdomain' => request()->route('subdomain'), 'circle' => $circle->id]) }}"
                                       class="min-h-[44px] inline-flex items-center justify-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors flex-1 sm:flex-none">
                                        <i class="ri-eye-line ml-1"></i>
                                        <span class="hidden sm:inline">عرض التفاصيل</span>
                                        <span class="sm:hidden">عرض</span>
                                    </a>

                                    @if($circle->status === 'active')
                                        <a href="{{ route('teacher.individual-circles.progress', ['subdomain' => request()->route('subdomain'), 'circle' => $circle->id]) }}"
                                           class="min-h-[44px] inline-flex items-center justify-center px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors flex-1 sm:flex-none">
                                            <i class="ri-bar-chart-line ml-1"></i>
                                            التقرير
                                        </a>

                                        @if($circle->subscription && $circle->subscription->student)
                                            @php
                                                $studentUser = ($circle->student instanceof \App\Models\User) ? $circle->student : ($circle->student->user ?? null);
                                                $conv = $studentUser ? auth()->user()->getOrCreatePrivateConversation($studentUser) : null;
                                            @endphp
                                            @if($conv)
                                            <a href="{{ route('chat', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy', 'conversation' => $conv->id]) }}"
                                               class="min-h-[44px] inline-flex items-center justify-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm flex-1 sm:flex-none"
                                               title="راسل الطالب">
                                                <i class="ri-message-line ml-1"></i>
                                                <span class="hidden sm:inline">راسل الطالب</span>
                                                <span class="sm:hidden">راسل</span>
                                            </a>
                                            @endif
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                @if($circles->hasPages())
                    <div class="px-4 md:px-6 py-4 border-t border-gray-200">
                        {{ $circles->links() }}
                    </div>
                @endif
            @else
                <div class="px-4 md:px-6 py-8 md:py-12 text-center">
                    <div class="w-14 h-14 md:w-16 md:h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                        <i class="ri-group-line text-xl md:text-2xl text-gray-400"></i>
                    </div>
                    <h3 class="text-base md:text-lg font-medium text-gray-900 mb-1 md:mb-2">لا توجد حلقات فردية</h3>
                    <p class="text-sm md:text-base text-gray-600">
                        @if(request('status'))
                            لا توجد حلقات بالحالة المحددة
                        @else
                            لم يتم تعيين أي حلقات فردية لك بعد
                        @endif
                    </p>
                    @if(request('status'))
                        <a href="{{ route('teacher.individual-circles.index', ['subdomain' => request()->route('subdomain')]) }}"
                           class="min-h-[44px] inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors mt-4">
                            عرض جميع الحلقات
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Status filter functionality
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            const currentUrl = new URL(window.location.href);
            if (this.value) {
                currentUrl.searchParams.set('status', this.value);
            } else {
                currentUrl.searchParams.delete('status');
            }
            window.location.href = currentUrl.toString();
        });
    }
});
</script>
@endsection
