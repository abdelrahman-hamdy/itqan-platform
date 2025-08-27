<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="{ 
        fileName: null,
        uploadProgress: 0,
        isUploading: false,
        uploadUrl: '{{ route('custom.file.upload') }}',
        csrfToken: '{{ csrf_token() }}'
    }">
        <div class="space-y-2">
            <!-- File Input -->
            <input
                type="file"
                id="{{ $getId() }}"
                name="{{ $getName() }}"
                x-ref="fileInput"
                @change="
                    const file = $event.target.files[0];
                    if (file) {
                        fileName = file.name;
                        uploadFile(file);
                    }
                "
                accept="{{ implode(',', $getAcceptedFileTypes()) }}"
                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
            >

            <!-- Upload Progress -->
            <div x-show="isUploading" class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" :style="`width: ${uploadProgress}%`"></div>
            </div>

            <!-- File Name Display -->
            <div x-show="fileName" class="text-sm text-gray-600">
                <span x-text="fileName"></span>
                <span x-show="isUploading" class="ml-2 text-blue-600">(Uploading...)</span>
            </div>

            <!-- Hidden input for form submission -->
            <input type="hidden" name="{{ $getName() }}" x-ref="hiddenInput">
        </div>
    </div>

    <script>
        function uploadFile(file) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            formData.append('disk', '{{ $getDisk() }}');
            formData.append('directory', '{{ $getDirectory() }}');

            const xhr = new XMLHttpRequest();
            
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    this.uploadProgress = percentComplete;
                }
            }.bind(this));

            xhr.addEventListener('load', function() {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        this.$refs.hiddenInput.value = response.path;
                        this.isUploading = false;
                        this.uploadProgress = 100;
                    } else {
                        alert('Upload failed: ' + response.message);
                        this.isUploading = false;
                    }
                } else {
                    alert('Upload failed');
                    this.isUploading = false;
                }
            }.bind(this));

            xhr.addEventListener('error', function() {
                alert('Upload failed');
                this.isUploading = false;
            }.bind(this));

            xhr.open('POST', this.uploadUrl);
            xhr.send(formData);
            this.isUploading = true;
            this.uploadProgress = 0;
        }
    </script>
</x-dynamic-component>
