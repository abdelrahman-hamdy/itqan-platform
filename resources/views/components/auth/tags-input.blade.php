@props([
    'label' => '',
    'name' => '',
    'required' => false,
    'placeholder' => 'اضغط Enter للإضافة',
    'helperText' => null,
    'maxTags' => 10,
])

<div class="mb-4" x-data="tagsInput({{ $maxTags }})">
    <label for="{{ $name }}_input" class="block text-sm font-medium text-gray-700 mb-2">
        {{ $label }}
        @if($required)
            <span class="text-red-600">*</span>
        @endif
    </label>

    <!-- Tags Display -->
    <div class="min-h-[52px] px-4 py-2 border border-gray-300 rounded-button focus-within:ring-2 focus-within:ring-primary focus-within:border-transparent transition-smooth @error($name) border-red-500 ring-2 ring-red-200 @enderror">
        <div class="flex flex-wrap gap-2">
            <!-- Tags -->
            <template x-for="(tag, index) in tags" :key="index">
                <div class="tag inline-flex items-center gap-2 px-3 py-1.5 bg-primary text-white rounded-full text-sm font-medium">
                    <span x-text="tag"></span>
                    <button type="button"
                            @click="removeTag(index)"
                            class="hover:bg-white/20 rounded-full p-0.5 transition-smooth">
                        <i class="ri-close-line text-sm"></i>
                    </button>
                </div>
            </template>

            <!-- Input Field -->
            <input
                x-ref="input"
                id="{{ $name }}_input"
                type="text"
                x-model="currentInput"
                @keydown.enter.prevent="addTag"
                @keydown.backspace="handleBackspace"
                @paste="handlePaste"
                :disabled="tags.length >= maxTags"
                class="flex-1 min-w-[120px] outline-none bg-transparent text-gray-900 placeholder-gray-400 disabled:cursor-not-allowed"
                :placeholder="tags.length === 0 ? '{{ $placeholder }}' : ''"
            >
        </div>
    </div>

    <!-- Hidden Input for Form Submission -->
    <input type="hidden" name="{{ $name }}" x-model="tagsString">

    <!-- Helper Text -->
    <div class="mt-1.5 flex items-center justify-between text-xs">
        <div class="flex items-center text-gray-500">
            @if($helperText)
                <i class="ri-information-line ms-1"></i>
                <span>{{ $helperText }}</span>
            @else
                <i class="ri-information-line ms-1"></i>
                <span>اضغط Enter بعد كل رمز، أو الصق قائمة مفصولة بفواصل</span>
            @endif
        </div>
        <div class="text-gray-500">
            <span x-text="tags.length"></span> / <span x-text="maxTags"></span>
        </div>
    </div>

    <!-- Error Message -->
    @error($name)
        <p class="mt-1.5 text-sm text-red-600 flex items-center animate-shake">
            <i class="ri-error-warning-line ms-1"></i>
            {{ $message }}
        </p>
    @enderror

    <!-- Instructions -->
    <div x-show="tags.length === 0" class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg">
        <div class="flex items-start">
            <i class="ri-lightbulb-line text-blue-500 text-lg ms-2 flex-shrink-0 mt-0.5"></i>
            <div class="text-sm text-blue-700">
                <p class="font-medium mb-1">كيفية إضافة رموز الطلاب:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>اكتب رمز الطالب واضغط Enter</li>
                    <li>أو الصق عدة رموز مفصولة بفواصل أو فراغات</li>
                    <li>يمكنك إضافة حتى {{ $maxTags }} رموز</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function tagsInput(maxTags) {
    return {
        tags: [],
        currentInput: '',
        maxTags: maxTags,

        get tagsString() {
            return this.tags.join(',');
        },

        addTag() {
            const tag = this.currentInput.trim().toUpperCase();

            if (!tag) return;

            if (this.tags.length >= this.maxTags) {
                this.showError('لقد وصلت للحد الأقصى من الرموز');
                return;
            }

            if (this.tags.includes(tag)) {
                this.showError('هذا الرمز موجود بالفعل');
                return;
            }

            // Validate code format (letters and numbers only, minimum 3 characters)
            if (!/^[A-Z0-9]{3,}$/.test(tag)) {
                this.showError('الرمز يجب أن يحتوي على حروف وأرقام فقط (3 أحرف على الأقل)');
                return;
            }

            this.tags.push(tag);
            this.currentInput = '';
        },

        removeTag(index) {
            this.tags.splice(index, 1);
        },

        handleBackspace() {
            if (this.currentInput === '' && this.tags.length > 0) {
                this.tags.pop();
            }
        },

        handlePaste(event) {
            event.preventDefault();
            const pastedText = (event.clipboardData || window.clipboardData).getData('text');

            // Split by comma, space, or newline
            const codes = pastedText.split(/[,\s\n]+/)
                                   .map(code => code.trim().toUpperCase())
                                   .filter(code => code && /^[A-Z0-9]{3,}$/.test(code));

            codes.forEach(code => {
                if (this.tags.length < this.maxTags && !this.tags.includes(code)) {
                    this.tags.push(code);
                }
            });

            this.currentInput = '';
        },

        showError(message) {
            // You could integrate with a toast notification system here
        }
    }
}
</script>

<style>
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-4px); }
        75% { transform: translateX(4px); }
    }

    .animate-shake {
        animation: shake 0.3s ease-in-out;
    }
</style>
