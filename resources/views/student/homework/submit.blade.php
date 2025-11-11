<x-layouts.student-layout title="تسليم الواجب">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="{{ route('student.homework.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 transition-colors">
                <i class="ri-arrow-right-line ml-1"></i>
                العودة إلى قائمة الواجبات
            </a>
        </div>

        @if(isset($homework))
            <!-- Use the submission form component -->
            <x-homework.submission-form
                :homework="$homework"
                :submission="$submission ?? null"
                homeworkType="{{ $homeworkType ?? 'academic' }}"
                action="{{ route('student.homework.submit.process', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'id' => $homework->id, 'type' => $homeworkType ?? 'academic']) }}"
                method="POST"
            />
        @else
            <!-- Error State -->
            <div class="bg-red-50 border border-red-200 rounded-xl p-8 text-center">
                <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="ri-error-warning-line text-red-600 text-4xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-red-900 mb-2">خطأ في تحميل الواجب</h3>
                <p class="text-red-700 mb-4">
                    عذراً، لم نتمكن من تحميل معلومات الواجب المطلوب.
                </p>
                <a href="{{ route('student.homework.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                    <i class="ri-arrow-right-line ml-2"></i>
                    العودة إلى قائمة الواجبات
                </a>
            </div>
        @endif
    </div>

    @if(session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                alert('{{ session('success') }}');
            });
        </script>
    @endif

    @if(session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                alert('{{ session('error') }}');
            });
        </script>
    @endif
</x-layouts.student-layout>
