<script data-navigate-once>
    const registerBottomSheetFallback = () => {
        if (typeof window.Alpine === 'undefined') {
            return;
        }

        window.Alpine.data('bottomSheet', (config = {}) => ({
            open: config.open ?? false,
            name: config.name ?? null,
            height: config.height ?? 60,
            maxHeight: config.maxHeight ?? '92dvh',
            closeOnEscape: config.closeOnEscape ?? true,

            get panelStyle() {
                return `height: ${this.height}dvh; max-height: ${this.maxHeight};`;
            },

            init() {
                this.onOpen = (event) => {
                    if (!event.detail?.name || event.detail.name === this.name) {
                        this.show();
                    }
                };

                this.onClose = (event) => {
                    if (!event.detail?.name || event.detail.name === this.name) {
                        this.hide();
                    }
                };

                this.onKeydown = (event) => {
                    if (event.key === 'Escape' && this.closeOnEscape) {
                        this.hide();
                    }
                };

                window.addEventListener('open-bottom-sheet', this.onOpen);
                window.addEventListener('close-bottom-sheet', this.onClose);
                document.addEventListener('keydown', this.onKeydown);
            },

            destroy() {
                window.removeEventListener('open-bottom-sheet', this.onOpen);
                window.removeEventListener('close-bottom-sheet', this.onClose);
                document.removeEventListener('keydown', this.onKeydown);
                document.body.style.overflow = '';
            },

            show() {
                this.open = true;
                document.body.style.overflow = 'hidden';
                this.$nextTick(() => this.$refs.closeButton?.focus?.());
            },

            hide() {
                this.open = false;
                document.body.style.overflow = '';
            },

            startDrag() {},
        }));
    };

    if (typeof window.Alpine !== 'undefined') {
        registerBottomSheetFallback();
    } else {
        document.addEventListener('alpine:init', registerBottomSheetFallback, { once: true });
    }
</script>

@vite(['resources/js/app.js'])
