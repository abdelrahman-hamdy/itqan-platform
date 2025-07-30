@if(auth()->check() && auth()->user()->role === 'super_admin')
    <div class="flex items-center gap-4 mr-4">
        @livewire('academy-selector')
    </div>
@endif 