<x-filament-panels::page>
    <style>
        .rm-actions { display: flex; gap: 12px; margin-top: 16px; flex-wrap: wrap; }
        .rm-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-top: 18px; }
        .rm-card { background: white; border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px; box-shadow: 0 1px 2px rgba(0,0,0,0.04); }
        .rm-card-label { color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
        .rm-card-value { color: #111827; font-size: 22px; font-weight: 700; margin-top: 4px; }
        .rm-group { background: white; border: 1px solid #e5e7eb; border-radius: 10px; margin-top: 18px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.04); }
        .rm-group-header { padding: 14px 16px; background: #f9fafb; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
        .rm-mechanic { font-size: 16px; font-weight: 700; color: #111827; }
        .rm-meta { font-size: 13px; color: #4b5563; }
        .rm-table-wrap { overflow-x: auto; }
        .rm-table { width: 100%; border-collapse: collapse; min-width: 980px; }
        .rm-table th { background: #fff; color: #6b7280; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; text-align: left; padding: 10px 12px; border-bottom: 1px solid #e5e7eb; }
        .rm-table td { color: #374151; font-size: 13px; padding: 10px 12px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        .rm-table tr:last-child td { border-bottom: none; }
        .rm-center { text-align: center; }
        .rm-os { font-weight: 700; color: #111827; }
        .rm-idle { color: #92400e; font-weight: 700; }
        .rm-muted { color: #9ca3af; }
        .rm-empty { margin-top: 18px; padding: 18px; border: 1px dashed #d1d5db; border-radius: 10px; color: #6b7280; background: white; }
        @media (max-width: 900px) { .rm-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 640px) { .rm-summary { grid-template-columns: 1fr; } }
    </style>

    <div>
        {{ $this->form }}

        <div class="rm-actions">
            <x-filament::button wire:click="carregarDados" icon="heroicon-o-magnifying-glass" color="success">
                Visualizar em Tela
            </x-filament::button>

            <x-filament::button wire:click="gerarPdf" icon="heroicon-o-arrow-down-tray" color="danger">
                Baixar PDF
            </x-filament::button>
        </div>

        @if(!empty($dadosRelatorio))
            <div class="rm-summary">
                <div class="rm-card">
                    <div class="rm-card-label">Mecânicos</div>
                    <div class="rm-card-value">{{ $dadosRelatorio['total_mecanicos'] }}</div>
                </div>
                <div class="rm-card">
                    <div class="rm-card-label">Apontamentos</div>
                    <div class="rm-card-value">{{ $dadosRelatorio['total_apontamentos'] }}</div>
                </div>
                <div class="rm-card">
                    <div class="rm-card-label">Tempo trabalhado</div>
                    <div class="rm-card-value">{{ $this->formatarMinutos($dadosRelatorio['total_trabalhado_minutos']) }}</div>
                </div>
                <div class="rm-card">
                    <div class="rm-card-label">Tempo ocioso</div>
                    <div class="rm-card-value">{{ $this->formatarMinutos($dadosRelatorio['total_ocioso_minutos']) }}</div>
                </div>
            </div>

            @forelse($dadosRelatorio['grupos'] as $grupo)
                <div class="rm-group">
                    <div class="rm-group-header">
                        <div>
                            <div class="rm-mechanic">{{ $grupo['colaborador_codigo'] }} - {{ $grupo['colaborador_nome'] }}</div>
                            <div class="rm-meta">{{ $grupo['total_apontamentos'] }} apontamento(s)</div>
                        </div>
                        <div class="rm-meta">
                            Trabalhado: <strong>{{ $this->formatarMinutos($grupo['total_trabalhado_minutos']) }}</strong>
                            &nbsp;|&nbsp;
                            Ocioso: <strong>{{ $this->formatarMinutos($grupo['total_ocioso_minutos']) }}</strong>
                        </div>
                    </div>
                    <div class="rm-table-wrap">
                        <table class="rm-table">
                            <thead>
                                <tr>
                                    <th>Seq.</th>
                                    <th>OS</th>
                                    <th>Veículo</th>
                                    <th>Início</th>
                                    <th>Fim</th>
                                    <th>Trabalhado</th>
                                    <th>Ocioso anterior</th>
                                    <th>Serviços</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($grupo['linhas'] as $linha)
                                    <tr>
                                        <td class="rm-center">{{ $loop->iteration }}</td>
                                        <td class="rm-os">#{{ $linha['ordem_servico_id'] }}</td>
                                        <td>{{ $linha['veiculo'] }}</td>
                                        <td>{{ $linha['iniciado_em_formatado'] }}</td>
                                        <td>{{ $linha['encerrado_em_formatado'] }}</td>
                                        <td>{{ $this->formatarMinutos($linha['trabalhado_minutos']) }}</td>
                                        <td class="{{ ($linha['ocioso_minutos'] ?? 0) > 0 ? 'rm-idle' : 'rm-muted' }}">
                                            {{ $this->formatarMinutos($linha['ocioso_minutos']) }}
                                        </td>
                                        <td>
                                            @forelse($linha['servicos'] as $servico)
                                                <div>{{ $servico }}</div>
                                            @empty
                                                <span class="rm-muted">-</span>
                                            @endforelse
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="rm-empty">Nenhum apontamento encontrado para os filtros selecionados.</div>
            @endforelse
        @elseif($buscaRealizada)
            <div class="rm-empty">Nenhum apontamento encontrado para os filtros selecionados.</div>
        @endif
    </div>
</x-filament-panels::page>
