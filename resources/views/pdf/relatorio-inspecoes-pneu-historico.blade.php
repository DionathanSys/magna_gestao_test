<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Histórico de Sulcos por Pneu</title>
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
        <p class="title">Histórico de Sulcos por Pneu</p>
        <p class="subtitle">
            Pneu: {{ $pneu->numero_fogo }} |
            Marca / Modelo: {{ trim(implode(' / ', array_filter([$pneu->marcaCatalogo?->nome, $pneu->modeloCatalogo?->nome]))) ?: '-' }} |
            Medida: {{ $pneu->medidaCatalogo?->codigo ?? '-' }}
        </p>
        <p class="summary">
            Veículo atual: <strong>{{ $pneu->posicaoVeiculo?->veiculo?->placa ?? 'Não aplicado' }}</strong><br>
            Total de inspeções: <strong>{{ $resumo['total_inspecoes'] }}</strong><br>
            Primeira inspeção: <strong>{{ $resumo['primeira_inspecao'] ?? '-' }}</strong><br>
            Última inspeção: <strong>{{ $resumo['ultima_inspecao'] ?? '-' }}</strong><br>
            Média mais recente: <strong>{{ $resumo['media_atual'] !== null ? number_format((float) $resumo['media_atual'], 2, ',', '.') : '-' }}</strong><br>
            Menor sulco registrado: <strong>{{ $resumo['menor_sulco'] !== null ? number_format((float) $resumo['menor_sulco'], 2, ',', '.') : '-' }}</strong><br>
            Data de geração: <strong>{{ $dataGeracao }}</strong>
        </p>
    </div>

    @if($linhas->isEmpty())
        <div class="empty">Nenhuma inspeção encontrada para o pneu informado.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width: 8%;">Data</th>
                    <th style="width: 9%;">Tipo</th>
                    <th style="width: 10%;">Resultado</th>
                    <th style="width: 8%;">Veículo</th>
                    <th style="width: 10%;">Posição</th>
                    <th style="width: 6%;">Ciclo</th>
                    <th style="width: 8%;">KM</th>
                    <th style="width: 6%;">Int.</th>
                    <th style="width: 6%;">Centro</th>
                    <th style="width: 6%;">Ext.</th>
                    <th style="width: 6%;">Média</th>
                    <th style="width: 6%;">Recap.</th>
                    <th style="width: 11%;">Observação</th>
                </tr>
            </thead>
            <tbody>
                @foreach($linhas as $linha)
                    <tr>
                        <td>{{ $linha['data_inspecao'] ?? '-' }}</td>
                        <td>{{ $linha['tipo'] ?? '-' }}</td>
                        <td>{{ $linha['resultado'] ?? '-' }}</td>
                        <td>{{ $linha['veiculo'] ?: '-' }}</td>
                        <td>{{ $linha['posicao'] ?: '-' }}</td>
                        <td>{{ $linha['ciclo'] !== null ? 'Ciclo '.$linha['ciclo'] : '-' }}</td>
                        <td>{{ $linha['km_referencia'] !== null ? number_format((float) $linha['km_referencia'], 0, ',', '.') : '-' }}</td>
                        <td>{{ $linha['sulco_interno'] !== null ? number_format((float) $linha['sulco_interno'], 2, ',', '.') : '-' }}</td>
                        <td>{{ $linha['sulco_centro'] !== null ? number_format((float) $linha['sulco_centro'], 2, ',', '.') : '-' }}</td>
                        <td>{{ $linha['sulco_externo'] !== null ? number_format((float) $linha['sulco_externo'], 2, ',', '.') : '-' }}</td>
                        <td>{{ $linha['media_sulcos'] !== null ? number_format((float) $linha['media_sulcos'], 2, ',', '.') : '-' }}</td>
                        <td>
                            @if($linha['apto_recapagem'] === null)
                                -
                            @else
                                {{ $linha['apto_recapagem'] ? 'SIM' : 'NAO' }}
                            @endif
                        </td>
                        <td>{{ $linha['observacao'] ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
