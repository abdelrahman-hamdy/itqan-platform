{{-- Custom Confirmation Modal --}}
<div x-data="{
    show: false,
    title: '',
    message: '',
    confirmText: 'تأكيد',
    cancelText: 'إلغاء',
    confirmAction: null,
    isDangerous: false,
    confirmColor: null,

    init() {
        this.$watch('show', value => {
            if (value) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        });
    },

    open(data) {
        this.title = data.title || 'تأكيد العملية';
        this.message = data.message || 'هل أنت متأكد من هذا الإجراء؟';
        this.confirmText = data.confirmText || 'تأكيد';
        this.cancelText = data.cancelText || 'إلغاء';
        this.confirmAction = data.onConfirm || null;
        this.isDangerous = data.isDangerous || false;
        this.confirmColor = data.confirmColor || null;
        this.show = true;
    },

    confirm() {
        if (this.confirmAction && typeof this.confirmAction === 'function') {
            this.confirmAction();
        }
        this.close();
    },

    close() {
        this.show = false;
        this.confirmAction = null;
    }
}"
@open-confirmation.window="open($event.detail)"
@keydown.escape.window="show && close()"
x-show="show"
x-cloak
class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
x-transition:enter="transition ease-out duration-200"
x-transition:enter-start="opacity-0"
x-transition:enter-end="opacity-100"
x-transition:leave="transition ease-in duration-150"
x-transition:leave-start="opacity-100"
x-transition:leave-end="opacity-0"
style="display: none;">

    {{-- Modal Content --}}
    <div @click.stop
        class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full mx-auto overflow-hidden"
        x-transition:enter="transition ease-out duration-200 delay-100"
        x-transition:enter-start="opacity-0 scale-95 translate-y-4"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95">

        {{-- Icon & Title Section --}}
        <div class="p-6 pb-4">
            {{-- Icon --}}
            <div class="mx-auto flex items-center justify-center w-16 h-16 rounded-full mb-4"
                :class="{
                    'bg-red-100 dark:bg-red-900/30': isDangerous,
                    'bg-orange-100 dark:bg-orange-900/30': !isDangerous && confirmColor === 'orange',
                    'bg-blue-100 dark:bg-blue-900/30': !isDangerous && confirmColor !== 'orange'
                }">
                {{-- Danger icon --}}
                <svg x-show="isDangerous" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-8 h-8 text-red-600 dark:text-red-400">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                {{-- Orange/Archive icon --}}
                <svg x-show="!isDangerous && confirmColor === 'orange'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-8 h-8 text-orange-600 dark:text-orange-400">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                </svg>
                {{-- Default/Info icon --}}
                <svg x-show="!isDangerous && confirmColor !== 'orange'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-8 h-8 text-blue-600 dark:text-blue-400">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
                </svg>
            </div>

            {{-- Title --}}
            <h3 class="text-xl font-bold text-center text-gray-900 dark:text-white mb-2" x-text="title" x-show="title" x-cloak></h3>

            {{-- Message --}}
            <p class="text-center text-gray-600 dark:text-gray-300 text-sm leading-relaxed" x-text="message"></p>
        </div>

        {{-- Actions --}}
        <div class="bg-gray-50 dark:bg-gray-900/50 px-6 py-4 flex gap-3 justify-end">
            {{-- Cancel Button --}}
            <button @click="close()"
                type="button"
                class="px-6 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-400 dark:focus:ring-gray-500">
                <span x-text="cancelText"></span>
            </button>

            {{-- Confirm Button --}}
            <button @click="confirm()"
                type="button"
                class="px-6 py-2.5 text-sm font-semibold text-white rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 shadow-md"
                :class="{
                    'bg-red-600 hover:bg-red-700 dark:bg-red-600 dark:hover:bg-red-700 focus:ring-red-500': isDangerous,
                    'bg-orange-500 hover:bg-orange-600 dark:bg-orange-500 dark:hover:bg-orange-600 focus:ring-orange-400': !isDangerous && confirmColor === 'orange',
                    'bg-blue-600 hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-700 focus:ring-blue-500': !isDangerous && confirmColor !== 'orange'
                }">
                <span x-text="confirmText"></span>
            </button>
        </div>
    </div>
</div>
