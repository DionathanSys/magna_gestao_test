<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}

        <div class="flex gap-3">
            <x-filament::button
                wire:click="carregarDados"
                icon="heroicon-o-magnifying-glass"
                color="success"
            >
                Visualizar em Tela
            </x-filament::button>

            <x-filament::button
                wire:click="gerarRelatorio"
                icon="heroicon-o-arrow-down-tray"
            >
                Baixar PDF
            </x-filament::button>
        </div>

        @if(count($dadosRelatorio) > 0)
            <x-filament::section>
                <x-slot name="heading">
                    Resultados do Relatório
                </x-slot>

                <x-slot name="description">
                    Total de {{ count($dadosRelatorio) }} registro(s) encontrado(s)
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Placa
                            </th>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Plano Preventivo
                            </th>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Periodicidade<br>(km)
                            </th>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                KM Atual
                            </th>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                KM Última<br>Execução
                            </th>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Data Última<br>Execução
                            </th>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Próxima<br>Execução (km)
                            </th>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                KM<br>Restante
                            </th>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                KM Médio<br>Diário
                            </th>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Data Prevista<br>Próxima Exec.
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($dadosRelatorio as $item)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-3 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $item['placa'] }}
                                </td>
                                <td class="px-3 py-4 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $item['plano_descricao'] }}
                                </td>
                                <td class="px-3 py-4 whitespace-nowrap text-sm text-center text-gray-700 dark:text-gray-300">
                                    {{ number_format($item['periodicidade'], 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-4 whitespace-nowrap text-sm text-center text-gray-700 dark:text-gray-300">
                                    {{ number_format($item['km_atual'], 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-4 whitespace-nowrap text-sm text-center text-gray-700 dark:text-gray-300">
                                    @if($item['km_ultima_execucao'] > 0)
                                        {{ number_format($item['km_ultima_execucao'], 0, ',', '.') }}
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-3 py-4 whitespace-nowrap text-sm text-center text-gray-700 dark:text-gray-300">
                                    @if($item['data_ultima_execucao'])
                                        {{ date('d/m/Y', strtotime($item['data_ultima_execucao'])) }}
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-3 py-4 whitespace-nowrap text-sm text-center text-gray-700 dark:text-gray-300">
                                    {{ number_format($item['proxima_execucao'], 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-4 whitespace-nowrap text-sm text-center font-semibold
                                    @if($item['km_restante'] <= 0)
                                        text-red-600 dark:text-red-400
                                    @elseif($item['km_restante'] <= 1000)
                                        text-yellow-600 dark:text-yellow-400
                                    @else
                                        text-green-600 dark:text-green-400
                                    @endif
                                ">
                                    {{ number_format($item['km_restante'], 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-4 whitespace-nowrap text-sm text-center text-gray-700 dark:text-gray-300">
                                    @if($item['km_medio_diario'] > 0)
                                        {{ number_format($item['km_medio_diario'], 1, ',', '.') }}
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-3 py-4 whitespace-nowrap text-sm text-center
                                    @if($item['data_prevista'] === 'Atrasado')
                                        font-semibold text-red-600 dark:text-red-400
                                    @else
                                        text-gray-700 dark:text-gray-300
                                    @endif
                                ">
                                    @if($item['data_prevista'] === 'Atrasado')
                                        Atrasado
                                    @elseif($item['data_prevista'])
                                        {{ date('d/m/Y', strtotime($item['data_prevista'])) }}
                                    @else
                                        <span class="text-gray-400">N/D</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @elseif(isset($dadosRelatorio) && count($dadosRelatorio) === 0 && $this->data)
            <x-filament::section>
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Nenhum registro encontrado</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tente ajustar os filtros e pesquisar novamente.</p>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
