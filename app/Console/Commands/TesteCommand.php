<?php

namespace App\Console\Commands;

use App\Services\Veiculo\VeiculoCacheService;
use Illuminate\Console\Command;

class TesteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teste-command {--placa= : Placa do Veículo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        VeiculoCacheService::invalidarCacheVeiculos();
    }
}
