<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models;
use App\Models\ImportLog;
use App\Services\Veiculo\Queries\GetQuilometragemUltimoMovimento;
use App\Services\Veiculo\VeiculoService;
use Illuminate\Database\Eloquent\Casts\Json;
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
        $log = ImportLog::find(130);
        ds('Log de Importação');
        ds()->table(Json::decode($log->error_rows))->label('Error Rows');
    }
}
