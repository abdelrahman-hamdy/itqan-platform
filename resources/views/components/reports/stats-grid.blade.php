@props([
    'stats' => [], // Array of StatDTO objects or arrays
    'columns' => 4,
])

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-{{ $columns }} gap-6 mb-6">
    @foreach($stats as $stat)
        @php
            // Support both DTO objects and arrays
            $label = is_object($stat) ? $stat->label : ($stat['label'] ?? '');
            $value = is_object($stat) ? $stat->value : ($stat['value'] ?? '');
            $color = is_object($stat) ? $stat->color : ($stat['color'] ?? 'blue');
            $icon = is_object($stat) ? $stat->icon : ($stat['icon'] ?? 'ri-star-line');
            $trend = is_object($stat) ? $stat->trend : ($stat['trend'] ?? null);
            $trendValue = is_object($stat) ? $stat->trendValue : ($stat['trend_value'] ?? null);
        @endphp

        <x-ui.stat-card
            :label="$label"
            :value="$value"
            :color="$color"
            :icon="$icon"
            :trend="$trend"
            :trendValue="$trendValue"
        />
    @endforeach
</div>
