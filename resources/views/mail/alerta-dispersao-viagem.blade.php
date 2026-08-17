<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Alerta - Dispersão de Viagens</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #f59e0b 0%, #dc2626 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 { margin: 0; font-size: 28px; font-weight: 300; }
        .header p { margin: 10px 0 0 0; opacity: 0.9; }
        .content { padding: 30px; }

        .resumo-table {
            width: 100%;
            margin-bottom: 30px;
            border-collapse: collapse;
        }
        .resumo-table td {
            width: 50%;
            padding: 10px;
            vertical-align: top;
        }
        .resumo-card {
            background: #fff3cd;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #f59e0b;
        }
        .resumo-card h3 { margin: 0; font-size: 32px; color: #b45309; }
        .resumo-card p { margin: 5px 0 0 0; color: #92400e; font-size: 14px; }

        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        .table th, .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #fde68a;
            font-size: 13px;
        }
        .table th {
            background-color: #fef3c7;
            font-weight: 600;
            color: #92400e;
        }
        .table tr:hover {
            background-color: #fffbeb;
        }
        .table td {
            color: #57534e;
        }

        .km-dispersao {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            background-color: #fee2e2;
            color: #991b1b;
        }

        .vazio {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
            font-style: italic;
        }

        .footer {
            background-color: #fef3c7;
            padding: 20px;
            text-align: center;
            color: #92400e;
            font-size: 14px;
            border-top: 2px solid #f59e0b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Alerta - Dispersão de Viagens</h1>
            <p>Gerado em: {{ $data_processamento }}</p>
        </div>

        <div class="content">
            <table class="resumo-table">
                <tr>
                    <td>
                        <div class="resumo-card">
                            <h3>{{ $viagens->count() }}</h3>
                            <p>Viagens com dispersão ≥ {{ $limite_km }} km</p>
                        </div>
                    </td>
                    <td>
                        <div class="resumo-card">
                            <h3>{{ number_format($viagens->sum('km_dispersao'), 2, ',', '.') }}</h3>
                            <p>Total de km de dispersão</p>
                        </div>
                    </td>
                </tr>
            </table>

            <table class="table">
                <thead>
                    <tr>
                        <th>Nº Viagem</th>
                        <th>Nº Interno</th>
                        <th>Doc. Transp.</th>
                        <th>Veículo</th>
                        <th>Integrados</th>
                        <th>Data Início</th>
                        <th>Km Rodado</th>
                        <th>Km Dispersão</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($viagens as $viagem)
                        <tr>
                            <td><strong>{{ $viagem->numero_viagem }}</strong></td>
                            <td>{{ $viagem->numero_interno ?? 'N/A' }}</td>
                            <td>{{ $viagem->documento_transporte ?? 'N/A' }}</td>
                            <td>{{ $viagem->veiculo?->placa ?? 'N/A' }}</td>
                            <td>
                                @php
                                    $integrados = collect($viagem->integrados_json ?? [])
                                        ->pluck('nome')
                                        ->filter()
                                        ->unique()
                                        ->values();
                                    if ($integrados->isEmpty()) {
                                        $integrados = $viagem->cargas->pluck('integrado.nome')->filter()->unique()->values();
                                    }
                                @endphp
                                {{ $integrados->isEmpty() ? 'N/A' : $integrados->implode(', ') }}
                            </td>
                            <td>{{ $viagem->data_inicio ? \Carbon\Carbon::parse($viagem->data_inicio)->format('d/m/Y') : 'N/A' }}</td>
                            <td>{{ number_format((float) ($viagem->km_rodado ?? 0), 2, ',', '.') }}</td>
                            <td>
                                <span class="km-dispersao">
                                    {{ number_format((float) ($viagem->km_dispersao ?? 0), 2, ',', '.') }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if($viagens->isEmpty())
                <div class="vazio">
                    <h3>✅ Nenhuma viagem com dispersão acima do limite</h3>
                    <p>Não há viagens para alertar neste momento.</p>
                </div>
            @endif
        </div>

        <div class="footer">
            <p>⚠️ Este é um email automático de alerta do sistema Magna Gestão.<br>
            Não responda este email.</p>
        </div>
    </div>
</body>
</html>
