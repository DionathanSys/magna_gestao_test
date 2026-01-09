<?php

namespace App\Console\Commands;

use App\Enum\ClienteEnum;
use App\Enum\Frete\TipoDocumentoEnum;
use App\Jobs\VincularRegistroResultadoJob;
use Illuminate\Console\Command;
use App\Models;
use App\Models\ImportLog;
use App\Models\ViagemBugio;
use App\Services\Veiculo\VeiculoService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
        $itens_ordem_servico = Schema::getColumnListing('itens_ordem_servico');
        dump('itens_ordem_servico', $itens_ordem_servico);
        $veiculos = Schema::getColumnListing('veiculos');
        dump('veiculos', $veiculos);
        $ordens_servico = Schema::getColumnListing('ordens_servico');
        dd('ordens_servico', $ordens_servico);
    }
}
