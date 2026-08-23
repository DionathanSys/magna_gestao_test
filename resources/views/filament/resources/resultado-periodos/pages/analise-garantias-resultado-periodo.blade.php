<x-filament-panels::page>
    @php
        $alertasGarantia = $garantiasServico->where('em_garantia', true);
    @endphp

    <style>
        .garantias-analise { display: grid; gap: 1.25rem; color: #0f172a; }
        .garantias-tabs { display: flex; gap: .5rem; overflow-x: auto; border-bottom: 1px solid #e2e8f0; }
        .garantias-tab { flex: 0 0 auto; padding: .7rem .9rem; border-bottom: 2px solid transparent; color: #64748b; font-size: .82rem; font-weight: 700; text-decoration: none; }
        .garantias-tab.active { border-color: #0f766e; color: #0f766e; }
        .garantias-summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .8rem; }
        .garantias-stat, .garantias-card { border: 1px solid #e2e8f0; border-radius: 1rem; background: #fff; }
        .garantias-stat { padding: 1rem; }
        .garantias-stat-label { color: #64748b; font-size: .74rem; font-weight: 700; }
        .garantias-stat-value { margin-top: .32rem; color: #0f766e; font-size: 1.25rem; font-weight: 750; letter-spacing: -.025em; }
        .garantias-stat-value.alert { color: #b45309; }
        .garantias-stat-detail { margin-top: .2rem; color: #94a3b8; font-size: .72rem; }
        .garantias-card { padding: 1.25rem; }
        .garantias-card h2 { margin: 0; font-size: 1.05rem; }
        .garantias-card > p { margin: .3rem 0 0; color: #64748b; font-size: .8rem; }
        .garantias-list { display: grid; gap: .75rem; margin-top: 1.15rem; }
        .garantias-item { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(10rem, .9fr) auto; gap: 1rem; align-items: center; padding: .9rem 1rem; border: 1px solid #e2e8f0; border-radius: .85rem; }
        .garantias-item.alert { border-color: #fcd34d; background: #fffbeb; }
        .garantias-service { color: #334155; font-size: .82rem; font-weight: 750; }
        .garantias-service small, .garantias-detail { display: block; margin-top: .15rem; color: #64748b; font-size: .71rem; font-weight: 500; }
        .garantias-badge { padding: .28rem .55rem; border-radius: 999px; background: #f1f5f9; color: #475569; font-size: .68rem; font-weight: 750; white-space: nowrap; }
        .garantias-badge.alert { background: #fef3c7; color: #92400e; }
        .garantias-empty { padding: 1.5rem; color: #94a3b8; font-size: .8rem; text-align: center; }
        .dark .garantias-analise { color: #e2e8f0; }
        .dark .garantias-tabs, .dark .garantias-stat, .dark .garantias-card, .dark .garantias-item { border-color: rgba(148, 163, 184, .18); }
        .dark .garantias-stat, .dark .garantias-card { background: #111827; }
        .dark .garantias-item.alert { border-color: rgba(251, 191, 36, .4); background: rgba(146, 64, 14, .2); }
        .dark .garantias-service { color: #e2e8f0; }
        .dark .garantias-badge { background: rgba(148, 163, 184, .12); color: #cbd5e1; }
        .dark .garantias-badge.alert { background: rgba(180, 83, 9, .25); color: #fcd34d; }
        @media (max-width: 700px) { .garantias-summary { grid-template-columns: 1fr; } .garantias-card { padding: 1rem; } .garantias-item { grid-template-columns: 1fr auto; gap: .55rem .8rem; } .garantias-detail { grid-column: 1 / -1; } }
    </style>

    <div class="garantias-analise">
        <nav class="garantias-tabs" aria-label="Análises do resultado">
            <a class="garantias-tab" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise', ['record' => $record]) }}">Visão geral</a>
            <a class="garantias-tab" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise-manutencao', ['record' => $record]) }}">Custos de manutenção</a>
            <a class="garantias-tab" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise-servicos', ['record' => $record]) }}">Serviços internos</a>
            <a class="garantias-tab active" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise-garantias', ['record' => $record]) }}">Garantias</a>
        </nav>

        <section class="garantias-summary">
            <article class="garantias-stat"><div class="garantias-stat-label">Execuções analisadas</div><div class="garantias-stat-value">{{ $garantiasServico->count() }}</div><div class="garantias-stat-detail">Serviços concluídos no período</div></article>
            <article class="garantias-stat"><div class="garantias-stat-label">Retornos em garantia</div><div class="garantias-stat-value {{ $alertasGarantia->isNotEmpty() ? 'alert' : '' }}">{{ $alertasGarantia->count() }}</div><div class="garantias-stat-detail">Reexecuções dentro do limite</div></article>
            <article class="garantias-stat"><div class="garantias-stat-label">Taxa de retorno</div><div class="garantias-stat-value {{ $alertasGarantia->isNotEmpty() ? 'alert' : '' }}">{{ $garantiasServico->isEmpty() ? '0,0' : number_format(($alertasGarantia->count() / $garantiasServico->count()) * 100, 1, ',', '.') }}%</div><div class="garantias-stat-detail">Sobre as execuções do período</div></article>
        </section>

        <section class="garantias-card">
            <h2>Garantias de serviços</h2>
            <p>Histórico de serviços concluídos e alertas de retorno dentro da garantia para {{ $record->veiculo?->placa ?? 'o veículo' }}.</p>
            <div class="garantias-list">
                @forelse ($garantiasServico as $garantia)
                    <article class="garantias-item {{ $garantia['em_garantia'] ? 'alert' : '' }}">
                        <div class="garantias-service">{{ $garantia['servico'] }}<small>{{ $garantia['codigo'] ? 'Cód. ' . $garantia['codigo'] . ' · ' : '' }}OS #{{ $garantia['ordem_servico_id'] }}{{ $garantia['posicao'] ? ' · Posição ' . $garantia['posicao'] : '' }}</small></div>
                        <div class="garantias-detail">{{ $garantia['data_execucao'] }} · {{ $garantia['km_durabilidade'] === null ? 'Primeira execução' : number_format($garantia['km_durabilidade'], 0, ',', '.') . ' km / ' . number_format($garantia['dias_durabilidade'], 0, ',', '.') . ' dias' }}<br>Limite: {{ number_format($garantia['garantia_km'], 0, ',', '.') }} km / {{ number_format($garantia['garantia_dias'], 0, ',', '.') }} dias</div>
                        <span class="garantias-badge {{ $garantia['em_garantia'] ? 'alert' : '' }}">{{ $garantia['em_garantia'] ? 'Em garantia' : 'Fora da garantia' }}</span>
                        @if ($garantia['motivo_alerta'])<div class="garantias-detail" style="grid-column: 1 / -1; margin-top: -.3rem; color: #b45309;">{{ $garantia['motivo_alerta'] }}</div>@endif
                    </article>
                @empty
                    <div class="garantias-empty">Nenhum serviço concluído com acompanhamento de garantia foi encontrado no período.</div>
                @endforelse
            </div>
        </section>
    </div>
</x-filament-panels::page>
