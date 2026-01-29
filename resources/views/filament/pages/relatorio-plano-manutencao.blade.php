<x-filament-panels::page>
    <style>
        .relatorio-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .relatorio-table thead {
            background-color: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
        }
        .relatorio-table th {
            padding: 12px 8px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .relatorio-table th.text-center {
            text-align: center;
        }
        .relatorio-table th.sortable {
            cursor: pointer;
            user-select: none;
            position: relative;
            padding-right: 24px;
        }
        .relatorio-table th.sortable:hover {
            background-color: #f3f4f6;
        }
        .relatorio-table th.sortable .sort-icon {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 14px;
            color: #9ca3af;
        }
        .relatorio-table th.sortable.active .sort-icon {
            color: #4b5563;
        }
        .relatorio-table td {
            padding: 12px 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
            color: #374151;
        }
        .relatorio-table tbody tr:hover {
            background-color: #f9fafb;
        }
        .relatorio-table td.text-center {
            text-align: center;
        }
        .relatorio-table td.font-medium {
            font-weight: 500;
            color: #111827;
        }
        .relatorio-table td.text-gray {
            color: #9ca3af;
        }
        .relatorio-table td.status-ok {
            color: #059669;
            font-weight: 600;
        }
        .relatorio-table td.status-warning {
            color: #d97706;
            font-weight: 600;
        }
        .relatorio-table td.status-danger {
            color: #dc2626;
            font-weight: 600;
        }
        .section-box {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 20px;
            margin-top: 20px;
        }
        .section-heading {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 5px;
        }
        .section-description {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 15px;
        }
        .table-container {
            overflow-x: auto;
        }
        .buttons-container {
            display: flex;
            gap: 12px;
        }
    </style>

    <div>
        {{ $this->form }}

        <div class="buttons-container" style="margin-top: 20px;">
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

        @if(!empty($dadosRelatorio) && is_array($dadosRelatorio) && count($dadosRelatorio) > 0)
            <div class="section-box">
                <div class="section-heading">
                    Resultados do Relatório
                </div>
                <div class="section-description">
                    Total de {{ count($dadosRelatorio) }} registro(s) encontrado(s)
                </div>

                <div class="table-container">
                    <table class="relatorio-table">
                        <thead>
                        <tr>
                            <th>Placa</th>
                            <th>Plano Preventivo</th>
                            <th class="text-center">Periodicidade<br>(km)</th>
                            <th class="text-center">KM Atual</th>
                            <th class="text-center">KM Última<br>Execução</th>
                            <th class="text-center">Data Última<br>Execução</th>
                            <th class="text-center">Próxima<br>Execução (km)</th>
                            <th class="text-center sortable {{ $ordenarPor === 'km_restante' ? 'active' : '' }}" 
                                wire:click="ordenarPorColuna('km_restante')">
                                KM<br>Restante
                                <span class="sort-icon">
                                    @if($ordenarPor === 'km_restante')
                                        @if($direcaoOrdenacao === 'asc')
                                            ▲
                                        @else
                                            ▼
                                        @endif
                                    @else
                                        ⇅
                                    @endif
                                </span>
                            </th>
                            <th class="text-center">KM Médio<br>Diário</th>
                            <th class="text-center">Data Prevista<br>Próxima Exec.</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($dadosRelatorio as $item)
                            <tr>
                                <td class="font-medium">
                                    {{ $item['placa'] }}
                                </td>
                                <td>
                                    {{ $item['plano_descricao'] }}
                                </td>
                                <td class="text-center">
                                    {{ number_format($item['periodicidade'], 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    {{ number_format($item['km_atual'], 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    @if($item['km_ultima_execucao'] > 0)
                                        {{ number_format($item['km_ultima_execucao'], 0, ',', '.') }}
                                    @else
                                        <span class="text-gray">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($item['data_ultima_execucao'])
                                        {{ date('d/m/Y', strtotime($item['data_ultima_execucao'])) }}
                                    @else
                                        <span class="text-gray">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    {{ number_format($item['proxima_execucao'], 0, ',', '.') }}
                                </td>
                                <td class="text-center 
                                    @if($item['km_restante'] <= 0) status-danger
                                    @elseif($item['km_restante'] <= 1000) status-warning
                                    @else status-ok
                                    @endif">
                                    {{ number_format($item['km_restante'], 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    @if($item['km_medio_diario'] > 0)
                                        {{ number_format($item['km_medio_diario'], 1, ',', '.') }}
                                    @else
                                        <span class="text-gray">-</span>
                                    @endif
                                </td>
                                <td class="text-center @if($item['data_prevista'] === 'Atrasado') status-danger @endif">
                                    @if($item['data_prevista'] === 'Atrasado')
                                        Atrasado
                                    @elseif($item['data_prevista'])
                                        {{ date('d/m/Y', strtotime($item['data_prevista'])) }}
                                    @else
                                        <span class="text-gray">N/D</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
