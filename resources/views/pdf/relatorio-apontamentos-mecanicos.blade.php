<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Relatório de Apontamentos dos Mecânicos</title>
    <style>
        body { font-family: "DejaVu Sans", sans-serif; font-size: 9px; color: #111; }
        h1 { font-size: 16px; text-align: center; margin: 0 0 4px; }
        .subtitle { text-align: center; color: #444; margin-bottom: 10px; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .summary td { border: 1px solid #111; padding: 6px; width: 25%; }
        .label { font-size: 8px; color: #555; text-transform: uppercase; }
        .value { font-size: 12px; font-weight: bold; margin-top: 2px; }
        .group { margin-top: 10px; page-break-inside: avoid; }
        .group-title { background: #eee; border: 1px solid #111; padding: 6px; font-weight: bold; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { border: 1px solid #111; padding: 4px; vertical-align: top; }
        table.data th { background: #f3f3f3; font-size: 8px; text-transform: uppercase; }
        .center { text-align: center; }
        .right { text-align: right; }
        .idle { font-weight: bold; color: #7c2d12; }
        .muted { color: #777; }
        .footer { margin-top: 10px; font-size: 8px; text-align: right; color: #555; }
    </style>
</head>

<body>
    @php
        $formatarMinutos = function (?int $minutos): string {
            if ($minutos === null) {
                return '-';
            }

            return intdiv($minutos, 60).'h '.($minutos % 60).'min';
        };
    @endphp

    <h1>RELATÓRIO DE APONTAMENTOS DOS MECÂNICOS</h1>
    <div class="subtitle">
        Período:
        {{ $dados['periodo_inicio_formatado'] ?? 'Não informado' }}
        a
        {{ $dados['periodo_fim_formatado'] ?? 'Não informado' }}
        | Gerado em: {{ $dataGeracao }}
    </div>

    <table class="summary">
        <tr>
            <td>
                <div class="label">Mecânicos</div>
                <div class="value">{{ $dados['total_mecanicos'] }}</div>
            </td>
            <td>
                <div class="label">Apontamentos</div>
                <div class="value">{{ $dados['total_apontamentos'] }}</div>
            </td>
            <td>
                <div class="label">Tempo trabalhado</div>
                <div class="value">{{ $formatarMinutos($dados['total_trabalhado_minutos']) }}</div>
            </td>
            <td>
                <div class="label">Tempo ocioso</div>
                <div class="value">{{ $formatarMinutos($dados['total_ocioso_minutos']) }}</div>
            </td>
        </tr>
    </table>

    @forelse ($dados['grupos'] as $grupo)
        <div class="group">
            <div class="group-title">
                {{ $grupo['colaborador_codigo'] }} - {{ $grupo['colaborador_nome'] }}
                | Apontamentos: {{ $grupo['total_apontamentos'] }}
                | Trabalhado: {{ $formatarMinutos($grupo['total_trabalhado_minutos']) }}
                | Ocioso: {{ $formatarMinutos($grupo['total_ocioso_minutos']) }}
            </div>
            <table class="data">
                <thead>
                    <tr>
                        <th width="4%">Seq.</th>
                        <th width="7%">OS</th>
                        <th width="8%">Veículo</th>
                        <th width="12%">Início</th>
                        <th width="12%">Fim</th>
                        <th width="8%">Trab.</th>
                        <th width="8%">Ocioso</th>
                        <th width="41%">Serviços</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($grupo['linhas'] as $linha)
                        <tr>
                            <td class="center">{{ $loop->iteration }}</td>
                            <td class="center">#{{ $linha['ordem_servico_id'] }}</td>
                            <td class="center">{{ $linha['veiculo'] }}</td>
                            <td>{{ $linha['iniciado_em_formatado'] }}</td>
                            <td>{{ $linha['encerrado_em_formatado'] }}</td>
                            <td class="center">{{ $formatarMinutos($linha['trabalhado_minutos']) }}</td>
                            <td class="center {{ ($linha['ocioso_minutos'] ?? 0) > 0 ? 'idle' : 'muted' }}">
                                {{ $formatarMinutos($linha['ocioso_minutos']) }}
                            </td>
                            <td>
                                @forelse ($linha['servicos'] as $servico)
                                    <div>{{ $servico }}</div>
                                @empty
                                    <span class="muted">-</span>
                                @endforelse
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <p>Nenhum apontamento encontrado.</p>
    @endforelse

    <div class="footer">Magna Gestão | Relatório gerado automaticamente</div>
</body>

</html>
