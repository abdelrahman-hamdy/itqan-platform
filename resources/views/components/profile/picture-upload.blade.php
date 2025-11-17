@props(['currentAvatar' => null, 'userName' => 'User'])

<div class="flex justify-center mb-8 pb-8 border-b border-gray-200">
    <div x-data="profilePictureUpload('{{ $currentAvatar ? asset('storage/' . $currentAvatar) : '' }}', '{{ $userName }}')" class="text-center">
        <!-- Profile Picture Display -->
        <div class="relative inline-block">
            <!-- Avatar Image -->
            <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-white shadow-lg ring-2 ring-primary/20 mb-4">
                <img :src="previewUrl || defaultAvatar"
                     alt="صورة الملف الشخصي"
                     class="w-full h-full object-cover"
                     x-show="previewUrl || defaultAvatar">

                <!-- Placeholder if no image -->
                <div x-show="!previewUrl && !defaultAvatar"
                     class="w-full h-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center">
                    <i class="ri-user-line text-5xl text-white"></i>
                </div>
            </div>

            <!-- Camera Icon Badge -->
            <div class="absolute bottom-6 right-0 bg-primary text-white rounded-full p-2 shadow-lg">
                <i class="ri-camera-line text-lg"></i>
            </div>
        </div>

        <!-- Upload Button -->
        <div class="mt-4">
            <input type="file"
                   id="avatar"
                   name="avatar"
                   accept="image/*"
                   class="hidden"
                   @change="handleFileSelect">

            <label for="avatar"
                   class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-primary to-secondary text-white font-medium rounded-lg cursor-pointer hover:shadow-lg transition-all duration-200 hover:scale-105">
                <i class="ri-upload-2-line ml-2"></i>
                <span x-text="hasImage ? 'تغيير الصورة' : 'إضافة صورة'"></span>
            </label>
        </div>

        <!-- File Info -->
        <div x-show="fileName" class="mt-3 text-sm text-gray-600">
            <i class="ri-file-image-line ml-1"></i>
            <span x-text="fileName"></span>
        </div>

        <!-- Remove Button (only if there's a preview) -->
        <div x-show="previewUrl" class="mt-2">
            <button type="button"
                    @click="removeImage"
                    class="text-sm text-red-600 hover:text-red-700 font-medium">
                <i class="ri-delete-bin-line ml-1"></i>
                إزالة الصورة
            </button>
        </div>

        <!-- Validation Error -->
        @error('avatar')
            <div class="mt-3 text-sm text-red-600 bg-red-50 px-4 py-2 rounded-lg">
                <i class="ri-error-warning-line ml-1"></i>
                {{ $message }}
            </div>
        @enderror

        <!-- Helper Text -->
        <p class="mt-3 text-xs text-gray-500">
            JPG, PNG أو GIF (الحد الأقصى 2 ميجابايت)
        </p>
    </div>
</div>

<script>
function profilePictureUpload(currentAvatar, userName) {
    return {
        previewUrl: currentAvatar || '',
        defaultAvatar: currentAvatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(userName)}&background=4169E1&color=fff&size=128`,
        fileName: '',
        hasImage: !!currentAvatar,

        handleFileSelect(event) {
            const file = event.target.files[0];

            if (file) {
                // Validate file type
                if (!file.type.startsWith('image/')) {
                    alert('يرجى اختيار ملف صورة');
                    return;
                }

                // Validate file size (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('حجم الصورة يجب أن يكون أقل من 2 ميجابايت');
                    return;
                }

                this.fileName = file.name;
                this.hasImage = true;

                // Create preview
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.previewUrl = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },

        removeImage() {
            this.previewUrl = '';
            this.fileName = '';
            this.hasImage = false;
            document.getElementById('avatar').value = '';
        }
    }
}
</script>
