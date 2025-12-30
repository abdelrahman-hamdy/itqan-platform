<x-layouts.student title="{{ __('student.homework_submission.submit_title') }}">
    <div class="space-y-6">
        <!-- Back Button -->
        <div>
            <a href="{{ route('student.homework.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 transition-colors">
                <i class="ri-arrow-right-line ms-1"></i>
                {{ __('student.homework_submission.back_to_homework') }}
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
                <h3 class="text-xl font-semibold text-red-900 mb-2">{{ __('student.homework_submission.error_loading_title') }}</h3>
                <p class="text-red-700 mb-4">
                    {{ __('student.homework_submission.error_loading_message') }}
                </p>
                <a href="{{ route('student.homework.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                    <i class="ri-arrow-right-line ms-2"></i>
                    {{ __('student.homework_submission.back_to_homework') }}
                </a>
            </div>
        @endif
    </div>

    @if(session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                window.toast?.success('{{ session('success') }}');
            });
        </script>
    @endif

    @if(session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                window.toast?.error('{{ session('error') }}');
            });
        </script>
    @endif
</x-layouts.student>
