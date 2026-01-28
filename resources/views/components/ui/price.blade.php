@props([
    'amount' => 0,
    'currency' => null,
    'academy' => null,
    'showDecimals' => true,
    'symbolFirst' => false,
    'class' => '',
    'useCode' => false,
])

@php
    $formattedPrice = $useCode
        ? formatPriceWithCode($amount, $currency, $academy, $showDecimals)
        : formatPrice($amount, $currency, $academy, $showDecimals, $symbolFirst);
@endphp

<span {{ $attributes->merge(['class' => $class]) }}>
    {{ $formattedPrice }}
</span>
