const registerBottomSheet = () => {
    if (typeof window.Alpine === 'undefined') {
        return
    }

    window.Alpine.data('bottomSheet', (config = {}) => ({
        open: config.open ?? false,
        dragging: false,
        startY: 0,
        offsetY: 0,
        dragVelocity: 0,
        lastDragY: 0,
        dragLastTime: 0,

        name: config.name ?? null,
        height: config.height ?? 60,
        maxHeight: config.maxHeight ?? '92dvh',
        closeOnOverlay: config.closeOnOverlay ?? true,
        closeOnEscape: config.closeOnEscape ?? true,
        draggable: config.draggable ?? true,

        threshold: 120,
        velocityThreshold: 0.8,

        get panelStyle() {
            let style = `height: ${this.height}dvh; max-height: ${this.maxHeight};`

            if (this.dragging) {
                style += ` translate: 0 ${this.offsetY}px;`
            }

            return style
        },

        init() {
            this.onOpenEvent = (event) => {
                const detail = event.detail ?? {}
                const eventName = detail.name ?? null

                if (this.name === null || eventName === null || this.name === eventName) {
                    this.show()
                }
            }

            this.onCloseEvent = (event) => {
                const detail = event.detail ?? {}
                const eventName = detail.name ?? null

                if (this.name === null || eventName === null || this.name === eventName) {
                    this.hide()
                }
            }

            this.onKeydown = (event) => {
                if (event.key === 'Escape' && this.closeOnEscape) {
                    this.hide()
                }
            }

            this.onWindowPointerMove = (event) => {
                if (!this.dragging) {
                    return
                }

                const now = performance.now()
                const elapsed = now - this.dragLastTime

                if (elapsed > 0) {
                    this.dragVelocity = (event.clientY - this.lastDragY) / elapsed
                }

                this.lastDragY = event.clientY
                this.dragLastTime = now
                this.offsetY = Math.max(0, event.clientY - this.startY)
            }

            this.onWindowPointerUp = () => {
                if (!this.dragging) {
                    return
                }

                const shouldClose = this.offsetY > this.threshold || this.dragVelocity > this.velocityThreshold

                this.dragging = false
                this.offsetY = 0

                if (shouldClose) {
                    this.hide()
                }
            }

            window.addEventListener('open-bottom-sheet', this.onOpenEvent)
            window.addEventListener('close-bottom-sheet', this.onCloseEvent)

            if (this.closeOnEscape) {
                document.addEventListener('keydown', this.onKeydown)
            }

            window.addEventListener('pointermove', this.onWindowPointerMove)
            window.addEventListener('pointerup', this.onWindowPointerUp)
            window.addEventListener('pointercancel', this.onWindowPointerUp)
        },

        destroy() {
            window.removeEventListener('open-bottom-sheet', this.onOpenEvent)
            window.removeEventListener('close-bottom-sheet', this.onCloseEvent)
            document.removeEventListener('keydown', this.onKeydown)
            window.removeEventListener('pointermove', this.onWindowPointerMove)
            window.removeEventListener('pointerup', this.onWindowPointerUp)
            window.removeEventListener('pointercancel', this.onWindowPointerUp)

            document.body.style.overflow = ''
        },

        show() {
            this.open = true
            document.body.style.overflow = 'hidden'

            this.$nextTick(() => {
                this.$refs.closeButton?.focus?.()
            })
        },

        hide() {
            if (!this.open) {
                return
            }

            this.dragging = false
            this.offsetY = 0
            this.open = false
            document.body.style.overflow = ''
        },

        startDrag(event) {
            if (!this.draggable || !this.open) {
                return
            }

            if (event.cancelable) {
                event.preventDefault()
            }

            this.dragging = true
            this.startY = event.clientY
            this.offsetY = 0
            this.dragVelocity = 0
            this.lastDragY = event.clientY
            this.dragLastTime = performance.now()
        },
    }))
}

if (typeof window.Alpine !== 'undefined') {
    registerBottomSheet()
} else {
    document.addEventListener('alpine:init', registerBottomSheet, { once: true })
}
