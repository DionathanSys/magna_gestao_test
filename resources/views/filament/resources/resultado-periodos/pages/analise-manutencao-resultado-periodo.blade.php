<x-filament-panels::page>
    <style>
        .manutencao-analise { display: grid; gap: 1.25rem; color: #0f172a; }
        .manutencao-tabs { display: flex; gap: .5rem; border-bottom: 1px solid #e2e8f0; }
        .manutencao-tab { padding: .7rem .9rem; border-bottom: 2px solid transparent; color: #64748b; font-size: .82rem; font-weight: 700; text-decoration: none; }
        .manutencao-tab.active { border-color: #0f766e; color: #0f766e; }
        .manutencao-card { padding: 1.25rem; border: 1px solid #e2e8f0; border-radius: 1.1rem; background: #fff; }
        .manutencao-card h2 { margin: 0; font-size: 1.1rem; }
        .manutencao-card > p { margin: .3rem 0 0; color: #64748b; font-size: .82rem; }
        .manutencao-list { display: grid; gap: .8rem; margin-top: 1.15rem; }
        .manutencao-os { overflow: hidden; border: 1px solid #e2e8f0; border-radius: .9rem; }
        .manutencao-os-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .85rem 1rem; background: #f8fafc; cursor: pointer; list-style: none; }
        .manutencao-os-head::-webkit-details-marker { display: none; }
        .manutencao-os-head::after { content: '+'; color: #0f766e; font-size: 1.1rem; font-weight: 750; }
        .manutencao-os[open] .manutencao-os-head::after { content: '−'; }
        .manutencao-os-title { color: #1e293b; font-size: .86rem; font-weight: 750; }
        .manutencao-os-meta { margin-top: .15rem; color: #64748b; font-size: .73rem; }
        .manutencao-os-total { color: #0f766e; font-size: .9rem; font-weight: 750; white-space: nowrap; }
        .manutencao-item { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(7rem, .8fr) auto; gap: 1rem; align-items: center; padding: .75rem 1rem; border-top: 1px solid #f1f5f9; }
        .manutencao-product { overflow: hidden; color: #334155; font-size: .8rem; font-weight: 650; text-overflow: ellipsis; white-space: nowrap; }
        .manutencao-product small, .manutencao-detail { display: block; margin-top: .14rem; overflow: hidden; color: #94a3b8; font-size: .71rem; font-weight: 400; text-overflow: ellipsis; white-space: nowrap; }
        .manutencao-value { color: #334155; font-size: .8rem; font-weight: 750; text-align: right; white-space: nowrap; }
        .manutencao-empty { padding: 1.4rem; color: #94a3b8; font-size: .8rem; text-align: center; }
        .dark .manutencao-analise { color: #e2e8f0; }
        .dark .manutencao-tabs, .dark .manutencao-card, .dark .manutencao-os { border-color: rgba(148, 163, 184, .18); }
        .dark .manutencao-card { background: #111827; }
        .dark .manutencao-os-head { background: rgba(148, 163, 184, .08); }
        .dark .manutencao-os-title, .dark .manutencao-product, .dark .manutencao-value { color: #e2e8f0; }
        .dark .manutencao-item { border-color: rgba(148, 163, 184, .12); }
        @media (max-width: 520px) { .manutencao-card { padding: 1rem; } .manutencao-item { grid-template-columns: 1fr auto; gap: .45rem .8rem; } .manutencao-detail { grid-column: 1 / -1; } }
    </style>

    <div class="manutencao-analise">
        <nav class="manutencao-tabs" aria-label="Análises do resultado">
            <a class="manutencao-tab" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise', ['record' => $record]) }}">Visão geral</a>
            <a class="manutencao-tab active" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise-manutencao', ['record' => $record]) }}">Custos de manutenção</a>
        </nav>

        <section class="manutencao-card">
            <h2>Custos de manutenção por OS</h2>
            <p>Itens vinculados ao resultado de {{ $record->veiculo?->placa ?? 'veículo não identificado' }}, agrupados para facilitar a análise dos gastos.</p>
            <div class="manutencao-list">
                @forelse ($manutencoesPorOs as $manutencaoOs)
                    <details class="manutencao-os">
                        <summary class="manutencao-os-head">
                            <div>
                                <div class="manutencao-os-title">{{ $manutencaoOs['ordem_servico_id'] ? 'OS #' . $manutencaoOs['ordem_servico_id'] : 'Sem OS vinculada' }}</div>
                                <div class="manutencao-os-meta">{{ $manutencaoOs['tipo'] ?: 'Tipo não informado' }}{{ $manutencaoOs['status'] ? ' · ' . $manutencaoOs['status'] : '' }} · {{ $manutencaoOs['lancamentos']->count() }} {{ $manutencaoOs['lancamentos']->count() === 1 ? 'item' : 'itens' }}</div>
                            </div>
                            <div class="manutencao-os-total">R$ {{ number_format($manutencaoOs['total'], 2, ',', '.') }}</div>
                        </summary>
                        @foreach ($manutencaoOs['lancamentos'] as $lancamento)
                            <div class="manutencao-item">
                                <div class="manutencao-product">{{ $lancamento['produto'] }}<small>{{ $lancamento['codigo'] ? 'Cód. ' . $lancamento['codigo'] . ' · ' : '' }}{{ $lancamento['parceiro'] ?: 'Parceiro não informado' }}</small></div>
                                <div class="manutencao-detail">{{ $lancamento['data'] }} · {{ number_format($lancamento['quantidade'], 4, ',', '.') }} {{ $lancamento['unidade'] }}{{ $lancamento['grupo'] ? ' · ' . $lancamento['grupo'] : '' }}</div>
                                <div class="manutencao-value">R$ {{ number_format($lancamento['valor'], 2, ',', '.') }}</div>
                            </div>
                        @endforeach
                    </details>
                @empty
                    <div class="manutencao-empty">Nenhum custo de manutenção foi vinculado a este resultado.</div>
                @endforelse
            </div>
        </section>
    </div>
</x-filament-panels::page>
