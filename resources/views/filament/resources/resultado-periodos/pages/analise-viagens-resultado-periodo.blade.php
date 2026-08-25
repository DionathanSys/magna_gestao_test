<x-filament-panels::page>
    <style>
        .operacao-analise { display: grid; gap: 1.25rem; color: #0f172a; }
        .operacao-tabs { display: flex; gap: .5rem; overflow-x: auto; border-bottom: 1px solid #e2e8f0; }
        .operacao-tab { flex: 0 0 auto; padding: .7rem .9rem; border-bottom: 2px solid transparent; color: #64748b; font-size: .82rem; font-weight: 700; text-decoration: none; }
        .operacao-tab.active { border-color: #0f766e; color: #0f766e; }
        .operacao-card { overflow: hidden; border: 1px solid #e2e8f0; border-radius: 1.1rem; background: #fff; }
        .operacao-header { padding: 1.25rem; }
        .operacao-header h2 { margin: 0; font-size: 1.1rem; }
        .operacao-header p { margin: .3rem 0 0; color: #64748b; font-size: .82rem; }
        .operacao-table-wrap { overflow-x: auto; border-top: 1px solid #e2e8f0; }
        .operacao-table { width: 100%; border-collapse: collapse; font-size: .8rem; }
        .operacao-table th { padding: .75rem 1.25rem; color: #64748b; font-size: .7rem; font-weight: 750; letter-spacing: .04em; text-align: left; text-transform: uppercase; white-space: nowrap; }
        .operacao-table td { padding: .85rem 1.25rem; border-top: 1px solid #f1f5f9; color: #334155; white-space: nowrap; }
        .operacao-table td.number { text-align: right; }
        .operacao-table td.danger { color: #be123c; font-weight: 750; }
        .operacao-empty { padding: 1.5rem; color: #94a3b8; font-size: .82rem; text-align: center; }
        .dark .operacao-analise { color: #e2e8f0; }
        .dark .operacao-tabs, .dark .operacao-card, .dark .operacao-table-wrap { border-color: rgba(148, 163, 184, .18); }
        .dark .operacao-card { background: #111827; }
        .dark .operacao-table td { border-color: rgba(148, 163, 184, .12); color: #e2e8f0; }
    </style>

    <div class="operacao-analise">
        <nav class="operacao-tabs" aria-label="Análises do resultado">
            <a class="operacao-tab" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise', ['record' => $record]) }}">Visão geral</a>
            <a class="operacao-tab active" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise-viagens', ['record' => $record]) }}">Viagens</a>
            <a class="operacao-tab" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise-abastecimentos', ['record' => $record]) }}">Abastecimentos</a>
            <a class="operacao-tab" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise-manutencao', ['record' => $record]) }}">Custos de manutenção</a>
            <a class="operacao-tab" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise-servicos', ['record' => $record]) }}">Serviços internos</a>
            <a class="operacao-tab" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise-garantias', ['record' => $record]) }}">Garantias</a>
        </nav>

        <section class="operacao-card">
            <div class="operacao-header">
                <h2>Viagens vinculadas</h2>
                <p>{{ $viagensAnalise->count() }} {{ $viagensAnalise->count() === 1 ? 'viagem vinculada' : 'viagens vinculadas' }} ao resultado de {{ $record->veiculo?->placa ?? 'veículo não identificado' }}.</p>
            </div>
            @if ($viagensAnalise->isNotEmpty())
                <div class="operacao-table-wrap">
                    <table class="operacao-table">
                        <thead><tr><th>Viagem</th><th>Data</th><th>Documento</th><th class="number">KM pago</th><th class="number">KM rodado</th><th class="number">Dispersão</th></tr></thead>
                        <tbody>
                            @foreach ($viagensAnalise as $viagem)
                                <tr><td>#{{ $viagem['numero'] }}</td><td>{{ $viagem['data'] }}</td><td>{{ $viagem['documento'] ?: 'Não informado' }}</td><td class="number">{{ number_format($viagem['km_pago'], 0, ',', '.') }} km</td><td class="number">{{ number_format($viagem['km_rodado'], 0, ',', '.') }} km</td><td class="number {{ $viagem['dispersao_km'] > 0 ? 'danger' : '' }}">{{ number_format($viagem['dispersao_km'], 2, ',', '.') }} km</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="operacao-empty">Nenhuma viagem vinculada a este resultado.</div>
            @endif
        </section>
    </div>
</x-filament-panels::page>
