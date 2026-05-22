<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Movimentações de Pneus</title>
    <style>
        @page {
            margin: 18px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1f2937;
        }

        .header {
            margin-bottom: 18px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
            color: #111827;
            margin: 0 0 6px;
        }

        .subtitle {
            font-size: 11px;
            color: #4b5563;
            margin: 0;
        }

        .summary {
            margin-top: 8px;
            font-size: 11px;
            color: #374151;
        }

        .vehicle-block {
            margin-top: 18px;
            page-break-inside: avoid;
        }

        .vehicle-title {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            font-size: 13px;
            font-weight: bold;
            color: #111827;
        }

        .vehicle-meta {
            margin: 6px 0 10px;
            font-size: 10px;
            color: #4b5563;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 6px 7px;
            vertical-align: top;
            word-wrap: break-word;
        }

        th {
            background: #e5e7eb;
            font-size: 10px;
            text-align: left;
            color: #111827;
        }

        td {
            font-size: 10px;
        }

        .muted {
            color: #6b7280;
        }

        .empty {
            margin-top: 20px;
            padding: 12px;
            border: 1px dashed #cbd5e1;
            background: #f8fafc;
            color: #475569;
        }
    </style>
</head>
<body>
    <div class="header">
        <p class="title">Relatório de Movimentações de Pneus</p>
        <p class="subtitle">Período do registro: {{ $dataInicial }} até {{ $dataFinal }}</p>
        <div class="summary">
            Total de movimentações: <strong>{{ $totalMovimentacoes }}</strong><br>
            Total de operações: <strong>{{ $totalOperacoes }}</strong><br>
            Data de geração: <strong>{{ $dataGeracao }}</strong>
        </div>
    </div>

    @forelse($veiculos as $veiculo)
        <div class="vehicle-block">
            <div class="vehicle-title">Veículo: {{ $veiculo['placa'] }}</div>
            <div class="vehicle-meta">Movimentações no período: {{ $veiculo['total_movimentacoes'] }} | Operações consolidadas: {{ count($veiculo['movimentacoes']) }}</div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 10%;">Criado em</th>
                        <th style="width: 8%;">Motivo</th>
                        <th style="width: 6%;">Eixo</th>
                        <th style="width: 8%;">Posição</th>
                        <th style="width: 9%;">Pneu removido</th>
                        <th style="width: 9%;">Pneu aplicado</th>
                        <th style="width: 8%;">Dt. remoção</th>
                        <th style="width: 8%;">Dt. aplicação</th>
                        <th style="width: 7%;">KM remoção</th>
                        <th style="width: 7%;">KM aplicação</th>
                        <th style="width: 6%;">Sulco rem.</th>
                        <th style="width: 6%;">Sulco apl.</th>
                        <th style="width: 14%;">Observação</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($veiculo['movimentacoes'] as $movimento)
                        <tr>
                            <td>{{ $movimento['created_at']?->format('d/m/Y H:i') }}</td>
                            <td>{{ $movimento['motivo'] ?? '-' }}</td>
                            <td>{{ $movimento['eixo'] ?? '-' }}</td>
                            <td>{{ $movimento['posicao'] ?? '-' }}</td>
                            <td>{{ $movimento['pneu_removido'] ?? '-' }}</td>
                            <td>{{ $movimento['pneu_aplicado'] ?? '-' }}</td>
                            <td>{{ $movimento['data_remocao'] ? \Carbon\Carbon::parse($movimento['data_remocao'])->format('d/m/Y') : '-' }}</td>
                            <td>{{ $movimento['data_aplicacao'] ? \Carbon\Carbon::parse($movimento['data_aplicacao'])->format('d/m/Y') : '-' }}</td>
                            <td>{{ $movimento['km_remocao'] !== null ? number_format((float) $movimento['km_remocao'], 0, ',', '.') : '-' }}</td>
                            <td>{{ $movimento['km_aplicacao'] !== null ? number_format((float) $movimento['km_aplicacao'], 0, ',', '.') : '-' }}</td>
                            <td>{{ $movimento['sulco_remocao'] !== null ? number_format((float) $movimento['sulco_remocao'], 2, ',', '.') : '-' }}</td>
                            <td>{{ $movimento['sulco_aplicacao'] !== null ? number_format((float) $movimento['sulco_aplicacao'], 2, ',', '.') : '-' }}</td>
                            <td>{{ $movimento['observacao'] ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <div class="empty">
            Nenhuma movimentação encontrada para o período informado.
        </div>
    @endforelse
</body>
</html>
