<script>
    document.addEventListener('alpine:init', () => {
        window.addEventListener('filament-close-action-group', () => {
            document
                .querySelectorAll('[x-data*="isOpen"]')
                .forEach(el => {
                    if (el.__x?.$data?.isOpen !== undefined) {
                        el.__x.$data.isOpen = false;
                    }
                });
        });
    });
</script>
