<?php

namespace App\Console\Commands;

use App\Enum\ClienteEnum;
use App\Enum\Frete\TipoDocumentoEnum;
use App\Jobs\VincularRegistroResultadoJob;
use Illuminate\Console\Command;
use App\Models;
use App\Models\ImportLog;
use App\Models\ViagemBugio;
use App\Services\Veiculo\Queries\GetQuilometragemUltimoMovimento;
use App\Services\Veiculo\VeiculoCacheService;
use App\Services\Veiculo\VeiculoService;
use Carbon\Carbon;
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
        $destinos = 'required|string|in:' . implode(',', TipoDocumentoEnum::toSelectArray());

        dd($destinos);
    }
}
