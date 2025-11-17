@props(['label', 'name', 'value' => [], 'placeholder' => 'أضف عنصراً جديداً'])

<div x-data="tagsInput(@js(old($name, $value)))">
    <label class="block text-sm font-medium text-gray-700 mb-2">{{ $label }}</label>

    <!-- Tags Display -->
    <div class="flex flex-wrap gap-2 mb-2" x-show="tags.length > 0">
        <template x-for="(tag, index) in tags" :key="index">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-primary/10 text-primary border border-primary/20">
                <span x-text="tag"></span>
                <button type="button" @click="removeTag(index)" class="mr-1 text-primary/70 hover:text-primary">
                    <i class="ri-close-line"></i>
                </button>
            </span>
        </template>
    </div>

    <!-- Input -->
    <input type="text"
           x-model="newTag"
           @keydown.enter.prevent="addTag"
           @keydown.comma.prevent="addTag"
           placeholder="{{ $placeholder }}"
           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">

    <!-- Hidden inputs for form submission -->
    <template x-for="(tag, index) in tags" :key="index">
        <input type="hidden" :name="'{{ $name }}[' + index + ']'" :value="tag">
    </template>

    <p class="text-xs text-gray-500 mt-1">اضغط Enter أو فاصلة لإضافة عنصر جديد</p>

    @error($name)
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

<script>
function tagsInput(initialTags = []) {
    return {
        tags: initialTags || [],
        newTag: '',
        addTag() {
            const tag = this.newTag.trim();
            if (tag && !this.tags.includes(tag)) {
                this.tags.push(tag);
                this.newTag = '';
            }
        },
        removeTag(index) {
            this.tags.splice(index, 1);
        }
    }
}
</script>
