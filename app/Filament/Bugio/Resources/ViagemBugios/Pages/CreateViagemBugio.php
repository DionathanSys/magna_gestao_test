<?php

namespace App\Filament\Bugio\Resources\ViagemBugios\Pages;

use App\Enum\ClienteEnum;
use App\Enum\Frete\TipoDocumentoEnum;
use App\Filament\Bugio\Resources\ViagemBugios\ViagemBugioResource;
use App\Jobs\SolicitarCteBugio;
use App\Models\DocumentoFrete;
use App\Models\Integrado;
use App\Models\Veiculo;
use App\Models\ViagemBugio;
use App\Services\ViagemBugio\ViagemBugioService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use App\Services\NotificacaoService as notify;
use App\Services\ViagemNumberService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CreateViagemBugio extends CreateRecord
{
    protected static string $resource = ViagemBugioResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {

        $data['destinos']['integrado_nome']  = Integrado::find($data['destinos']['integrado_id'])?->nome ?? 'N/A';
        $data['destinos']                    = [$data['destinos']];
        $data['veiculo_id']                  = Veiculo::query()->where('placa', $data['veiculo'])->value('id');
        $data['km_pago']                     = $data['km_total'] ?? 0;
        $data['km_rodado']                   = 0;
        $data['data_competencia']            = $data['data_competencia'] ?? now()->format('Y-m-d');
        $data['frete']                       = $this->calcularFrete($data['km_total']);
        $data['condutor']                    = collect(db_config('config-bugio.motoristas'))->firstWhere('cpf', $data['motorista'] ?? null)['motorista'] ?? null;
        $data['created_by']                  = Auth::id();
        $data['numero_sequencial']           = $data['numero_sequencial'] ?? $this->getNroSequencial();
        $data['status']                      = 'pendente';
        $data['info_adicionais']['motorista-cpf'] = $data['motorista'] ?? null;

        unset($data['data-integrados']);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $service = new ViagemBugioService();
        $result = $service->criarViagem($data);

        if ($service->hasError()) {
            notify::error(mensagem: 'Falha ao criar viagem Bugio');
            $this->halt();
        }

        notify::success('Viagem Criada com Sucesso');

        // $bugioService = new ViagemBugioService();

        // if($result->info_adicionais['tipo_documento'] !== TipoDocumentoEnum::NFS->value){
        //     $bugioService->solicitarCte($result);
        //     notify::success('Solicitado emissão de CTe via email');
        //     return $result;
        // }

        // $bugioService->createViagemFromBugio($result);

        return $result;
    }

    private function calcularFrete(float $kmTotal): float
    {
        $valorQuilometro = db_config('config-bugio.valor-quilometro', 0);

        return $valorQuilometro * $kmTotal;
    }

    private function getNroSequencial()
    {
        $service = new ViagemNumberService();
        $n = $service->next(ClienteEnum::BUGIO->prefixoViagem());
        return $n['numero_sequencial'];
    }
}
