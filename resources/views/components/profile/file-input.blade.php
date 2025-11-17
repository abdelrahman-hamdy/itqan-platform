@props(['label', 'name', 'currentFile' => null, 'accept' => 'image/*'])

<div>
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-2">{{ $label }}</label>
    <input type="file"
           id="{{ $name }}"
           name="{{ $name }}"
           accept="{{ $accept }}"
           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-secondary">
    @if($currentFile)
        <div class="mt-2">
            <p class="text-sm text-gray-600">الصورة الحالية:</p>
            <img src="{{ asset('storage/' . $currentFile) }}" alt="الصورة الحالية" class="w-16 h-16 rounded-full object-cover mt-1">
        </div>
    @endif
    @error($name)
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>
