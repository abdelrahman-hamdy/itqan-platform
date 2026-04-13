<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $profile = $supervisor->supervisorProfile;
@endphp

<div>
    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.supervisors.page_title'), 'url' => route('manage.supervisors.index', ['subdomain' => $subdomain])],
            ['label' => __('supervisor.supervisors.edit_supervisor')],
        ]"
        view-type="supervisor"
    />

    <!-- Page Header -->
    <div class="mb-6 md:mb-8">
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('supervisor.supervisors.edit_title') }}</h1>
        <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('supervisor.supervisors.edit_subtitle') }}</p>
        @if($profile?->supervisor_code)
            <p class="mt-1 text-sm text-gray-500 font-mono">
                <i class="ri-hashtag text-gray-400"></i>
                {{ $profile->supervisor_code }}
            </p>
        @endif
    </div>

    {{-- Global Error --}}
    @if($errors->has('error'))
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
            <p class="text-sm text-red-700">{{ $errors->first('error') }}</p>
        </div>
    @endif

    <form method="POST"
          action="{{ route('manage.supervisors.update', ['subdomain' => $subdomain, 'supervisor' => $supervisor->id]) }}"
          enctype="multipart/form-data"
          x-data="{
              gender: '{{ old('gender', $profile?->gender ?? '') }}',
              showPass: false,
              showConfirm: false,
              loading: false,
              previewUrl: '',
              fileName: '',
              hasImage: {{ $supervisor->avatar ? 'true' : 'false' }},
              existingAvatar: '{{ $supervisor->avatar ? Storage::disk('public')->url($supervisor->avatar) : '' }}',
              assetBase: '{{ asset('app-design-assets') }}',
              get defaultAvatarUrl() {
                  const g = this.gender === 'female' ? 'female' : 'male';
                  return this.assetBase + '/' + g + '-supervisor-avatar.png';
              },
              get displayUrl() {
                  if (this.previewUrl) return this.previewUrl;
                  if (this.existingAvatar) return this.existingAvatar;
                  return '';
              },
              handleFileSelect(event) {
                  const file = event.target.files[0];
                  if (!file) return;
                  if (!file.type.startsWith('image/')) return;
                  if (file.size > 2 * 1024 * 1024) {
                      window.toast?.warning('{{ __('common.profile.image_size_warning') }}');
                      return;
                  }
                  this.fileName = file.name;
                  this.hasImage = true;
                  const reader = new FileReader();
                  reader.onload = (e) => { this.previewUrl = e.target.result; };
                  reader.readAsDataURL(file);
              },
              removeImage() {
                  this.previewUrl = '';
                  this.fileName = '';
                  this.hasImage = false;
                  this.existingAvatar = '';
                  document.getElementById('avatar').value = '';
              }
          }"
          @submit="loading = true">
        @csrf
        @method('PUT')

        <div class="max-w-3xl mx-auto space-y-6">

            <!-- Personal Information -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="ri-user-line text-blue-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('supervisor.supervisors.personal_info') }}</h3>
                </div>

                <!-- Avatar Upload -->
                <div class="mb-6 pb-6 border-b border-gray-200 flex justify-center">
                    <div class="flex flex-col items-center text-center">
                        <div class="relative inline-block">
                            <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-white shadow-lg ring-2 ring-primary/20 relative bg-blue-100">
                                <img :src="displayUrl" alt="" class="w-full h-full object-cover relative z-10" x-show="displayUrl" x-cloak>
                                <div x-show="!displayUrl" class="absolute inset-0">
                                    <img :src="defaultAvatarUrl" alt=""
                                         class="absolute object-cover"
                                         style="width: 120%; height: 120%; top: 0; left: 50%; transform: translateX(-50%);">
                                </div>
                            </div>
                            <div class="absolute bottom-1 w-9 h-9 bg-primary text-white rounded-full flex items-center justify-center shadow-lg z-20" style="inset-inline-end: 0.25rem;">
                                <i class="ri-camera-line text-lg"></i>
                            </div>
                        </div>

                        <div class="mt-4">
                            <input type="file" id="avatar" name="avatar" accept="image/*" class="hidden" @change="handleFileSelect">
                            <label for="avatar"
                                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-white font-medium rounded-lg cursor-pointer hover:bg-primary-600 transition-all duration-200 text-sm">
                                <i class="ri-upload-2-line"></i>
                                <span x-text="hasImage ? '{{ __('common.profile.change_image') }}' : '{{ __('common.profile.add_image') }}'"></span>
                            </label>
                        </div>

                        <div x-show="fileName" class="mt-2 text-sm text-gray-600">
                            <i class="ri-file-image-line me-1"></i>
                            <span x-text="fileName"></span>
                        </div>

                        <div x-show="displayUrl" class="mt-2" x-cloak>
                            <button type="button" @click="removeImage" class="cursor-pointer text-sm text-red-600 hover:text-red-700 font-medium">
                                <i class="ri-delete-bin-line me-1"></i>
                                {{ __('common.profile.remove_image') }}
                            </button>
                        </div>

                        @error('avatar')
                            <div class="mt-2 text-sm text-red-600 bg-red-50 px-4 py-2 rounded-lg">
                                <i class="ri-error-warning-line me-1"></i>
                                {{ $message }}
                            </div>
                        @enderror

                        <p class="mt-2 text-xs text-gray-500">{{ __('common.profile.image_hint') }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- First Name --}}
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('auth.register.student.first_name') }} <span class="text-red-600">*</span>
                        </label>
                        <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $supervisor->first_name) }}" required
                               class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('first_name') border-red-500 @enderror">
                        @error('first_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Last Name --}}
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('auth.register.student.last_name') }} <span class="text-red-600">*</span>
                        </label>
                        <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $supervisor->last_name) }}" required
                               class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('last_name') border-red-500 @enderror">
                        @error('last_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('auth.register.student.email') }} <span class="text-red-600">*</span>
                        </label>
                        <input type="email" name="email" id="email" value="{{ old('email', $supervisor->email) }}" required
                               class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Phone --}}
                    <div>
                        <x-forms.phone-input
                            name="phone"
                            label="{{ __('auth.register.teacher.step2.phone') }}"
                            :required="false"
                            countryCodeField="phone_country_code"
                            countryField="phone_country"
                            initialCountry="sa"
                            :value="old('phone', $supervisor->phone)"
                            :error="$errors->first('phone')"
                        />
                    </div>

                    {{-- Gender --}}
                    <div>
                        <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('supervisor.teachers.gender_label') }} <span class="text-red-600">*</span>
                        </label>
                        <select name="gender" id="gender" required x-model="gender"
                                class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('gender') border-red-500 @enderror">
                            <option value="">{{ __('supervisor.teachers.gender_placeholder') }}</option>
                            <option value="male" {{ old('gender', $profile?->gender) === 'male' ? 'selected' : '' }}>{{ __('supervisor.teachers.male') }}</option>
                            <option value="female" {{ old('gender', $profile?->gender) === 'female' ? 'selected' : '' }}>{{ __('supervisor.teachers.female') }}</option>
                        </select>
                        @error('gender')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Permissions -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="ri-shield-star-line text-amber-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('supervisor.supervisors.permissions_info') }}</h3>
                </div>

                <h4 class="text-sm font-medium text-gray-700 mt-2">{{ __('supervisor.supervisors.perm_group_users') }}</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach(['can_manage_teachers', 'can_manage_students', 'can_manage_parents', 'can_reset_passwords'] as $perm)
                        <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                            <input type="hidden" name="{{ $perm }}" value="0">
                            <input type="checkbox" name="{{ $perm }}" value="1"
                                   class="h-5 w-5 text-amber-600 focus:ring-amber-500 border-gray-300 rounded"
                                   {{ old($perm, $profile?->$perm) ? 'checked' : '' }}>
                            <div>
                                <p class="font-medium text-gray-900">{{ __("supervisor.supervisors.{$perm}") }}</p>
                                <p class="text-sm text-gray-500">{{ __("supervisor.supervisors.{$perm}_description") }}</p>
                            </div>
                        </label>
                    @endforeach
                </div>

                <h4 class="text-sm font-medium text-gray-700 mt-4">{{ __('supervisor.supervisors.perm_group_financial') }}</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach(['can_view_subscriptions', 'can_manage_subscriptions', 'can_manage_payments', 'can_manage_teacher_earnings'] as $perm)
                        <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                            <input type="hidden" name="{{ $perm }}" value="0">
                            <input type="checkbox" name="{{ $perm }}" value="1"
                                   class="h-5 w-5 text-amber-600 focus:ring-amber-500 border-gray-300 rounded"
                                   {{ old($perm, $profile?->$perm) ? 'checked' : '' }}>
                            <div>
                                <p class="font-medium text-gray-900">{{ __("supervisor.supervisors.{$perm}") }}</p>
                                <p class="text-sm text-gray-500">{{ __("supervisor.supervisors.{$perm}_description") }}</p>
                            </div>
                        </label>
                    @endforeach
                </div>

                <h4 class="text-sm font-medium text-gray-700 mt-4">{{ __('supervisor.supervisors.perm_group_monitoring') }}</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                        <input type="hidden" name="can_monitor_sessions" value="0">
                        <input type="checkbox" name="can_monitor_sessions" value="1"
                               class="h-5 w-5 text-amber-600 focus:ring-amber-500 border-gray-300 rounded"
                               {{ old('can_monitor_sessions', $profile?->can_monitor_sessions) ? 'checked' : '' }}>
                        <div>
                            <p class="font-medium text-gray-900">{{ __('supervisor.supervisors.can_monitor_sessions') }}</p>
                            <p class="text-sm text-gray-500">{{ __('supervisor.supervisors.can_monitor_sessions_description') }}</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Teacher Assignments -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="ri-team-line text-indigo-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('supervisor.supervisors.assignments_info') }}</h3>
                </div>

                @include('supervisor.supervisors._teacher-assignments')
            </div>

            <!-- Notes -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="ri-sticky-note-line text-gray-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('supervisor.supervisors.notes_info') }}</h3>
                </div>

                <textarea name="notes" id="notes" rows="4"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('notes') border-red-500 @enderror"
                          placeholder="{{ __('supervisor.supervisors.notes') }}...">{{ old('notes', $profile?->notes) }}</textarea>
                @error('notes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password (Optional) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="ri-shield-check-line text-red-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('supervisor.supervisors.security_info') }}</h3>
                </div>

                <p class="text-sm text-gray-500 mb-4">{{ __('supervisor.supervisors.password_optional_hint') }}</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('auth.register.student.password') }}
                        </label>
                        <div class="relative">
                            <input :type="showPass ? 'text' : 'password'" name="password" id="password"
                                   minlength="6"
                                   class="min-h-[44px] w-full px-3 py-2 pe-10 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('password') border-red-500 @enderror"
                                   placeholder="{{ __('supervisor.teachers.new_password_placeholder') }}">
                            <button type="button" @click="showPass = !showPass"
                                class="cursor-pointer absolute inset-y-0 end-0 flex items-center pe-3 text-gray-400 hover:text-gray-600">
                                <i :class="showPass ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('supervisor.teachers.confirm_password') }}
                        </label>
                        <div class="relative">
                            <input :type="showConfirm ? 'text' : 'password'" name="password_confirmation" id="password_confirmation"
                                   minlength="6"
                                   class="min-h-[44px] w-full px-3 py-2 pe-10 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                   placeholder="{{ __('supervisor.teachers.confirm_password_placeholder') }}">
                            <button type="button" @click="showConfirm = !showConfirm"
                                class="cursor-pointer absolute inset-y-0 end-0 flex items-center pe-3 text-gray-400 hover:text-gray-600">
                                <i :class="showConfirm ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex items-center gap-3">
                <button type="submit" :disabled="loading"
                    class="cursor-pointer min-h-[44px] inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="ri-save-line" x-show="!loading"></i>
                    <i class="ri-loader-4-line animate-spin" x-show="loading" x-cloak></i>
                    {{ __('supervisor.supervisors.edit_supervisor') }}
                </button>
                <a href="{{ route('manage.supervisors.index', ['subdomain' => $subdomain]) }}"
                   class="cursor-pointer min-h-[44px] inline-flex items-center gap-2 px-4 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                    {{ __('common.cancel') }}
                </a>
            </div>
        </div>
    </form>
</div>

</x-layouts.supervisor>
