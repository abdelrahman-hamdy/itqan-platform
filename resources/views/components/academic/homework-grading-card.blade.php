@props([
    'session',  // Academic session object
    'report',   // Session report object
    'student'   // Student object
])

@php
    $hasHomework = !empty($session->homework_description) || !empty($session->homework_file);
    $hasSubmitted = $report->homework_submitted_at !== null;
    $isGraded = $report->homework_degree !== null;
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold text-gray-900">
            <i class="ri-file-edit-line ms-2 rtl:ms-2 ltr:me-2"></i>
            {{ __('components.academic.homework_grading.title') }} {{ $student->full_name }}
        </h2>
        @if($isGraded)
            <div class="flex items-center px-3 py-1 bg-green-100 border border-green-300 rounded-full">
                <i class="ri-checkbox-circle-line text-green-600 ms-1 rtl:ms-1 ltr:me-1"></i>
                <span class="text-xs font-bold text-green-700">{{ __('components.academic.homework_grading.graded') }}</span>
            </div>
        @endif
    </div>

    @if(!$hasHomework)
        <!-- No Homework Assigned -->
        <div class="text-center py-8">
            <i class="ri-file-list-line text-6xl text-gray-300 mb-3"></i>
            <p class="text-gray-500 text-sm">{{ __('components.academic.homework_grading.no_homework') }}</p>
            <button type="button"
                    onclick="toggleAssignHomework()"
                    class="mt-4 px-4 py-2 bg-primary text-white text-sm font-bold rounded-lg hover:bg-primary-dark transition-colors">
                <i class="ri-add-line ms-1 rtl:ms-1 ltr:me-1"></i>
                {{ __('components.academic.homework_grading.assign_homework') }}
            </button>
        </div>

        <!-- Assign Homework Form (Hidden by default) -->
        <div id="assign-homework-form" class="hidden mt-4">
            <form action="{{ route('teacher.academic-sessions.assign-homework', $session->id) }}"
                  method="POST"
                  enctype="multipart/form-data">
                @csrf

                <div class="mb-4">
                    <label class="block text-sm font-bold text-gray-700 mb-2">{{ __('components.academic.homework_grading.homework_description') }}</label>
                    <textarea name="homework_description"
                              rows="4"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary"
                              placeholder="{{ __('components.academic.homework_grading.description_placeholder') }}"
                              required></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-bold text-gray-700 mb-2">{{ __('components.academic.homework_grading.homework_file_optional') }}</label>
                    <input type="file"
                           name="homework_file"
                           accept=".pdf,.doc,.docx"
                           class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50">
                </div>

                <div class="flex gap-2">
                    <button type="submit"
                            class="flex-1 px-4 py-2 bg-primary text-white font-bold rounded-lg hover:bg-primary-dark transition-colors">
                        {{ __('components.academic.homework_grading.save_homework') }}
                    </button>
                    <button type="button"
                            onclick="toggleAssignHomework()"
                            class="px-4 py-2 bg-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-300 transition-colors">
                        {{ __('components.academic.homework_grading.cancel') }}
                    </button>
                </div>
            </form>
        </div>
    @else
        <!-- Homework Details -->
        <div class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <label class="block text-sm font-bold text-gray-700 mb-2">{{ __('components.academic.homework_grading.homework_description') }}</label>
            <p class="text-gray-700 whitespace-pre-wrap">{{ $session->homework_description }}</p>

            @if($session->homework_file)
                <a href="{{ Storage::url($session->homework_file) }}"
                   target="_blank"
                   class="inline-flex items-center mt-2 text-sm text-blue-600 hover:text-blue-700">
                    <i class="ri-attachment-line ms-1 rtl:ms-1 ltr:me-1"></i>
                    {{ basename($session->homework_file) }}
                </a>
            @endif
        </div>

        @if(!$hasSubmitted)
            <!-- Waiting for Submission -->
            <div class="text-center py-6 bg-yellow-50 rounded-lg border border-yellow-200">
                <i class="ri-time-line text-4xl text-yellow-500 mb-2"></i>
                <p class="text-yellow-700 text-sm font-bold">{{ __('components.academic.homework_grading.waiting_for_submission') }}</p>
            </div>
        @else
            <!-- Student Submission -->
            <div class="border-t border-gray-200 pt-4 mt-4">
                <div class="mb-4">
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-bold text-gray-700">{{ __('components.academic.homework_grading.submission_date') }}</label>
                        <span class="text-sm text-gray-600">{{ $report->homework_submitted_at->format('Y-m-d H:i') }}</span>
                    </div>

                    @if($report->homework_file)
                        <a href="{{ Storage::url($report->homework_file) }}"
                           target="_blank"
                           class="flex items-center p-3 bg-blue-50 rounded-lg border border-blue-200 hover:bg-blue-100 transition-colors">
                            <i class="ri-file-line text-blue-600 text-xl ms-2 rtl:ms-2 ltr:me-2"></i>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-blue-900">{{ __('components.academic.homework_grading.view_student_file') }}</div>
                                <div class="text-xs text-blue-600">{{ basename($report->homework_file) }}</div>
                            </div>
                            <i class="ri-arrow-left-s-line text-blue-600 rtl:ri-arrow-left-s-line ltr:ri-arrow-right-s-line"></i>
                        </a>
                    @endif
                </div>

                @if($isGraded)
                    <!-- Display Current Grade -->
                    <div class="p-4 bg-green-50 rounded-lg border border-green-200 mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-bold text-green-900">{{ __('components.academic.homework_grading.current_grade') }}</label>
                            <div class="flex items-center">
                                <span class="text-2xl font-bold text-green-600">{{ number_format($report->homework_degree, 1) }}</span>
                                <span class="text-sm text-green-600 me-1 rtl:me-1 ltr:ms-1">/10</span>
                            </div>
                        </div>
                        @if($report->notes)
                            <div class="text-sm text-green-800 mt-2">
                                <strong>{{ __('components.academic.homework_grading.notes') }}</strong> {{ $report->notes }}
                            </div>
                        @endif
                        <button type="button"
                                onclick="toggleGradingForm()"
                                class="mt-3 w-full px-3 py-2 bg-white border border-green-300 text-green-700 text-sm font-bold rounded hover:bg-green-50 transition-colors">
                            <i class="ri-edit-line ms-1 rtl:ms-1 ltr:me-1"></i>
                            {{ __('components.academic.homework_grading.edit_grading') }}
                        </button>
                    </div>
                @endif

                <!-- Grading Form -->
                <form action="{{ route('teacher.academic-sessions.grade-homework', [$session->id, $report->id]) }}"
                      method="POST"
                      id="grading-form"
                      class="{{ $isGraded ? 'hidden' : '' }}">
                    @csrf

                    <!-- Homework Grade -->
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            <i class="ri-star-line ms-1 rtl:ms-1 ltr:me-1"></i>
                            {{ __('components.academic.homework_grading.homework_grade') }}
                        </label>
                        <input type="number"
                               name="homework_grade"
                               min="0"
                               max="10"
                               step="0.5"
                               value="{{ $report->homework_degree ?? '' }}"
                               class="w-full px-4 py-2 text-lg font-bold border border-gray-300 rounded-lg focus:outline-none focus:border-primary"
                               required>
                    </div>

                    <!-- Feedback -->
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            <i class="ri-message-2-line ms-1 rtl:ms-1 ltr:me-1"></i>
                            {{ __('components.academic.homework_grading.feedback') }}
                        </label>
                        <textarea name="notes"
                                  rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary"
                                  placeholder="{{ __('components.academic.homework_grading.feedback_placeholder') }}">{{ $report->notes ?? '' }}</textarea>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit"
                            class="w-full flex items-center justify-center px-4 py-3 bg-primary text-white font-bold rounded-lg hover:bg-primary-dark transition-colors">
                        <i class="ri-check-line ms-2 rtl:ms-2 ltr:me-2"></i>
                        {{ $isGraded ? __('components.academic.homework_grading.update_grading') : __('components.academic.homework_grading.save_grading') }}
                    </button>

                    @if($isGraded)
                        <button type="button"
                                onclick="toggleGradingForm()"
                                class="w-full mt-2 px-4 py-2 bg-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-300 transition-colors">
                            {{ __('components.academic.homework_grading.cancel') }}
                        </button>
                    @endif
                </form>
            </div>
        @endif
    @endif
</div>

@push('scripts')
<script>
    function toggleAssignHomework() {
        const form = document.getElementById('assign-homework-form');
        form.classList.toggle('hidden');
    }

    function toggleGradingForm() {
        const form = document.getElementById('grading-form');
        form.classList.toggle('hidden');
    }
</script>
@endpush
