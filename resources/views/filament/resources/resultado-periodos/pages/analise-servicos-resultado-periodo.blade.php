<x-filament-panels::page>
    @php
        $totalItens = $servicosOrdensInternas->sum(fn (array $ordem): int => $ordem['itens']->count());
        $totalConcluidos = $servicosOrdensInternas->flatMap(fn (array $ordem) => $ordem['itens'])->where('status', 'CONCLUÍDO')->count();
    @endphp

    <style>
        .servicos-analise { display: grid; gap: 1.25rem; color: #0f172a; }
        .servicos-tabs { display: flex; gap: .5rem; overflow-x: auto; border-bottom: 1px solid #e2e8f0; }
        .servicos-tab { flex: 0 0 auto; padding: .7rem .9rem; border-bottom: 2px solid transparent; color: #64748b; font-size: .82rem; font-weight: 700; text-decoration: none; }
        .servicos-tab.active { border-color: #0f766e; color: #0f766e; }
        .servicos-summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .8rem; }
        .servicos-stat, .servicos-card { border: 1px solid #e2e8f0; border-radius: 1rem; background: #fff; }
        .servicos-stat { padding: 1rem; }
        .servicos-stat-label { color: #64748b; font-size: .74rem; font-weight: 700; }
        .servicos-stat-value { margin-top: .32rem; color: #0f766e; font-size: 1.25rem; font-weight: 750; letter-spacing: -.025em; }
        .servicos-stat-detail { margin-top: .2rem; color: #94a3b8; font-size: .72rem; }
        .servicos-card { padding: 1.25rem; }
        .servicos-card h2 { margin: 0; font-size: 1.05rem; }
        .servicos-card > p { margin: .3rem 0 0; color: #64748b; font-size: .8rem; }
        .servicos-list { display: grid; gap: .8rem; margin-top: 1.15rem; }
        .servicos-os { overflow: hidden; border: 1px solid #e2e8f0; border-radius: .85rem; }
        .servicos-os-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .85rem 1rem; background: #f8fafc; cursor: pointer; list-style: none; }
        .servicos-os-head::-webkit-details-marker { display: none; }
        .servicos-os-head::after { content: '+'; color: #0f766e; font-size: 1.1rem; font-weight: 750; }
        .servicos-os[open] .servicos-os-head::after { content: '−'; }
        .servicos-os-title { color: #1e293b; font-size: .85rem; font-weight: 750; }
        .servicos-os-meta, .servicos-item-detail { margin-top: .15rem; color: #64748b; font-size: .72rem; }
        .servicos-os-count { color: #0f766e; font-size: .8rem; font-weight: 750; white-space: nowrap; }
        .servicos-item { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .75rem 1rem; border-top: 1px solid #f1f5f9; }
        .servicos-item-name { color: #334155; font-size: .8rem; font-weight: 700; }
        .servicos-item-name small { display: block; margin-top: .15rem; color: #94a3b8; font-size: .7rem; font-weight: 500; }
        .servicos-item-status { padding: .25rem .5rem; border-radius: 999px; background: #f1f5f9; color: #475569; font-size: .69rem; font-weight: 700; white-space: nowrap; }
        .servicos-empty { padding: 1.5rem; color: #94a3b8; font-size: .8rem; text-align: center; }
        .dark .servicos-analise { color: #e2e8f0; }
        .dark .servicos-tabs, .dark .servicos-stat, .dark .servicos-card, .dark .servicos-os { border-color: rgba(148, 163, 184, .18); }
        .dark .servicos-stat, .dark .servicos-card { background: #111827; }
        .dark .servicos-os-head, .dark .servicos-item-status { background: rgba(148, 163, 184, .08); }
        .dark .servicos-os-title, .dark .servicos-item-name { color: #e2e8f0; }
        .dark .servicos-item { border-color: rgba(148, 163, 184, .12); }
        @media (max-width: 620px) { .servicos-summary { grid-template-columns: 1fr; } .servicos-card { padding: 1rem; } .servicos-item { align-items: flex-start; } }
    </style>

    <div class="servicos-analise">
        <nav class="servicos-tabs" aria-label="Análises do resultado">
            <a class="servicos-tab" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise', ['record' => $record]) }}">Visão geral</a>
            <a class="servicos-tab" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise-viagens', ['record' => $record]) }}">Viagens</a>
            <a class="servicos-tab" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise-abastecimentos', ['record' => $record]) }}">Abastecimentos</a>
            <a class="servicos-tab" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise-manutencao', ['record' => $record]) }}">Custos de manutenção</a>
            <a class="servicos-tab active" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise-servicos', ['record' => $record]) }}">Serviços internos</a>
            <a class="servicos-tab" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise-garantias', ['record' => $record]) }}">Garantias</a>
        </nav>

        <section class="servicos-summary">
            <article class="servicos-stat"><div class="servicos-stat-label">Ordens internas</div><div class="servicos-stat-value">{{ $servicosOrdensInternas->count() }}</div><div class="servicos-stat-detail">Abertas dentro do período</div></article>
            <article class="servicos-stat"><div class="servicos-stat-label">Serviços lançados</div><div class="servicos-stat-value">{{ $totalItens }}</div><div class="servicos-stat-detail">Itens das ordens do veículo</div></article>
            <article class="servicos-stat"><div class="servicos-stat-label">Serviços concluídos</div><div class="servicos-stat-value">{{ $totalConcluidos }}</div><div class="servicos-stat-detail">Com status concluído</div></article>
        </section>

        <section class="servicos-card">
            <h2>Serviços das ordens internas</h2>
            <p>Ordens abertas para {{ $record->veiculo?->placa ?? 'o veículo' }} durante este resultado.</p>
            <div class="servicos-list">
                @forelse ($servicosOrdensInternas as $ordem)
                    <details class="servicos-os">
                        <summary class="servicos-os-head">
                            <div><div class="servicos-os-title">OS #{{ $ordem['id'] }}</div><div class="servicos-os-meta">{{ $ordem['tipo'] ?: 'Tipo não informado' }} · {{ $ordem['status'] ?: 'Status não informado' }} · {{ $ordem['data_inicio'] ?: 'Data não informada' }}{{ $ordem['quilometragem'] ? ' · ' . number_format($ordem['quilometragem'], 0, ',', '.') . ' km' : '' }}</div></div>
                            <div class="servicos-os-count">{{ $ordem['itens']->count() }} {{ $ordem['itens']->count() === 1 ? 'serviço' : 'serviços' }}</div>
                        </summary>
                        @foreach ($ordem['itens'] as $item)
                            <div class="servicos-item"><div class="servicos-item-name">{{ $item['descricao'] }}<small>{{ $item['codigo'] ? 'Cód. ' . $item['codigo'] : 'Código não informado' }}{{ $item['posicao'] ? ' · Posição ' . $item['posicao'] : '' }}</small></div><span class="servicos-item-status">{{ $item['status'] ?: 'Sem status' }}</span></div>
                        @endforeach
                    </details>
                @empty
                    <div class="servicos-empty">Nenhuma ordem de serviço interna foi aberta para este veículo no período.</div>
                @endforelse
            </div>
        </section>
    </div>
</x-filament-panels::page>
