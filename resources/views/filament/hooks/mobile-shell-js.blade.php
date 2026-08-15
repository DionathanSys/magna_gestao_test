<script data-navigate-once>
    // Fallback mínimo: evita quebrar o Alpine da página caso o bundle
    // `resources/js/mobile/bottom-sheet.js` não tenha carregado.
    // A definição completa (com drag) registrada pelo bundle sobrescreve esta.
    document.addEventListener('alpine:init', () => {
        if (typeof window.Alpine === 'undefined') {
            return;
        }

        window.Alpine.data('bottomSheet', () => ({ open: false }));
    });
</script>

@vite(['resources/js/mobile/bottom-sheet.js'])
