{{-- Modern Chat Page - Clean Design --}}
<div class="w-full h-full flex bg-white shadow-sm rounded-lg overflow-hidden">
    {{-- Conversations Sidebar - Hidden on Mobile when Chat is Open --}}
    <div class="hidden md:flex w-full md:w-96 lg:w-[28rem] shrink-0 border-l border-gray-200 bg-white flex-col">
        <livewire:wirechat.chats />
    </div>

    {{-- Chat Area --}}
    <main class="flex flex-col flex-1 bg-gray-50 relative">
        <livewire:wirechat.chat conversation="{{ $this->conversation->id }}" />
    </main>
</div>
