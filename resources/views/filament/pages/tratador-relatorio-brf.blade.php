<x-filament-panels::page>
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }
        .stat-card {
            background: #f9fafb;
            border-radius: 8px;
            padding: 16px;
        }
        .stat-label {
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 4px;
        }
        .stat-value {
            font-size: 26px;
            font-weight: 700;
            color: #111827;
        }
        .stat-value.green { color: #059669; }
        .stat-value.amber { color: #d97706; }
        .stat-value.red { color: #dc2626; }
        .section-box {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 20px;
            margin-top: 20px;
        }
        .section-heading {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 16px;
        }
        .buttons-container {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        .divergencias-box {
            margin-top: 16px;
            padding: 16px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 6px;
        }
        .divergencias-title {
            font-size: 14px;
            font-weight: 600;
            color: #dc2626;
            margin-bottom: 8px;
        }
        .divergencias-list {
            margin: 0;
            padding-left: 20px;
            font-size: 13px;
            color: #991b1b;
        }
    </style>

    <div>
        {{ $this->form }}

        <div class="buttons-container">
            <x-filament::button
                wire:click="processar"
                icon="heroicon-o-play"
                color="primary"
            >
                Processar Relatório
            </x-filament::button>
        </div>

        @if($processed && $stats)
            <div class="section-box">
                <div class="section-heading">
                    Resumo do Processamento
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Linhas Lidas</div>
                        <div class="stat-value">{{ $stats['total_rows_read'] }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Viagens</div>
                        <div class="stat-value">{{ $stats['total_trips'] }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">1 Entrega</div>
                        <div class="stat-value green">{{ $stats['single_delivery_trips'] }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Multi-Entregas</div>
                        <div class="stat-value amber">{{ $stats['multi_delivery_trips'] }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Linhas Geradas</div>
                        <div class="stat-value">{{ $stats['total_output_rows'] }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Linhas Rateadas</div>
                        <div class="stat-value red">{{ $stats['rateado_rows'] }}</div>
                    </div>
                </div>

                @if(count($stats['divergencias']) > 0)
                    <div class="divergencias-box">
                        <div class="divergencias-title">
                            Divergências ({{ count($stats['divergencias']) }})
                        </div>
                        <ul class="divergencias-list">
                            @foreach($stats['divergencias'] as $divergencia)
                                <li>{{ $divergencia }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div style="margin-top: 20px;">
                    <x-filament::button
                        wire:click="baixar"
                        icon="heroicon-o-arrow-down-tray"
                        color="success"
                    >
                        Baixar Relatório Tratado
                    </x-filament::button>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
