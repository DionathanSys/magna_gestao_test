<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register("{{ asset('sw.js') }}", {
                scope: '/',
            }).catch(() => {
            });
        });
    }
</script>
