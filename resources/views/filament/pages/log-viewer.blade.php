<x-filament-panels::page>
    {{-- Sentry Widget is rendered via getHeaderWidgets() --}}

    {{-- OPcodes Log Viewer (Embedded) --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-document-text class="w-5 h-5 text-blue-500" />
                <span>{{ __('سجلات الخادم') }}</span>
            </div>
        </x-slot>

        <x-slot name="headerEnd">
            <x-filament::icon-button
                icon="heroicon-m-arrow-top-right-on-square"
                tag="a"
                href="/log-viewer"
                target="_blank"
                color="gray"
                size="sm"
                :tooltip="__('فتح في نافذة جديدة')"
            />
        </x-slot>

        <div class="-m-6">
            <iframe
                src="/log-viewer"
                class="w-full border-0"
                style="height: 700px;"
                title="Log Viewer"
            ></iframe>
        </div>
    </x-filament::section>
</x-filament-panels::page>
