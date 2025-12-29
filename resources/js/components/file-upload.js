/**
 * File Upload Alpine.js Component
 * Handles AJAX file uploads with progress tracking
 */

export function fileUploadComponent(uploadUrl, disk, directory) {
    return {
        fileName: null,
        uploadProgress: 0,
        isUploading: false,
        uploadUrl,
        disk,
        directory,

        handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) {
                this.fileName = file.name;
                this.uploadFile(file);
            }
        },

        uploadFile(file) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            formData.append('disk', this.disk);
            formData.append('directory', this.directory);

            const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    this.uploadProgress = (e.loaded / e.total) * 100;
                }
            });

            xhr.addEventListener('load', () => {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            this.$refs.hiddenInput.value = response.path;
                            this.uploadProgress = 100;
                        } else {
                            window.toast?.error('Upload failed: ' + response.message);
                        }
                    } catch {
                        window.toast?.error('Invalid response from server');
                    }
                } else {
                    window.toast?.error('Upload failed');
                }
                this.isUploading = false;
            });

            xhr.addEventListener('error', () => {
                window.toast?.error('Upload failed');
                this.isUploading = false;
            });

            xhr.open('POST', this.uploadUrl);
            xhr.send(formData);
            this.isUploading = true;
            this.uploadProgress = 0;
        }
    };
}

// Make available globally for Alpine x-data
window.fileUploadComponent = fileUploadComponent;
