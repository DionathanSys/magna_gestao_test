<div class="space-y-3">
    @if ($ordens->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Nenhuma ordem pendente ou em execução encontrada.
        </p>
    @else
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">OS</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Veículo</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Status</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Abertura</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Fornecedor</th>
                        <th class="px-3 py-2 text-right font-semibold text-gray-700 dark:text-gray-200">Ação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                    @foreach ($ordens as $ordem)
                        <tr>
                            <td class="whitespace-nowrap px-3 py-2 font-medium text-gray-900 dark:text-white">
                                #{{ $ordem->id }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 text-gray-700 dark:text-gray-300">
                                {{ $ordem->veiculo?->placa ?? 'N/A' }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 text-gray-700 dark:text-gray-300">
                                {{ $ordem->status?->value ?? $ordem->status }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 text-gray-700 dark:text-gray-300">
                                {{ $ordem->data_inicio?->format('d/m/Y') ?? 'N/A' }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 text-gray-700 dark:text-gray-300">
                                {{ $ordem->parceiro?->nome ?? 'N/A' }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 text-right">
                                <a
                                    href="{{ \App\Filament\Resources\OrdemServicos\OrdemServicoResource::getUrl('custom', ['record' => $ordem->id]) }}"
                                    class="text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
                                >
                                    Abrir
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
