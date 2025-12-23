<?php

namespace App\Console\Commands;

use App\Enum\ClienteEnum;
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
        $destinos = ViagemBugio::query()
            ->where('numero_sequencial', 1)
            ->get()
            ->flatMap(fn($row) => collect($row['destinos'])->pluck('integrado_id'))
            ->map(fn($v) => (int) $v)
            ->unique()
            ->values();

        dd($destinos);
    }
}
