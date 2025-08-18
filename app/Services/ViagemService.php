<?php

namespace App\Services;

use App\DTO\ViagemDTO;
use App\Enum\MotivoDivergenciaViagem;
use App\Models\Integrado;
use App\Models\Veiculo;
use App\Models\Viagem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use PhpParser\Node\Stmt\Echo_;

class ViagemService
{
    public Viagem           $viagem;
    public CargaService     $cargaService;
    public IntegradoService $integradoService;
    public array $veiculos = [];

    public function __construct()
    {
        $this->viagem           = new Viagem();
        $this->cargaService     = new CargaService();
        $this->integradoService = new IntegradoService();
        $this->veiculos         = Veiculo::all()->pluck('id', 'placa')->toArray();
    }

    public function create(ViagemDTO $viagemDto)
    {
        try {

            $viagem = $this->viagem
                ->where('numero_viagem', $viagemDto->numero_viagem)
                ->first();

            switch (true) {
                case ($viagem && $viagem->conferido == false):
                    Log::info("Viagem Nº {$viagem->numero_viagem} atualizada");
                    $viagem->update($viagemDto->toArray());
                    break;
                case ($viagem && $viagem->conferido == true):
                    Log::info("Viagem Nº {$viagem->numero_viagem} já conferida, não será atualizado");
                    break;
                default:
                    Log::info("Viagem Nº {$viagemDto->numero_viagem} criada");
                    $viagem = $this->viagem->create($viagemDto->toArray());
                    $carga = $this->cargaService->create($viagemDto->integrado, $viagem);
            }

            return $viagem;
        } catch (\Exception $e) {
            dump($viagem, $e);
            return $e;
        }
    }

    public function recalcularViagem(Viagem $viagem)
    {
        try {

            Log::debug("Recalculando viagem {$viagem->numero_viagem}", [
                'metodo' => __METHOD__ . ' - ' . __LINE__,
                'viagem' => $viagem->id,
            ]);

            if ($viagem->km_pago > $viagem->km_rodado) {
                $viagem->km_pago_excedente = $viagem->km_pago - $viagem->km_rodado;
                $viagem->km_rodado_excedente = 0;
            } else {
                $viagem->km_pago_excedente = 0;
                $viagem->km_rodado_excedente = $viagem->km_rodado - $viagem->km_pago;
            }

            if($viagem->km_rota_corrigido > 0) {
                $viagem->km_cobrar = $viagem->km_rota_corrigido - $viagem->km_pago;
            }

            Log::debug("Viagem recalculada com sucesso {$viagem->numero_viagem}", [
                'metodo' => __METHOD__ . ' - ' . __LINE__,
                'divergencias' => $this->verificaDivergencia($viagem),
            ]);
            $viagem->save();

        } catch (\Exception $e) {
            Log::error('Erro ao recalcular viagem', [
                'metodo' => __METHOD__ . ' - ' . __LINE__,
                'mensagem' => $e->getMessage(),
                'viagem_id' => $viagem->id,
            ]);
        }
    }

    public function processarImportacao(Collection $rows, string $dataCorte)
    {

        $header = config('mapperColumns.import.viagem');
        $rows = $rows->skip(1);

        $rows->each(function ($row) use ($header, $dataCorte) {

            try {

                $dataFim = Carbon::createFromFormat('d/m/Y H:i', $row[$header['data_fim']])->format('Y-m-d');

                if ($dataFim >= $dataCorte) {

                    if ($row[$header['integrado']]) {
                        $integrado = $this->integradoService->buscaIntegrado($row[$header['integrado']]);
                    }

                    $km_rodado = $row[$header['km_rodado']]  ?? 0;
                    $km_pago = $row[$header['km_pago']]  ?? 0;
                    $km_divergencia = ($km_rodado - $km_pago ?? 0);
                    $km_cadastro = $integrado->km_rota ?? 0;

                    if ($km_pago > $km_rodado) {
                        $km_pago_excedente = $km_pago - $km_rodado;
                        $km_rodado_excedente = 0;
                    } else {
                        $km_pago_excedente = 0;
                        $km_rodado_excedente = $km_rodado - $km_pago;
                    }

                    $viagemDto = ViagemDTO::makeFromArray(
                        [
                            'numero_viagem'         => $row[$header['numero_viagem']],
                            'documento_transporte'  => $row[$header['documento_transporte']] ?? null,
                            'integrado'             => $integrado ?? null,
                            'veiculo_id'            => $this->veiculos[$row[$header['placa']]],
                            'km_rodado'             => $km_rodado,
                            'km_pago'               => $km_pago,
                            'km_divergencia'        => $km_divergencia,
                            'km_cadastro'           => $km_cadastro,
                            'km_pago_excedente'     => $km_pago_excedente,
                            'km_rodado_excedente'   => $km_rodado_excedente,
                            'data_competencia'      => $dataFim,
                            'data_inicio'           => $row[$header['data_inicio']],
                            'data_fim'              => $row[$header['data_fim']],
                        ]
                    );

                    $viagem = $this->create($viagemDto);

                }
            } catch (\Exception $e) {
                Log::error('Erro ao processar importação de viagem', [
                    'metodo' => __METHOD__ . ' - ' . __LINE__,
                    'mensagem' => $e->getMessage(),
                    'row' => $row,
                ]);

                echo "<pre>";
                echo "Erro ao processar viagem: " . $row[$header['numero_viagem']] . " Placa: " . $row[$header['placa']] . "\n";

                return;
            }
        });
    }

    public function verificaDivergencia(Viagem $viagem): array
    {
        $divergencias = [];

        if ($viagem->km_divergencia > 1) {
            $divergencias['km_divergencia'] = $viagem->km_divergencia;
        }

        if (! $viagem->documento_transporte) {
            $divergencias['documento_transporte'] = 'Documento de transporte não informado';
        }

        if (! $viagem->integrado) {
            $divergencias['integrado'] = 'Integrado não informado';
        }

        if (! $viagem->km_cadastro) {
            $divergencias['km_cadastro'] = 'KM de cadastro não informado';
        }

        //TODO: Implementar modo de atualizar a viagem com as divergências encontradas

        return $divergencias;
    }
}
