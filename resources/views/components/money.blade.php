@props([
    'amount' => 0,
])

<span {{ $attributes->merge(['class' => 'tabular-nums']) }}>{{ \App\Support\Money::format($amount) }}</span>
