@props(['label', 'name', 'options' => [], 'selected' => [], 'columns' => 2])

<div>
    <label class="block text-sm font-medium text-gray-700 mb-3">{{ $label }}</label>
    <div class="grid grid-cols-{{ $columns }} md:grid-cols-{{ min($columns * 2, 4) }} gap-3">
        @foreach($options as $value => $label)
            <label class="flex items-center">
                <input type="checkbox"
                       name="{{ $name }}[]"
                       value="{{ $value }}"
                       {{ in_array($value, old($name, $selected)) ? 'checked' : '' }}
                       class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                <span class="mr-2 text-sm text-gray-700">{{ $label }}</span>
            </label>
        @endforeach
    </div>
    @error($name)
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>
