<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="fileUploadComponent('{{ route('custom.file.upload') }}', '{{ $getDisk() }}', '{{ $getDirectory() }}')">
        <div class="space-y-2">
            <!-- File Input -->
            <input
                type="file"
                id="{{ $getId() }}"
                x-ref="fileInput"
                aria-describedby="{{ $getId() }}-status"
                @change="handleFileSelect($event)"
                accept="{{ implode(',', $getAcceptedFileTypes()) }}"
                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
            >

            <!-- Upload Progress -->
            <div x-show="isUploading" class="w-full bg-gray-200 rounded-full h-2.5" role="progressbar" :aria-valuenow="uploadProgress" aria-valuemin="0" aria-valuemax="100">
                <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" :style="`width: ${uploadProgress}%`"></div>
            </div>

            <!-- File Name Display -->
            <div id="{{ $getId() }}-status" x-show="fileName" class="text-sm text-gray-600" aria-live="polite">
                <span x-text="fileName"></span>
                <span x-show="isUploading" class="ml-2 text-blue-600">(Uploading...)</span>
            </div>

            <!-- Hidden input for form submission -->
            <input type="hidden" name="{{ $getName() }}" x-ref="hiddenInput">
        </div>
    </div>
</x-dynamic-component>
