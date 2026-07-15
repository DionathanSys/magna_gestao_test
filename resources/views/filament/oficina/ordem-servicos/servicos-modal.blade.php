<div class="space-y-3">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b text-left">
                <th class="py-2 pr-3">Código</th>
                <th class="py-2 pr-3">Serviço</th>
                <th class="py-2 pr-3">Posição</th>
                <th class="py-2 pr-3">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($ordemServico->itens as $item)
                <tr class="border-b">
                    <td class="py-2 pr-3">{{ $item->servico->codigo ?? '-' }}</td>
                    <td class="py-2 pr-3">{{ $item->servico->descricao ?? '-' }}</td>
                    <td class="py-2 pr-3">{{ $item->posicao ?: '-' }}</td>
                    <td class="py-2 pr-3">{{ $item->status?->value ?? $item->status ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="py-4 text-center text-gray-500">Nenhum serviço vinculado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
