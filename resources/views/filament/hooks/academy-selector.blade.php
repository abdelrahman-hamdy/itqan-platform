@if(auth()->check() && auth()->user()->isSuperAdmin())
    <div class="flex items-center ms-4">
        @livewire('academy-selector')
    </div>
@endif 