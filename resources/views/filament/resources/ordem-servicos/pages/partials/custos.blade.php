<section class="os-list-panel" x-data="{ activeTab: 'vinculados' }">
    <div class="os-list-title">Custos</div>

    <div class="os-tab-list" role="tablist" aria-label="Abas de custos">
        <button type="button" class="os-tab-button" :class="{ 'is-active': activeTab === 'vinculados' }" x-on:click="activeTab = 'vinculados'">
            Vinculados ({{ $record->manutencaoLancamentos->count() }})
        </button>
        <button type="button" class="os-tab-button" :class="{ 'is-active': activeTab === 'pendentes' }" x-on:click="activeTab = 'pendentes'">
            Pendentes ({{ $livewire->lancamentosPendentes->count() }})
        </button>
    </div>

    <div x-show="activeTab === 'vinculados'" x-cloak>
        @if ($record->manutencaoLancamentos->isEmpty())
            <div class="os-empty-list">Nenhum custo vinculado.</div>
        @else
            <div class="os-simple-list">
                @foreach ($record->manutencaoLancamentos->sortByDesc('data_negociacao') as $lancamento)
                    <div class="os-simple-item">
                        <strong>{{ $lancamento->produto }}</strong>
                        <span>Data: {{ $lancamento->data_negociacao?->format('d/m/Y') ?? 'Sem data' }}</span>
                        <span>Origem: {{ $lancamento->origem ?? '-' }} | Nro: {{ $lancamento->nr_os_nf ?: '-' }}</span>
                        <span>Parceiro: {{ $lancamento->parceiro ?? 'N/A' }}</span>
                        <span>Valor: R$ {{ number_format(($lancamento->valor_total_centavos ?? 0) / 100, 2, ',', '.') }}</span>
                        <span>Vínculo: {{ $lancamento->tipo_vinculo === 'automatico' ? 'Automático' : 'Manual' }}</span>
                        <div class="os-item-actions">
                            <x-filament::button size="xs" color="danger" wire:click="desvincularLancamento({{ $lancamento->id }})">
                                Desvincular
                            </x-filament::button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div x-show="activeTab === 'pendentes'" x-cloak>
        @if ($livewire->lancamentosPendentes->isEmpty())
            <div class="os-empty-list">Nenhum custo pendente para este veículo.</div>
        @else
            <div class="os-simple-list">
                @foreach ($livewire->lancamentosPendentes as $lancamento)
                    <div class="os-simple-item">
                        <strong>{{ $lancamento->produto }}</strong>
                        <span>Data: {{ $lancamento->data_negociacao?->format('d/m/Y') ?? 'Sem data' }}</span>
                        <span>Origem: {{ $lancamento->origem ?? '-' }} | Nro: {{ $lancamento->nr_os_nf ?: '-' }}</span>
                        <span>Parceiro: {{ $lancamento->parceiro ?? 'N/A' }}</span>
                        <span>Valor: R$ {{ number_format(($lancamento->valor_total_centavos ?? 0) / 100, 2, ',', '.') }}</span>
                        <div class="os-item-actions">
                            <x-filament::button size="xs" color="primary" wire:click="vincularLancamento({{ $lancamento->id }})">
                                Vincular nesta OS
                            </x-filament::button>
                            <x-filament::button size="xs" color="warning" wire:click="dispensarLancamento({{ $lancamento->id }})">
                                Dispensar
                            </x-filament::button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
