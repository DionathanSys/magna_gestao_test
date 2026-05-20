<x-filament-panels::page>
    @php
        $mapaPorEixo = $this->getMapaPorEixo();
        $selectedPosicao = $this->getSelectedPosicao();
        $selectedPneu = $selectedPosicao?->pneu;
        $ultimaInspecao = $selectedPneu ? $this->getUltimaInspecaoResumo($selectedPneu) : null;
    @endphp

    <div class="space-y-6">
        <x-filament::section>
            <div class="grid gap-4 md:grid-cols-4">
                <div>
                    <p class="text-sm text-gray-500">Veículo</p>
                    <p class="text-lg font-semibold">{{ $this->record->placa }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Km Atual</p>
                    <p class="text-lg font-semibold">{{ number_format($this->record->quilometragem_atual ?? 0, 0, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Tipo</p>
                    <p class="text-lg font-semibold">{{ $this->record->tipoVeiculo?->descricao ?? $this->record->tipoVeiculo?->nome ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Posições</p>
                    <p class="text-lg font-semibold">{{ $this->getPosicoes()->count() }}</p>
                </div>
            </div>
        </x-filament::section>

        <div class="space-y-4">
            @foreach ($mapaPorEixo as $eixo => $posicoes)
                <x-filament::section :heading="$eixo . 'º Eixo'">
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        @foreach ($posicoes as $posicao)
                            @php
                                $pneu = $posicao->pneu;
                                $inspecao = $pneu?->inspecoes?->first();
                            @endphp

                            <button
                                type="button"
                                wire:click="abrirInspecao({{ $posicao->id }})"
                                class="w-full rounded-xl border border-gray-200 bg-white p-4 text-left shadow-sm transition hover:border-primary-400 hover:shadow-md dark:border-white/10 dark:bg-gray-900"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs uppercase tracking-wide text-gray-500">Posição {{ $posicao->posicao }}</p>
                                        <p class="text-lg font-semibold text-gray-950 dark:text-white">
                                            {{ $pneu?->numero_fogo ?? 'Sem pneu' }}
                                        </p>
                                    </div>
                                    <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-white/10 dark:text-gray-200">
                                        Seq. {{ $posicao->sequencia }}
                                    </span>
                                </div>

                                <div class="mt-4 space-y-2 text-sm text-gray-600 dark:text-gray-300">
                                    <p>{{ $pneu?->marcaCatalogo?->nome ?? 'N/A' }} / {{ $pneu?->modeloCatalogo?->nome ?? 'N/A' }}</p>
                                    <p>Medida: {{ $pneu?->medidaCatalogo?->codigo ?? 'N/A' }}</p>
                                    <p>Vida atual: {{ $pneu?->ciclo_vida ?? 'N/A' }}</p>
                                    <p>Km rodado na posição: {{ number_format($posicao->km_rodado ?? 0, 0, ',', '.') }}</p>
                                    <p>
                                        Última inspeção:
                                        {{ $inspecao?->data_inspecao?->format('d/m/Y') ?? 'Sem registro' }}
                                    </p>
                                    <p>Resultado: {{ $inspecao?->resultado?->value ?? 'N/A' }}</p>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </x-filament::section>
            @endforeach
        </div>
    </div>

    @if ($this->isInspecaoSlideOverOpen)
        <div class="fixed inset-0 z-40 bg-black/40" wire:click="fecharInspecao"></div>

        <div class="fixed inset-y-0 right-0 z-50 w-full max-w-2xl overflow-y-auto bg-white shadow-2xl dark:bg-gray-900">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-white/10">
                <div>
                    <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Inspecionar Pneu</h2>
                    <p class="text-sm text-gray-500">
                        {{ $selectedPneu?->numero_fogo ?? 'Sem pneu selecionado' }}
                        @if ($selectedPosicao)
                            - {{ $selectedPosicao->eixo }}º eixo / {{ $selectedPosicao->posicao }}
                        @endif
                    </p>
                </div>

                <x-filament::icon-button icon="heroicon-m-x-mark" color="gray" wire:click="fecharInspecao" />
            </div>

            <div class="space-y-6 p-6">
                <x-filament::section heading="Resumo do Pneu">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-sm text-gray-500">Marca / Modelo</p>
                            <p class="font-medium">{{ $selectedPneu?->marcaCatalogo?->nome ?? 'N/A' }} / {{ $selectedPneu?->modeloCatalogo?->nome ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Medida</p>
                            <p class="font-medium">{{ $selectedPneu?->medidaCatalogo?->codigo ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Ciclo Atual</p>
                            <p class="font-medium">{{ $selectedPneu?->ciclo_vida ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Km Rodado na Posição</p>
                            <p class="font-medium">{{ number_format($selectedPosicao?->km_rodado ?? 0, 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Última Inspeção</p>
                            <p class="font-medium">{{ $ultimaInspecao?->data_inspecao?->format('d/m/Y') ?? 'Sem registro' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Último Resultado</p>
                            <p class="font-medium">{{ $ultimaInspecao?->resultado?->value ?? 'N/A' }}</p>
                        </div>
                    </div>
                </x-filament::section>

                <form wire:submit="salvarInspecao" class="space-y-6">
                    {{ $this->form }}

                    <div class="flex items-center justify-end gap-3">
                        <x-filament::button color="gray" type="button" wire:click="fecharInspecao">
                            Cancelar
                        </x-filament::button>

                        <x-filament::button type="submit">
                            Salvar Inspeção
                        </x-filament::button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</x-filament-panels::page>
