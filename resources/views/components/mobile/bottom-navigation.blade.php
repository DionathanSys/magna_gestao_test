@props([
    'items' => [],
    'homeUrl' => null,
])

@php
    $current = rtrim(request()->url(), '/');
    $home = rtrim($homeUrl ?? url('/mobile'), '/');
@endphp

<nav class="mb-bottom-nav" aria-label="Navegação inferior">
    <div class="mb-bottom-nav-inner">
        @foreach ($items as $item)
            @php
                $itemUrl = rtrim($item['url'] ?? '#', '/');
                $isActive = $current === $itemUrl || ($itemUrl !== $home && str_starts_with($current, $itemUrl));
            @endphp

            <a
                href="{{ $item['url'] }}"
                class="mb-bottom-nav-item {{ $isActive ? 'is-active' : '' }}"
                aria-current="{{ $isActive ? 'page' : 'false' }}"
            >
                @if (($item['icon'] ?? null) !== null)
                    <x-filament::icon :icon="$item['icon']" class="mb-bottom-nav-icon" />
                @endif
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>
