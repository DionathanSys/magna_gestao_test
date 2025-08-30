<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Planos de Manutenção - Vencimento</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #0066cc;
            padding-bottom: 15px;
        }

        .header h1 {
            color: #0066cc;
            font-size: 18px;
            margin-bottom: 5px;
        }

        .header p {
            color: #666;
            font-size: 11px;
        }

        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }

        .info-item {
            font-size: 11px;
        }

        .info-item strong {
            color: #0066cc;
        }

        .table-container {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #0066cc;
            color: white;
            font-weight: bold;
            text-align: center;
        }

        .status-urgente {
            background-color: #ffebee;
            color: #c62828;
            font-weight: bold;
        }

        .status-atencao {
            background-color: #fff3e0;
            color: #ef6c00;
            font-weight: bold;
        }

        .status-ok {
            background-color: #e8f5e8;
            color: #2e7d32;
        }

        .km-restante {
            text-align: right;
            font-weight: bold;
        }

        .footer {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        .page-break {
            page-break-after: always;
        }

        .summary {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f1f3f4;
            border-radius: 4px;
        }

        .summary h3 {
            color: #0066cc;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .summary-item {
            display: inline-block;
            margin-right: 20px;
            font-size: 11px;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-danger {
            background-color: #dc3545;
            color: white;
        }

        .badge-warning {
            background-color: #ffc107;
            color: black;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Relatório de Planos de Manutenção Preventiva</h1>
        <p>Planos com Vencimento Próximo ou Vencidos</p>
    </div>

    <div class="info-section">
        <div class="info-item">
            <strong>Data de Geração:</strong> {{ date('d/m/Y H:i') }}
        </div>
        <div class="info-item">
            <strong>Tolerância KM:</strong> {{ number_format($kmTolerancia ?? 2500, 0, ',', '.') }} km
        </div>
        <div class="info-item">
            <strong>Total de Registros:</strong> {{ count($planos) }}
        </div>
    </div>

    @if(count($planos) > 0)
        <div class="summary">
            <h3>Resumo por Status</h3>
            @php
                $urgentes = collect($planos)->filter(fn($plano) => $plano['km_restante'] <= 500)->count();
                $atencao = collect($planos)->filter(fn($plano) => $plano['km_restante'] > 500 && $plano['km_restante'] <= 1500)->count();
                $preventivo = collect($planos)->filter(fn($plano) => $plano['km_restante'] > 1500)->count();
            @endphp

            <div class="summary-item">
                <span class="badge badge-danger">Urgente</span>
                {{ $urgentes }} planos (≤ 500 km)
            </div>
            <div class="summary-item">
                <span class="badge badge-warning">Atenção</span>
                {{ $atencao }} planos (501 - 1.500 km)
            </div>
            <div class="summary-item">
                <span class="badge" style="background-color: #28a745; color: white;">Preventivo</span>
                {{ $preventivo }} planos (> 1.500 km)
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 8%;">Veículo</th>
                        <th style="width: 35%;">Plano Preventivo</th>
                        <th style="width: 10%;">KM Última</th>
                        <th style="width: 10%;">KM Restante</th>
                        <th style="width: 12%;">Próxima Execução</th>
                        <th style="width: 12%;">Dt. Prevista</th>
                        <th style="width: 5%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($planos as $plano)
                        @php
                            $statusClass = '';
                            $statusText = '';

                            if($plano['km_restante'] <= 500) {
                                $statusClass = 'status-urgente';
                                $statusText = 'URGENTE';
                            } elseif($plano['km_restante'] <= 1500) {
                                $statusClass = 'status-atencao';
                                $statusText = 'ATENÇÃO';
                            } else {
                                $statusClass = 'status-ok';
                                $statusText = 'OK';
                            }
                        @endphp

                        <tr class="{{ $statusClass }}">
                            <td style="font-weight: bold;">{{ e($plano['placa'] ?? 'N/A') }}</td>
                            <td>{{ e($plano['plano_preventivo_id'] ?? 'N/A') }} - {{ e($plano['descricao'] ?? 'N/A') }}</td>
                            <td style="text-align: right;">
                                {{ isset($plano['km_execucao']) ? number_format($plano['km_execucao'], 0, ',', '.') : '0' }}
                            </td>
                            <td class="km-restante">
                                {{ number_format($plano['km_restante'], 0, ',', '.') }}
                            </td>
                            <td style="text-align: right;">
                                {{ number_format($plano['km_proxima_execucao'], 0, ',', '.') }}
                            </td>
                            <td style="text-align: right;">
                                {{ $plano['data_prevista'] }}
                            </td>
                            <td style="text-align: center; font-size: 9px;">
                                {{ $statusText }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="font-size: 10px; color: #666; margin-top: 20px;">
            <p><strong>Legenda:</strong></p>
            <p>&bull; <span style="color: #c62828; font-weight: bold;">URGENTE:</span> Manutenção deve ser realizada em até 500 km</p>
            <p>&bull; <span style="color: #ef6c00; font-weight: bold;">ATENÇÃO:</span> Manutenção deve ser programada (501 - 1.500 km)</p>
            <p>&bull; <span style="color: #2e7d32; font-weight: bold;">OK:</span> Manutenção preventiva (acima de 1.500 km)</p>
        </div>

    @else
        <div class="no-data">
            <h3>Nenhum plano de manutenção próximo do vencimento</h3>
            <p>Todos os planos estão dentro da tolerância estabelecida.</p>
        </div>
    @endif

    <div class="footer">
        <p>Sistema de Gestão de Frota - Relatório gerado automaticamente em {{ date('d/m/Y H:i:s') }}</p>
        <p>Página @php echo '{PAGE_NUM}'; @endphp de @php echo '{PAGE_COUNT}'; @endphp</p>
    </div>
</body>
</html>
