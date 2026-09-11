<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="theme-color" content="#07111f">
    <title>Resultado por período | {{ $dashboard['identificacao']['periodo'] }}</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #07111f;
            --panel: #0d1b2a;
            --panel-soft: #102338;
            --line: #1d3a50;
            --text: #f8fafc;
            --muted: #8fa7b9;
            --quiet: #658094;
            --teal: #5eead4;
            --teal-deep: #0f766e;
            --amber: #fbbf24;
            --rose: #fb7185;
            --sky: #7dd3fc;
            --green: #86efac;
        }

        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--text); font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .dashboard-shell { width: min(100% - 2rem, 1240px); margin: 0 auto; padding: 2rem 0 3rem; }
        .dashboard-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1.5rem; margin-bottom: 2.5rem; }
        .brand { color: var(--teal); font-size: .76rem; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
        .eyebrow { margin: 0 0 .55rem; color: var(--muted); font-size: .72rem; font-weight: 750; letter-spacing: .12em; text-transform: uppercase; }
        .dashboard-header h1 { max-width: 38rem; margin: 0; font-size: clamp(1.8rem, 4vw, 3.4rem); line-height: 1.03; letter-spacing: -.055em; }
        .dashboard-header-copy { margin-top: .85rem; color: var(--muted); font-size: .92rem; }
        .access-box { min-width: 15rem; padding: .9rem 1rem; border: 1px solid var(--line); border-radius: .9rem; background: rgb(13 27 42 / 70%); }
        .access-label { color: var(--quiet); font-size: .68rem; font-weight: 750; letter-spacing: .1em; text-transform: uppercase; }
        .access-name { margin-top: .25rem; color: var(--text); font-size: .88rem; font-weight: 700; }
        .access-expires { margin-top: .5rem; color: var(--muted); font-size: .73rem; }
        .period-strip { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1.15rem; padding: 1rem 1.15rem; border: 1px solid var(--line); border-radius: 1rem; background: linear-gradient(110deg, #102b40, #0d1b2a 70%); }
        .period-title { color: var(--text); font-size: 1.05rem; font-weight: 750; }
        .period-subtitle { margin-top: .22rem; color: var(--muted); font-size: .78rem; }
        .vehicle-list { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: .45rem; }
        .vehicle-pill { padding: .35rem .6rem; border: 1px solid #28536a; border-radius: 999px; color: var(--teal); background: rgb(15 118 110 / 12%); font-size: .72rem; font-weight: 750; }
        .metric-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .85rem; }
        .metric-card, .section-card { min-width: 0; border: 1px solid var(--line); border-radius: 1rem; background: var(--panel); box-shadow: 0 1rem 2.5rem rgb(0 0 0 / 10%); }
        .metric-card { padding: 1.1rem; }
        .metric-card.feature { border-color: #28796f; background: linear-gradient(145deg, #103e45, var(--panel) 72%); }
        .metric-label { color: var(--muted); font-size: .73rem; font-weight: 700; letter-spacing: .02em; }
        .metric-value { margin-top: .48rem; color: var(--text); font-size: clamp(1.25rem, 2.5vw, 1.8rem); font-weight: 780; letter-spacing: -.045em; white-space: nowrap; }
        .feature .metric-value { color: var(--teal); font-size: clamp(1.55rem, 3vw, 2.35rem); }
        .metric-note { min-height: 1.1rem; margin-top: .42rem; color: var(--quiet); font-size: .72rem; line-height: 1.45; }
        .positive { color: var(--green) !important; }
        .negative { color: var(--rose) !important; }
        .dashboard-section { margin-top: 2.2rem; }
        .section-heading { display: flex; align-items: end; justify-content: space-between; gap: 1rem; margin-bottom: .8rem; }
        .section-heading h2 { margin: 0; font-size: 1.1rem; letter-spacing: -.025em; }
        .section-heading p { margin: .25rem 0 0; color: var(--muted); font-size: .78rem; }
        .section-grid { display: grid; grid-template-columns: minmax(0, 1.35fr) minmax(18rem, .65fr); gap: .85rem; }
        .section-card { padding: 1.2rem; }
        .section-card h3 { margin: 0; font-size: .92rem; }
        .section-card-intro { margin: .35rem 0 1.15rem; color: var(--muted); font-size: .76rem; line-height: 1.45; }
        .comparison-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .65rem; }
        .comparison-item { padding: .8rem; border-radius: .75rem; background: var(--panel-soft); }
        .comparison-label { color: var(--muted); font-size: .69rem; font-weight: 700; line-height: 1.35; }
        .comparison-value { margin-top: .35rem; color: var(--text); font-size: 1.05rem; font-weight: 760; white-space: nowrap; }
        .comparison-detail { margin-top: .25rem; color: var(--quiet); font-size: .68rem; line-height: 1.35; }
        .dispersion-list { display: grid; gap: .8rem; }
        .dispersion-row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding-bottom: .75rem; border-bottom: 1px solid rgb(29 58 80 / 75%); }
        .dispersion-row:last-child { padding-bottom: 0; border-bottom: 0; }
        .dispersion-label { color: var(--muted); font-size: .74rem; }
        .dispersion-value { color: var(--text); font-size: .94rem; font-weight: 750; text-align: right; white-space: nowrap; }
        .dispersion-percent { display: block; margin-top: .18rem; color: var(--quiet); font-size: .68rem; font-weight: 500; }
        .cost-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .85rem; }
        .cost-card { position: relative; overflow: hidden; }
        .cost-card::before { position: absolute; inset: 0 auto 0 0; width: 3px; background: var(--amber); content: ''; }
        .cost-card.maintenance::before { background: var(--rose); }
        .cost-card.salary::before { background: var(--sky); }
        .cost-card .metric-value { font-size: 1.42rem; }
        .table-card { overflow: hidden; padding: 0; }
        .table-card-header { padding: 1.2rem 1.2rem .9rem; }
        .table-scroll { overflow-x: auto; }
        table { width: 100%; min-width: 750px; border-collapse: collapse; }
        th { padding: .7rem 1.2rem; border-top: 1px solid var(--line); color: var(--quiet); font-size: .66rem; font-weight: 750; letter-spacing: .08em; text-align: left; text-transform: uppercase; white-space: nowrap; }
        td { padding: .85rem 1.2rem; border-top: 1px solid rgb(29 58 80 / 65%); color: var(--muted); font-size: .77rem; white-space: nowrap; }
        td strong { color: var(--text); font-size: .82rem; }
        td.numeric { color: var(--text); font-variant-numeric: tabular-nums; text-align: right; }
        th.numeric { text-align: right; }
        .table-empty { padding: 1.2rem; color: var(--muted); font-size: .8rem; }
        .footnote { margin: 1.25rem 0 0; color: var(--quiet); font-size: .7rem; line-height: 1.55; }
        .dashboard-footer { display: flex; justify-content: space-between; gap: 1rem; margin-top: 2.5rem; padding-top: 1rem; border-top: 1px solid var(--line); color: var(--quiet); font-size: .68rem; }

        @media (max-width: 900px) {
            .metric-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .section-grid { grid-template-columns: 1fr; }
            .cost-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 620px) {
            .dashboard-shell { width: min(100% - 1.2rem, 1240px); padding-top: 1.2rem; }
            .dashboard-header, .period-strip, .section-heading, .dashboard-footer { display: block; }
            .dashboard-header { margin-bottom: 1.7rem; }
            .access-box { min-width: 0; margin-top: 1.2rem; }
            .vehicle-list { justify-content: flex-start; margin-top: .8rem; }
            .metric-grid, .cost-grid, .comparison-grid { grid-template-columns: 1fr; }
            .metric-card { padding: .95rem; }
            .section-card { padding: 1rem; }
            .section-heading p { margin-top: .4rem; }
            .dashboard-footer span + span { display: block; margin-top: .4rem; }
        }
    </style>
</head>
<body>
@php
    $metricas = $dashboard['metricas'];
    $observacoes = $dashboard['observacoes'];
    $money = static fn (?float $value): string => $value === null ? 'N/D' : 'R$ '.number_format($value, 2, ',', '.');
    $number = static fn (?float $value, int $decimals = 0): string => $value === null ? 'N/D' : number_format($value, $decimals, ',', '.');
    $percent = static fn (?float $value): string => $value === null ? 'N/D' : number_format($value, 2, ',', '.').'%';
    $signedNumber = static fn (?float $value): string => $value === null ? 'N/D' : ($value > 0 ? '+' : '').number_format($value, 0, ',', '.').' km';
    $signedPercent = static fn (?float $value): string => $value === null ? 'N/D' : ($value > 0 ? '+' : '').number_format($value, 2, ',', '.').'%';
@endphp
<main class="dashboard-shell">
    <header class="dashboard-header">
        <div>
            <div class="brand">Magna Gestão</div>
            <p class="eyebrow" style="margin-top: 2.3rem;">Painel compartilhado</p>
            <h1>Resultado operacional<br>por período</h1>
            <p class="dashboard-header-copy">Indicadores consolidados dos resultados selecionados.</p>
        </div>
        <div class="access-box">
            <div class="access-label">Acesso autorizado para</div>
            <div class="access-name">{{ $share->destinatario_nome }}</div>
            @if ($share->destinatario_email)
                <div class="access-expires">{{ $share->destinatario_email }}</div>
            @endif
            <div class="access-expires">Válido até {{ $share->expires_at->format('d/m/Y H:i') }}</div>
        </div>
    </header>

    <section class="period-strip">
        <div>
            <div class="period-title">{{ $dashboard['identificacao']['periodo'] }}</div>
            <div class="period-subtitle">{{ $observacoes['total_resultados'] }} resultado(s) · {{ $observacoes['documentos'] }} documento(s) de frete</div>
        </div>
        <div class="vehicle-list" aria-label="Veículos incluídos">
            @foreach ($dashboard['identificacao']['veiculos'] as $placa)
                <span class="vehicle-pill">{{ $placa }}</span>
            @endforeach
        </div>
    </section>

    <section class="metric-grid" aria-label="Indicadores principais">
        <article class="metric-card feature">
            <div class="metric-label">Faturamento geral</div>
            <div class="metric-value">{{ $money($metricas['faturamento']) }}</div>
            <div class="metric-note">Receita líquida dos documentos vinculados</div>
        </article>
        <article class="metric-card">
            <div class="metric-label">Quantidade de veículos</div>
            <div class="metric-value">{{ $number($metricas['veiculos']) }}</div>
            <div class="metric-note">{{ $observacoes['total_resultados'] }} resultado(s) selecionado(s)</div>
        </article>
        <article class="metric-card">
            <div class="metric-label">Faturamento médio</div>
            <div class="metric-value">{{ $money($metricas['faturamento_medio']) }}</div>
            <div class="metric-note">Média por veículo incluído</div>
        </article>
        <article class="metric-card">
            <div class="metric-label">KM pago</div>
            <div class="metric-value">{{ $number($metricas['km_pago']) }} km</div>
            <div class="metric-note">{{ $observacoes['viagens'] }} viagem(ns) vinculada(s)</div>
        </article>
    </section>

    <section class="dashboard-section">
        <div class="section-heading">
            <div>
                <h2>Quilometragem e dispersão</h2>
                <p>Comparação entre os quilômetros registrados nas diferentes fontes.</p>
            </div>
        </div>
        <div class="section-grid">
            <article class="section-card">
                <h3>KM rodado abastecimento x KM rodado viagens</h3>
                <p class="section-card-intro">O KM de abastecimento depende de um registro anterior e de um abastecimento final. A base abaixo considera {{ $observacoes['veiculos_com_km_abastecimento'] }} veículo(s) com essa referência.</p>
                <div class="comparison-grid">
                    <div class="comparison-item">
                        <div class="comparison-label">KM rodado abastecimento</div>
                        <div class="comparison-value">{{ $metricas['km_rodado_abastecimento'] === null ? 'N/D' : $number($metricas['km_rodado_abastecimento']).' km' }}</div>
                        <div class="comparison-detail">{{ $observacoes['veiculos_com_km_abastecimento'] }} de {{ $metricas['veiculos'] }} veículo(s) com base</div>
                    </div>
                    <div class="comparison-item">
                        <div class="comparison-label">KM rodado viagens</div>
                        <div class="comparison-value">{{ $number($metricas['km_rodado_viagens']) }} km</div>
                        <div class="comparison-detail">Soma informada nas viagens</div>
                    </div>
                    <div class="comparison-item">
                        <div class="comparison-label">Diferença entre fontes</div>
                        <div class="comparison-value {{ ($metricas['diferenca_km_abastecimento_viagens'] ?? 0) < 0 ? 'negative' : 'positive' }}">{{ $signedNumber($metricas['diferenca_km_abastecimento_viagens']) }}</div>
                        <div class="comparison-detail">{{ $signedPercent($metricas['percentual_km_abastecimento_viagens']) }} sobre KM de viagens</div>
                    </div>
                </div>
            </article>
            <article class="section-card">
                <h3>Dispersão</h3>
                <p class="section-card-intro">Diferença entre KM rodado e KM pago, com percentual sobre a base paga.</p>
                <div class="dispersion-list">
                    <div class="dispersion-row">
                        <div class="dispersion-label">Abastecimento x KM pago</div>
                        <div class="dispersion-value {{ ($metricas['dispersao_km_abastecimento'] ?? 0) < 0 ? 'negative' : '' }}">{{ $signedNumber($metricas['dispersao_km_abastecimento']) }}<span class="dispersion-percent">{{ $signedPercent($metricas['percentual_dispersao_km_abastecimento']) }} do KM pago</span></div>
                    </div>
                    <div class="dispersion-row">
                        <div class="dispersion-label">Viagens x KM pago</div>
                        <div class="dispersion-value {{ ($metricas['dispersao_km_viagens'] ?? 0) < 0 ? 'negative' : '' }}">{{ $signedNumber($metricas['dispersao_km_viagens']) }}<span class="dispersion-percent">{{ $signedPercent($metricas['percentual_dispersao_km_viagens']) }} do KM pago</span></div>
                    </div>
                </div>
            </article>
        </div>
    </section>

    <section class="dashboard-section">
        <div class="section-heading">
            <div>
                <h2>Custos e proporções</h2>
                <p>Valores consolidados e sua participação no faturamento geral.</p>
            </div>
        </div>
        <div class="cost-grid">
            <article class="section-card cost-card">
                <div class="metric-label">Custo combustível</div>
                <div class="metric-value">{{ $money($metricas['combustivel']) }}</div>
                <div class="metric-note">{{ $observacoes['abastecimentos'] }} abastecimento(s)</div>
            </article>
            <article class="section-card cost-card">
                <div class="metric-label">% Diesel / faturamento</div>
                <div class="metric-value">{{ $percent($metricas['percentual_combustivel_faturamento']) }}</div>
                <div class="metric-note">Participação do custo de combustível</div>
            </article>
            <article class="section-card cost-card">
                <div class="metric-label">Custo médio do diesel</div>
                <div class="metric-value">{{ $money($metricas['custo_medio_diesel']) }}</div>
                <div class="metric-note">Preço médio por litro · {{ $number($metricas['litros'], 2) }} L</div>
            </article>
            <article class="section-card cost-card">
                <div class="metric-label">Custo diesel R$ / Km</div>
                <div class="metric-value">{{ $money($metricas['custo_diesel_por_km']) }}</div>
                <div class="metric-note">Custo de combustível / KM abastecimento</div>
            </article>
            <article class="section-card cost-card maintenance">
                <div class="metric-label">Custo manutenção</div>
                <div class="metric-value">{{ $money($metricas['manutencao']) }}</div>
                <div class="metric-note">Lançamentos de manutenção vinculados</div>
            </article>
            <article class="section-card cost-card maintenance">
                <div class="metric-label">% Manut. / faturamento</div>
                <div class="metric-value">{{ $percent($metricas['percentual_manutencao_faturamento']) }}</div>
                <div class="metric-note">Participação do custo de manutenção</div>
            </article>
            <article class="section-card cost-card maintenance">
                <div class="metric-label">Custo médio veículo</div>
                <div class="metric-value">{{ $money($metricas['custo_medio_veiculo']) }}</div>
                <div class="metric-note">Diesel + manutenção + salário / veículo</div>
            </article>
            <article class="section-card cost-card salary">
                <div class="metric-label">Custo salário</div>
                <div class="metric-value">{{ $money($metricas['salario']) }}</div>
                <div class="metric-note">Folha de pagamento informada no período</div>
            </article>
            <article class="section-card cost-card salary">
                <div class="metric-label">% Salário / faturamento</div>
                <div class="metric-value">{{ $percent($metricas['percentual_salario_faturamento']) }}</div>
                <div class="metric-note">Participação da folha no faturamento</div>
            </article>
        </div>
    </section>

    <section class="dashboard-section">
        <div class="section-heading">
            <div>
                <h2>Resumo por veículo</h2>
                <p>Detalhamento dos resultados que formam o consolidado.</p>
            </div>
        </div>
        <article class="section-card table-card">
            <div class="table-card-header">
                <h3>Resultados selecionados</h3>
            </div>
            <div class="table-scroll">
                @if ($dashboard['linhas']->isNotEmpty())
                    <table>
                        <thead>
                            <tr>
                                <th>Veículo</th>
                                <th class="numeric">Faturamento</th>
                                <th class="numeric">KM abast.</th>
                                <th class="numeric">KM viagens</th>
                                <th class="numeric">KM pago</th>
                                <th class="numeric">Diesel</th>
                                <th class="numeric">Manutenção</th>
                                <th class="numeric">Salário</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($dashboard['linhas'] as $linha)
                            <tr>
                                <td><strong>{{ $linha['placa'] }}</strong>@if ($linha['tipo_veiculo'])<br><small>{{ $linha['tipo_veiculo'] }}</small>@endif</td>
                                <td class="numeric">{{ $money($linha['faturamento']) }}</td>
                                <td class="numeric">{{ $linha['km_rodado_abastecimento'] === null ? 'N/D' : $number($linha['km_rodado_abastecimento']).' km' }}</td>
                                <td class="numeric">{{ $number($linha['km_rodado_viagens']) }} km</td>
                                <td class="numeric">{{ $number($linha['km_pago']) }} km</td>
                                <td class="numeric">{{ $money($linha['combustivel']) }}</td>
                                <td class="numeric">{{ $money($linha['manutencao']) }}</td>
                                <td class="numeric">{{ $money($linha['folha_pagamento']) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="table-empty">Nenhum resultado disponível.</div>
                @endif
            </div>
        </article>
        <p class="footnote">N/D indica que a informação não pôde ser apurada. O KM rodado por abastecimento usa o último abastecimento anterior como referência. O custo médio do veículo considera combustível, manutenção e salário.</p>
    </section>

    <footer class="dashboard-footer">
        <span>Dados protegidos por link de acesso temporário.</span>
        <span>Atualizado na consulta · validade até {{ $share->expires_at->format('d/m/Y H:i') }}</span>
    </footer>
</main>
</body>
</html>
