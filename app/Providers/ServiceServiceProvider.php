<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class ServiceServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //  // Registrar Services como Singletons
        // $this->app->singleton(\App\Services\Pneu\PneuService::class);
        // $this->app->singleton(\App\Services\Veiculo\VeiculoService::class);
        // $this->app->singleton(\App\Services\OrdemServico\OrdemServicoService::class);

        // // Registrar Reports
        // $this->app->bind(\App\Services\Pneu\Reports\RelatorioVidaUtilPneu::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Model::unguard();
    }
}
