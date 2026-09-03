<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Primeiras Aplicações por Ciclo</title>
    <style>
        @page { margin: 18px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        .header { margin-bottom: 18px; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; }
        .title { font-size: 20px; font-weight: bold; color: #111827; margin: 0 0 6px; }
        .subtitle, .summary { margin: 0; color: #4b5563; }
        .summary { margin-top: 8px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #d1d5db; padding: 6px; vertical-align: top; word-wrap: break-word; }
        th { background: #e5e7eb; font-size: 9px; text-align: left; color: #111827; }
        .empty { margin-top: 20px; padding: 12px; border: 1px dashed #cbd5e1; background: #f8fafc; color: #475569; }
    </style>
</head>
<body>
    <div class="header">
        <p class="title">Primeiras Aplicações por Ciclo</p>
        <p class="subtitle">Período de aplicação: {{ $dataInicial }} até {{ $dataFinal }}</p>
        <p class="summary">Total de ciclos iniciados no período: <strong>{{ $aplicacoes->count() }}</strong><br>Data de geração: <strong>{{ $dataGeracao }}</strong></p>
    </div>

    @if($aplicacoes->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th style="width: 8%;">Dt. aplicação</th>
                    <th style="width: 8%;">Nº de fogo</th>
                    <th style="width: 5%;">Ciclo</th>
                    <th style="width: 9%;">Placa</th>
                    <th style="width: 6%;">Eixo</th>
                    <th style="width: 8%;">Posição</th>
                    <th style="width: 8%;">KM inicial</th>
                    <th style="width: 7%;">Sulco</th>
                    <th style="width: 11%;">Marca / modelo</th>
                    <th style="width: 9%;">Medida</th>
                    <th style="width: 10%;">Desenho</th>
                    <th style="width: 11%;">Observação</th>
                </tr>
            </thead>
            <tbody>
                @foreach($aplicacoes as $aplicacao)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($aplicacao->data_inicial)->format('d/m/Y') }}</td>
                    <td>{{ $aplicacao->pneu?->numero_fogo ?? '-' }}</td>
                    <td>{{ $aplicacao->ciclo?->numero ?? $aplicacao->ciclo_vida }}</td>
                    <td>{{ $aplicacao->veiculo?->placa ?? '-' }}</td>
                    <td>{{ $aplicacao->eixo ?? '-' }}</td>
                    <td>{{ $aplicacao->posicao ?? '-' }}</td>
                    <td>{{ $aplicacao->km_inicial !== null ? number_format((float) $aplicacao->km_inicial, 0, ',', '.') : '-' }}</td>
                    <td>{{ $aplicacao->sulco_movimento !== null ? number_format((float) $aplicacao->sulco_movimento, 2, ',', '.') : '-' }}</td>
                    <td>{{ collect([$aplicacao->pneu?->marcaCatalogo?->nome, $aplicacao->pneu?->modeloCatalogo?->nome])->filter()->implode(' / ') ?: '-' }}</td>
                    <td>{{ $aplicacao->pneu?->medidaCatalogo?->codigo ?? '-' }}</td>
                    <td>{{ $aplicacao->ciclo?->desenhoPneu?->descricao ?? '-' }}</td>
                    <td>{{ $aplicacao->observacao ?: '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty">Nenhuma primeira aplicação de ciclo foi encontrada para o período informado.</div>
    @endif
</body>
</html>
