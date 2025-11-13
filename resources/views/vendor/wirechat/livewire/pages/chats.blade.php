{{-- Modern Chats Page - Clean Design --}}
<div class="w-full h-full flex bg-white shadow-sm rounded-lg overflow-hidden">
    {{-- Conversations Sidebar --}}
    <div class="w-full md:w-96 lg:w-[28rem] shrink-0 border-l border-gray-200 bg-white flex flex-col">
        <livewire:wirechat.chats />
    </div>

    {{-- Welcome/Empty State --}}
    <main class="hidden md:flex flex-col items-center justify-center flex-1 bg-gray-50">
        <div class="text-center space-y-4 max-w-md px-4">
            {{-- Icon --}}
            <div class="w-24 h-24 mx-auto bg-primary/10 rounded-full flex items-center justify-center">
                <i class="ri-message-3-line text-5xl text-primary"></i>
            </div>

            {{-- Welcome Message --}}
            <h2 class="text-2xl font-bold text-gray-900">
                @lang('wirechat::pages.chat.messages.welcome')
            </h2>
            <p class="text-gray-600 text-lg">
                اختر محادثة من القائمة للبدء في المراسلة
            </p>

            {{-- Features List --}}
            <div class="grid grid-cols-2 gap-4 pt-6 text-right">
                <div class="flex items-start space-x-3 space-x-reverse">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center shrink-0">
                        <i class="ri-check-double-line text-lg text-green-600"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">رسائل فورية</p>
                        <p class="text-xs text-gray-500">استلام وإرسال فوري</p>
                    </div>
                </div>
                <div class="flex items-start space-x-3 space-x-reverse">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center shrink-0">
                        <i class="ri-file-line text-lg text-blue-600"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">مشاركة الملفات</p>
                        <p class="text-xs text-gray-500">صور ومستندات</p>
                    </div>
                </div>
                <div class="flex items-start space-x-3 space-x-reverse">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center shrink-0">
                        <i class="ri-group-line text-lg text-purple-600"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">محادثات جماعية</p>
                        <p class="text-xs text-gray-500">تواصل مع عدة أشخاص</p>
                    </div>
                </div>
                <div class="flex items-start space-x-3 space-x-reverse">
                    <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center shrink-0">
                        <i class="ri-lock-line text-lg text-orange-600"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">آمن ومشفر</p>
                        <p class="text-xs text-gray-500">خصوصية تامة</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
