<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::card>
            <div class="space-y-4">
                <h3 class="text-lg font-medium">Filtros do Relatório</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Veículo</label>
                        <select wire:model="data.veiculo_id" class="block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">Todos os veículos ativos</option>
                            @foreach(\App\Models\Veiculo::where('is_active', true)->orderBy('placa')->get() as $veiculo)
                                <option value="{{ $veiculo->id }}">{{ $veiculo->placa }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Plano Preventivo</label>
                        <select wire:model="data.plano_preventivo_id" class="block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">Todos os planos ativos</option>
                            @foreach(\App\Models\PlanoPreventivo::where('is_active', true)->orderBy('descricao')->get() as $plano)
                                <option value="{{ $plano->id }}">{{ $plano->descricao }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">KM Restante Máximo</label>
                        <input 
                            type="number" 
                            wire:model="data.km_restante_maximo" 
                            placeholder="Ex: 5000"
                            min="0"
                            class="block w-full border-gray-300 rounded-md shadow-sm"
                        />
                        <p class="mt-1 text-xs text-gray-500">Deixe vazio para trazer todos</p>
                    </div>
                </div>
            </div>
        </x-filament::card>

        <div class="flex gap-3">
            <x-filament::button
                wire:click="gerarRelatorio"
                icon="heroicon-o-arrow-down-tray"
            >
                Baixar PDF
            </x-filament::button>

            <x-filament::button
                wire:click="visualizarRelatorio"
                color="info"
                icon="heroicon-o-eye"
            >
                Visualizar PDF
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page>
