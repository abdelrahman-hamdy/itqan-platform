@props(['currentAvatar' => null, 'userName' => 'User', 'user' => null, 'userType' => null])

@php
    // Resolve default avatar URL using same logic as <x-avatar>
    $resolvedUserType = $userType;
    $resolvedGender = 'male';

    if ($user) {
        if (!$resolvedUserType) {
            $userClass = get_class($user);
            if ($userClass === 'App\Models\QuranTeacherProfile') {
                $resolvedUserType = 'quran_teacher';
            } elseif ($userClass === 'App\Models\AcademicTeacherProfile') {
                $resolvedUserType = 'academic_teacher';
            } else {
                $resolvedUserType = 'student';
            }
        }
        $resolvedGender = $user->gender ?? $user->user?->gender ?? 'male';
    }

    $avatarConfig = match($resolvedUserType) {
        'quran_teacher' => [
            'bgColor' => 'bg-yellow-100',
            'defaultAvatarUrl' => asset('app-design-assets/' . ($resolvedGender === 'female' ? 'female' : 'male') . '-quran-teacher-avatar.png'),
        ],
        'academic_teacher' => [
            'bgColor' => 'bg-violet-100',
            'defaultAvatarUrl' => asset('app-design-assets/' . ($resolvedGender === 'female' ? 'female' : 'male') . '-academic-teacher-avatar.png'),
        ],
        default => [
            'bgColor' => 'bg-blue-100',
            'defaultAvatarUrl' => asset('app-design-assets/' . ($resolvedGender === 'female' ? 'female' : 'male') . '-student-avatar.png'),
        ],
    };
@endphp

<div class="mb-8 pb-8 border-b border-gray-200" style="display: flex; justify-content: center; width: 100%;">
    <div x-data="profilePictureUpload('{{ $currentAvatar ? asset('storage/' . $currentAvatar) : '' }}')" style="display: flex; flex-direction: column; align-items: center; text-align: center;">
        <!-- Profile Picture Display -->
        <div class="relative" style="display: inline-block;">
            <!-- Avatar Container -->
            <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-white shadow-lg ring-2 ring-primary/20 {{ $avatarConfig['bgColor'] }} relative">
                <!-- Preview image (shown when user selects a file) -->
                <img x-ref="previewImg"
                     :src="previewUrl"
                     alt="{{ __('common.profile.avatar_alt') }}"
                     class="w-full h-full object-cover relative z-10"
                     x-show="previewUrl"
                     x-cloak>

                <!-- Default state (shown when no preview selected) -->
                <div x-show="!previewUrl" class="absolute inset-0">
                    @if($currentAvatar)
                        <img src="{{ asset('storage/' . $currentAvatar) }}"
                             alt="{{ __('common.profile.avatar_alt') }}"
                             class="w-full h-full object-cover">
                    @elseif($avatarConfig['defaultAvatarUrl'])
                        <img src="{{ $avatarConfig['defaultAvatarUrl'] }}"
                             alt="{{ $userName }}"
                             class="absolute object-cover"
                             style="width: 120%; height: 120%; top: 0; left: 50%; transform: translateX(-50%);">
                    @else
                        <div class="w-full h-full flex items-center justify-center">
                            <i class="ri-user-line text-5xl text-gray-400"></i>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Camera Icon Badge -->
            <div class="absolute bottom-1 w-9 h-9 bg-primary text-white rounded-full flex items-center justify-center shadow-lg z-20" style="inset-inline-end: 0.25rem;">
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
                   class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-white font-medium rounded-lg cursor-pointer hover:bg-primary-600 transition-all duration-200">
                <i class="ri-upload-2-line"></i>
                <span x-text="hasImage ? '{{ __('common.profile.change_image') }}' : '{{ __('common.profile.add_image') }}'"></span>
            </label>
        </div>

        <!-- File Info -->
        <div x-show="fileName" class="mt-3 text-sm text-gray-600">
            <i class="ri-file-image-line me-1"></i>
            <span x-text="fileName"></span>
        </div>

        <!-- Remove Button (only if there's a preview) -->
        <div x-show="previewUrl" class="mt-2">
            <button type="button"
                    @click="removeImage"
                    class="text-sm text-red-600 hover:text-red-700 font-medium">
                <i class="ri-delete-bin-line me-1"></i>
                {{ __('common.profile.remove_image') }}
            </button>
        </div>

        <!-- Validation Error -->
        @error('avatar')
            <div class="mt-3 text-sm text-red-600 bg-red-50 px-4 py-2 rounded-lg">
                <i class="ri-error-warning-line me-1"></i>
                {{ $message }}
            </div>
        @enderror

        <!-- Helper Text -->
        <p class="mt-3 text-xs text-gray-500">
            {{ __('common.profile.image_hint') }}
        </p>
    </div>
</div>

<script>
function profilePictureUpload(currentAvatar) {
    return {
        previewUrl: '',
        fileName: '',
        hasImage: !!currentAvatar,
        translations: {
            selectImageWarning: '{{ __('common.profile.select_image_warning') }}',
            imageSizeWarning: '{{ __('common.profile.image_size_warning') }}'
        },

        handleFileSelect(event) {
            const file = event.target.files[0];

            if (file) {
                // Validate file type
                if (!file.type.startsWith('image/')) {
                    window.toast?.warning(this.translations.selectImageWarning);
                    return;
                }

                // Validate file size (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    window.toast?.warning(this.translations.imageSizeWarning);
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
