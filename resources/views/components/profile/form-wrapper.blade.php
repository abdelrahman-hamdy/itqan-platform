@props(['title', 'description', 'action', 'method' => 'POST', 'enctype' => null, 'backRoute'])

<div class="max-w-4xl mx-auto">

    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center space-x-4 space-x-reverse mb-4">
            @if(isset($backRoute))
                <a href="{{ $backRoute }}" class="text-primary hover:text-secondary transition-colors">
                    <i class="ri-arrow-right-line text-xl"></i>
                </a>
            @endif
            <h1 class="text-3xl font-bold text-gray-900">{{ $title }}</h1>
        </div>
        <p class="text-gray-600">{{ $description }}</p>
    </div>

    <!-- Form Container -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-8">
            <form action="{{ $action }}" method="POST" {{ $enctype ? "enctype={$enctype}" : '' }}>
                @csrf
                @if($method !== 'POST')
                    @method($method)
                @endif

                {{ $slot }}

                <!-- Form Actions -->
                <div class="flex items-center justify-end space-x-4 space-x-reverse mt-8 pt-6 border-t border-gray-200">
                    @if(isset($backRoute))
                        <a href="{{ $backRoute }}"
                           class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            إلغاء
                        </a>
                    @endif
                    <button type="submit"
                            class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-secondary transition-colors">
                        حفظ التغييرات
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Success Message -->
    @if(session('success'))
        <div class="mt-6 bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center space-x-3 space-x-reverse">
                <div class="w-5 h-5 bg-green-500 rounded-full flex items-center justify-center">
                    <i class="ri-check-line text-white text-sm"></i>
                </div>
                <p class="text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif

</div>
