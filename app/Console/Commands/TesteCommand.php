<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models;
use App\Services\Veiculo\Queries\GetQuilometragemUltimoMovimento;
use App\Services\Veiculo\VeiculoService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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
        $data = [
            'placa' => $this->option('placa') ?? null,
        ];

        $validate = Validator::make($data, [
            'placa' => ['required', 'string', 'exists:veiculos,placa'],
            // 'placa' => ['required', 'string', new \App\Rules\VeiculoExistsRule()],
        ], [
            'placa' => 'placa do veículo',
        ]);

        if ($validate->fails()) {
            $this->error("Validação falhou: " . implode(", ", $validate->errors()->all()));
            return;
        }

        $this->info("Validação bem-sucedida!");
    }
}
