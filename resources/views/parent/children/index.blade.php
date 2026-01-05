@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy?->subdomain ?? 'itqan-academy';
@endphp

<x-layouts.parent-layout :title="__('parent.children.title')">
    <div class="space-y-6">

        <!-- Page Header -->
        <div class="mb-4 md:mb-8">
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 mb-1 md:mb-2">
                <i class="ri-team-line text-purple-600 ms-1.5 md:ms-2"></i>
                {{ __('parent.children.title') }}
            </h1>
            <p class="text-sm md:text-base text-gray-600">
                {{ __('parent.children.description') }}
            </p>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-3 md:p-4 flex items-start gap-2 md:gap-3">
                <i class="ri-checkbox-circle-line text-green-600 text-lg md:text-xl flex-shrink-0 mt-0.5"></i>
                <div class="flex-1">
                    <p class="font-medium text-sm md:text-base">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-3 md:p-4 flex items-start gap-2 md:gap-3">
                <i class="ri-error-warning-line text-red-600 text-lg md:text-xl flex-shrink-0 mt-0.5"></i>
                <div class="flex-1">
                    <p class="font-medium text-sm md:text-base">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-3 md:p-4">
                <div class="flex items-start gap-2 md:gap-3">
                    <i class="ri-error-warning-line text-red-600 text-lg md:text-xl flex-shrink-0 mt-0.5"></i>
                    <div class="flex-1">
                        <p class="font-medium mb-2 text-sm md:text-base">{{ __('parent.children.errors_title') }}</p>
                        <ul class="list-disc list-inside space-y-1 text-sm">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <!-- Add New Child Form -->
        <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
            <div class="mb-4 md:mb-6">
                <h2 class="text-base md:text-xl font-bold text-gray-900 mb-1 md:mb-2">
                    <i class="ri-user-add-line text-blue-600 ms-1.5 md:ms-2"></i>
                    {{ __('parent.children.add_new_title') }}
                </h2>
                <p class="text-xs md:text-sm text-gray-600">
                    {{ __('parent.children.add_new_description') }}
                </p>
            </div>

            <form action="{{ route('parent.children.store', ['subdomain' => $subdomain]) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="student_code" class="block text-sm font-medium text-gray-700 mb-1.5 md:mb-2">
                        {{ __('parent.children.student_code_label') }} <span class="text-red-500">{{ __('parent.common.required') }}</span>
                    </label>
                    <div class="relative">
                        <input
                            type="text"
                            name="student_code"
                            id="student_code"
                            class="w-full min-h-[44px] px-4 py-2.5 md:py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors text-sm md:text-base @error('student_code') border-red-500 @enderror"
                            placeholder="{{ __('parent.children.student_code_placeholder') }}"
                            value="{{ old('student_code') }}"
                            required
                        >
                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="ri-barcode-line text-lg md:text-xl"></i>
                        </div>
                    </div>
                    @error('student_code')
                        <p class="mt-1 text-xs md:text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1.5 md:mt-2 text-[10px] md:text-xs text-gray-500">
                        <i class="ri-information-line ms-1"></i>
                        {{ __('parent.children.student_code_info') }}
                    </p>
                </div>

                <div class="flex justify-end gap-3 pt-2 md:pt-4">
                    <button
                        type="submit"
                        class="min-h-[44px] px-4 md:px-6 py-2.5 md:py-3 bg-blue-600 hover:bg-blue-700 text-white text-sm md:text-base font-medium rounded-lg transition-colors flex items-center gap-2"
                    >
                        <i class="ri-add-line text-base md:text-lg"></i>
                        {{ __('parent.children.add_student_button') }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Current Children List -->
        <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-4 md:p-6 border-b border-gray-200">
                <h2 class="text-base md:text-xl font-bold text-gray-900">
                    <i class="ri-team-line text-purple-600 ms-1.5 md:ms-2"></i>
                    {{ __('parent.children.linked_children_title') }}
                    <span class="text-xs md:text-sm font-normal text-gray-500 me-1.5 md:me-2">({{ $children->count() }})</span>
                </h2>
            </div>

            @if($children->count() > 0)
                <div class="divide-y divide-gray-100">
                    @foreach($children as $child)
                        <div class="p-4 md:p-6 hover:bg-gray-50 transition-colors">
                            <div class="flex flex-col sm:flex-row sm:items-start gap-3 md:gap-4">
                                <!-- Avatar -->
                                <div class="flex items-center gap-3 sm:block">
                                    <x-avatar :user="$child" userType="student" size="md" />
                                    <div class="sm:hidden flex-1 min-w-0">
                                        <h3 class="text-base font-bold text-gray-900 truncate">
                                            {{ $child->full_name }}
                                        </h3>
                                        <div class="flex items-center gap-1 text-xs text-gray-600">
                                            <i class="ri-barcode-line text-purple-600"></i>
                                            <span class="font-mono">{{ $child->student_code }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Student Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 md:gap-4">
                                        <div class="flex-1 hidden sm:block">
                                            <h3 class="text-base md:text-lg font-bold text-gray-900 mb-1">
                                                {{ $child->full_name }}
                                            </h3>
                                            <div class="flex flex-wrap items-center gap-2 md:gap-4 text-xs md:text-sm text-gray-600">
                                                <div class="flex items-center gap-1">
                                                    <i class="ri-barcode-line text-purple-600"></i>
                                                    <span class="font-mono">{{ $child->student_code }}</span>
                                                </div>
                                                @if($child->gradeLevel)
                                                    <div class="flex items-center gap-1">
                                                        <i class="ri-book-line text-blue-600"></i>
                                                        <span>{{ $child->gradeLevel->getDisplayName() }}</span>
                                                    </div>
                                                @endif
                                                @if($child->email)
                                                    <div class="hidden md:flex items-center gap-1">
                                                        <i class="ri-mail-line text-gray-500"></i>
                                                        <span dir="ltr">{{ $child->email }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Mobile info row -->
                                        <div class="flex flex-wrap items-center gap-2 text-xs text-gray-600 sm:hidden">
                                            @if($child->gradeLevel)
                                                <div class="flex items-center gap-1">
                                                    <i class="ri-book-line text-blue-600"></i>
                                                    <span>{{ $child->gradeLevel->getDisplayName() }}</span>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex items-center gap-2 w-full sm:w-auto">
                                            <!-- View Profile Button -->
                                            @if($child->user_id)
                                                <button
                                                    type="button"
                                                    x-data
                                                    @click="viewChildDashboard('{{ $child->id }}', $event)"
                                                    class="min-h-[44px] flex-1 sm:flex-none px-3 py-2 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg transition-colors text-xs md:text-sm font-medium flex items-center justify-center gap-1"
                                                    title="{{ __('parent.children.view_dashboard_title') }}"
                                                >
                                                    <i class="ri-eye-line"></i>
                                                    <span>{{ __('parent.children.view_dashboard') }}</span>
                                                </button>
                                            @endif

                                            <!-- Remove Button -->
                                            <form
                                                action="{{ route('parent.children.destroy', ['subdomain' => $subdomain, 'student' => $child->id]) }}"
                                                method="POST"
                                                id="remove-child-form-{{ $child->id }}"
                                                class="flex-1 sm:flex-none"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="button"
                                                    x-data
                                                    @click="confirmRemoveChild('{{ $child->id }}', '{{ $child->full_name }}')"
                                                    class="min-h-[44px] w-full px-3 py-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg transition-colors text-xs md:text-sm font-medium flex items-center justify-center gap-1"
                                                    title="{{ __('parent.children.unlink_title') }}"
                                                >
                                                    <i class="ri-link-unlink"></i>
                                                    <span>{{ __('parent.children.unlink') }}</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-8 md:p-12 text-center">
                    <div class="w-16 h-16 md:w-20 md:h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                        <i class="ri-user-search-line text-gray-400 text-2xl md:text-3xl"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-1 md:mb-2 text-base md:text-lg">{{ __('parent.children.no_children_title') }}</h3>
                    <p class="text-sm md:text-base text-gray-500 mb-4 md:mb-6">{{ __('parent.children.no_children_description') }}</p>
                    <div class="inline-flex items-center gap-2 px-3 md:px-4 py-2 bg-blue-50 text-blue-700 rounded-lg text-xs md:text-sm">
                        <i class="ri-lightbulb-line"></i>
                        <span>{{ __('parent.children.need_code_tip') }}</span>
                    </div>
                </div>
            @endif
        </div>

    </div>

    @push('scripts')
    <script>
    // Function to select a child and navigate to dashboard
    function viewChildDashboard(childId, event) {
        const button = event.target.closest('button');
        // Show loading state
        button.disabled = true;
        button.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> <span>{{ __('parent.children.loading') }}</span>';

        // Call the select-child API
        fetch('{{ route("parent.select-child", ["subdomain" => $subdomain]) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ child_id: childId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Navigate to parent dashboard
                window.location.href = '{{ route("parent.dashboard", ["subdomain" => $subdomain]) }}';
            } else {
                // Reset button on error
                button.disabled = false;
                button.innerHTML = '<i class="ri-eye-line"></i> <span>{{ __('parent.children.view_dashboard') }}</span>';
                window.toast?.error('{{ __('parent.children.error_selecting_child') }}');
            }
        })
        .catch(error => {
            // Reset button on error
            button.disabled = false;
            button.innerHTML = '<i class="ri-eye-line"></i> <span>{{ __('parent.children.view_dashboard') }}</span>';
            window.toast?.error('{{ __('parent.children.error_selecting_child') }}');
        });
    }

    // Function to confirm removing a child
    function confirmRemoveChild(childId, childName) {
        // Wait for confirmAction to be available
        if (typeof window.confirmAction === 'function') {
            window.confirmAction({
                title: '{{ __('parent.children.unlink_confirm_title') }}',
                message: '{{ __('parent.children.unlink_confirm_message', ['name' => '']) }}'.replace(':name', childName),
                confirmText: '{{ __('parent.children.unlink_button') }}',
                cancelText: '{{ __('parent.children.cancel_button') }}',
                isDangerous: true,
                icon: 'ri-link-unlink',
                onConfirm: function() {
                    document.getElementById('remove-child-form-' + childId).submit();
                }
            });
        } else {
            // Fallback to native confirm
            if (confirm('{{ __('parent.children.unlink_confirm_message', ['name' => '']) }}'.replace(':name', childName))) {
                document.getElementById('remove-child-form-' + childId).submit();
            }
        }
    }
    </script>
    @endpush
</x-layouts.parent-layout>
