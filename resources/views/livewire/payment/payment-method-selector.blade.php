<div class="space-y-4">
    <!-- Section Title -->
    <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold text-gray-900 flex items-center gap-2">
            <i class="ri-bank-card-line text-blue-600"></i>
            {{ __('student.saved_payment_methods.choose_method') }}
        </h3>
    </div>

    <!-- Saved Payment Methods -->
    @if($this->hasSavedMethods)
        <div class="space-y-2">
            <p class="text-sm text-gray-600 mb-3">{{ __('student.saved_payment_methods.saved_cards') }}</p>

            @foreach($this->savedMethods as $method)
                <label
                    class="flex items-center gap-4 p-4 bg-white border-2 rounded-xl cursor-pointer transition-all
                        {{ $selectedMethodId === $method->id && $paymentType === 'saved'
                            ? 'border-blue-500 bg-blue-50/50'
                            : 'border-gray-200 hover:border-gray-300' }}"
                >
                    <input
                        type="radio"
                        name="payment_method"
                        value="{{ $method->id }}"
                        wire:click="selectMethod({{ $method->id }})"
                        {{ $selectedMethodId === $method->id && $paymentType === 'saved' ? 'checked' : '' }}
                        class="w-5 h-5 text-blue-600 border-gray-300 focus:ring-blue-500"
                    >

                    <!-- Card Icon -->
                    <div class="w-12 h-8 bg-gray-100 rounded-lg flex items-center justify-center shrink-0">
                        @if($method->brand === 'visa')
                            <i class="ri-visa-line text-xl text-blue-600"></i>
                        @elseif($method->brand === 'mastercard')
                            <i class="ri-mastercard-line text-xl text-orange-600"></i>
                        @else
                            <i class="{{ $method->getBrandIcon() }} text-xl text-gray-600"></i>
                        @endif
                    </div>

                    <!-- Card Info -->
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-gray-900">
                                {{ $method->getBrandDisplayName() }}
                            </span>
                            <span class="text-gray-600">
                                {{ $method->getMaskedNumber() }}
                            </span>
                            @if($method->is_default)
                                <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">
                                    {{ __('student.saved_payment_methods.default_badge') }}
                                </span>
                            @endif
                        </div>
                        @if($method->getExpiryDisplay())
                            <p class="text-xs text-gray-500">
                                {{ __('student.saved_payment_methods.expires_at') }} {{ $method->getExpiryDisplay() }}
                            </p>
                        @endif
                    </div>
                </label>
            @endforeach
        </div>
    @endif

    <!-- New Card Option -->
    <div class="pt-3 border-t border-gray-100">
        <label
            class="flex items-center gap-4 p-4 bg-white border-2 rounded-xl cursor-pointer transition-all
                {{ $paymentType === 'new'
                    ? 'border-blue-500 bg-blue-50/50'
                    : 'border-gray-200 hover:border-gray-300' }}"
        >
            <input
                type="radio"
                name="payment_method"
                value="new"
                wire:click="useNewCard"
                {{ $paymentType === 'new' ? 'checked' : '' }}
                class="w-5 h-5 text-blue-600 border-gray-300 focus:ring-blue-500"
            >

            <div class="w-12 h-8 bg-gray-100 rounded-lg flex items-center justify-center shrink-0">
                <i class="ri-add-line text-xl text-gray-600"></i>
            </div>

            <div class="flex-1">
                <span class="font-medium text-gray-900">{{ __('student.saved_payment_methods.new_card') }}</span>
                <p class="text-xs text-gray-500">{{ __('student.saved_payment_methods.new_card_description') }}</p>
            </div>
        </label>

        <!-- Save Card Toggle (when new card selected) -->
        @if($paymentType === 'new')
            <div class="mt-3 ms-16">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input
                        type="checkbox"
                        wire:click="toggleSaveCard"
                        {{ $saveCard ? 'checked' : '' }}
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                    >
                    <span class="text-sm text-gray-700">{{ __('student.saved_payment_methods.save_for_future') }}</span>
                </label>
                <p class="text-xs text-gray-500 mt-1 ms-7">
                    {{ __('student.saved_payment_methods.save_for_future_description') }}
                </p>
            </div>
        @endif
    </div>

    <!-- Wallet Option (if supported) -->
    <label
        class="flex items-center gap-4 p-4 bg-white border-2 rounded-xl cursor-pointer transition-all
            {{ $paymentType === 'wallet'
                ? 'border-blue-500 bg-blue-50/50'
                : 'border-gray-200 hover:border-gray-300' }}"
    >
        <input
            type="radio"
            name="payment_method"
            value="wallet"
            wire:click="useWallet"
            {{ $paymentType === 'wallet' ? 'checked' : '' }}
            class="w-5 h-5 text-blue-600 border-gray-300 focus:ring-blue-500"
        >

        <div class="w-12 h-8 bg-gray-100 rounded-lg flex items-center justify-center shrink-0">
            <i class="ri-wallet-3-line text-xl text-green-600"></i>
        </div>

        <div class="flex-1">
            <span class="font-medium text-gray-900">{{ __('student.saved_payment_methods.mobile_wallet') }}</span>
            <p class="text-xs text-gray-500">{{ __('student.saved_payment_methods.mobile_wallet_description') }}</p>
        </div>
    </label>

    <!-- Security Note -->
    <div class="flex items-center gap-2 text-xs text-gray-500 pt-2">
        <i class="ri-shield-check-line text-green-600"></i>
        <span>{{ __('student.saved_payment_methods.security_note') }}</span>
    </div>

    <!-- Hidden fields for form submission -->
    <input type="hidden" name="payment_type" value="{{ $paymentType }}">
    <input type="hidden" name="saved_payment_method_id" value="{{ $selectedMethodId }}">
    <input type="hidden" name="save_card" value="{{ $saveCard ? '1' : '0' }}">
</div>
