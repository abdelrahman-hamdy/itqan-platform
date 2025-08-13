<x-layouts.student 
    :title="'حلقة فردية - ' . config('app.name', 'منصة إتقان')"
    :description="'تفاصيل الحلقة الفردية للقرآن الكريم'">

<div class="max-w-5xl mx-auto">
    <!-- Breadcrumb -->
    <nav class="mb-8">
        <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
            <li><a href="{{ route('student.quran', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">ملفي الشخصي</a></li>
            <li>/</li>
            <li class="text-gray-900">حلقة فردية</li>
        </ol>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2">
            <!-- Circle Header -->
            <x-individual-circle.circle-header :circle="$individualCircle" view-type="student" />

            <!-- All Sessions -->
            @php
                $allSessions = $individualCircle->sessions()->orderBy('scheduled_at', 'asc')->get();
            @endphp
            <x-circle.progress-sessions-list 
                :sessions="$allSessions" 
                title="جلسات الحلقة الفردية"
                subtitle="جميع الجلسات"
                view-type="student"
                :limit="null"
                :show-all-button="false"
                empty-message="لا توجد جلسات مجدولة بعد" />
        </div>

        <!-- Sidebar -->
        <x-individual-circle.sidebar :circle="$individualCircle" view-type="student" />
    </div>
</div>

</x-layouts.student>