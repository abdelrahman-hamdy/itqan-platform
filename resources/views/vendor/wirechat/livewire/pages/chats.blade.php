{{-- Modern Chats Page - Clean Design --}}
<div class="h-full flex bg-white shadow-lg rounded-xl overflow-hidden border border-gray-200">
    {{-- Conversations Sidebar --}}
    <div class="w-full md:w-96 lg:w-[28rem] shrink-0 border-l border-gray-200 bg-white">
        <livewire:wirechat.chats />
    </div>

    {{-- Welcome/Empty State - Shows beside the sidebar on desktop --}}
    <main class="hidden md:flex flex-1 items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100">
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
                ابدأ محادثة جديدة أو اختر محادثة موجودة من القائمة
            </p>
        </div>
    </main>
</div>
