<x-filament-panels::page>
    <style>
        .manutencao-analise { display: grid; gap: 1.25rem; color: #0f172a; }
        .manutencao-analise > .fi-ta { min-width: 0; }
        .manutencao-tabs { display: flex; gap: .5rem; overflow-x: auto; border-bottom: 1px solid #e2e8f0; }
        .manutencao-tab { flex: 0 0 auto; padding: .7rem .9rem; border-bottom: 2px solid transparent; color: #64748b; font-size: .82rem; font-weight: 700; text-decoration: none; }
        .manutencao-tab.active { border-color: #0f766e; color: #0f766e; }
        .dark .manutencao-analise { color: #e2e8f0; }
        .dark .manutencao-tabs { border-color: rgba(148, 163, 184, .18); }
    </style>

    <div class="manutencao-analise">
        <nav class="manutencao-tabs" aria-label="Análises do resultado">
            <a class="manutencao-tab" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise', ['record' => $record]) }}">Visão geral</a>
            <a class="manutencao-tab" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise-viagens', ['record' => $record]) }}">Viagens</a>
            <a class="manutencao-tab" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise-abastecimentos', ['record' => $record]) }}">Abastecimentos</a>
            <a class="manutencao-tab active" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise-manutencao', ['record' => $record]) }}">Custos de manutenção</a>
            <a class="manutencao-tab" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise-servicos', ['record' => $record]) }}">Serviços internos</a>
            <a class="manutencao-tab" href="{{ \App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource::getUrl('analise-garantias', ['record' => $record]) }}">Garantias</a>
        </nav>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
