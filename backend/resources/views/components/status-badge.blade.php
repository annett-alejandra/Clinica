@props([
    'type' => 'stable', // stable, critical, caution, info, accent
    'label' => null,
])

@php
    $typeClasses = [
        'stable' => 'badge-stable',
        'critical' => 'badge-critical',
        'caution' => 'badge-caution',
        'info' => 'badge-info',
        'accent' => 'badge-accent',
    ][$type] ?? 'badge-stable';
@endphp

<span {{ $attributes->merge(['class' => 'status-badge ' . $typeClasses]) }}>
    <span class="status-badge-dot" aria-hidden="true"></span>
    <span>{{ $label ?? $slot }}</span>
</span>

