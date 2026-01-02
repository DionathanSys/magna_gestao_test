<?php

namespace App\Filament\Bugio\Resources\ViagemBugios\Pages;

use App\Enum\Frete\TipoDocumentoEnum;
use App\Filament\Bugio\Resources\ViagemBugios\ViagemBugioResource;
use App\Jobs\SolicitarCteBugio;
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

        $data['destinos']['integrado_nome']  = Integrado::find($data['destinos']['integrado_id'])?->nome ?? 'N/A';
        $data['veiculo_id']         = Veiculo::query()->where('placa', $data['veiculo'])->value('id');
        $data['km_pago']            = $data['km_total'] ?? 0;
        $data['km_rodado']          = 0;
        $data['data_competencia']   = $data['data_competencia'] ? Carbon::createFromFormat('d/m/Y', $data['data_competencia'])->format('Y-m-d') : now()->format('Y-m-d');
        $data['frete']              = $this->calcularFrete($data['km_total']);
        $data['condutor']           = collect(db_config('config-bugio.motoristas'))->firstWhere('cpf', $data['motorista'] ?? null)['motorista'] ?? null;
        $data['info_adicionais']['motorista'] = [
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

        $data['anexos'] = $result->anexos()->create([
            'descricao' => 'Doc. Viagem ID: ' . $result->id . ' - Nro. Notas ' . implode(',', $data['nro_notas']),
            'attachments' => $data['anexos'],
            'created_by' => $result->created_by,
            'updated_by' => $result->updated_by,
        ]);

        $this->solicitarCte($data);

        return $result;
    }

    protected function solicitarCte(array $data)
    {

        foreach ($data['anexos'] as $index => $anexo){
            $data['anexos']['index'] = 'private/' . $anexo;
        }


        $data = [
            'km_total'          => $data['km_total'],
            'valor_frete'       => $data['frete'],
            'motorista'         => [
                'cpf' => $data['info_adicionais']['motorista']['cpf'],
            ],
            'veiculo'           => $data['veiculo'],
            'nro_notas'         => $data['nro_notas'],
            'cte_retroativo'    => $data['info_adicionais']['cte_retroativo'] ?? false,
            'cte_complementar'  => $data['info_adicionais']['tipo_documento'] == TipoDocumentoEnum::CTE_COMPLEMENTO->value,
            'destinos'          => [
                $data['destinos']
            ],
            'veiculo_id'        => $data['veiculo_id'],
            'km_pago'           => $data['km_pago'],
            'km_rodado'         => $data['km_rodado'],
            'veiculo_id'        => $data['veiculo_id'],
            'status'            => 'pendente',
            'created_by'        => $data['created_by'],
            'updated_by'        => $data['created_by'],
            'condutor'          => $data['condutor'],
            'data_competencia'  => $data['data_competencia'],
            'frete'             => $data['frete'],
            'anexos'            => $data['anexos'],
        ];

        Log::debug('dados do form novo', [
            'data' => $data,
        ]);

        SolicitarCteBugio::dispatch($data);
    }

    private function calcularFrete(float $kmTotal): float
    {
        $valorQuilometro = db_config('config-bugio.valor-quilometro', 0);

        return $valorQuilometro * $kmTotal;
    }
}
