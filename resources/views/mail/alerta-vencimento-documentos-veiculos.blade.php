<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Alerta de vencimento</title>
    <style>
        body { font-family: Arial, sans-serif; color: #1f2937; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th { background: #f3f4f6; text-align: left; padding: 8px; border: 1px solid #e5e7eb; }
        td { padding: 8px; border: 1px solid #e5e7eb; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-weight: bold; font-size: 12px; }
        .danger { background: #fee2e2; color: #991b1b; }
        .warning { background: #fef3c7; color: #92400e; }
        .success { background: #dcfce7; color: #166534; }
        .gray { background: #f3f4f6; color: #4b5563; }
    </style>
</head>
<body>
    <h2>Alerta de vencimento de documentos de veículos</h2>

    <p>
        Tipo: <strong>{{ $tipoLabel }}</strong><br>
        Unidades: <strong>{{ implode(', ', $regra['unidades'] ?? []) }}</strong><br>
        Gerado em: <strong>{{ $dataGeracao }}</strong>
    </p>

    <table>
        <thead>
            <tr>
                <th>Veículo</th>
                <th>Unidade</th>
                <th>Documento</th>
                <th>Início</th>
                <th>Fim</th>
                <th>Dias</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($documentos as $documento)
                <tr>
                    <td>{{ $documento->veiculo?->placa ?? 'N/A' }}</td>
                    <td>{{ $documento->veiculo?->filial ?? 'N/A' }}</td>
                    <td>{{ $documento->nome }}</td>
                    <td>{{ $documento->data_inicio?->format('d/m/Y') ?? '-' }}</td>
                    <td>{{ $documento->data_fim?->format('d/m/Y') ?? '-' }}</td>
                    <td>{{ $documento->dias_restantes === null ? '-' : number_format($documento->dias_restantes, 0, ',', '.') }}</td>
                    <td>
                        <span class="badge {{ $documento->getStatusColor() }}">
                            {{ $documento->status_documento }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
