{{-- Modern Chat Page - Clean Design --}}
<div class="h-full flex bg-white shadow-lg rounded-xl overflow-hidden border border-gray-200">
    {{-- Conversations Sidebar - Shows on desktop --}}
    <div class="hidden md:block w-96 lg:w-[28rem] shrink-0 border-l border-gray-200 bg-white">
        <livewire:wirechat.chats />
    </div>

    {{-- Chat Area - Takes remaining space beside sidebar --}}
    <main class="flex-1 flex flex-col bg-gradient-to-br from-gray-50 to-gray-100 min-w-0">
        <livewire:wirechat.chat conversation="{{ $this->conversation->id }}" />
    </main>
</div>
