<?php

namespace App\Providers;

use App\Models\Viagem;
use App\Models\DocumentoFrete;
use App\Observers\ViagemObserver;
use App\Observers\DocumentoFreteObserver;
use Filament\Support\Facades\FilamentView;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\RateLimiter;
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
        Model::unguard();

        // Registrar Observers
        Viagem::observe(ViagemObserver::class);
        DocumentoFrete::observe(DocumentoFreteObserver::class);

    }
}
