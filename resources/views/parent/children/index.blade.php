@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy?->subdomain ?? 'itqan-academy';
@endphp

<x-layouts.parent-layout title="إدارة الأبناء">
    <div class="space-y-6">

        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="ri-team-line text-purple-600 ml-2"></i>
                إدارة الأبناء
            </h1>
            <p class="text-gray-600">
                عرض وإضافة الأبناء المرتبطين بحسابك
            </p>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4 flex items-start gap-3">
                <i class="ri-checkbox-circle-line text-green-600 text-xl flex-shrink-0 mt-0.5"></i>
                <div class="flex-1">
                    <p class="font-medium">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4 flex items-start gap-3">
                <i class="ri-error-warning-line text-red-600 text-xl flex-shrink-0 mt-0.5"></i>
                <div class="flex-1">
                    <p class="font-medium">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4">
                <div class="flex items-start gap-3">
                    <i class="ri-error-warning-line text-red-600 text-xl flex-shrink-0 mt-0.5"></i>
                    <div class="flex-1">
                        <p class="font-medium mb-2">يرجى تصحيح الأخطاء التالية:</p>
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <!-- Add New Child Form -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-2">
                    <i class="ri-user-add-line text-blue-600 ml-2"></i>
                    إضافة ابن جديد
                </h2>
                <p class="text-sm text-gray-600">
                    أدخل كود الطالب للتحقق وإضافته إلى حسابك. سيتم التحقق تلقائياً من تطابق رقم هاتف ولي الأمر.
                </p>
            </div>

            <form action="{{ route('parent.children.store', ['subdomain' => $subdomain]) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="student_code" class="block text-sm font-medium text-gray-700 mb-2">
                        كود الطالب <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input
                            type="text"
                            name="student_code"
                            id="student_code"
                            class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors @error('student_code') border-red-500 @enderror"
                            placeholder="مثال: ST-01-123456789"
                            value="{{ old('student_code') }}"
                            required
                        >
                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="ri-barcode-line text-xl"></i>
                        </div>
                    </div>
                    @error('student_code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-xs text-gray-500">
                        <i class="ri-information-line ml-1"></i>
                        يمكنك الحصول على كود الطالب من إدارة الأكاديمية
                    </p>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button
                        type="submit"
                        class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors flex items-center gap-2"
                    >
                        <i class="ri-add-line text-lg"></i>
                        إضافة الطالب
                    </button>
                </div>
            </form>
        </div>

        <!-- Current Children List -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">
                    <i class="ri-team-line text-purple-600 ml-2"></i>
                    الأبناء المرتبطون بحسابك
                    <span class="text-sm font-normal text-gray-500 mr-2">({{ $children->count() }})</span>
                </h2>
            </div>

            @if($children->count() > 0)
                <div class="divide-y divide-gray-100">
                    @foreach($children as $child)
                        <div class="p-6 hover:bg-gray-50 transition-colors">
                            <div class="flex items-start gap-4">
                                <!-- Avatar -->
                                <x-avatar :user="$child" userType="student" size="md" />

                                <!-- Student Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex-1">
                                            <h3 class="text-lg font-bold text-gray-900 mb-1">
                                                {{ $child->full_name }}
                                            </h3>
                                            <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600">
                                                <div class="flex items-center gap-1">
                                                    <i class="ri-barcode-line text-purple-600"></i>
                                                    <span class="font-mono">{{ $child->student_code }}</span>
                                                </div>
                                                @if($child->gradeLevel)
                                                    <div class="flex items-center gap-1">
                                                        <i class="ri-book-line text-blue-600"></i>
                                                        <span>{{ $child->gradeLevel->name }}</span>
                                                    </div>
                                                @endif
                                                @if($child->email)
                                                    <div class="flex items-center gap-1">
                                                        <i class="ri-mail-line text-gray-500"></i>
                                                        <span dir="ltr">{{ $child->email }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex items-start gap-2">
                                            <!-- View Profile Button -->
                                            @if($child->user_id)
                                                <button
                                                    type="button"
                                                    onclick="viewChildDashboard('{{ $child->id }}')"
                                                    class="px-3 py-2 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg transition-colors text-sm font-medium flex items-center gap-1"
                                                    title="عرض لوحة التحكم"
                                                >
                                                    <i class="ri-eye-line"></i>
                                                    <span>عرض</span>
                                                </button>
                                            @endif

                                            <!-- Remove Button -->
                                            <form
                                                action="{{ route('parent.children.destroy', ['subdomain' => $subdomain, 'student' => $child->id]) }}"
                                                method="POST"
                                                id="remove-child-form-{{ $child->id }}"
                                                class="inline"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="button"
                                                    onclick="confirmRemoveChild('{{ $child->id }}', '{{ $child->full_name }}')"
                                                    class="px-3 py-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg transition-colors text-sm font-medium flex items-center gap-1"
                                                    title="إلغاء الربط"
                                                >
                                                    <i class="ri-link-unlink"></i>
                                                    <span>إلغاء الربط</span>
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
                <div class="p-12 text-center">
                    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="ri-user-search-line text-gray-400 text-3xl"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2 text-lg">لا يوجد أبناء مرتبطون بحسابك</h3>
                    <p class="text-gray-500 mb-6">ابدأ بإضافة أول طالب باستخدام النموذج أعلاه</p>
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-700 rounded-lg text-sm">
                        <i class="ri-lightbulb-line"></i>
                        <span>تحتاج إلى كود الطالب من إدارة الأكاديمية</span>
                    </div>
                </div>
            @endif
        </div>

    </div>

    @push('scripts')
    <script>
    // Function to select a child and navigate to dashboard
    function viewChildDashboard(childId) {
        // Show loading state
        event.target.disabled = true;
        event.target.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> <span>جاري التحميل...</span>';

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
                event.target.disabled = false;
                event.target.innerHTML = '<i class="ri-eye-line"></i> <span>عرض</span>';
                alert('حدث خطأ أثناء تحديد الطالب. يرجى المحاولة مرة أخرى.');
            }
        })
        .catch(error => {
            console.error('Error selecting child:', error);
            // Reset button on error
            event.target.disabled = false;
            event.target.innerHTML = '<i class="ri-eye-line"></i> <span>عرض</span>';
            alert('حدث خطأ أثناء تحديد الطالب. يرجى المحاولة مرة أخرى.');
        });
    }

    // Function to confirm removing a child
    function confirmRemoveChild(childId, childName) {
        // Wait for confirmAction to be available
        if (typeof window.confirmAction === 'function') {
            window.confirmAction({
                title: 'إلغاء ربط الطالب',
                message: 'هل أنت متأكد من إلغاء ربط ' + childName + ' من حسابك؟ يمكنك إعادة ربطه لاحقاً باستخدام كود الطالب.',
                confirmText: 'إلغاء الربط',
                cancelText: 'رجوع',
                isDangerous: true,
                icon: 'ri-link-unlink',
                onConfirm: function() {
                    document.getElementById('remove-child-form-' + childId).submit();
                }
            });
        } else {
            console.error('confirmAction function not available yet');
            // Fallback to native confirm
            if (confirm('هل أنت متأكد من إلغاء ربط ' + childName + ' من حسابك؟')) {
                document.getElementById('remove-child-form-' + childId).submit();
            }
        }
    }
    </script>
    @endpush
</x-layouts.parent-layout>
