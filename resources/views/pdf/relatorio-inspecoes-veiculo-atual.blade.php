<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório Atual de Sulcos por Veículo</title>
    <style>
        @page { margin: 18px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        .header { margin-bottom: 18px; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; }
        .title { font-size: 20px; font-weight: bold; color: #111827; margin: 0 0 6px; }
        .subtitle, .summary { font-size: 11px; color: #4b5563; margin: 0; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 14px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 7px; vertical-align: top; word-wrap: break-word; }
        th { background: #e5e7eb; font-size: 10px; text-align: left; color: #111827; }
        td { font-size: 10px; }
        .empty { margin-top: 20px; padding: 12px; border: 1px dashed #cbd5e1; background: #f8fafc; color: #475569; }
    </style>
</head>
<body>
    <div class="header">
        <p class="title">Relatório Atual de Sulcos por Veículo</p>
        <p class="subtitle">Veículo: {{ $veiculo->placa }} | KM atual: {{ number_format((float) ($veiculo->kmAtual?->quilometragem ?? 0), 0, ',', '.') }}</p>
        <p class="summary">
            Total de posições: <strong>{{ $resumo['total_posicoes'] }}</strong><br>
            Pneus aplicados: <strong>{{ $resumo['total_aplicados'] }}</strong><br>
            Posições com inspeção: <strong>{{ $resumo['total_com_inspecao'] }}</strong><br>
            Média geral atual: <strong>{{ $resumo['media_geral'] !== null ? number_format((float) $resumo['media_geral'], 2, ',', '.') : '-' }}</strong><br>
            Menor sulco atual: <strong>{{ $resumo['menor_sulco'] !== null ? number_format((float) $resumo['menor_sulco'], 2, ',', '.') : '-' }}</strong><br>
            Data de geração: <strong>{{ $dataGeracao }}</strong>
        </p>
    </div>

    @if($linhas->isEmpty())
        <div class="empty">Nenhuma posição encontrada para o veículo informado.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width: 8%;">Eixo</th>
                    <th style="width: 8%;">Posição</th>
                    <th style="width: 9%;">Pneu</th>
                    <th style="width: 16%;">Marca / Modelo</th>
                    <th style="width: 9%;">Medida</th>
                    <th style="width: 8%;">Últ. inspeção</th>
                    <th style="width: 10%;">Resultado</th>
                    <th style="width: 8%;">KM</th>
                    <th style="width: 6%;">Int.</th>
                    <th style="width: 6%;">Centro</th>
                    <th style="width: 6%;">Ext.</th>
                    <th style="width: 6%;">Média</th>
                </tr>
            </thead>
            <tbody>
                @foreach($linhas as $linha)
                    <tr>
                        <td>{{ $linha['eixo'] ?? '-' }}</td>
                        <td>{{ $linha['posicao'] ?? '-' }}</td>
                        <td>{{ $linha['pneu'] ?? 'Sem pneu' }}</td>
                        <td>{{ $linha['marca_modelo'] ?: '-' }}</td>
                        <td>{{ $linha['medida'] ?: '-' }}</td>
                        <td>{{ $linha['data_inspecao'] ?? '-' }}</td>
                        <td>{{ $linha['resultado'] ?? ($linha['pneu'] ? 'Sem inspeção' : '-') }}</td>
                        <td>{{ $linha['km_referencia'] !== null ? number_format((float) $linha['km_referencia'], 0, ',', '.') : '-' }}</td>
                        <td>{{ $linha['sulco_interno'] !== null ? number_format((float) $linha['sulco_interno'], 2, ',', '.') : '-' }}</td>
                        <td>{{ $linha['sulco_centro'] !== null ? number_format((float) $linha['sulco_centro'], 2, ',', '.') : '-' }}</td>
                        <td>{{ $linha['sulco_externo'] !== null ? number_format((float) $linha['sulco_externo'], 2, ',', '.') : '-' }}</td>
                        <td>{{ $linha['media_sulcos'] !== null ? number_format((float) $linha['media_sulcos'], 2, ',', '.') : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
