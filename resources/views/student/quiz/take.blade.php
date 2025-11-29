<x-layouts.student-layout title="{{ $quiz->title }}">
    <div class="container mx-auto px-4 py-8 max-w-4xl" x-data="quizPage({{ $remainingTime ?? 'null' }})">
        <!-- Quiz Header -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $quiz->title }}</h1>
                    @if($quiz->description)
                        <p class="mt-2 text-gray-600">{{ $quiz->description }}</p>
                    @endif
                </div>

                @if($quiz->duration_minutes)
                    <div class="text-left bg-gray-100 rounded-lg p-4" :class="{ 'bg-red-100': remainingTime < 60 }">
                        <p class="text-sm text-gray-500 mb-1">الوقت المتبقي</p>
                        <p class="text-2xl font-bold" :class="{ 'text-red-600': remainingTime < 60, 'text-gray-900': remainingTime >= 60 }" x-text="formatTime(remainingTime)"></p>
                    </div>
                @endif
            </div>

            <div class="mt-4 flex flex-wrap gap-4 text-sm text-gray-500">
                <span class="inline-flex items-center">
                    <i class="ri-question-line ml-1"></i>
                    {{ $questions->count() }} سؤال
                </span>
                <span class="inline-flex items-center">
                    <i class="ri-percent-line ml-1"></i>
                    درجة النجاح: {{ $quiz->passing_score }}%
                </span>
            </div>
        </div>

        @php
            $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
        @endphp
        <!-- Quiz Form -->
        <form action="{{ route('student.quiz.submit', ['subdomain' => $subdomain, 'attempt_id' => $attempt->id]) }}" method="POST" id="quizForm">
            @csrf

            <div class="space-y-6">
                @foreach($questions as $index => $question)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-start gap-4">
                            <span class="flex-shrink-0 w-8 h-8 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center font-bold">
                                {{ $index + 1 }}
                            </span>
                            <div class="flex-1">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">
                                    {{ $question->question_text }}
                                </h3>

                                <div class="space-y-3">
                                    @foreach($question->options as $optionIndex => $option)
                                        <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors peer-checked:border-blue-500 peer-checked:bg-blue-50">
                                            <input type="radio"
                                                   name="answers[{{ $question->id }}]"
                                                   value="{{ $optionIndex }}"
                                                   class="peer hidden">
                                            <span class="flex-shrink-0 w-6 h-6 border-2 border-gray-300 rounded-full ml-3 flex items-center justify-center peer-checked:border-blue-500 peer-checked:bg-blue-500">
                                                <span class="w-2 h-2 bg-white rounded-full hidden peer-checked:block"></span>
                                            </span>
                                            <span class="text-gray-700">{{ $option }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Submit Section -->
            <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <p class="text-gray-600">
                        تأكد من إجابتك على جميع الأسئلة قبل التقديم
                    </p>
                    <button type="button"
                            @click="showConfirmModal = true"
                            class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                        <i class="ri-check-line ml-2"></i>
                        تقديم الاختبار
                    </button>
                </div>
            </div>
        </form>

        <!-- Confirmation Modal -->
        <div x-show="showConfirmModal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 overflow-y-auto"
             style="display: none;">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showConfirmModal = false"></div>

            <!-- Modal -->
            <div class="flex min-h-full items-center justify-center p-4">
                <div x-show="showConfirmModal"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6"
                     @click.stop>
                    <!-- Icon -->
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 mb-4">
                        <i class="ri-question-line text-3xl text-blue-600"></i>
                    </div>

                    <!-- Content -->
                    <div class="text-center">
                        <h3 class="text-xl font-bold text-gray-900 mb-2">تأكيد تقديم الاختبار</h3>
                        <p class="text-gray-600 mb-6">
                            هل أنت متأكد من تقديم الاختبار؟
                            <br>
                            <span class="text-sm text-gray-500">لن تتمكن من تعديل إجاباتك بعد التقديم</span>
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3">
                        <button type="button"
                                @click="showConfirmModal = false"
                                class="flex-1 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition-colors">
                            إلغاء
                        </button>
                        <button type="button"
                                @click="submitQuiz()"
                                class="flex-1 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-colors inline-flex items-center justify-center gap-2">
                            <i class="ri-check-line"></i>
                            تأكيد التقديم
                        </button>
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
</x-layouts.student-layout>
