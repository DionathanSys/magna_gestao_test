<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Viagens por Veículo</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: DejaVu Sans, sans-serif;
        }

        body {
            margin: 0;
            color: #111827;
            font-size: 10px;
        }

        h1 {
            margin: 0 0 4px;
            font-size: 18px;
        }

        h2 {
            margin: 14px 0 6px;
            padding: 6px 8px;
            background: #e5e7eb;
            font-size: 12px;
        }

        .meta {
            margin-bottom: 14px;
            color: #4b5563;
            font-size: 10px;
        }

        .summary {
            margin-bottom: 12px;
            padding: 8px 10px;
            border: 1px solid #d1d5db;
            background: #f9fafb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            padding: 7px 6px;
            border: 1px solid #d1d5db;
            background: #f3f4f6;
            text-align: left;
            font-size: 9px;
        }

        td {
            padding: 6px;
            border: 1px solid #d1d5db;
            vertical-align: top;
            font-size: 9px;
        }

        .plate {
            font-weight: bold;
            font-size: 11px;
        }

        .muted {
            color: #6b7280;
        }
    </style>
</head>
<body>
    @php
        $gruposCliente = collect($veiculos)
            ->flatMap(function (array $veiculo): array {
                return collect($veiculo['clientes'])
                    ->map(fn (string $cliente): array => [
                        'cliente' => $cliente,
                        'veiculo' => $veiculo,
                    ])
                    ->all();
            })
            ->groupBy('cliente')
            ->sortKeys();
    @endphp

    <h1>Dashboard Viagens por Veículo</h1>

    <div class="meta">
        Gerado em {{ $dataGeracao }} · Período: {{ $periodo['inicio'] ?? '-' }} até {{ $periodo['fim'] ?? '-' }}
    </div>

    <div class="summary">
        Total de veículos: <strong>{{ $totalVeiculos }}</strong> · Total de viagens: <strong>{{ number_format($totalViagens, 0, ',', '.') }}</strong>
    </div>

    @foreach ($gruposCliente as $cliente => $items)
        <h2>{{ $cliente }}</h2>

        <table>
            <thead>
                <tr>
                    <th style="width: 9%;">Veículo</th>
                    <th style="width: 19%;">Destino</th>
                    <th style="width: 14%;">Local atual</th>
                    <th style="width: 8%;">Peso</th>
                    <th style="width: 11%;">Status</th>
                    <th style="width: 13%;">Início</th>
                    <th style="width: 10%;">Duração</th>
                    <th style="width: 8%;">Km pago</th>
                    <th style="width: 8%;">Viagens encerradas</th>
                    <th style="width: 17%;">Mov. diário</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    @php($veiculo = $item['veiculo'])
                    <tr>
                        <td class="plate">{{ $veiculo['placa'] }}</td>
                        <td>{{ $veiculo['viagem_atual']['destino'] ?? 'N/A' }}</td>
                        <td>{{ $veiculo['viagem_atual']['local_atual'] ?? 'N/A' }}</td>
                        <td>{{ $veiculo['viagem_atual']['peso_humano'] ?? 'N/A' }}</td>
                        <td>{{ $veiculo['viagem_atual']['status'] ?? 'N/A' }}</td>
                        <td>{{ $veiculo['viagem_atual']['inicio_humano'] ?? 'N/A' }}</td>
                        <td>{{ $veiculo['viagem_atual']['duracao_viagem'] ?? 'N/A' }}</td>
                        <td>{{ $veiculo['viagem_atual']['km_pago_humano'] ?? '0,0' }}</td>
                        <td>{{ number_format($veiculo['total_encerradas'], 0, ',', '.') }}</td>
                        <td>
                            @if ($veiculo['movimento_diario']['disponivel'] ?? false)
                                Km {{ $veiculo['movimento_diario']['km'] }}<br>
                                <span class="muted">{{ $veiculo['movimento_diario']['tempo_movimento'] }}</span>
                            @else
                                N/A
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
</body>
</html>
