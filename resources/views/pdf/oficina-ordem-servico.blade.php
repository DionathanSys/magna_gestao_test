<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Relatório Oficina OS #{{ $ordemServico->id }}</title>
    <style>
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; color: #111; }
        .box { border: 1px solid #111; padding: 6px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #111; padding: 4px; vertical-align: top; }
        th { background: #eee; }
        .no-border td { border: none; }
        .title { font-size: 15px; font-weight: bold; text-align: center; margin-bottom: 8px; }
        .section-title { font-weight: bold; margin-bottom: 4px; }
        .right { text-align: right; }
        .center { text-align: center; }
        .responsavel-header { width: 100%; margin-bottom: 6px; }
        .responsavel-header td { border: none; padding: 2px 0; }
        .assinatura { height: 18px; border-bottom: 1px solid #111; }
        .assinatura-label { font-size: 8px; text-align: center; }
    </style>
</head>

<body>
    @php
        $apontamentosPorColaborador = $ordemServico->apontamentosOficina
            ->sortBy('iniciado_em')
            ->groupBy('colaborador_id');
    @endphp

    <div class="title">RELATÓRIO DE SERVIÇOS DA OFICINA</div>

    <div class="box">
        <table class="no-border">
            <tr>
                <td><strong>OS:</strong> {{ $ordemServico->id }}</td>
                <td><strong>Veículo:</strong> {{ $ordemServico->veiculo->placa ?? '-' }}</td>
                <td><strong>Status:</strong> {{ $ordemServico->status?->value ?? $ordemServico->status }}</td>
            </tr>
            <tr>
                <td><strong>Abertura:</strong> {{ $ordemServico->data_inicio?->format('d/m/Y H:i') ?? '-' }}</td>
                <td><strong>KM:</strong> {{ number_format($ordemServico->quilometragem ?? 0, 0, ',', '.') }}</td>
                <td><strong>Gerado em:</strong> {{ $dataGeracao }}</td>
            </tr>
        </table>
    </div>

    <div class="box">
        <div class="section-title">Serviços da OS</div>
        <table>
            <thead>
                <tr>
                    <th width="15%">Código</th>
                    <th width="55%">Serviço</th>
                    <th width="15%">Posição</th>
                    <th width="15%">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($ordemServico->itens as $item)
                    <tr>
                        <td>{{ $item->servico->codigo ?? '-' }}</td>
                        <td>{{ $item->servico->descricao ?? '-' }}</td>
                        <td class="center">{{ $item->posicao ?: '-' }}</td>
                        <td class="center">{{ $item->status?->value ?? $item->status ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @forelse ($apontamentosPorColaborador as $apontamentos)
        @php
            $colaborador = $apontamentos->first()->colaborador;
            $totalMinutos = $apontamentos->sum(function ($apontamento) {
                return $apontamento->encerrado_em
                    ? $apontamento->iniciado_em->diffInMinutes($apontamento->encerrado_em)
                    : 0;
            });
        @endphp

        <div class="box">
            <table class="responsavel-header">
                <tr>
                    <td width="65%" class="section-title">
                        Responsável: {{ $colaborador->nome ?? '-' }} | Código: {{ $colaborador->codigo ?? '-' }}
                    </td>
                    <td width="35%">
                        <div class="assinatura"></div>
                        <div class="assinatura-label">Assinatura do responsável</div>
                    </td>
                </tr>
            </table>
            <table>
                <thead>
                    <tr>
                        <th width="48%">Serviços</th>
                        <th width="20%">Início</th>
                        <th width="20%">Fim</th>
                        <th width="12%">Tempo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($apontamentos as $apontamento)
                        @php
                            $minutos = $apontamento->encerrado_em
                                ? $apontamento->iniciado_em->diffInMinutes($apontamento->encerrado_em)
                                : 0;
                        @endphp
                        <tr>
                            <td>
                                @forelse ($apontamento->itens as $item)
                                    <div>{{ $item->servico->codigo ?? '-' }} - {{ $item->servico->descricao ?? '-' }}</div>
                                @empty
                                    -
                                @endforelse
                            </td>
                            <td>{{ $apontamento->iniciado_em?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td>{{ $apontamento->encerrado_em?->format('d/m/Y H:i') ?? 'Aberto' }}</td>
                            <td class="center">{{ intdiv($minutos, 60) }}h {{ $minutos % 60 }}min</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td colspan="3" class="right"><strong>Total do responsável</strong></td>
                        <td class="center"><strong>{{ intdiv($totalMinutos, 60) }}h {{ $totalMinutos % 60 }}min</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    @empty
        <div class="box">Nenhum apontamento de oficina registrado para esta OS.</div>
    @endforelse
</body>

</html>
