<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Planos Preventivos Executados</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        h1 { color: #1d4ed8; font-size: 17px; margin-bottom: 4px; }
        p { color: #6b7280; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th { background: #1d4ed8; color: #fff; padding: 7px 5px; text-align: left; }
        td { border: 1px solid #d1d5db; padding: 6px 5px; }
        tr:nth-child(even) { background: #f9fafb; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>Planos Preventivos Executados</h1>
    <p>Gerado em: {{ $dataGeracao }}</p>

    <table>
        <thead>
            <tr>
                <th>Veículo</th>
                <th>Plano preventivo</th>
                <th class="right">KM</th>
                <th>Data</th>
                <th>Origem</th>
                <th>OS</th>
                <th>Status da OS</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($dados as $item)
                <tr>
                    <td>{{ $item['placa'] ?? '-' }}</td>
                    <td>{{ $item['plano_descricao'] ?? 'Plano não informado' }}</td>
                    <td class="right">{{ number_format($item['km_execucao'] ?? 0, 0, ',', '.') }}</td>
                    <td>{{ $item['data_execucao'] ? date('d/m/Y', strtotime($item['data_execucao'])) : '-' }}</td>
                    <td>{{ $item['origem'] }}</td>
                    <td>{{ $item['ordem_servico_id'] ? '#'.$item['ordem_servico_id'] : '-' }}</td>
                    <td>{{ $item['ordem_servico_status'] ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
