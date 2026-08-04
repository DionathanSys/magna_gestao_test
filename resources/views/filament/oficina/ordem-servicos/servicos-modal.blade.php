<div class="space-y-3">
    @forelse ($ordemServico->itens as $item)
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:border-white/10 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0 space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-md bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700 dark:bg-white/10 dark:text-gray-200">
                            {{ $item->servico->codigo ?? 'Sem código' }}
                        </span>
                        <span class="rounded-md border border-gray-200 px-2 py-1 text-xs font-medium text-gray-600 dark:border-white/10 dark:text-gray-300">
                            {{ $item->posicao ?: 'Sem posição' }}
                        </span>
                    </div>

                    <div class="text-base font-semibold text-gray-950 dark:text-white">
                        {{ $item->servico->descricao ?? 'Serviço não informado' }}
                    </div>
                </div>

                <div class="shrink-0 rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700 dark:bg-primary-500/10 dark:text-primary-300">
                    {{ $item->status?->value ?? $item->status ?? '-' }}
                </div>
            </div>
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
            Nenhum serviço vinculado.
        </div>
    @endforelse
</div>
