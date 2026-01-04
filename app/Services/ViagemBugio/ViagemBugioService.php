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
        try {
            if (! in_array($viagemBugio->info_adicionais['tipo_documento'], [TipoDocumentoEnum::CTE->value, TipoDocumentoEnum::CTE_COMPLEMENTO->value])) {
                return;
            }

            $anexos = [];

            foreach ($viagemBugio->anexos as $index => $anexo) {
                if (Storage::disk('local')->exists($anexo)) {
                    $anexos[$index] = $anexo;
                } else {
                    Log::alert($anexo . ' Não encontrado');
                }
            }

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

            SolicitarCteBugio::dispatch($data);

            $viagemBugio->update([
                'status' => 'em_andamento',
            ]);

        } catch (\Exception $e) {
            Log::error('Falha ao solicitar CTe via email', [
                'data' => $data,
            ]);
        }
    }

    public static function createViagemFromBugio(ViagemBugio $viagemBugio)
    {

        try {

            $destinos = ViagemBugio::query()
                ->where('numero_sequencial', $viagemBugio->numero_sequencial)
                ->get()
                ->flatMap(fn($row) => collect($row['destinos'])->pluck('integrado_id'))
                ->map(fn($v) => (int) $v)
                ->unique()
                ->values();

            Log::debug('destinos', [
                'detinos' => $destinos,
            ]);

            $data = [
                'veiculo_id'            => $viagemBugio->veiculo_id,
                'unidade_negocio'       => $viagemBugio->veiculo->filial,
                'cliente'               => ClienteEnum::BUGIO->value,
                'numero_viagem'         => 'BG-' . $viagemBugio->numero_sequencial,
                'documento_transporte'  => (string) $viagemBugio->numero_sequencial,
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

                $query = Viagem::query()->where('numero_viagem', 'BG-'.$viagemBugio->numero_sequencial);

                if(!$query->exists){
                    notify::error(mensagem: 'Viagem não encontrada');
                    return null;
                }

                $viagem = $query->get()->first();

            } else {
                notify::success('Viagem criada com sucesso! ID da Viagem: ' . $viagem->id);
            }


            $dataDocFrete = [
                'veiculo_id'            => $viagemBugio->veiculo_id,
                'parceiro_destino'      => 'BUGIO NUTRICAO',
                'parceiro_origem'       => 'BUGIO AGROPECUARIA',
                'numero_documento'      => (string) $viagemBugio->nro_documento,
                'documento_transporte'  => $viagemBugio->numero_sequencial,
                'data_emissao'          => $viagemBugio->data_emissao,
                'valor_total'           => $viagemBugio->frete,
                'valor_icms'            => 0,
                'tipo_documento'        => $viagemBugio->info_adicionais['tipo_documento'],
                'viagem_id'             => $viagem->id ?? null,
                'data_emissao'          => now(),
            ];

            $documentoService = new DocumentoFreteService();
            $documentoFrete = $documentoService->criarDocumentoFrete($dataDocFrete);

            notify::success('Documento de frete criado');

            ViagemBugio::query()
                ->where('numero_sequencial', $viagemBugio->numero_sequencial)
                ->update([
                    'documento_frete_id' => $documentoFrete?->id,
                    'viagem_id' => $viagem->id ?? null,
                ]);

            foreach ($destinos as $integradoId) {
                $integrado = \App\Models\Integrado::find($integradoId);

                Log::debug('Integrado', [
                    'integrado' => $integrado,
                ]);

                if (!$integrado) {
                    notify::alert('Integrado ID: ' . $integradoId . ' não encontrado. Pulando criação de carga.');
                    continue;
                }

                $cargaService = new CargaService();
                $carga = $cargaService->create($integrado, $viagem);

                Log::debug('Carga criada', [
                    'carga' => $carga,
                ]);

                notify::success('Carga criada para Integrado ID: ' . $integrado->nome . ' na Viagem ID: ' . $viagem->id);
            }

            notify::success(mensagem: 'Cadastro da viagem BG-' . $viagemBugio->numero_sequencial . ' finalizada com sucesso!');

            return $viagem;
        } catch (\Exception $e) {
            Log::debug('Erro em criação de viagem/doc frete Bugio ', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'viagemBugio' => $viagemBugio,
                'erro' => $e->getMessage(),
            ]);
        }
    }
}
