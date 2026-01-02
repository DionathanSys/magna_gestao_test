<?php

namespace App\Filament\Bugio\Resources\ViagemBugios\Pages;

use App\Filament\Bugio\Resources\ViagemBugios\ViagemBugioResource;
use App\Models\Integrado;
use App\Models\Veiculo;
use App\Services\ViagemBugio\ViagemBugioService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use App\Services\NotificacaoService as notify;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CreateViagemBugio extends CreateRecord
{
    protected static string $resource = ViagemBugioResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {

        dd($data);
        $data['destinos']['integrado_nome']  = Integrado::find($data['destinos']['integrado_id'])?->nome ?? 'N/A';
        $data['veiculo_id']         = Veiculo::query()->where('placa', $data['veiculo'])->value('id');
        $data['km_pago']            = $data['km_total'] ?? 0;
        $data['km_rodado']          = 0;
        $data['data_competencia']   = $data['data_competencia'] ? Carbon::createFromFormat('d/m/Y', $data['data_competencia'])->format('Y-m-d') : now()->format('Y-m-d');
        $data['frete']              = $data['valor_frete'] ?? 0.0;
        $data['condutor']           = collect(db_config('config-bugio.motoristas'))->firstWhere('cpf', $data['motorista'] ?? null)['motorista'] ?? null;
        $data['motorista']          = [
            'cpf' => $data['motorista'] ?? null,
        ];
        $data['created_by']         = Auth::id();

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


        $anexos = $result->anexos()->create([
            'descricao' => 'Doc. Viagem ID: ' . $result->id,
            'attachments' => $data['anexos'],
            'created_by' => $result->created_by,
            'updated_by' => $result->updated_by,
        ]);

        Log::debug($anexos, $result);

        return $result;
    }
}
