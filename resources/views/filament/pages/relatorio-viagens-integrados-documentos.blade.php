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
            white-space: nowrap;
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
            padding: 10px 8px;
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
        .relatorio-table td.text-right {
            text-align: right;
        }
        .relatorio-table td.font-medium {
            font-weight: 500;
            color: #111827;
        }
        .relatorio-table td.text-gray {
            color: #9ca3af;
            font-style: italic;
        }
        .relatorio-table td.status-ok {
            color: #059669;
            font-weight: 600;
        }
        .relatorio-table td.status-nao {
            color: #dc2626;
            font-weight: 600;
        }
        .buttons-container {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        .table-container {
            overflow-x: auto;
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
    </style>

    <div>
        {{ $this->form }}

        <div class="buttons-container">
            <x-filament::button
                wire:click="carregarDados"
                icon="heroicon-o-magnifying-glass"
                color="success"
            >
                Carregar Dados
            </x-filament::button>

            <x-filament::button
                wire:click="exportarExcel"
                icon="heroicon-o-arrow-down-tray"
            >
                Exportar Excel
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
                            <th class="sortable {{ $ordenarPor === 'numero_viagem' ? 'active' : '' }}"
                                wire:click="ordenarPorColuna('numero_viagem')">
                                Nº Viagem
                                <span class="sort-icon">
                                    @if($ordenarPor === 'numero_viagem')
                                        @if($direcaoOrdenacao === 'asc') ▲ @else ▼ @endif
                                    @else
                                        ⇅
                                    @endif
                                </span>
                            </th>
                            <th class="sortable {{ $ordenarPor === 'data_competencia_sort' ? 'active' : '' }}"
                                wire:click="ordenarPorColuna('data_competencia_sort')">
                                Data Comp.
                                <span class="sort-icon">
                                    @if($ordenarPor === 'data_competencia_sort')
                                        @if($direcaoOrdenacao === 'asc') ▲ @else ▼ @endif
                                    @else
                                        ⇅
                                    @endif
                                </span>
                            </th>
                            <th>Placa</th>
                            <th>Integrado(s)</th>
                            <th>Município(s)</th>
                            <th class="text-center sortable {{ $ordenarPor === 'km_rodado' ? 'active' : '' }}"
                                wire:click="ordenarPorColuna('km_rodado')">
                                Km Rodado
                                <span class="sort-icon">
                                    @if($ordenarPor === 'km_rodado')
                                        @if($direcaoOrdenacao === 'asc') ▲ @else ▼ @endif
                                    @else
                                        ⇅
                                    @endif
                                </span>
                            </th>
                            <th class="text-center sortable {{ $ordenarPor === 'km_pago' ? 'active' : '' }}"
                                wire:click="ordenarPorColuna('km_pago')">
                                Km Pago
                                <span class="sort-icon">
                                    @if($ordenarPor === 'km_pago')
                                        @if($direcaoOrdenacao === 'asc') ▲ @else ▼ @endif
                                    @else
                                        ⇅
                                    @endif
                                </span>
                            </th>
                            <th>Nº Doc. Frete</th>
                            <th class="text-right sortable {{ $ordenarPor === 'valor_liquido' ? 'active' : '' }}"
                                wire:click="ordenarPorColuna('valor_liquido')">
                                Valor Líquido
                                <span class="sort-icon">
                                    @if($ordenarPor === 'valor_liquido')
                                        @if($direcaoOrdenacao === 'asc') ▲ @else ▼ @endif
                                    @else
                                        ⇅
                                    @endif
                                </span>
                            </th>
                            <th>Nº Notas (NF-e)</th>
                            <th class="text-center">Conf.</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($dadosRelatorio as $item)
                            <tr>
                                <td class="font-medium">
                                    {{ $item['numero_viagem'] }}
                                </td>
                                <td>
                                    {{ $item['data_competencia'] }}
                                </td>
                                <td>
                                    {{ $item['placa'] }}
                                </td>
                                <td>
                                    {{ $item['integrados'] }}
                                </td>
                                <td>
                                    {{ $item['municipios'] }}
                                </td>
                                <td class="text-center">
                                    {{ number_format($item['km_rodado'], 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    {{ number_format($item['km_pago'], 0, ',', '.') }}
                                </td>
                                <td>
                                    {{ $item['numeros_documentos'] }}
                                </td>
                                <td class="text-right">
                                    R$ {{ number_format($item['valor_liquido'], 2, ',', '.') }}
                                </td>
                                <td>
                                    {{ $item['numeros_notas'] }}
                                </td>
                                <td class="text-center">
                                    @if($item['conferido'])
                                        <span class="status-ok">✓</span>
                                    @else
                                        <span class="status-nao">✗</span>
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
