<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Plano de Manutenção</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9px;
            line-height: 1.3;
            color: #333;
            padding: 15px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #0066cc;
            padding-bottom: 10px;
        }

        .header h1 {
            color: #0066cc;
            font-size: 16px;
            margin-bottom: 5px;
        }

        .header p {
            color: #666;
            font-size: 9px;
        }

        .info-section {
            margin-bottom: 15px;
            padding: 8px;
            background-color: #f8f9fa;
            border-radius: 3px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }

        .info-item {
            font-size: 9px;
        }

        .info-label {
            font-weight: bold;
            color: #555;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th {
            background-color: #0066cc;
            color: white;
            padding: 6px 4px;
            text-align: left;
            font-size: 8px;
            font-weight: bold;
            border: 1px solid #0055aa;
        }

        td {
            padding: 5px 4px;
            border: 1px solid #ddd;
            font-size: 8px;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f0f0f0;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .status-ok {
            color: #28a745;
            font-weight: bold;
        }

        .status-warning {
            color: #ffc107;
            font-weight: bold;
        }

        .status-danger {
            color: #dc3545;
            font-weight: bold;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        .sem-dados {
            text-align: center;
            padding: 30px;
            color: #999;
            font-size: 11px;
        }

        .placa-destaque {
            font-weight: bold;
            color: #0066cc;
        }
    </style>
</head>
<body>
<div class="header">
    <h1>Relatório de Plano de Manutenção Preventiva</h1>
    <p>Gerado em: {{ $dataGeracao }}</p>
</div>

<div class="info-section">
    <div class="info-row">
        <div class="info-item">
            <span class="info-label">Total de Registros:</span> {{ $totalRegistros }}
        </div>
        <div class="info-item">
            <span class="info-label">Filtros Aplicados:</span>
            @if(!empty($filtros['veiculo_id']))
                Veículo
            @endif
            @if(!empty($filtros['plano_preventivo_id']))
                @if(!empty($filtros['veiculo_id'])), @endif
                Plano
            @endif
            @if(isset($filtros['km_restante_maximo']) && $filtros['km_restante_maximo'] !== null)
                @if(!empty($filtros['veiculo_id']) || !empty($filtros['plano_preventivo_id'])), @endif
                KM Restante ≤ {{ number_format($filtros['km_restante_maximo'], 0, ',', '.') }} km
            @endif
            @if(empty($filtros['veiculo_id']) && empty($filtros['plano_preventivo_id']) && (!isset($filtros['km_restante_maximo']) || $filtros['km_restante_maximo'] === null))
                Nenhum
            @endif
        </div>
    </div>
</div>

@if(count($dados) > 0)
    <table>
        <thead>
        <tr>
            <th style="width: 8%;">Placa</th>
            <th style="width: 18%;">Plano Preventivo</th>
            <th style="width: 8%;" class="text-center">Periodicidade<br>(km)</th>
            <th style="width: 8%;" class="text-center">KM Atual</th>
            <th style="width: 8%;" class="text-center">KM Última<br>Execução</th>
            <th style="width: 10%;" class="text-center">Data Última<br>Execução</th>
            <th style="width: 8%;" class="text-center">Próxima<br>Execução (km)</th>
            <th style="width: 8%;" class="text-center">KM<br>Restante</th>
            <th style="width: 8%;" class="text-center">KM Médio<br>Diário</th>
            <th style="width: 10%;" class="text-center">Data Prevista<br>Próxima Exec.</th>
        </tr>
        </thead>
        <tbody>
        @foreach($dados as $item)
            <tr>
                <td class="placa-destaque">{{ $item['placa'] }}</td>
                <td>{{ $item['plano_descricao'] }}</td>
                <td class="text-center">{{ number_format($item['periodicidade'], 0, ',', '.') }}</td>
                <td class="text-center">{{ number_format($item['km_atual'], 0, ',', '.') }}</td>
                <td class="text-center">
                    @if($item['km_ultima_execucao'] > 0)
                        {{ number_format($item['km_ultima_execucao'], 0, ',', '.') }}
                    @else
                        <span style="color: #999;">-</span>
                    @endif
                </td>
                <td class="text-center">
                    @if($item['data_ultima_execucao'])
                        {{ date('d/m/Y', strtotime($item['data_ultima_execucao'])) }}
                    @else
                        <span style="color: #999;">-</span>
                    @endif
                </td>
                <td class="text-center">{{ number_format($item['proxima_execucao'], 0, ',', '.') }}</td>
                <td class="text-center
                    @if($item['km_restante'] <= 0)
                        status-danger
                    @elseif($item['km_restante'] <= 1000)
                        status-warning
                    @else
                        status-ok
                    @endif
                ">
                    {{ number_format($item['km_restante'], 0, ',', '.') }}
                </td>
                <td class="text-center">
                    @if($item['km_medio_diario'] > 0)
                        {{ number_format($item['km_medio_diario'], 1, ',', '.') }}
                    @else
                        <span style="color: #999;">-</span>
                    @endif
                </td>
                <td class="text-center">
                    @if($item['data_prevista'] === 'Atrasado')
                        <span class="status-danger">Atrasado</span>
                    @elseif($item['data_prevista'])
                        {{ date('d/m/Y', strtotime($item['data_prevista'])) }}
                    @else
                        <span style="color: #999;">N/D</span>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@else
    <div class="sem-dados">
        <p><strong>Nenhum registro encontrado com os filtros aplicados.</strong></p>
    </div>
@endif

<div class="footer">
    <p>Sistema de Gestão - Magna | Relatório gerado automaticamente</p>
</div>
</body>
</html>
