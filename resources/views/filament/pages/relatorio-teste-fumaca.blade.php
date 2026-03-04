<x-filament-panels::page>
    <style>
        .relatorio-table {
            width: 100%;
            border-collapse: collapse;
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
        .relatorio-table th.text-center,
        .relatorio-table td.text-center {
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
        .relatorio-table td.font-medium {
            font-weight: 600;
            color: #111827;
        }
        .relatorio-table td.text-gray {
            color: #9ca3af;
        }
        .badge-warning {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 9999px;
            background-color: #fef3c7;
            color: #92400e;
            font-weight: 700;
            font-size: 12px;
        }
        .badge-danger {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 9999px;
            background-color: #fee2e2;
            color: #991b1b;
            font-weight: 700;
            font-size: 12px;
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
            margin-bottom: 4px;
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
            margin-top: 20px;
        }
        .info-box {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 14px 18px;
            font-size: 13px;
            color: #1e40af;
            margin-bottom: 8px;
        }
    </style>

    <div>
        <div class="info-box">
            Este relatÃ³rio lista todos os veÃ­culos ativos cujo <strong>teste de fumaÃ§a</strong>
            foi realizado hÃ¡ <strong>mais de 150 dias</strong>.
            Clique em <em>Carregar Dados</em> para visualizar os resultados.
        </div>

        <div class="buttons-container">
            <x-filament::button
                wire:click="carregarDados"
                icon="heroicon-o-magnifying-glass"
                color="success"
            >
                Carregar Dados
            </x-filament::button>

            <x-filament::button
                wire:click="gerarPdf"
                icon="heroicon-o-arrow-down-tray"
                color="danger"
            >
                Baixar PDF
            </x-filament::button>
        </div>

        @if(count($dadosRelatorio) > 0)
            <div class="section-box">
                <div class="section-heading">VeÃ­culos com Teste de FumaÃ§a Vencido</div>
                <div class="section-description">
                    Total de <strong>{{ count($dadosRelatorio) }}</strong> veÃ­culo(s) com teste vencido hÃ¡ mais de 150 dias.
                    ReferÃªncia: {{ \Carbon\Carbon::today()->format('d/m/Y') }}.
                </div>

                <div class="table-container">
                    <table class="relatorio-table">
                        <thead>
                            <tr>
                                <th class="sortable {{ $ordenarPor === 'placa' ? 'active' : '' }}"
                                    wire:click="ordenarPorColuna('placa')">
                                    Placa
                                    <span class="sort-icon">
                                        @if($ordenarPor === 'placa')
                                            {{ $direcaoOrdenacao === 'asc' ? 'â–²' : 'â–¼' }}
                                        @else
                                            â‡…
                                        @endif
                                    </span>
                                </th>

                                <th class="text-center sortable {{ $ordenarPor === 'km_atual' ? 'active' : '' }}"
                                    wire:click="ordenarPorColuna('km_atual')">
                                    KM Atual
                                    <span class="sort-icon">
                                        @if($ordenarPor === 'km_atual')
                                            {{ $direcaoOrdenacao === 'asc' ? 'â–²' : 'â–¼' }}
                                        @else
                                            â‡…
                                        @endif
                                    </span>
                                </th>

                                <th class="text-center sortable {{ $ordenarPor === 'data_teste' ? 'active' : '' }}"
                                    wire:click="ordenarPorColuna('data_teste')">
                                    Data do Ãšltimo<br>Teste de FumaÃ§a
                                    <span class="sort-icon">
                                        @if($ordenarPor === 'data_teste')
                                            {{ $direcaoOrdenacao === 'asc' ? 'â–²' : 'â–¼' }}
                                        @else
                                            â‡…
                                        @endif
                                    </span>
                                </th>

                                <th class="text-center sortable {{ $ordenarPor === 'dias_vencido' ? 'active' : '' }}"
                                    wire:click="ordenarPorColuna('dias_vencido')">
                                    Dias Vencido
                                    <span class="sort-icon">
                                        @if($ordenarPor === 'dias_vencido')
                                            {{ $direcaoOrdenacao === 'asc' ? 'â–²' : 'â–¼' }}
                                        @else
                                            â‡…
                                        @endif
                                    </span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dadosRelatorio as $item)
                                <tr>
                                    <td class="font-medium">{{ $item['placa'] }}</td>

                                    <td class="text-center">
                                        @if($item['km_atual'] > 0)
                                            {{ number_format($item['km_atual'], 0, ',', '.') }}
                                        @else
                                            <span class="text-gray">â€”</span>
                                        @endif
                                    </td>

                                    <td class="text-center">
                                        {{ \Carbon\Carbon::parse($item['data_teste'])->format('d/m/Y') }}
                                    </td>

                                    <td class="text-center">
                                        @if($item['dias_vencido'] > 100)
                                            <span class="badge-danger">{{ $item['dias_vencido'] }} dias</span>
                                        @else
                                            <span class="badge-warning">{{ $item['dias_vencido'] }} dias</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif($buscaRealizada)
            <div class="section-box">
                <p style="color:#6b7280;font-size:14px;">
                    Nenhum veÃ­culo encontrado com teste de fumaÃ§a vencido hÃ¡ mais de 150 dias.
                </p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
