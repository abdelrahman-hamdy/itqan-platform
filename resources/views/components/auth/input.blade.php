@props([
    'label' => '',
    'name' => '',
    'type' => 'text',
    'required' => false,
    'icon' => null,
    'placeholder' => '',
    'value' => '',
    'helperText' => null,
    'autocomplete' => null,
])

<div class="mb-4" x-data="{
    focused: false,
    hasValue: false,
    showPassword: false,
    inputType: '{{ $type }}'
}" x-init="hasValue = $refs.input.value.length > 0">
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-2">
        {{ $label }}
        @if($required)
            <span class="text-red-500">*</span>
        @endif
    </label>

    <div class="relative">
        @if($icon)
            <div class="absolute inset-y-0 end-0 pe-3 flex items-center pointer-events-none"
                 :class="{ 'text-primary': focused, 'text-gray-400': !focused }">
                <i class="{{ $icon }} text-lg transition-smooth"></i>
            </div>
        @endif

        <input
            x-ref="input"
            :type="inputType === 'password' ? (showPassword ? 'text' : 'password') : inputType"
            id="{{ $name }}"
            name="{{ $name }}"
            @if($required) required @endif
            @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if($type === 'number')
                @keypress="if(!/[0-9]/.test($event.key)) $event.preventDefault()"
                inputmode="numeric"
                pattern="[0-9]*"
            @endif
            @focus="focused = true"
            @blur="focused = false; hasValue = $event.target.value.length > 0"
            @input="hasValue = $event.target.value.length > 0"
            class="input-field appearance-none block w-full px-4 py-3 @if($icon) pe-11 @endif @if($type === 'password') ps-11 @endif border border-gray-300 rounded-button text-gray-900 placeholder-gray-400 focus:outline-none transition-smooth @error($name) border-red-500 ring-2 ring-red-200 @enderror"
            placeholder="{{ $placeholder }}"
            value="{{ old($name, $value) }}"
        >

        <!-- Password Toggle Button -->
        @if($type === 'password')
            <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 start-0 ps-3 flex items-center text-gray-400 hover:text-primary transition-smooth focus:outline-none"
                tabindex="-1"
            >
                <i x-show="!showPassword" class="ri-eye-line text-lg"></i>
                <i x-show="showPassword" x-cloak class="ri-eye-off-line text-lg"></i>
            </button>
        @else
            <!-- Validation Icon -->
            <div class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
                @error($name)
                    <i class="ri-error-warning-fill text-red-500"></i>
                @else
                    <i x-show="hasValue && !focused" x-cloak class="ri-checkbox-circle-fill text-green-500 validation-success"></i>
                @enderror
            </div>
        @endif
    </div>

    <!-- Helper Text (Hidden for password fields) -->
    @if($helperText && $type !== 'password')
        <p class="mt-1.5 text-xs text-gray-500 flex items-center">
            <i class="ri-information-line ms-1"></i>
            {{ $helperText }}
        </p>
    @endif

    <!-- Error Message -->
    @error($name)
        <p class="mt-1.5 text-sm text-red-600 flex items-center animate-shake">
            <i class="ri-error-warning-line ms-1"></i>
            {{ $message }}
        </p>
    @enderror
</div>

<style>
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-4px); }
        75% { transform: translateX(4px); }
    }

    .animate-shake {
        animation: shake 0.3s ease-in-out;
    }

    [x-cloak] {
        display: none !important;
    }
</style>
