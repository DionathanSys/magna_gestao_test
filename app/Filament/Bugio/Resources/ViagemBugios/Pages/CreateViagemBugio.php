<?php

namespace App\Filament\Bugio\Resources\ViagemBugios\Pages;

use App\Enum\Frete\TipoDocumentoEnum;
use App\Filament\Bugio\Resources\ViagemBugios\ViagemBugioResource;
use App\Jobs\SolicitarCteBugio;
use App\Models\Integrado;
use App\Models\Veiculo;
use App\Models\ViagemBugio;
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
        $data['info_adicionais']['motorista-cpf'] = $data['motorista'] ?? null;
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

        notify::success('Viagem Criada com Sucesso');

        $this->solicitarCte($result);

        notify::success('Solicitado CTe');

        return $result;
    }

    protected function solicitarCte(ViagemBugio $viagemBugio)
    {
        $anexos = [];

        Log::debug('anexos antes do ajustes', [
            'anexos' => $viagemBugio
        ]);
        
        foreach ($viagemBugio->anexos as $index => $anexo){
            $anexos[$index] = 'private/' . $anexo;
        }
        
        Log::debug('anexos depois do ajustes', $anexos);

        $data = [
            'km_total'          => $viagemBugio->km_pago,
            'valor_frete'       => $viagemBugio->frete,
            'anexos'            => $anexos,
            'destinos'          => $viagemBugio->destinos,
            'veiculo'           => $viagemBugio->veiculo->placa,
            'created_by'        => $viagemBugio->created_by,
            'nro_notas'         => $viagemBugio->nro_notas,
            'cte_retroativo'    => $viagemBugio->info_adicionais['cte_retroativo'] ?? false,
            'cte_complementar'  => $viagemBugio->info_adicionais['tipo_documento'] == TipoDocumentoEnum::CTE_COMPLEMENTO->value,
            'cte_referencia'    => $viagemBugio->info_adicionais['cte_referencia'] ?? null,
            'motorista'         => [
                'cpf' => $viagemBugio->info_adicionais['motorista-cpf'],
            ],

        ];

        Log::debug('dados do form novo para solicitar email', [
            'data' => $data,
        ]);

        // SolicitarCteBugio::dispatch($data);
    }

    private function calcularFrete(float $kmTotal): float
    {
        $valorQuilometro = db_config('config-bugio.valor-quilometro', 0);

        return $valorQuilometro * $kmTotal;
    }
}
