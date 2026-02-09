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
    showPassword: false,
    inputType: '{{ $type }}'
}">
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-2">
        {{ $label }}
        @if($required)
            <span class="text-red-600">*</span>
        @endif
    </label>

    <div class="relative">
        {{-- Icon on the END side (left in RTL, right in LTR) --}}
        @if($icon && $type !== 'password')
            <div class="absolute inset-y-0 end-0 pe-3 flex items-center pointer-events-none text-gray-400">
                <i class="{{ $icon }} text-lg"></i>
            </div>
        @endif

        <input
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
            class="input-field appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all duration-200 @if($icon && $type !== 'password') pe-11 @endif @if($type === 'password') ps-11 @endif @error($name) border-red-500 ring-2 ring-red-200 @enderror"
            placeholder="{{ $placeholder }}"
            value="{{ old($name, $value) }}"
        >

        {{-- Password Toggle on START side (right in RTL, left in LTR) --}}
        @if($type === 'password')
            <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 start-0 ps-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors focus:outline-none"
                tabindex="-1"
                aria-label="إظهار كلمة المرور"
            >
                <i x-show="!showPassword" class="ri-eye-line text-lg"></i>
                <i x-show="showPassword" x-cloak class="ri-eye-off-line text-lg"></i>
            </button>
        @endif
    </div>

    {{-- Helper Text --}}
    @if($helperText)
        <p class="mt-1.5 text-xs text-gray-600">{{ $helperText }}</p>
    @endif

    {{-- Error Message --}}
    @error($name)
        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
