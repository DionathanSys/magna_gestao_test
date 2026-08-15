@props([
    'click' => null,
    'href' => null,
    'active' => false,
])

@php
    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if ($href)
        href="{{ $href }}"
    @else
        type="button"
    @endif
    @if ($click)
        x-on:click="{{ $click }}"
    @endif
    {{ $attributes->class([
        'mb-record-card',
        'is-active' => $active,
    ]) }}
>
    <div class="mb-record-card-top">
        <div class="mb-record-card-head">
            @if (isset($title))
                <div class="mb-record-card-title">{{ $title }}</div>
            @endif
            @if (isset($subtitle))
                <div class="mb-record-card-subtitle">{{ $subtitle }}</div>
            @endif
        </div>

        @if (isset($badge))
            <div class="mb-record-card-badge">{{ $badge }}</div>
        @endif
    </div>

    @if (isset($meta))
        <div class="mb-record-card-meta">{{ $meta }}</div>
    @endif

    @if (isset($footer))
        <div class="mb-record-card-footer">{{ $footer }}</div>
    @endif
</{{ $tag }}>
