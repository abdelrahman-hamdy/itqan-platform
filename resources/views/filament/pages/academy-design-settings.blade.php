<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex justify-end gap-3">
            <x-filament::button type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove>حفظ التغييرات</span>
                <span wire:loading>جاري الحفظ...</span>
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
