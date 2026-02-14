@php
    use Filament\Notifications\Livewire\Notifications;
    use Filament\Support\Enums\Alignment;
    use Filament\Support\Enums\VerticalAlignment;
    use Illuminate\Support\Arr;

    $color = $getColor() ?? 'gray';
    $isInline = $isInline();
    $status = $getStatus();
    $title = $getTitle();
    $hasTitle = filled($title);
    $date = $getDate();
    $hasDate = filled($date);
    $body = $getBody();
    $hasBody = filled($body);

    // Extract the URL from the first action for card-level clickability
    $actions = $getActions();
    $primaryActionUrl = null;
    if (! empty($actions)) {
        $firstAction = $actions[0] ?? null;
        if ($firstAction && method_exists($firstAction, 'getUrl')) {
            $primaryActionUrl = $firstAction->getUrl();
        }
    }
@endphp

<x-filament-notifications::notification
    :notification="$notification"
    :x-transition:enter-start="
        Arr::toCssClasses([
            'opacity-0',
            ($this instanceof Notifications)
            ? match (static::$alignment) {
                Alignment::Start, Alignment::Left => '-translate-x-12',
                Alignment::End, Alignment::Right => 'translate-x-12',
                Alignment::Center => match (static::$verticalAlignment) {
                    VerticalAlignment::Start => '-translate-y-12',
                    VerticalAlignment::End => 'translate-y-12',
                    default => null,
                },
                default => null,
            }
            : null,
        ])
    "
    :x-transition:leave-end="
        Arr::toCssClasses([
            'opacity-0',
            'scale-95' => ! $isInline,
        ])
    "
    @class([
        'fi-no-notification w-full overflow-hidden transition duration-300',
        ...match ($isInline) {
            true => [
                'fi-inline',
            ],
            false => [
                'max-w-sm rounded-xl bg-white shadow-lg ring-1 dark:bg-gray-900',
                match ($color) {
                    'gray' => 'ring-gray-950/5 dark:ring-white/10',
                    default => 'fi-color-custom ring-custom-600/20 dark:ring-custom-400/30',
                },
                is_string($color) ? 'fi-color-' . $color : null,
                'fi-status-' . $status => $status,
            ],
        },
    ])
    @style([
        \Filament\Support\get_color_css_variables(
            $color,
            shades: [50, 400, 600],
            alias: 'notifications::notification',
        ) => ! ($isInline || $color === 'gray'),
    ])
>
    {{-- Clickable wrapper: navigates to primary action URL on card click --}}
    @if ($primaryActionUrl)
        <div
            x-on:click="
                if ($event.target.closest('button, a, .fi-no-notification-actions')) return;
                markAsRead();
                window.location.href = '{{ $primaryActionUrl }}';
            "
            class="cursor-pointer"
        >
    @endif

    <div
        @class([
            'flex w-full gap-3 p-4',
            'hover:bg-gray-50 dark:hover:bg-white/5 transition-colors' => $primaryActionUrl,
            match ($color) {
                'gray' => null,
                default => 'bg-custom-50 dark:bg-custom-400/10',
            },
        ])
    >
        @if ($icon = $getIcon())
            <x-filament-notifications::icon
                :color="$getIconColor()"
                :icon="$icon"
                :size="$getIconSize()"
            />
        @endif

        <div class="mt-0.5 grid flex-1">
            @if ($hasTitle)
                <x-filament-notifications::title>
                    {{ str($title)->sanitizeHtml()->toHtmlString() }}
                </x-filament-notifications::title>
            @endif

            @if ($hasDate)
                <x-filament-notifications::date @class(['mt-1' => $hasTitle])>
                    {{ $date }}
                </x-filament-notifications::date>
            @endif

            @if ($hasBody)
                <x-filament-notifications::body
                    @class(['mt-1' => $hasTitle || $hasDate])
                >
                    {{ str($body)->sanitizeHtml()->toHtmlString() }}
                </x-filament-notifications::body>
            @endif

            @if ($actions)
                <x-filament-notifications::actions
                    :actions="$actions"
                    @class(['mt-3' => $hasTitle || $hasDate || $hasBody])
                />
            @endif
        </div>

        <x-filament-notifications::close-button />
    </div>

    @if ($primaryActionUrl)
        </div>
    @endif
</x-filament-notifications::notification>
