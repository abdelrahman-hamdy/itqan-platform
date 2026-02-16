<x-layouts.student title="{{ $quiz->title }}">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-8" x-data="quizPage({{ $remainingTime ?? 'null' }})">
        <!-- Quiz Header -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-4 md:mb-6">
            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900">{{ $quiz->title }}</h1>
                    @if($quiz->description)
                        <p class="mt-2 text-sm md:text-base text-gray-600">{{ $quiz->description }}</p>
                    @endif
                </div>

                @if($quiz->duration_minutes)
                    <div class="text-center md:text-start bg-gray-100 rounded-xl p-3 md:p-4 flex-shrink-0" :class="{ 'bg-red-100': remainingTime < 60 }">
                        <p class="text-xs md:text-sm text-gray-500 mb-1">{{ __('student.quiz.time_remaining') }}</p>
                        <p class="text-xl md:text-2xl font-bold" :class="{ 'text-red-600': remainingTime < 60, 'text-gray-900': remainingTime >= 60 }" x-text="formatTime(remainingTime)"></p>
                    </div>
                @endif
            </div>

            <div class="mt-3 md:mt-4 flex flex-wrap gap-3 md:gap-4 text-xs md:text-sm text-gray-500">
                <span class="inline-flex items-center">
                    <i class="ri-question-line ms-1"></i>
                    {{ $questions->count() }} {{ __('student.quiz.question') }}
                </span>
                <span class="inline-flex items-center">
                    <i class="ri-percent-line ms-1"></i>
                    {{ __('student.quiz.passing_score_label') }} {{ $quiz->passing_score }}%
                </span>
            </div>
        </div>

        @php
            $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
        @endphp
        <!-- Quiz Form -->
        <form action="{{ route('student.quiz.submit', ['subdomain' => $subdomain, 'attempt_id' => $attempt->id]) }}" method="POST" id="quizForm">
            @csrf

            <div class="space-y-4 md:space-y-6">
                @foreach($questions as $index => $question)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                        <div class="flex items-start gap-3 md:gap-4">
                            <span class="flex-shrink-0 w-7 h-7 md:w-8 md:h-8 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center font-bold text-sm md:text-base">
                                {{ $index + 1 }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-base md:text-lg font-medium text-gray-900 mb-3 md:mb-4">
                                    {{ $question->question_text }}
                                </h3>

                                <div class="space-y-2 md:space-y-3">
                                    @foreach($question->options as $optionIndex => $option)
                                        <label class="flex items-center gap-3 min-h-[48px] p-3 md:p-4 border rounded-xl cursor-pointer hover:bg-gray-50 transition-colors peer-checked:border-blue-500 peer-checked:bg-blue-50">
                                            <input type="radio"
                                                   name="answers[{{ $question->id }}]"
                                                   value="{{ $optionIndex }}"
                                                   class="peer hidden">
                                            <span class="flex-shrink-0 w-5 h-5 md:w-6 md:h-6 border-2 border-gray-300 rounded-full flex items-center justify-center peer-checked:border-blue-500 peer-checked:bg-blue-500">
                                                <span class="w-2 h-2 bg-white rounded-full hidden peer-checked:block"></span>
                                            </span>
                                            <span class="text-sm md:text-base text-gray-700">{{ $option }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Submit Section -->
            <div class="mt-6 md:mt-8 bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <p class="text-sm md:text-base text-gray-600">
                        {{ __('student.quiz.answer_all_warning') }}
                    </p>
                    <button type="button"
                            @click="showConfirmModal = true"
                            class="inline-flex items-center justify-center min-h-[48px] px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-colors w-full sm:w-auto">
                        <i class="ri-check-line ms-2"></i>
                        {{ __('student.quiz.submit_quiz') }}
                    </button>
                </div>
            </div>
        </form>

        <!-- Confirmation Modal - Bottom sheet on mobile, centered on desktop -->
        <div x-show="showConfirmModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 overflow-y-auto"
             style="display: none;">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showConfirmModal = false"></div>

            <!-- Modal Container - Bottom sheet on mobile, centered on desktop -->
            <div class="fixed inset-0 flex items-end md:items-center justify-center p-0 md:p-4">
                <div x-show="showConfirmModal"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-full md:translate-y-0 md:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 md:scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0 md:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-full md:translate-y-0 md:scale-95"
                     class="relative bg-white w-full md:max-w-md rounded-t-2xl md:rounded-2xl shadow-xl overflow-hidden"
                     @click.stop>

                    <!-- Mobile drag handle -->
                    <div class="md:hidden absolute top-2 left-1/2 -translate-x-1/2 w-10 h-1 rounded-full bg-gray-300 z-10"></div>

                    <div class="p-6 pt-8 md:pt-6">
                        <!-- Icon -->
                        <div class="mx-auto flex h-14 w-14 md:h-16 md:w-16 items-center justify-center rounded-full bg-blue-100 mb-4">
                            <i class="ri-question-line text-2xl md:text-3xl text-blue-600"></i>
                        </div>

                        <!-- Content -->
                        <div class="text-center">
                            <h3 class="text-lg md:text-xl font-bold text-gray-900 mb-2">{{ __('student.quiz.confirm_submit_title') }}</h3>
                            <p class="text-sm md:text-base text-gray-600 mb-6">
                                {{ __('student.quiz.confirm_submit_message') }}
                                <br>
                                <span class="text-xs md:text-sm text-gray-500">{{ __('student.quiz.no_edit_after_submit') }}</span>
                            </p>
                        </div>

                        <!-- Actions - Stack on mobile, row on desktop -->
                        <div class="flex flex-col-reverse md:flex-row gap-3">
                            <button type="button"
                                    @click="showConfirmModal = false"
                                    class="flex-1 min-h-[48px] md:min-h-[44px] px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition-colors">
                                {{ __('student.quiz.cancel') }}
                            </button>
                            <button type="button"
                                    @click="submitQuiz()"
                                    class="flex-1 min-h-[48px] md:min-h-[44px] px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-colors inline-flex items-center justify-center gap-2">
                                <i class="ri-check-line"></i>
                                {{ __('student.quiz.confirm_submission') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function quizPage(initialTime) {
            return {
                remainingTime: initialTime,
                interval: null,
                showConfirmModal: false,

                init() {
                    if (this.remainingTime !== null) {
                        this.interval = setInterval(() => {
                            this.remainingTime--;
                            if (this.remainingTime <= 0) {
                                clearInterval(this.interval);
                                document.getElementById('quizForm').submit();
                            }
                        }, 1000);
                    }
                },

                formatTime(seconds) {
                    if (seconds === null) return '--:--';
                    const mins = Math.floor(seconds / 60);
                    const secs = seconds % 60;
                    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
                },

                submitQuiz() {
                    this.showConfirmModal = false;
                    document.getElementById('quizForm').submit();
                }
            }
        }
    </script>
    @endpush

    <style>
        input[type="radio"]:checked + span {
            border-color: rgb(59 130 246); /* blue-500 */
            background-color: rgb(59 130 246); /* blue-500 */
        }
        input[type="radio"]:checked + span > span {
            display: block;
        }
        input[type="radio"]:checked ~ span:last-child {
            color: rgb(29 78 216); /* blue-700 */
            font-weight: 500;
        }
        label:has(input[type="radio"]:checked) {
            border-color: rgb(59 130 246); /* blue-500 */
            background-color: rgb(239 246 255); /* blue-50 */
        }
    </style>
</x-layouts.student>
