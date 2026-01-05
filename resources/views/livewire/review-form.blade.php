<div>
    {{-- Review Button --}}
    @if($canReview)
        <button
            wire:click="openModal"
            class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg transition-colors duration-200"
        >
            <i class="ri-star-line text-lg"></i>
            <span>{{ __('components.review_form.add_review') }}</span>
        </button>
    @elseif($existingReview)
        <div class="flex items-center gap-2 text-sm text-gray-600">
            <span>{{ __('components.review_form.your_rating') }}</span>
            <div class="flex items-center gap-0.5">
                @for($i = 1; $i <= 5; $i++)
                    <i class="ri-star-{{ $i <= $existingReview->rating ? 'fill text-yellow-400' : 'line text-gray-300' }} text-lg"></i>
                @endfor
            </div>
        </div>
    @endif

    {{-- Modal --}}
    @if($showModal)
        <div
            class="fixed inset-0 z-50 overflow-y-auto"
            x-data="{ show: true }"
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        >
            {{-- Backdrop --}}
            <div
                class="fixed inset-0 bg-black/50 backdrop-blur-sm"
                wire:click="closeModal"
            ></div>

            {{-- Modal Content --}}
            <div class="flex min-h-full items-center justify-center p-4">
                <div
                    class="relative w-full max-w-md bg-white rounded-2xl shadow-xl"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    @click.stop
                >
                    {{-- Header --}}
                    <div class="flex items-center justify-between p-6 border-b border-gray-100">
                        <h3 class="text-xl font-bold text-gray-900">
                            {{ $reviewType === 'teacher' ? __('components.review_form.rate_teacher') : __('components.review_form.rate_course') }}
                        </h3>
                        <button
                            wire:click="closeModal"
                            class="p-2 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100 transition-colors"
                        >
                            <i class="ri-close-line text-xl"></i>
                        </button>
                    </div>

                    {{-- Body --}}
                    <div class="p-6">
                        {{-- Reviewable Name --}}
                        <div class="text-center mb-6">
                            <p class="text-gray-600">
                                {{ $reviewType === 'teacher' ? __('components.review_form.rate_experience_with') : __('components.review_form.rate_experience_in') }}
                            </p>
                            <p class="text-lg font-semibold text-gray-900 mt-1">{{ $reviewableName }}</p>
                        </div>

                        @if(!$canReview)
                            <div class="text-center py-4">
                                <div class="inline-flex items-center justify-center w-16 h-16 bg-red-100 rounded-full mb-4">
                                    <i class="ri-error-warning-line text-3xl text-red-500"></i>
                                </div>
                                <p class="text-gray-700">{{ $cannotReviewReason }}</p>
                            </div>
                        @else
                            <form wire:submit.prevent="submitReview">
                                {{-- Star Rating --}}
                                <div class="mb-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-3 text-center">
                                        {{ __('components.review_form.choose_rating') }}
                                    </label>
                                    <div class="flex items-center justify-center gap-2">
                                        @foreach($stars as $star)
                                            <button
                                                type="button"
                                                wire:click="setRating({{ $star }})"
                                                class="p-1 transition-transform hover:scale-110 focus:outline-none"
                                            >
                                                <i class="ri-star-{{ $rating >= $star ? 'fill text-yellow-400' : 'line text-gray-300 hover:text-yellow-300' }} text-4xl transition-colors"></i>
                                            </button>
                                        @endforeach
                                    </div>
                                    @error('rating')
                                        <p class="mt-2 text-sm text-red-600 text-center">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Comment --}}
                                <div class="mb-6">
                                    <label for="comment" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('components.review_form.comment_optional') }}
                                    </label>
                                    <textarea
                                        id="comment"
                                        wire:model="comment"
                                        rows="4"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 resize-none"
                                        placeholder="{{ __('components.review_form.comment_placeholder') }}"
                                    ></textarea>
                                    @error('comment')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Submit Button --}}
                                <div class="flex gap-3">
                                    <button
                                        type="button"
                                        wire:click="closeModal"
                                        class="flex-1 px-4 py-3 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-colors"
                                    >
                                        {{ __('components.review_form.cancel') }}
                                    </button>
                                    <button
                                        type="submit"
                                        class="flex-1 px-4 py-3 bg-yellow-500 hover:bg-yellow-600 text-white rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                        wire:loading.attr="disabled"
                                        @if($rating < 1) disabled @endif
                                    >
                                        <span wire:loading.remove>{{ __('components.review_form.submit_review') }}</span>
                                        <span wire:loading>
                                            <i class="ri-loader-4-line animate-spin"></i>
                                            {{ __('components.review_form.submitting') }}
                                        </span>
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
