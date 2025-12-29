<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="{ 
        isUploading: false,
        uploadProgress: 0,
        uploadUrl: '{{ route('custom.file.upload') }}',
        csrfToken: '{{ csrf_token() }}',
        disk: '{{ $getDisk() }}',
        directory: '{{ $getDirectory() }}',
        acceptedTypes: {{ json_encode($getAcceptedFileTypes()) }},
        maxSize: {{ $getMaxSize() }},
        multiple: {{ $isMultiple() ? 'true' : 'false' }}
    }">
        <div class="space-y-4">
            <!-- File Input -->
            <div class="relative">
                <input
                    type="file"
                    x-ref="fileInput"
                    @change="handleFileSelect($event)"
                    :accept="acceptedTypes.join(',')"
                    :multiple="multiple"
                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                >
                
                <!-- Upload Progress -->
                <div x-show="isUploading" class="mt-2">
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" :style="`width: ${uploadProgress}%`"></div>
                    </div>
                    <p class="text-sm text-gray-600 mt-1">Uploading... <span x-text="uploadProgress"></span>%</p>
                </div>
            </div>

            <!-- Display current value -->
            <div x-show="$wire.get('{{ $getName() }}')" class="p-3 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-sm text-green-700">
                    <strong>Current file:</strong> <span x-text="$wire.get('{{ $getName() }}')"></span>
                </p>
            </div>

            <!-- Hidden text input for Filament -->
            <input 
                type="text" 
                name="{{ $getName() }}" 
                x-ref="textInput"
                value="{{ $getState() }}"
                class="sr-only"
            >
        </div>

        <script>
            function handleFileSelect(event) {
                const files = event.target.files;
                if (!files.length) return;

                Array.from(files).forEach(file => {
                    // Validate file size
                    if (file.size > this.maxSize * 1024) {
                        window.toast?.error(`File ${file.name} is too large. Maximum size is ${this.maxSize}KB`);
                        return;
                    }

                    // Validate file type
                    const isValidType = this.acceptedTypes.length === 0 || 
                        this.acceptedTypes.some(type => file.type.includes(type.split('/')[1]) || file.type === type);
                    
                    if (!isValidType) {
                        window.toast?.error(`File ${file.name} is not an accepted file type`);
                        return;
                    }

                    this.uploadFile(file);
                });
            }

            function uploadFile(file) {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('_token', this.csrfToken);
                formData.append('disk', this.disk);
                formData.append('directory', this.directory);

                const xhr = new XMLHttpRequest();
                
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        this.uploadProgress = Math.round((e.loaded / e.total) * 100);
                    }
                });

                xhr.addEventListener('load', () => {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                // Update the hidden text input
                                this.$refs.textInput.value = response.path;
                                
                                // Update Filament's form state
                                this.$wire.set('{{ $getName() }}', response.path);
                                
                                // Clear the file input
                                this.$refs.fileInput.value = '';
                                
                            } else {
                                window.toast?.error('Upload failed: ' + response.message);
                            }
                        } catch (e) {
                            window.toast?.error('Upload failed: Invalid response');
                        }
                    } else {
                        window.toast?.error('Upload failed: Server error');
                    }
                    this.isUploading = false;
                    this.uploadProgress = 0;
                });

                xhr.addEventListener('error', () => {
                    window.toast?.error('Upload failed: Network error');
                    this.isUploading = false;
                    this.uploadProgress = 0;
                });

                xhr.open('POST', this.uploadUrl);
                xhr.send(formData);
                this.isUploading = true;
                this.uploadProgress = 0;
            }
        </script>
    </div>
</x-dynamic-component>
