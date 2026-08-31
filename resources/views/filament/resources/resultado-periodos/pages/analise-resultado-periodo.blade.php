<x-filament-panels::page>
    @php
        $veiculo = $record->veiculo;
        $periodo = \Carbon\Carbon::parse($record->data_inicio)->format('d/m/Y') . ' a ' . \Carbon\Carbon::parse($record->data_fim)->format('d/m/Y');
        $margemPositiva = ($resumo['margem_liquida'] ?? 0) >= 0;
        $statusTom = $record->status === \App\Enum\StatusDiversosEnum::PENDENTE->value ? 'open' : 'closed';
    @endphp

    <style>
        .resultado-analise { display: grid; gap: 1.25rem; color: #0f172a; }
        .resultado-analise * { box-sizing: border-box; }
        .resultado-hero { display: flex; justify-content: space-between; gap: 1.5rem; padding: 1.5rem; border: 1px solid #dbe5f1; border-radius: 1.25rem; background: linear-gradient(135deg, #eff6ff 0%, #ffffff 58%, #ecfdf5 100%); }
        .resultado-eyebrow { margin: 0 0 .35rem; color: #475569; font-size: .75rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        .resultado-hero h2 { margin: 0; font-size: 1.75rem; font-weight: 750; letter-spacing: -.035em; }
        .resultado-hero p { margin: .4rem 0 0; color: #64748b; font-size: .9rem; }
        .resultado-hero-meta { display: flex; flex-wrap: wrap; justify-content: flex-end; align-content: flex-start; gap: .6rem; }
        .resultado-chip { display: inline-flex; align-items: center; min-height: 2rem; padding: 0 .7rem; border-radius: 999px; background: rgba(255, 255, 255, .78); border: 1px solid #dbe5f1; color: #334155; font-size: .8rem; font-weight: 650; }
        .resultado-chip.open { border-color: #86efac; background: #f0fdf4; color: #15803d; }
        .resultado-chip.closed { border-color: #cbd5e1; background: #f8fafc; color: #475569; }
        .resultado-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .9rem; }
        .resultado-kpi { min-width: 0; padding: 1rem; border: 1px solid #e2e8f0; border-radius: 1rem; background: #fff; box-shadow: 0 1px 2px rgba(15, 23, 42, .03); }
        .resultado-kpi-label { color: #64748b; font-size: .78rem; font-weight: 650; }
        .resultado-kpi-value { margin-top: .4rem; color: #0f172a; font-size: 1.35rem; font-weight: 750; letter-spacing: -.025em; }
        .resultado-kpi-value.positive { color: #047857; }
        .resultado-kpi-value.negative { color: #be123c; }
        .resultado-kpi-detail { margin-top: .35rem; color: #94a3b8; font-size: .75rem; }
        .resultado-grid { display: grid; grid-template-columns: minmax(0, 1.45fr) minmax(19rem, .8fr); gap: 1.25rem; }
        .resultado-card { min-width: 0; padding: 1.25rem; border: 1px solid #e2e8f0; border-radius: 1.1rem; background: #fff; }
        .resultado-card-title { margin: 0; color: #0f172a; font-size: 1rem; font-weight: 750; }
        .resultado-card-subtitle { margin: .3rem 0 0; color: #64748b; font-size: .8rem; }
        .resultado-financial-list { display: grid; gap: .95rem; margin-top: 1.25rem; }
        .resultado-financial-row { display: grid; grid-template-columns: minmax(6rem, .8fr) minmax(5rem, .55fr) minmax(9rem, 1.4fr); align-items: center; gap: .75rem; }
        .resultado-financial-label { color: #475569; font-size: .82rem; font-weight: 650; }
        .resultado-financial-value { color: #0f172a; font-size: .82rem; font-weight: 700; text-align: right; white-space: nowrap; }
        .resultado-progress { height: .55rem; overflow: hidden; border-radius: 999px; background: #f1f5f9; }
        .resultado-progress > span { display: block; height: 100%; border-radius: inherit; }
         .resultado-progress .amber { background: #f59e0b; }
         .resultado-progress .rose { background: #f43f5e; }
         .resultado-progress .sky { background: #0ea5e9; }
         .resultado-progress .emerald { background: #10b981; }
          .resultado-tabs { display: flex; gap: .5rem; overflow-x: auto; border-bottom: 1px solid #e2e8f0; }
          .resultado-tab { flex: 0 0 auto; padding: .7rem .9rem; border-bottom: 2px solid transparent; color: #64748b; font-size: .82rem; font-weight: 700; text-decoration: none; }
         .resultado-tab.active { border-color: #0f766e; color: #0f766e; }
         .resultado-metas { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; margin-top: 1.15rem; }
         .resultado-meta { padding: .9rem; border: 1px solid #e2e8f0; border-radius: .85rem; background: #f8fafc; }
         .resultado-meta-label { color: #64748b; font-size: .72rem; font-weight: 650; }
         .resultado-meta-value { margin-top: .28rem; color: #047857; font-size: 1.05rem; font-weight: 750; }
         .resultado-meta-value.over { color: #be123c; }
         .resultado-meta-detail { margin-top: .2rem; color: #94a3b8; font-size: .71rem; }
          .resultado-chart-summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .7rem; margin-top: 1.15rem; }
          .resultado-chart-stat { min-width: 0; padding: .75rem .85rem; border: 1px solid #e2e8f0; border-radius: .8rem; background: #f8fafc; }
          .resultado-chart-stat-label { color: #64748b; font-size: .7rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
          .resultado-chart-stat-value { margin-top: .28rem; color: #0f766e; font-size: .95rem; font-weight: 750; letter-spacing: -.02em; white-space: nowrap; }
          .resultado-chart-stat-detail { margin-top: .12rem; overflow: hidden; color: #94a3b8; font-size: .7rem; text-overflow: ellipsis; white-space: nowrap; }
          .resultado-chart { margin-top: 1rem; }
          .resultado-chart-bars { display: flex; align-items: flex-end; gap: 14px; overflow-x: auto; padding: 8px 0; }
          .resultado-chart-row { display: flex; flex: 0 0 72px; flex-direction: column; align-items: center; gap: 6px; }
          .resultado-chart-label, .resultado-chart-value { color: #334155; font-size: .72rem; font-weight: 650; white-space: nowrap; }
          .resultado-chart-track { position: relative; width: 100%; height: 180px; border-bottom: 1px solid #cbd5e1; }
          .resultado-chart-dot { position: absolute; z-index: 2; left: 50%; width: 14px; height: 14px; border: 3px solid #ccfbf1; border-radius: 999px; background: #0f766e; transform: translateX(-50%); }
          .resultado-chart-dot.zero { border-color: #e2e8f0; background: #94a3b8; }
          .resultado-chart-line { position: absolute; z-index: 1; top: 0; left: 50%; width: calc(100% + 14px); height: 100%; overflow: visible; }
          .resultado-chart-line path { fill: none; stroke: #0f766e; stroke-width: 2; }
        .resultado-ops { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; margin-top: 1.25rem; }
        .resultado-op { padding: .9rem; border-radius: .85rem; background: #f8fafc; }
        .resultado-op-label { color: #64748b; font-size: .72rem; font-weight: 650; }
        .resultado-op-value { margin-top: .25rem; color: #1e293b; font-size: 1.05rem; font-weight: 750; }
        .resultado-op-detail { margin-top: .2rem; color: #94a3b8; font-size: .72rem; }
        .resultado-alerts { display: grid; gap: .7rem; margin-top: 1.1rem; }
        .resultado-alert { padding: .85rem .9rem; border-radius: .85rem; border: 1px solid; }
        .resultado-alert-title { font-size: .8rem; font-weight: 750; }
        .resultado-alert-description { margin-top: .2rem; font-size: .75rem; line-height: 1.4; }
        .resultado-alert.success { border-color: #a7f3d0; background: #ecfdf5; color: #047857; }
        .resultado-alert.info { border-color: #bae6fd; background: #f0f9ff; color: #0369a1; }
        .resultado-alert.warning { border-color: #fde68a; background: #fffbeb; color: #a16207; }
        .resultado-alert.danger { border-color: #fecdd3; background: #fff1f2; color: #be123c; }
        .resultado-comparativo { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; margin-top: 1.1rem; }
        .resultado-comparativo-item { padding: .85rem; border: 1px solid #e2e8f0; border-radius: .85rem; background: #f8fafc; }
        .resultado-comparativo-label { color: #64748b; font-size: .72rem; font-weight: 650; }
        .resultado-comparativo-value { margin-top: .25rem; color: #1e293b; font-size: .95rem; font-weight: 750; }
        .resultado-comparativo-detail { margin-top: .2rem; color: #94a3b8; font-size: .71rem; }
        .resultado-comparativo-variation { margin-top: .35rem; color: #0f766e; font-size: .72rem; font-weight: 750; }
        .resultado-comparativo-variation.negative { color: #be123c; }
        .resultado-activity { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.25rem; }
        .resultado-activity-card { overflow: hidden; padding: 0; }
        .resultado-activity-header { display: flex; justify-content: space-between; gap: .75rem; padding: 1.15rem 1.25rem .8rem; }
        .resultado-activity-count { color: #64748b; font-size: .75rem; font-weight: 650; white-space: nowrap; }
        .resultado-list { border-top: 1px solid #f1f5f9; }
        .resultado-list-item { display: grid; gap: .2rem; padding: .8rem 1.25rem; border-bottom: 1px solid #f1f5f9; }
        .resultado-list-main { display: flex; justify-content: space-between; gap: .75rem; color: #334155; font-size: .8rem; font-weight: 700; }
        .resultado-list-main span:last-child { text-align: right; white-space: nowrap; }
        .resultado-list-detail { overflow: hidden; color: #94a3b8; font-size: .73rem; text-overflow: ellipsis; white-space: nowrap; }
        .resultado-empty { padding: 1.4rem 1.25rem; color: #94a3b8; font-size: .8rem; text-align: center; }
        .resultado-os-list { display: grid; gap: .8rem; margin-top: 1.15rem; }
        .resultado-os-group { overflow: hidden; border: 1px solid #e2e8f0; border-radius: .9rem; }
        .resultado-os-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .85rem 1rem; background: #f8fafc; }
        .resultado-os-title { color: #1e293b; font-size: .85rem; font-weight: 750; }
        .resultado-os-meta { margin-top: .15rem; color: #64748b; font-size: .73rem; }
        .resultado-os-total { color: #0f766e; font-size: .88rem; font-weight: 750; white-space: nowrap; }
        .resultado-os-item { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(7rem, .8fr) auto; gap: 1rem; align-items: center; padding: .75rem 1rem; border-top: 1px solid #f1f5f9; }
        .resultado-os-product { overflow: hidden; color: #334155; font-size: .8rem; font-weight: 650; text-overflow: ellipsis; white-space: nowrap; }
        .resultado-os-product small, .resultado-os-detail { display: block; margin-top: .14rem; overflow: hidden; color: #94a3b8; font-size: .71rem; font-weight: 400; text-overflow: ellipsis; white-space: nowrap; }
        .resultado-os-value { color: #334155; font-size: .8rem; font-weight: 750; text-align: right; white-space: nowrap; }
        .dark .resultado-analise { color: #e2e8f0; }
        .dark .resultado-hero { border-color: rgba(148, 163, 184, .2); background: linear-gradient(135deg, rgba(30, 58, 138, .25), rgba(15, 23, 42, .9) 55%, rgba(6, 78, 59, .25)); }
        .dark .resultado-hero h2, .dark .resultado-kpi-value, .dark .resultado-card-title, .dark .resultado-financial-value, .dark .resultado-op-value { color: #f8fafc; }
        .dark .resultado-eyebrow, .dark .resultado-hero p, .dark .resultado-chip, .dark .resultado-kpi-label, .dark .resultado-card-subtitle, .dark .resultado-financial-label, .dark .resultado-op-label, .dark .resultado-activity-count { color: #94a3b8; }
        .dark .resultado-chip, .dark .resultado-kpi, .dark .resultado-card { border-color: rgba(148, 163, 184, .18); background: #111827; }
        .dark .resultado-chip.open { border-color: rgba(74, 222, 128, .35); background: rgba(22, 163, 74, .15); color: #86efac; }
        .dark .resultado-chip.closed { background: rgba(148, 163, 184, .1); }
        .dark .resultado-op { background: rgba(148, 163, 184, .08); }
        .dark .resultado-comparativo-item { border-color: rgba(148, 163, 184, .18); background: rgba(148, 163, 184, .08); }
        .dark .resultado-comparativo-value { color: #e2e8f0; }
         .dark .resultado-progress { background: rgba(148, 163, 184, .15); }
         .dark .resultado-tabs { border-color: rgba(148, 163, 184, .18); }
          .dark .resultado-meta { border-color: rgba(148, 163, 184, .18); background: rgba(148, 163, 184, .08); }
          .dark .resultado-chart-stat { border-color: rgba(148, 163, 184, .18); background: rgba(148, 163, 184, .08); }
          .dark .resultado-chart-label, .dark .resultado-chart-value { color: #e2e8f0; }
          .dark .resultado-chart-track { border-color: rgba(148, 163, 184, .4); }
        .dark .resultado-list, .dark .resultado-list-item { border-color: rgba(148, 163, 184, .12); }
        .dark .resultado-list-main { color: #e2e8f0; }
        .dark .resultado-os-group { border-color: rgba(148, 163, 184, .18); }
        .dark .resultado-os-head { background: rgba(148, 163, 184, .08); }
        .dark .resultado-os-title, .dark .resultado-os-product, .dark .resultado-os-value { color: #e2e8f0; }
        .dark .resultado-os-item { border-color: rgba(148, 163, 184, .12); }
         @media (max-width: 1100px) { .resultado-kpis, .resultado-metas { grid-template-columns: repeat(2, minmax(0, 1fr)); } .resultado-activity { grid-template-columns: 1fr; } }
        @media (max-width: 800px) { .resultado-hero, .resultado-grid { grid-template-columns: 1fr; display: grid; } .resultado-hero-meta { justify-content: flex-start; } .resultado-financial-row { grid-template-columns: 1fr auto; } .resultado-financial-row .resultado-progress { grid-column: 1 / -1; } .resultado-ops { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 520px) { .resultado-kpis, .resultado-metas, .resultado-ops, .resultado-chart-summary, .resultado-comparativo { grid-template-columns: 1fr; } .resultado-hero { padding: 1.15rem; } .resultado-hero h2 { font-size: 1.4rem; } .resultado-card { padding: 1rem; } .resultado-os-item { grid-template-columns: 1fr auto; gap: .45rem .8rem; } .resultado-os-detail { grid-column: 1 / -1; } }
    </style>

    <div class="resultado-analise">
        <section class="resultado-hero">
            <div>
                <div class="resultado-eyebrow">Resultado do veículo</div>
                <h2>{{ $veiculo?->placa ?? 'Veículo não identificado' }}</h2>
                <p>{{ $veiculo?->tipoVeiculo?->descricao ?: 'Tipo de veículo não informado' }}</p>
            </div>
            <div class="resultado-hero-meta">
                <span class="resultado-chip {{ $statusTom }}">{{ $record->status }}</span>
                <span class="resultado-chip">{{ $periodo }}</span>
                <span class="resultado-chip">{{ $resumo['dias'] }} {{ $resumo['dias'] === 1 ? 'dia' : 'dias' }}</span>
            </div>
        </section>

        <nav class="resultado-tabs" aria-label="Análises do resultado">
            <a class="resultado-tab active" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise', ['record' => $record]) }}">Visão geral</a>
            <a class="resultado-tab" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise-viagens', ['record' => $record]) }}">Viagens</a>
            <a class="resultado-tab" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise-abastecimentos', ['record' => $record]) }}">Abastecimentos</a>
            <a class="resultado-tab" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise-manutencao', ['record' => $record]) }}">Custos de manutenção</a>
            <a class="resultado-tab" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise-servicos', ['record' => $record]) }}">Serviços internos</a>
            <a class="resultado-tab" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise-garantias', ['record' => $record]) }}">Garantias</a>
        </nav>

        <section class="resultado-kpis">
            <article class="resultado-kpi">
                <div class="resultado-kpi-label">Faturamento</div>
                <div class="resultado-kpi-value">R$ {{ number_format($resumo['faturamento'], 2, ',', '.') }}</div>
                <div class="resultado-kpi-detail">{{ $record->documentos_count }} {{ $record->documentos_count === 1 ? 'documento vinculado' : 'documentos vinculados' }}</div>
            </article>
            <article class="resultado-kpi">
                <div class="resultado-kpi-label">Resultado líquido</div>
                <div class="resultado-kpi-value {{ $resumo['resultado_liquido'] >= 0 ? 'positive' : 'negative' }}">R$ {{ number_format($resumo['resultado_liquido'], 2, ',', '.') }}</div>
                <div class="resultado-kpi-detail">Margem {{ $resumo['margem_liquida'] === null ? 'indisponível' : number_format($resumo['margem_liquida'], 1, ',', '.') . '%' }}</div>
            </article>
            <article class="resultado-kpi">
                <div class="resultado-kpi-label">Custo por KM</div>
                <div class="resultado-kpi-value">{{ $resumo['custo_por_km'] === null ? 'N/D' : 'R$ ' . number_format($resumo['custo_por_km'], 2, ',', '.') }}</div>
                <div class="resultado-kpi-detail">Diesel, manutenção e folha</div>
            </article>
            <article class="resultado-kpi">
                <div class="resultado-kpi-label">Consumo médio</div>
                <div class="resultado-kpi-value">{{ $resumo['consumo'] === null ? 'N/D' : number_format($resumo['consumo'], 2, ',', '.') . ' km/L' }}</div>
                <div class="resultado-kpi-detail">Meta: {{ $resumo['meta_consumo'] ? number_format($resumo['meta_consumo'], 2, ',', '.') . ' km/L' : 'não definida' }}</div>
            </article>
        </section>

        <section class="resultado-card">
            <h3 class="resultado-card-title">Metas do período</h3>
            <p class="resultado-card-subtitle">Indicadores comparados às metas máximas definidas para o veículo.</p>
            <div class="resultado-metas">
                @foreach ($metas as $meta)
                    @php $acimaDaMeta = $meta['valor'] !== null && $meta['valor'] > $meta['meta']; @endphp
                    <article class="resultado-meta">
                        <div class="resultado-meta-label">{{ $meta['label'] }}</div>
                        <div class="resultado-meta-value {{ $acimaDaMeta ? 'over' : '' }}">{{ $meta['valor'] === null ? 'N/D' : number_format($meta['valor'], 2, ',', '.') . $meta['unidade'] }}</div>
                        <div class="resultado-meta-detail">Meta máxima: {{ number_format($meta['meta'], 1, ',', '.') }}{{ $meta['unidade'] }}{{ $meta['valor'] !== null ? ($acimaDaMeta ? ' · acima da meta' : ' · dentro da meta') : '' }}</div>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="resultado-grid">
            <article class="resultado-card">
                <h3 class="resultado-card-title">Composição do resultado</h3>
                <p class="resultado-card-subtitle">Custos e margem calculados sobre o faturamento vinculado.</p>
                <div class="resultado-financial-list">
                    @foreach ($composicaoFinanceira as $item)
                        <div class="resultado-financial-row">
                            <div class="resultado-financial-label">{{ $item['label'] }}</div>
                            <div class="resultado-financial-value">R$ {{ number_format($item['valor'], 2, ',', '.') }}</div>
                            <div class="resultado-progress"><span class="{{ $item['cor'] }}" style="width: {{ number_format($item['percentual'], 2, '.', '') }}%"></span></div>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="resultado-card">
                <h3 class="resultado-card-title">Comparativo com período anterior</h3>
                @if ($comparativoPeriodoAnterior)
                    <p class="resultado-card-subtitle">Atual comparado a {{ $comparativoPeriodoAnterior['periodo'] }}.</p>
                    <div class="resultado-comparativo">
                        @foreach ($comparativoPeriodoAnterior['indicadores'] as $indicador)
                            @php
                                $formatarIndicador = fn (?float $valor): string => match ($indicador['formato']) {
                                    'moeda' => $valor === null ? 'N/D' : 'R$ ' . number_format($valor, 2, ',', '.'),
                                    'percentual' => $valor === null ? 'N/D' : number_format($valor, 1, ',', '.') . '%',
                                    default => $valor === null ? 'N/D' : number_format($valor, 2, ',', '.') . ' km/L',
                                };
                            @endphp
                            <div class="resultado-comparativo-item">
                                <div class="resultado-comparativo-label">{{ $indicador['label'] }}</div>
                                <div class="resultado-comparativo-value">{{ $formatarIndicador($indicador['atual']) }}</div>
                                <div class="resultado-comparativo-detail">Anterior: {{ $formatarIndicador($indicador['anterior']) }}</div>
                                @if ($indicador['variacao'] !== null)
                                    <div class="resultado-comparativo-variation {{ $indicador['variacao'] < 0 ? 'negative' : '' }}">{{ $indicador['variacao'] > 0 ? '+' : '' }}{{ number_format($indicador['variacao'], 1, ',', '.') }}{{ $indicador['formato'] === 'percentual' ? ' p.p.' : '%' }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="resultado-card-subtitle">Ainda não há resultado anterior fechado para este veículo.</p>
                    <div class="resultado-empty">O comparativo será disponibilizado quando houver um período anterior.</div>
                @endif
            </article>
        </section>

        <section class="resultado-card">
            <h3 class="resultado-card-title">Eficiência operacional</h3>
            <p class="resultado-card-subtitle">Quilometragem, consumo e cobertura dos registros que compõem o período.</p>
            <div class="resultado-ops">
                <div class="resultado-op"><div class="resultado-op-label">KM pago</div><div class="resultado-op-value">{{ number_format($resumo['km_pago'], 0, ',', '.') }} km</div><div class="resultado-op-detail">{{ $record->viagens_count }} {{ $record->viagens_count === 1 ? 'viagem' : 'viagens' }}</div></div>
                <div class="resultado-op"><div class="resultado-op-label">KM rodado por viagens</div><div class="resultado-op-value">{{ number_format($resumo['km_rodado_viagens'], 0, ',', '.') }} km</div><div class="resultado-op-detail">Soma registrada nas viagens</div></div>
                <div class="resultado-op"><div class="resultado-op-label">KM rodado por abastecimento</div><div class="resultado-op-value">{{ $resumo['km_rodado_abastecimento'] === null ? 'N/D' : number_format($resumo['km_rodado_abastecimento'], 0, ',', '.') . ' km' }}</div><div class="resultado-op-detail">@if ($referenciasKmAbastecimento['inicial']) Inicial: {{ $referenciasKmAbastecimento['inicial']->data_abastecimento?->format('d/m/Y H:i') }} · {{ number_format($referenciasKmAbastecimento['inicial']->quilometragem, 0, ',', '.') }} km @else Base para consumo e custo por KM @endif</div></div>
                <div class="resultado-op"><div class="resultado-op-label">Dispersão por abastecimento</div><div class="resultado-op-value">{{ $resumo['dispersao_km_abastecimento'] === null ? 'N/D' : number_format($resumo['dispersao_km_abastecimento'], 0, ',', '.') . ' km' }}</div><div class="resultado-op-detail">{{ $resumo['percentual_dispersao_km_abastecimento'] === null ? 'Percentual indisponível' : number_format($resumo['percentual_dispersao_km_abastecimento'], 2, ',', '.') . '% do KM pago' }}</div></div>
                <div class="resultado-op"><div class="resultado-op-label">Dispersão por KM real</div><div class="resultado-op-value">{{ number_format($resumo['dispersao_km_real'], 0, ',', '.') }} km</div><div class="resultado-op-detail">{{ $resumo['percentual_dispersao_km_real'] === null ? 'Percentual indisponível' : number_format($resumo['percentual_dispersao_km_real'], 2, ',', '.') . '% do KM pago' }}</div></div>
                <div class="resultado-op"><div class="resultado-op-label">Custo por KM rodado</div><div class="resultado-op-value">{{ $resumo['custo_por_km_rodado'] === null ? 'N/D' : 'R$ ' . number_format($resumo['custo_por_km_rodado'], 2, ',', '.') }}</div><div class="resultado-op-detail">Combustível, manutenção e folha</div></div>
                <div class="resultado-op"><div class="resultado-op-label">Custo por KM pago</div><div class="resultado-op-value">{{ $resumo['custo_por_km_pago'] === null ? 'N/D' : 'R$ ' . number_format($resumo['custo_por_km_pago'], 2, ',', '.') }}</div><div class="resultado-op-detail">Combustível, manutenção e folha</div></div>
                <div class="resultado-op"><div class="resultado-op-label">Diesel consumido</div><div class="resultado-op-value">{{ number_format($resumo['litros'], 2, ',', '.') }} L</div><div class="resultado-op-detail">{{ $record->abastecimentos_count }} {{ $record->abastecimentos_count === 1 ? 'abastecimento' : 'abastecimentos' }}</div></div>
                <div class="resultado-op"><div class="resultado-op-label">Custo de combustível</div><div class="resultado-op-value">R$ {{ number_format($resumo['combustivel'], 2, ',', '.') }}</div><div class="resultado-op-detail">R$ {{ $resumo['litros'] > 0 ? number_format($resumo['combustivel'] / $resumo['litros'], 3, ',', '.') : '0,000' }} por litro</div></div>
            </div>
        </section>

        <section class="resultado-card">
            <h3 class="resultado-card-title">Custo diário de manutenção</h3>
            <p class="resultado-card-subtitle">Gasto por dia no período, com base nos lançamentos de manutenção vinculados.</p>
            <div class="resultado-chart-summary">
                <div class="resultado-chart-stat"><div class="resultado-chart-stat-label">Total no período</div><div class="resultado-chart-stat-value">R$ {{ number_format($custosDiariosManutencao['valor_total'], 2, ',', '.') }}</div><div class="resultado-chart-stat-detail">Lançamentos vinculados</div></div>
                <div class="resultado-chart-stat"><div class="resultado-chart-stat-label">Média por dia</div><div class="resultado-chart-stat-value">R$ {{ number_format($custosDiariosManutencao['media_diaria'], 2, ',', '.') }}</div><div class="resultado-chart-stat-detail">Considerando todos os dias do período</div></div>
                <div class="resultado-chart-stat"><div class="resultado-chart-stat-label">Maior gasto diário</div><div class="resultado-chart-stat-value">R$ {{ number_format($custosDiariosManutencao['maior_valor'], 2, ',', '.') }}</div><div class="resultado-chart-stat-detail">{{ $custosDiariosManutencao['dia_maior_valor'] ? 'Em ' . $custosDiariosManutencao['dia_maior_valor'] : 'Sem lançamentos no período' }}</div></div>
            </div>
            <div class="resultado-chart">
                @php
                    $pontosGrafico = $custosDiariosManutencao['pontos'];
                    $escalaGrafico = $custosDiariosManutencao['escala_maxima'];
                @endphp
                <div class="resultado-chart-bars" role="img" aria-label="Gráfico de custo diário de manutenção">
                    @foreach ($pontosGrafico as $ponto)
                        @php
                            $percentualGrafico = ($ponto['valor'] / $escalaGrafico) * 100;
                            $proximoPonto = $pontosGrafico[$loop->index + 1] ?? null;
                        @endphp
                        <div class="resultado-chart-row" title="{{ $ponto['data'] }}: R$ {{ number_format($ponto['valor'], 2, ',', '.') }}">
                            <div class="resultado-chart-value">{{ number_format($ponto['valor'], 0, ',', '.') }}</div>
                            <div class="resultado-chart-track">
                                @if ($proximoPonto)
                                    @php $proximoPercentualGrafico = ($proximoPonto['valor'] / $escalaGrafico) * 100; @endphp
                                    <svg class="resultado-chart-line" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true"><path d="M 0 {{ 100 - $percentualGrafico }} L 100 {{ 100 - $proximoPercentualGrafico }}" /></svg>
                                @endif
                                <div class="resultado-chart-dot {{ $ponto['valor'] <= 0 ? 'zero' : '' }}" style="bottom: calc({{ $percentualGrafico }}% - 7px)"></div>
                            </div>
                            <div class="resultado-chart-label">{{ $ponto['data'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="resultado-activity">
            <article class="resultado-card resultado-activity-card">
                <div class="resultado-activity-header"><div><h3 class="resultado-card-title">Gastos por grupo de produto</h3><p class="resultado-card-subtitle">Custos de manutenção que mais impactaram o período.</p></div><span class="resultado-activity-count">{{ $gastosManutencaoPorGrupo->count() }} grupos</span></div>
                <div class="resultado-list">
                    @forelse ($gastosManutencaoPorGrupo as $gasto)
                        <div class="resultado-list-item"><div class="resultado-list-main"><span>{{ $gasto['grupo'] }}</span><span>R$ {{ number_format($gasto['total'], 2, ',', '.') }}</span></div><div class="resultado-list-detail">{{ $gasto['quantidade_lancamentos'] }} {{ $gasto['quantidade_lancamentos'] === 1 ? 'lançamento' : 'lançamentos' }}</div></div>
                    @empty
                        <div class="resultado-empty">Nenhum gasto de manutenção vinculado.</div>
                    @endforelse
                </div>
            </article>
            <article class="resultado-card resultado-activity-card">
                <div class="resultado-activity-header"><div><h3 class="resultado-card-title">Viagens com maior dispersão</h3><p class="resultado-card-subtitle">Maiores KM de dispersão rateados nas cargas da viagem.</p></div><span class="resultado-activity-count">{{ $viagensComDispersao->count() }} viagens</span></div>
                <div class="resultado-list">
                    @forelse ($viagensComDispersao as $viagem)
                        <div class="resultado-list-item"><div class="resultado-list-main"><span>#{{ $viagem['numero'] }}</span><span>{{ number_format($viagem['dispersao_km'], 2, ',', '.') }} km</span></div><div class="resultado-list-detail">{{ $viagem['data'] }} · {{ number_format($viagem['km_pago'], 0, ',', '.') }} km pagos · {{ number_format($viagem['km_rodado'], 0, ',', '.') }} km rodados{{ $viagem['documento'] ? ' · ' . $viagem['documento'] : '' }}</div></div>
                    @empty
                        <div class="resultado-empty">Nenhuma viagem com dispersão positiva no período.</div>
                    @endforelse
                </div>
            </article>
        </section>
    </div>
</x-filament-panels::page>
