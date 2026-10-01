@props(['totals' => [], 'sign' => '', 'empty' => '$0'])
{{-- One line per currency; dollars and dinars are never added together. --}}
<div {{ $attributes->merge(['class' => 'font-mono leading-tight']) }} dir="ltr">
    @forelse($totals as $code => $value)
        <div>{{ $sign }}{{ \App\Support\Money::format($value, $code) }}</div>
    @empty
        <div>{{ $empty }}</div>
    @endforelse
</div>
