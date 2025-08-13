<x-layouts.student 
    :title="'جلسات القرآن الكريم - ' . (auth()->user()->academy->name ?? 'أكاديمية إتقان')"
    :description="'عرض جميع جلسات القرآن الكريم للاشتراك - ' . (auth()->user()->academy->name ?? 'أكاديمية إتقان')">
<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">
        <!-- Page Header -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">جلسات القرآن الكريم</h1>
                    <p class="text-gray-600">
                        جلسات اشتراك: {{ $subscription->quranTeacher?->full_name ?? 'معلم غير محدد' }}
                    </p>
                </div>
                <a href="{{ route('student.subscriptions', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
                   class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors">
                    العودة للاشتراكات
                </a>
            </div>
        </div>

        <!-- Subscription Overview -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="text-center">
                    <div class="text-2xl font-bold text-primary">{{ $subscription->sessions_remaining ?? 0 }}</div>
                    <div class="text-sm text-gray-600">الجلسات المتبقية</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">{{ $subscription->sessions_used ?? 0 }}</div>
                    <div class="text-sm text-gray-600">الجلسات المكتملة</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-orange-600">{{ $subscription->progress_percentage ?? 0 }}%</div>
                    <div class="text-sm text-gray-600">نسبة التقدم</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600">{{ $subscription->verses_memorized ?? 0 }}</div>
                    <div class="text-sm text-gray-600">الآيات المحفوظة</div>
                </div>
            </div>
        </div>

        <!-- Current Progress -->
        @if($subscription->current_surah || $subscription->current_verse)
        <div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-xl shadow-sm p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-900 mb-4">التقدم الحالي</h2>
            <div class="flex items-center gap-4">
                @if($subscription->current_surah)
                <div class="bg-white rounded-lg px-4 py-2">
                    <span class="text-sm text-gray-600">السورة الحالية:</span>
                    <span class="font-bold text-gray-900">{{ $subscription->current_surah }}</span>
                </div>
                @endif
                @if($subscription->current_verse)
                <div class="bg-white rounded-lg px-4 py-2">
                    <span class="text-sm text-gray-600">الآية الحالية:</span>
                    <span class="font-bold text-gray-900">{{ $subscription->current_verse }}</span>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Sessions List -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900">جميع الجلسات</h2>
                @if($subscription->quranTeacher)
                <a href="{{ route('public.quran-teachers.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'teacher' => $subscription->quranTeacher->id]) }}" 
                   class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition-colors">
                    عرض ملف المعلم
                </a>
                @endif
            </div>

            @if($sessions->count() > 0)
            <div class="space-y-4">
                @foreach($sessions as $session)
                <div class="border border-gray-200 rounded-lg p-6 hover:border-primary transition-colors">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">
                                جلسة {{ $session->session_type === 'trial' ? 'تجريبية' : 'تعليمية' }}
                            </h4>
                            <p class="text-sm text-gray-600">
                                {{ $session->scheduled_at ? $session->scheduled_at->format('d/m/Y - H:i') : 'غير محدد' }}
                            </p>
                        </div>
                        <span class="px-3 py-1 text-xs font-medium rounded-full
                          {{ $session->status === 'completed' ? 'bg-green-100 text-green-800' : 
                             ($session->status === 'scheduled' ? 'bg-blue-100 text-blue-800' : 
                             ($session->status === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')) }}">
                            {{ ucfirst($session->status) }}
                        </span>
                    </div>

                    @if($session->duration_minutes)
                    <div class="flex items-center gap-4 text-sm text-gray-600 mb-4">
                        <span>المدة: {{ $session->duration_minutes }} دقيقة</span>
                        @if($session->actual_duration_minutes && $session->actual_duration_minutes !== $session->duration_minutes)
                        <span>المدة الفعلية: {{ $session->actual_duration_minutes }} دقيقة</span>
                        @endif
                    </div>
                    @endif

                    @if($session->status === 'completed')
                    <div class="bg-gray-50 rounded-lg p-4 mb-4">
                        <h5 class="font-medium text-gray-900 mb-2">ملخص الجلسة</h5>
                        @if($session->lesson_summary)
                        <p class="text-sm text-gray-700 mb-2">{{ $session->lesson_summary }}</p>
                        @endif
                        @if($session->verses_covered_start && $session->verses_covered_end)
                        <p class="text-sm text-gray-600">
                            الآيات المغطاة: من {{ $session->verses_covered_start }} إلى {{ $session->verses_covered_end }}
                        </p>
                        @endif
                        @if($session->homework_assigned)
                        <div class="mt-2">
                            <span class="text-sm font-medium text-gray-900">الواجب المنزلي:</span>
                            <p class="text-sm text-gray-700">{{ is_array($session->homework_assigned) ? implode(', ', $session->homework_assigned) : $session->homework_assigned }}</p>
                        </div>
                        @endif
                    </div>
                    @endif

                    @if($session->teacher_notes)
                    <div class="bg-blue-50 rounded-lg p-4 mb-4">
                        <h5 class="font-medium text-gray-900 mb-2">ملاحظات المعلم</h5>
                        <p class="text-sm text-gray-700">{{ $session->teacher_notes }}</p>
                    </div>
                    @endif

                    @if($session->student_notes)
                    <div class="bg-green-50 rounded-lg p-4 mb-4">
                        <h5 class="font-medium text-gray-900 mb-2">ملاحظاتك</h5>
                        <p class="text-sm text-gray-700">{{ $session->student_notes }}</p>
                    </div>
                    @endif

                    <!-- Session Actions -->
                    <div class="flex gap-2 mt-4">
                        @if($session->status === App\Enums\SessionStatus::SCHEDULED && $session->meeting_link)
                        <a href="{{ $session->meeting_link }}" target="_blank"
                           class="bg-green-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-600 transition-colors">
                            دخول الجلسة
                        </a>
                        @endif
                        
                        @if($session->status === App\Enums\SessionStatus::COMPLETED && $session->recording_enabled && $session->recording_url)
                        <a href="{{ $session->recording_url }}" target="_blank"
                           class="bg-purple-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-purple-600 transition-colors">
                            مشاهدة التسجيل
                        </a>
                        @endif
                        
                        @if($session->status === App\Enums\SessionStatus::SCHEDULED)
                        <button type="button" 
                                class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
                            إعادة جدولة
                        </button>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Pagination -->
            @if($sessions->hasPages())
            <div class="mt-8">
                {{ $sessions->links() }}
            </div>
            @endif
            
            @else
            <!-- Empty State -->
            <div class="text-center py-12">
                <div class="w-24 h-24 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">لا توجد جلسات حالياً</h3>
                <p class="text-gray-600 mb-6">سيتم عرض جلساتك هنا بمجرد أن يبدأ المعلم في جدولتها</p>
            </div>
            @endif
        </div>
    </div>
</div>

</x-layouts.student>