<script>
    window.addEventListener('filament-close-action-group', () => {
        // força fechamento simulando click fora
        document.body.click()
    })
</script>

<script>
document.addEventListener('livewire:init', () => {
    let loadingToast = null;
    
    Livewire.hook('commit', ({ succeed, fail }) => {
        // Remove toast anterior se existir
        if (loadingToast && document.body.contains(loadingToast)) {
            loadingToast.remove();
        }
        
        // Cria o toast de loading
        loadingToast = document.createElement('div');
        loadingToast.id = 'loading-toast';
        loadingToast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #f59e0b;
            color: white;
            padding: 16px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 9999;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease-out;
        `;
        
        loadingToast.innerHTML = `
            <svg class="animate-spin" style="width: 20px; height: 20px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Carregando dados...</span>
        `;
        
        // Adiciona animação de spin
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
            .animate-spin {
                animation: spin 1s linear infinite;
            }
        `;
        if (!document.getElementById('loading-toast-styles')) {
            style.id = 'loading-toast-styles';
            document.head.appendChild(style);
        }
        
        document.body.appendChild(loadingToast);
        
        // Remove o toast quando carregar com sucesso
        succeed(() => {
            if (loadingToast && document.body.contains(loadingToast)) {
                setTimeout(() => {
                    loadingToast.style.transition = 'opacity 0.3s, transform 0.3s';
                    loadingToast.style.opacity = '0';
                    loadingToast.style.transform = 'translateX(100%)';
                    setTimeout(() => {
                        if (loadingToast && document.body.contains(loadingToast)) {
                            loadingToast.remove();
                        }
                    }, 300);
                }, 300);
            }
        });
        
        // Remove o toast em caso de erro
        fail(() => {
            if (loadingToast && document.body.contains(loadingToast)) {
                loadingToast.remove();
            }
        });
    });
});
</script>
