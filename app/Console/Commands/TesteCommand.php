<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models;
use App\Services\Veiculo\Queries\GetQuilometragemUltimoMovimento;
use App\Services\Veiculo\VeiculoService;
use Illuminate\Support\Facades\Log;

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
        $abastecimentos = Models\Abastecimento::query()
            ->where('veiculo_id', 31)
            ->orderBy('data_abastecimento', 'desc')
            ->get();

            $abastecimentos->each(function($abastecimento) {
                dump($abastecimento->quilometragem_percorrida);
                dump($abastecimento->consumo_medio);
                dd($abastecimento->custo_por_km);
            });

            dd($abastecimentos)->label('Abastecimentos do Veículo');
    }
}
