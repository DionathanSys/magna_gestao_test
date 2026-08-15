@props([
    'name' => null,
    'height' => 60,
    'maxHeight' => null,
    'closeOnOverlay' => true,
    'closeOnEscape' => true,
    'draggable' => true,
    'showHandle' => true,
    'openState' => 'false',
])

<div
    x-data="bottomSheet({
        name: @js($name),
        height: {{ (int) $height }},
        maxHeight: @js($maxHeight ?? '92dvh'),
        closeOnOverlay: {{ $closeOnOverlay ? 'true' : 'false' }},
        closeOnEscape: {{ $closeOnEscape ? 'true' : 'false' }},
        draggable: {{ $draggable ? 'true' : 'false' }},
        open: {{ $openState }},
    })"
    role="dialog"
    aria-modal="true"
    :aria-hidden="(!open).toString()"
    :class="{ 'is-open': open }"
    class="mb-sheet-root"
>
    <div
        class="mb-sheet-overlay"
        :class="{ 'is-visible': open }"
        x-on:click="closeOnOverlay && hide()"
        aria-hidden="true"
    ></div>

    <div
        class="mb-sheet-panel"
        :class="{ 'is-open': open, 'is-dragging': dragging }"
        :style="panelStyle"
    >
        @if ($showHandle && $draggable)
            <div class="mb-sheet-handle" x-on:pointerdown="startDrag" role="presentation" aria-hidden="true"></div>
        @elseif ($showHandle)
            <div class="mb-sheet-handle mb-sheet-handle-static" aria-hidden="true"></div>
        @endif

        @if (isset($header))
            <div class="mb-sheet-header">{{ $header }}</div>
        @endif

        <div class="mb-sheet-content">
            {{ $content ?? ($slot ?? '') }}
        </div>

        @if (isset($footer))
            <div class="mb-sheet-footer">{{ $footer }}</div>
        @endif
    </div>
</div>
