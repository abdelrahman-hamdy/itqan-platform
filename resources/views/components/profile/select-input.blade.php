@props(['label', 'name', 'options' => [], 'value' => '', 'required' => false, 'placeholder' => 'اختر...'])

<div>
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-2">{{ $label }}</label>
    <select id="{{ $name }}"
            name="{{ $name }}"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
            {{ $required ? 'required' : '' }}>
        <option value="">{{ $placeholder }}</option>
        @foreach($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" {{ old($name, $value) == $optionValue ? 'selected' : '' }}>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>
    @error($name)
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>
