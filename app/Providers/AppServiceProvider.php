<?php

namespace App\Providers;

use App\Events\Viagem\RecalcularRateioKmDispersaoRequested;
use App\Listeners\Viagem\AtualizarRateioKmDispersaoCargas;
use App\Models\DocumentoFrete;
use App\Observers\DocumentoFreteObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

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

    }
}
