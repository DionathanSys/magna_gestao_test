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
        .operacao-table tr.referencia-final { background: #f0fdfa; }
        .operacao-reference { margin-top: .7rem; padding: .7rem .85rem; border: 1px solid #99f6e4; border-radius: .7rem; background: #f0fdfa; color: #115e59; font-size: .78rem; line-height: 1.45; }
        .operacao-badge { display: inline-flex; padding: .2rem .45rem; border-radius: 999px; background: #ccfbf1; color: #0f766e; font-size: .68rem; font-weight: 750; }
        .operacao-empty { padding: 1.5rem; color: #94a3b8; font-size: .82rem; text-align: center; }
        .dark .operacao-analise { color: #e2e8f0; }
        .dark .operacao-tabs, .dark .operacao-card, .dark .operacao-table-wrap { border-color: rgba(148, 163, 184, .18); }
        .dark .operacao-card { background: #111827; }
        .dark .operacao-table td { border-color: rgba(148, 163, 184, .12); color: #e2e8f0; }
        .dark .operacao-table tr.referencia-final, .dark .operacao-reference { background: rgba(13, 148, 136, .12); }
        .dark .operacao-reference { border-color: rgba(94, 234, 212, .3); color: #99f6e4; }
    </style>

    <div class="operacao-analise">
        <nav class="operacao-tabs" aria-label="Análises do resultado">
            <a class="operacao-tab" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise', ['record' => $record]) }}">Visão geral</a>
            <a class="operacao-tab" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise-viagens', ['record' => $record]) }}">Viagens</a>
            <a class="operacao-tab active" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise-abastecimentos', ['record' => $record]) }}">Abastecimentos</a>
            <a class="operacao-tab" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise-manutencao', ['record' => $record]) }}">Custos de manutenção</a>
            <a class="operacao-tab" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise-servicos', ['record' => $record]) }}">Serviços internos</a>
            <a class="operacao-tab" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise-garantias', ['record' => $record]) }}">Garantias</a>
        </nav>

        <section class="operacao-card">
            <div class="operacao-header">
                <h2>Abastecimentos vinculados</h2>
                <p>{{ $abastecimentosAnalise->count() }} {{ $abastecimentosAnalise->count() === 1 ? 'abastecimento vinculado' : 'abastecimentos vinculados' }} ao resultado de {{ $record->veiculo?->placa ?? 'veículo não identificado' }}.</p>
                @php
                    $abastecimentoInicialKm = $referenciasKmAbastecimento['inicial'];
                    $abastecimentoFinalKm = $referenciasKmAbastecimento['final'];
                @endphp
                @if ($abastecimentoInicialKm && $abastecimentoFinalKm)
                    <div class="operacao-reference">
                        KM rodado: {{ number_format($abastecimentoFinalKm->quilometragem, 0, ',', '.') }} km do abastecimento final de {{ $abastecimentoFinalKm->data_abastecimento?->format('d/m/Y H:i') }} menos {{ number_format($abastecimentoInicialKm->quilometragem, 0, ',', '.') }} km do abastecimento anterior de {{ $abastecimentoInicialKm->data_abastecimento?->format('d/m/Y H:i') }} = {{ number_format($referenciasKmAbastecimento['km_rodado'], 0, ',', '.') }} km.
                    </div>
                @endif
            </div>
            @if ($abastecimentosAnalise->isNotEmpty())
                <div class="operacao-table-wrap">
                    <table class="operacao-table">
                        <thead><tr><th>Data</th><th>Posto</th><th>Combustível</th><th class="number">Hodômetro</th><th class="number">Litros</th><th class="number">R$/L</th><th class="number">Valor total</th></tr></thead>
                        <tbody>
                            @foreach ($abastecimentosAnalise as $abastecimento)
                                <tr class="{{ $abastecimento['referencia_final_km'] ? 'referencia-final' : '' }}"><td>{{ $abastecimento['data'] }}</td><td>{{ $abastecimento['posto'] ?: 'Não informado' }}</td><td>{{ $abastecimento['tipo_combustivel'] ?: 'Não informado' }}</td><td class="number">{{ number_format($abastecimento['km'], 0, ',', '.') }} km</td><td class="number">{{ number_format($abastecimento['litros'], 2, ',', '.') }} L</td><td class="number">R$ {{ number_format($abastecimento['preco_por_litro'], 3, ',', '.') }}</td><td class="number">R$ {{ number_format($abastecimento['valor'], 2, ',', '.') }}@if ($abastecimento['referencia_final_km']) <span class="operacao-badge">Referência final do KM</span>@endif</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="operacao-empty">Nenhum abastecimento vinculado a este resultado.</div>
            @endif
        </section>
    </div>
</x-filament-panels::page>
