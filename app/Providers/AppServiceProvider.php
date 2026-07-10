<?php

namespace App\Providers;

use App\Events\Viagem\RecalcularRateioKmDispersaoRequested;
use App\Filament\Widgets\OficinaManutencaoComparativoPeriodo;
use App\Filament\Widgets\OficinaManutencaoEvolucaoMensal;
use App\Filament\Widgets\OficinaManutencaoItensRecorrentes;
use App\Filament\Widgets\OficinaManutencaoPorGrupoProduto;
use App\Filament\Widgets\OficinaManutencaoPorVeiculo;
use App\Filament\Widgets\OficinaManutencaoResumo;
use App\Filament\Widgets\OficinaManutencaoTipoResumo;
use App\Listeners\Viagem\AtualizarRateioKmDispersaoCargas;
use App\Models\DocumentoFrete;
use App\Observers\DocumentoFreteObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Aumentar limite de memória para suportar operações pesadas (tabelas Filament com muitos registros selecionados)
        ini_set('memory_limit', '256M');

        Model::unguard();

        Event::listen(
            RecalcularRateioKmDispersaoRequested::class,
            AtualizarRateioKmDispersaoCargas::class,
        );

        DocumentoFrete::observe(DocumentoFreteObserver::class);

        // Registra explicitamente os widgets da oficina para evitar falhas de
        // resolução do alias do Livewire em ambientes com cache/autoload defasado.
        Livewire::component('app.filament.widgets.oficina-manutencao-resumo', OficinaManutencaoResumo::class);
        Livewire::component('app.filament.widgets.oficina-manutencao-comparativo-periodo', OficinaManutencaoComparativoPeriodo::class);
        Livewire::component('app.filament.widgets.oficina-manutencao-tipo-resumo', OficinaManutencaoTipoResumo::class);
        Livewire::component('app.filament.widgets.oficina-manutencao-por-veiculo', OficinaManutencaoPorVeiculo::class);
        Livewire::component('app.filament.widgets.oficina-manutencao-por-grupo-produto', OficinaManutencaoPorGrupoProduto::class);
        Livewire::component('app.filament.widgets.oficina-manutencao-evolucao-mensal', OficinaManutencaoEvolucaoMensal::class);
        Livewire::component('app.filament.widgets.oficina-manutencao-itens-recorrentes', OficinaManutencaoItensRecorrentes::class);

    }
}
