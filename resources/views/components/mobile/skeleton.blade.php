@props([
    'lines' => 3,
])

<div {{ $attributes->class(['mb-skeleton-list']) }} aria-hidden="true">
    @for ($i = 0; $i < (int) $lines; $i++)
        <div
            class="mb-skeleton"
            style="height: 14px; width: {{ [100, 82, 64][$i % 3] }}%;"
        ></div>
    @endfor
</div>
