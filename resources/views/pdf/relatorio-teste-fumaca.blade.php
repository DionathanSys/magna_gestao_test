<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Relatório – Teste de Fumaça</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9px;
            line-height: 1.4;
            color: #333;
            padding: 18px;
        }

        .header {
            text-align: center;
            margin-bottom: 18px;
            border-bottom: 2px solid #dc2626;
            padding-bottom: 10px;
        }
        .header h1 { color: #dc2626; font-size: 15px; margin-bottom: 4px; }
        .header p  { color: #666; font-size: 8px; }

        .info-section {
            margin-bottom: 14px;
            padding: 8px 10px;
            background-color: #f8f9fa;
            border-left: 3px solid #dc2626;
            border-radius: 2px;
        }
        .info-section p { font-size: 8.5px; color: #555; }
        .info-section strong { color: #333; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th {
            background-color: #dc2626;
            color: white;
            padding: 6px 5px;
            text-align: left;
            font-size: 8px;
            font-weight: bold;
            border: 1px solid #b91c1c;
        }
        th.text-center { text-align: center; }

        td {
            padding: 5px 5px;
            border: 1px solid #e5e7eb;
            font-size: 8.5px;
            color: #374151;
        }
        td.text-center { text-align: center; }
        td.text-right  { text-align: right; }

        tr:nth-child(even) { background-color: #fafafa; }

        .badge-warning {
            background-color: #fef3c7;
            color: #92400e;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 8px;
        }
        .badge-danger {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 8px;
        }

        .footer {
            margin-top: 20px;
            border-top: 1px solid #e5e7eb;
            padding-top: 8px;
            font-size: 7.5px;
            color: #9ca3af;
            text-align: right;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Relatório – Teste de Fumaça</h1>
        <p>Veículos com teste vencido há mais de 150 dias &nbsp;|&nbsp; Gerado em {{ $dataGeracao }}</p>
    </div>

    <div class="info-section">
        <p>
            Total de veículos: <strong>{{ $totalRegistros }}</strong> &nbsp;&nbsp;
            Data de referência: <strong>{{ \Carbon\Carbon::today()->format('d/m/Y') }}</strong>
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Placa</th>
                <th class="text-center">KM Atual</th>
                <th class="text-center">Data Último Teste</th>
                <th class="text-center">Dias Vencido</th>
            </tr>
        </thead>
        <tbody>
            @forelse($dados as $item)
                <tr>
                    <td><strong>{{ $item['placa'] }}</strong></td>

                    <td class="text-right">
                        @if($item['km_atual'] > 0)
                            {{ number_format($item['km_atual'], 0, ',', '.') }}
                        @else
                            —
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
            @empty
                <tr>
                    <td colspan="4" class="text-center" style="color:#9ca3af; padding: 12px;">
                        Nenhum veículo encontrado.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Magna Gestão &nbsp;|&nbsp; Relatório Teste de Fumaça &nbsp;|&nbsp; {{ $dataGeracao }}
    </div>

</body>
</html>
