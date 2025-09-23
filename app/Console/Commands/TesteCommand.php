<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models;
use App\Services\Veiculo\Queries\GetQuilometragemUltimoMovimento;
use App\Services\Veiculo\VeiculoService;

class TesteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teste-command {--id= : id do Modelo}';

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
        $state = '2025-03-24'; // Exemplo de data de teste_fumaca
        $state = \Carbon\Carbon::parse($state);

        dd(match (true) {
            !$state => 'null',
            $state <= now()->subDays(180) => '180 dias, Now: ' . now()->subDays(180) . ' - Diferença: ' . $state->diffInDays(now()),
            $state <= now()->subDays(165) => '165 dias, Now: ' . now()->subDays(165) . ' - Diferença: ' . $state->diffInDays(now()),
            $state <= now()->subDays(150) => '150 dias, Now: ' . now()->subDays(150) . ' - Diferença: ' . $state->diffInDays(now()),
            default => 'default',
        });
    }
}
