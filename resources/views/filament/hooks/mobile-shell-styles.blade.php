<style>
    /* ============================================================
       Magna Mobile — componentes reutilizáveis
       Estilos injetados apenas no painel mobile via render hook.
       ============================================================ */

    /* ---- Conteúdo com espaço para a navegação inferior ---- */
    @media (max-width: 767px) {
        .fi-panel-mobile .fi-main {
            padding-bottom: calc(5.5rem + env(safe-area-inset-bottom));
        }
    }

    /* ============================================================
       Bottom Sheet
       ============================================================ */
    .mb-sheet-root {
        position: fixed;
        inset: 0;
        z-index: 60;
        pointer-events: none;
    }

    .mb-sheet-root.is-open {
        pointer-events: auto;
    }

    .mb-sheet-overlay {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.4);
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }

    .mb-sheet-overlay.is-visible {
        opacity: 1;
        visibility: visible;
    }

    .mb-sheet-panel {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        margin-inline: auto;
        width: 100%;
        max-width: 480px;
        display: flex;
        flex-direction: column;
        background: #fff;
        border-top: 1px solid #e2e8f0;
        border-radius: 28px 28px 0 0;
        box-shadow: 0 -12px 40px rgba(15, 23, 42, 0.16);
        overflow: hidden;
        overscroll-behavior: contain;
        transform: translateY(100%);
        transition: transform 0.28s cubic-bezier(0.22, 0.8, 0.24, 1),
            translate 0.28s cubic-bezier(0.22, 0.8, 0.24, 1);
        will-change: transform;
    }

    .mb-sheet-panel.is-open {
        transform: translateY(0);
    }

    .mb-sheet-panel.is-dragging {
        transition: none;
    }

    .mb-sheet-handle {
        flex: none;
        align-self: center;
        width: 40px;
        height: 4px;
        margin: 10px auto 2px;
        border-radius: 9999px;
        background: #cbd5e1;
        cursor: grab;
        touch-action: none;
        user-select: none;
    }

    .mb-sheet-handle-static {
        cursor: default;
    }

    .mb-sheet-header {
        flex: none;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.25rem 1.25rem 0.75rem;
    }

    .mb-sheet-title {
        font-size: 1.02rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.25;
    }

    .mb-sheet-close {
        flex: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        border-radius: 9999px;
        background: #f1f5f9;
        color: #475569;
        border: 0;
        cursor: pointer;
        -webkit-tap-highlight-color: transparent;
    }

    .mb-sheet-close:active {
        background: #e2e8f0;
    }

    .mb-sheet-content {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
        padding: 0 1.25rem 1.25rem;
    }

    .mb-sheet-footer {
        flex: none;
        border-top: 1px solid #f1f5f9;
        padding: 0.9rem 1.25rem calc(0.9rem + env(safe-area-inset-bottom));
    }

    /* ---- Detalhes exibidos dentro do sheet ---- */
    .mb-detail-hero {
        border-radius: 16px;
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        color: #fff;
        padding: 1rem 1.1rem;
        margin-bottom: 0.9rem;
    }

    .mb-detail-value {
        font-size: 1.45rem;
        font-weight: 850;
        line-height: 1.1;
    }

    .mb-detail-caption {
        margin-top: 0.2rem;
        font-size: 0.68rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: rgba(255, 255, 255, 0.66);
    }

    .mb-detail-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.5rem;
        margin-bottom: 0.9rem;
    }

    .mb-detail-item {
        border-radius: 12px;
        background: #f8fafc;
        border: 1px solid #f1f5f9;
        padding: 0.7rem 0.8rem;
    }

    .mb-detail-label {
        display: block;
        font-size: 0.66rem;
        color: #64748b;
    }

    .mb-detail-value2 {
        display: block;
        margin-top: 0.12rem;
        font-size: 0.88rem;
        font-weight: 750;
        color: #0f172a;
        overflow-wrap: anywhere;
    }

    .mb-detail-section {
        border-radius: 12px;
        background: #f8fafc;
        border: 1px solid #f1f5f9;
        padding: 0.75rem 0.8rem;
        margin-bottom: 0.55rem;
    }

    .mb-detail-route {
        margin-top: 0.12rem;
        font-size: 0.86rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.35;
    }

    /* ============================================================
       Record card (item de listagem)
       ============================================================ */
    .mb-record-card {
        display: block;
        width: 100%;
        text-align: left;
        border-radius: 16px;
        background: #fff;
        border: 1px solid #f1f5f9;
        padding: 1rem;
        box-shadow: 0 1px 4px rgba(15, 23, 42, 0.08);
        text-decoration: none;
        color: inherit;
        cursor: pointer;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        -webkit-tap-highlight-color: transparent;
    }

    .mb-record-card:hover {
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.12);
    }

    .mb-record-card:active {
        transform: scale(0.985);
    }

    .mb-record-card-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .mb-record-card-head {
        min-width: 0;
    }

    .mb-record-card-title {
        font-size: 1rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
        overflow-wrap: anywhere;
    }

    .mb-record-card-subtitle {
        margin-top: 0.18rem;
        font-size: 0.78rem;
        color: #64748b;
        overflow-wrap: anywhere;
    }

    .mb-record-card-badge {
        flex: none;
    }

    .mb-record-card-meta {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.45rem;
        margin-top: 0.85rem;
    }

    .mb-record-card-meta-item {
        border-radius: 0.75rem;
        background: #f8fafc;
        padding: 0.55rem 0.65rem;
    }

    .mb-record-card-meta-label {
        display: block;
        font-size: 0.66rem;
        color: #64748b;
    }

    .mb-record-card-meta-value {
        display: block;
        margin-top: 0.12rem;
        font-size: 0.8rem;
        font-weight: 750;
        color: #0f172a;
        overflow-wrap: anywhere;
    }

    .mb-record-card-footer {
        margin-top: 0.8rem;
        display: flex;
        gap: 0.35rem;
        flex-wrap: wrap;
        align-items: center;
    }

    .mb-record-card-route {
        border-left: 3px solid #111827;
        padding-left: 0.65rem;
        font-size: 0.8rem;
        font-weight: 700;
        color: #334155;
        line-height: 1.3;
        margin-top: 0.75rem;
    }

    /* ============================================================
       Skeleton
       ============================================================ */
    .mb-skeleton-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .mb-skeleton {
        border-radius: 0.6rem;
        background: #e2e8f0;
        animation: mb-skeleton-pulse 1.6s ease-in-out infinite;
    }

    @keyframes mb-skeleton-pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.45;
        }
    }

    /* ============================================================
       Navegação inferior
       ============================================================ */
    .mb-bottom-nav {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 40;
        background: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(10px);
        border-top: 1px solid #e2e8f0;
        padding-bottom: env(safe-area-inset-bottom);
        box-shadow: 0 -4px 20px rgba(15, 23, 42, 0.06);
    }

    .mb-bottom-nav-inner {
        display: flex;
        height: 60px;
        max-width: 480px;
        margin-inline: auto;
    }

    .mb-bottom-nav-item {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 2px;
        min-height: 44px;
        font-size: 0.64rem;
        font-weight: 700;
        color: #64748b;
        text-decoration: none;
        -webkit-tap-highlight-color: transparent;
    }

    .mb-bottom-nav-item.is-active {
        color: #0f172a;
    }

    .mb-bottom-nav-icon {
        width: 24px;
        height: 24px;
    }

    @media (min-width: 768px) {
        .mb-bottom-nav {
            display: none;
        }
    }
</style>
