<x-filament-panels::page>
    <div class="space-y-5">
        {{ $this->form }}

        <div class="flex flex-wrap gap-3">
            <x-filament::button
                wire:click="carregarDados"
                icon="heroicon-o-magnifying-glass"
                color="success"
            >
                Consultar
            </x-filament::button>

            <x-filament::button
                wire:click="gerarPdf"
                icon="heroicon-o-arrow-down-tray"
            >
                Baixar PDF
            </x-filament::button>
        </div>

        @if ($buscaRealizada)
            <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Veículo</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Plano preventivo</th>
                            <th class="whitespace-nowrap px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">KM</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Data</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Origem</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">OS</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Status da OS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse (collect($dadosRelatorio)->groupBy('plano_preventivo_id') as $execucoes)
                            @php($plano = $execucoes->first())
                            <tr class="bg-gray-100 dark:bg-white/10">
                                <td colspan="7" class="px-4 py-3 font-semibold text-gray-900 dark:text-gray-100">
                                    Plano: {{ $plano['plano_descricao'] ?? 'Plano não informado' }}
                                    <span class="ml-2 font-normal text-gray-500 dark:text-gray-400">({{ $execucoes->count() }} execução(ões))</span>
                                </td>
                            </tr>
                            @foreach ($execucoes as $item)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-900 dark:text-gray-100">{{ $item['placa'] ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ $item['plano_descricao'] ?? 'Plano não informado' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-gray-900 dark:text-gray-100">{{ number_format($item['km_execucao'] ?? 0, 0, ',', '.') }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-700 dark:text-gray-300">
                                        {{ $item['data_execucao'] ? \Illuminate\Support\Carbon::parse($item['data_execucao'])->format('d/m/Y') : '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-700 dark:text-gray-300">{{ $item['origem'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-700 dark:text-gray-300">
                                        {{ $item['ordem_servico_id'] ? '#'.$item['ordem_servico_id'] : '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-700 dark:text-gray-300">{{ $item['ordem_servico_status'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Nenhuma execução encontrada com os filtros informados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-filament-panels::page>
