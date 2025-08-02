@if(auth()->check() && auth()->user()->isSuperAdmin())
    <div class="flex items-center">
        @livewire('academy-selector')
    </div>
@endif 