@props(['label', 'name', 'value' => '', 'rows' => 3, 'required' => false, 'placeholder' => ''])

<div>
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-2">{{ $label }}</label>
    <textarea id="{{ $name }}"
              name="{{ $name }}"
              rows="{{ $rows }}"
              @if($placeholder) placeholder="{{ $placeholder }}" @endif
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
              {{ $required ? 'required' : '' }}>{{ old($name, $value) }}</textarea>
    @error($name)
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>
