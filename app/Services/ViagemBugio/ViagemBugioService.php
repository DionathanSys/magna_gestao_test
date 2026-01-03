<?php

namespace App\Services\ViagemBugio;

use App\{Models, Services, Enum};
use App\Enum\ClienteEnum;
use App\Enum\Frete\TipoDocumentoEnum;
use App\Enum\MotivoDivergenciaViagem;
use App\Jobs\SolicitarCteBugio;
use App\Models\Viagem;
use App\Models\ViagemBugio;
use App\Services\Carga\CargaService;
use App\Services\DocumentoFrete\DocumentoFreteService;
use App\Services\ViagemNumberService;
use App\Traits\ServiceResponseTrait;
use App\Traits\UserCheckTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\NotificacaoService as notify;
use Illuminate\Support\Facades\Auth;

class ViagemBugioService
{

    use ServiceResponseTrait, UserCheckTrait;

    public function criarViagem(array $data): ?Models\ViagemBugio
    {
        try {

            $action = new Actions\CriarViagem();
            $viagem = $action->handle($data);
            $this->setSuccess('Viagem criada com sucesso!');
            return $viagem;
        } catch (\Exception $e) {
            Log::error(__METHOD__ . '-' . __LINE__, [
                'error'     => $e->getMessage(),
                'data'      => $data,
                'user_id'   => $this->getUserIdChecked(),
            ]);
            $this->setError('Erro ao criar viagem', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public static function solicitarCte(ViagemBugio $viagemBugio)
    {
        $anexos = [];
        Log::debug('anexos antes do ajustes', [
            'anexos' => $viagemBugio->anexos
        ]);

        foreach ($viagemBugio->anexos as $index => $anexo) {
            if (Storage::disk('local')->exists($anexo)) {
                $anexos[$index] = $anexo;
            } else {
                notify::alert(mensagem: 'Arquivo não encontrado');
                Log::alert($anexo . ' Não encontrado');
            }
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

        SolicitarCteBugio::dispatch($data);
    }

    public static function createViagemFromBugio(ViagemBugio $viagemBugio)
    {

        try {

            if ($viagemBugio->numero_sequencial === null) {
                notify::alert('O registro de Viagem Bugio ID: ' . $viagemBugio->id . ' não possui número sequencial. Gerando um novo número.');
                $service = new ViagemNumberService();
                $n = $service->next(ClienteEnum::BUGIO->prefixoViagem());
                $viagemBugio->numero_sequencial = $n['numero_sequencial'];
                $viagemBugio->save();
            }

            $destinos = ViagemBugio::query()
                ->where('numero_sequencial', $viagemBugio->numero_sequencial)
                ->get()
                ->flatMap(fn($row) => collect($row['destinos'])->pluck('integrado_id'))
                ->map(fn($v) => (int) $v)
                ->unique()
                ->values();

            $data = [
                'veiculo_id'            => $viagemBugio->veiculo_id,
                'unidade_negocio'       => $viagemBugio->veiculo->filial,
                'cliente'               => ClienteEnum::BUGIO->value,
                'numero_viagem'         => 'BG-' . $viagemBugio->numero_sequencial,
                'documento_transporte'  => 'DocT-' . $viagemBugio->numero_sequencial,
                'km_rodado'             => 0,
                'km_cadastro'           => $viagemBugio->km_pago,
                'km_cobrar'             => 0,
                'km_pago'               => $viagemBugio->km_pago,
                'motivo_divergencia'    => MotivoDivergenciaViagem::SEM_OBS->value,
                'data_competencia'      => $viagemBugio->data_competencia,
                'data_inicio'           => $viagemBugio->data_competencia,
                'data_fim'              => $viagemBugio->data_competencia,
                'conferido'             => false,
                'condutor'              => $viagemBugio->condutor,
                'created_by'            => Auth::id(),
            ];

            $viagemService = new \App\Services\Viagem\ViagemService();
            $viagem = $viagemService->create($data);

            if (!$viagem) {
                notify::error('Erro ao criar viagem para o registro ID: ' . $viagemBugio->id);
                return null;
            }

            notify::success('Viagem criada com sucesso! ID da Viagem: ' . $viagem->id);

            $dataDocFrete = [
                'veiculo_id'            => $viagemBugio->veiculo_id,
                'parceiro_destino'      => 'BUGIO NUTRICAO',
                'parceiro_origem'       => 'BUGIO AGROPECUARIA',
                'numero_documento'      => $viagemBugio->info_adicionais['tipo_documento'] == TipoDocumentoEnum::NFS->value ?
                    $viagemBugio->numero_sequencial . '-' . $viagemBugio->id :
                    $viagemBugio->nro_documento,
                'documento_transporte'  => 'DocT-' . $viagemBugio->numero_sequencial,
                'data_emissao'          => $viagemBugio->data_emissao,
                'valor_total'           => $viagemBugio->frete,
                'valor_icms'            => 0,
                'tipo_documento'        => $viagemBugio->info_adicionais['tipo_documento'],
            ];

            $documentoService = new DocumentoFreteService();
            $documentoFrete = $documentoService->criarDocumentoFrete($dataDocFrete);

            notify::success('Documento de frete criado');

            ViagemBugio::query()
                ->where('numero_sequencial', $viagemBugio->numero_sequencial)
                ->update([
                    'documento_frete_id' => $$documentoFrete->id,
                    'viagem_id' => $viagem->id,
                ]);

            foreach ($destinos as $integradoId) {
                $integrado = \App\Models\Integrado::find($integradoId);
                if (!$integrado) {
                    notify::alert('Integrado ID: ' . $integradoId . ' não encontrado. Pulando criação de carga.');
                    continue;
                }

                $cargaService = new CargaService();
                $cargaService->create($integrado, $viagem);
                notify::success('Carga criada para Integrado ID: ' . $integrado->nome . ' na Viagem ID: ' . $viagem->id);
            }

            notify::success(mensagem: 'Cadastro da viagem BG-' . $viagemBugio->numero_sequencial . ' finalizada com sucesso!');

            return $viagem;
        } catch (\Exception $e) {
            Log::debug('Erro em criação de viagem/doc frete Bugio ', [
                'metodo' => __METHOD__.'@'.__LINE__,
                'viagemBugio' => $viagemBugio,
                'erro' => $e->getMessage(),
            ]);
            
        }
    }
}
