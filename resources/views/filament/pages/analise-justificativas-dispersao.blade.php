<x-filament-panels::page>
    <style>
        .dispersao-analysis { display: grid; gap: 1rem; }
        .dispersao-panel { overflow: hidden; border: 1px solid rgba(15, 23, 42, .1); border-radius: .75rem; background: #fff; }
        .dark .dispersao-panel { border-color: rgba(255, 255, 255, .1); background: #111827; }
        .dispersao-form { padding: 1rem; }
        .dispersao-actions { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1rem; }
        .dispersao-cards { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; }
        .dispersao-card { padding: 1rem 1.25rem; }
        .dispersao-card-label { color: #64748b; font-size: .8125rem; font-weight: 600; }
        .dispersao-card-value { margin-top: .35rem; color: #0f172a; font-size: 1.75rem; font-weight: 700; }
        .dark .dispersao-card-label { color: #94a3b8; }
        .dark .dispersao-card-value { color: #f8fafc; }
        .dispersao-heading { padding: 1rem 1.25rem; border-bottom: 1px solid rgba(15, 23, 42, .1); color: #0f172a; font-size: 1rem; font-weight: 700; }
        .dark .dispersao-heading { border-color: rgba(255, 255, 255, .1); color: #f8fafc; }
        .dispersao-table-wrap { overflow-x: auto; }
        .dispersao-table { width: 100%; min-width: 760px; border-collapse: collapse; }
        .dispersao-table th, .dispersao-table td { padding: .75rem 1rem; border-bottom: 1px solid rgba(15, 23, 42, .08); text-align: left; font-size: .8125rem; }
        .dispersao-table th { color: #64748b; font-size: .75rem; text-transform: uppercase; }
        .dispersao-table td { color: #334155; }
        .dark .dispersao-table th, .dark .dispersao-table td { border-color: rgba(255, 255, 255, .08); }
        .dark .dispersao-table th { color: #94a3b8; }
        .dark .dispersao-table td { color: #e2e8f0; }
        .dispersao-table tr:last-child td { border-bottom: 0; }
        .dispersao-table tbody tr[data-clickable] { cursor: pointer; }
        .dispersao-table tbody tr[data-clickable]:hover { background: #f8fafc; }
        .dark .dispersao-table tbody tr[data-clickable]:hover { background: rgba(255, 255, 255, .04); }
        .dispersao-number { text-align: right !important; white-space: nowrap; }
        .dispersao-empty { padding: 2rem; color: #64748b; text-align: center; }
        .dispersao-trip { color: #2563eb; font-weight: 700; text-decoration: none; }
        .dark .dispersao-trip { color: #93c5fd; }
        @media (max-width: 900px) { .dispersao-cards { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 560px) { .dispersao-cards { grid-template-columns: 1fr; } }
    </style>

    <div class="dispersao-analysis">
        <form wire:submit="carregarDados" class="dispersao-panel dispersao-form">
            {{ $this->form }}
            <div class="dispersao-actions">
                <x-filament::button type="submit" icon="heroicon-o-funnel">Aplicar filtros</x-filament::button>
                <x-filament::button type="button" color="gray" icon="heroicon-o-arrow-path" wire:click="limparFiltros">Limpar filtros</x-filament::button>
            </div>
        </form>

        <section class="dispersao-cards">
            <div class="dispersao-panel dispersao-card"><div class="dispersao-card-label">Justificativas</div><div class="dispersao-card-value">{{ number_format((int) ($indicadores['total_justificativas'] ?? 0), 0, ',', '.') }}</div></div>
            <div class="dispersao-panel dispersao-card"><div class="dispersao-card-label">Viagens justificadas</div><div class="dispersao-card-value">{{ number_format((int) ($indicadores['total_viagens'] ?? 0), 0, ',', '.') }}</div></div>
            <div class="dispersao-panel dispersao-card"><div class="dispersao-card-label">KM dispersão justificado</div><div class="dispersao-card-value">{{ number_format((float) ($indicadores['total_km_dispersao'] ?? 0), 2, ',', '.') }}</div></div>
            <div class="dispersao-panel dispersao-card"><div class="dispersao-card-label">Média de dispersão</div><div class="dispersao-card-value">{{ number_format((float) ($indicadores['media_percentual_dispersao'] ?? 0), 2, ',', '.') }}%</div></div>
        </section>

        <section class="dispersao-panel">
            <div class="dispersao-heading">Resumo por motivo</div>
            <div class="dispersao-table-wrap">
                <table class="dispersao-table">
                    <thead><tr><th>Motivo</th><th class="dispersao-number">Justificativas</th><th class="dispersao-number">Viagens</th><th class="dispersao-number">KM dispersão</th><th class="dispersao-number">Média %</th><th class="dispersao-number">Participação</th></tr></thead>
                    <tbody>
                        @forelse ($resumoMotivos as $resumo)
                            <tr data-clickable wire:click="selecionarMotivo(@js($resumo['motivo']))">
                                <td><strong>{{ $resumo['motivo'] }}</strong></td>
                                <td class="dispersao-number">{{ number_format((int) $resumo['total_justificativas'], 0, ',', '.') }}</td>
                                <td class="dispersao-number">{{ number_format((int) $resumo['total_viagens'], 0, ',', '.') }}</td>
                                <td class="dispersao-number">{{ number_format((float) $resumo['total_km_dispersao'], 2, ',', '.') }}</td>
                                <td class="dispersao-number">{{ number_format((float) $resumo['media_percentual_dispersao'], 2, ',', '.') }}%</td>
                                <td class="dispersao-number">{{ number_format((float) $resumo['participacao_percentual'], 2, ',', '.') }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="dispersao-empty">Nenhuma justificativa encontrada para os filtros selecionados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="dispersao-panel">
            <div class="dispersao-heading">Justificativas detalhadas <span style="font-weight: 400; color: #64748b">(até 200 registros mais recentes)</span></div>
            <div class="dispersao-table-wrap">
                <table class="dispersao-table">
                    <thead><tr><th>Data</th><th>Viagem</th><th>Placa</th><th>Motivo</th><th class="dispersao-number">KM dispersão</th><th class="dispersao-number">Dispersão %</th><th>Observação</th><th>Registrado por</th><th>Registro</th></tr></thead>
                    <tbody>
                        @forelse ($justificativas as $justificativa)
                            <tr>
                                <td>{{ $justificativa['data_competencia'] ? \Carbon\Carbon::parse($justificativa['data_competencia'])->format('d/m/Y') : '-' }}</td>
                                <td><a class="dispersao-trip" href="{{ \App\Filament\Resources\Viagems\ViagemResource::getUrl('view', ['record' => $justificativa['viagem_id']]) }}">{{ $justificativa['numero_viagem'] ?: '-' }}</a></td>
                                <td>{{ $justificativa['veiculo_placa'] ?: '-' }}</td>
                                <td>{{ $justificativa['motivo'] }}</td>
                                <td class="dispersao-number">{{ number_format((float) $justificativa['km_dispersao'], 2, ',', '.') }}</td>
                                <td class="dispersao-number">{{ number_format((float) $justificativa['dispersao_percentual'], 2, ',', '.') }}%</td>
                                <td>{{ $justificativa['observacao'] ?: '-' }}</td>
                                <td>{{ $justificativa['criador_nome'] ?: '-' }}</td>
                                <td>{{ \Carbon\Carbon::parse($justificativa['created_at'])->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="dispersao-empty">Nenhuma justificativa encontrada para os filtros selecionados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
