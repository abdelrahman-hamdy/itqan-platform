@props(['label', 'name', 'type' => 'text', 'value' => '', 'required' => false, 'readonly' => false, 'placeholder' => ''])

<div>
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-2">{{ $label }}</label>
    <input type="{{ $type }}"
           id="{{ $name }}"
           name="{{ $name }}"
           value="{{ old($name, $value) }}"
           @if($placeholder) placeholder="{{ $placeholder }}" @endif
           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @if($readonly) bg-gray-50 text-gray-500 cursor-not-allowed @endif"
           {{ $required ? 'required' : '' }}
           {{ $readonly ? 'readonly disabled tabindex="-1"' : '' }}>
    @error($name)
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>
