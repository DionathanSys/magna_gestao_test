<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property int $id_abastecimento
 * @property int $veiculo_id
 * @property int $quilometragem
 * @property string $posto_combustivel
 * @property \App\Enum\Abastecimento\TipoCombustivelEnum $tipo_combustivel
 * @property string $quantidade
 * @property mixed $preco_por_litro Preço por litro em centavos
 * @property mixed|null $preco_total Preço total em centavos
 * @property \Illuminate\Support\Carbon $data_abastecimento
 * @property int $considerar_fechamento
 * @property int $considerar_calculo_medio
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $resultado_periodo_id
 * @property-read mixed $consumo_medio
 * @property-read mixed $custo_por_km
 * @property-read mixed $dias_desde_ultimo_abastecimento
 * @property-read mixed $is_primeiro_abastecimento
 * @property-read mixed $quilometragem_percorrida
 * @property-read \App\Models\ResultadoPeriodo|null $resultadoPeriodo
 * @property-read mixed $title
 * @property-read mixed $ultimo_abastecimento_anterior
 * @property-read \App\Models\Veiculo $veiculo
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Abastecimento anterioresAData($data, $veiculoId = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Abastecimento newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Abastecimento newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Abastecimento query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Abastecimento ultimoAteData($data, $veiculoId = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Abastecimento whereConsiderarCalculoMedio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Abastecimento whereConsiderarFechamento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Abastecimento whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Abastecimento whereDataAbastecimento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Abastecimento whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Abastecimento whereIdAbastecimento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Abastecimento wherePostoCombustivel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Abastecimento wherePrecoPorLitro($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Abastecimento wherePrecoTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Abastecimento whereQuantidade($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Abastecimento whereQuilometragem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Abastecimento whereResultadoPeriodoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Abastecimento whereTipoCombustivel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Abastecimento whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Abastecimento whereVeiculoId($value)
 */
	class Abastecimento extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $veiculo_id
 * @property int|null $ordem_servico_id
 * @property \Illuminate\Support\Carbon|null $data_agendamento
 * @property string|null $data_limite
 * @property string|null $data_realizado
 * @property int $servico_id
 * @property int|null $plano_preventivo_id
 * @property string|null $posicao
 * @property \App\Enum\OrdemServico\StatusOrdemServicoEnum $status
 * @property string|null $observacao
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $parceiro_id
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\OrdemServico|null $ordemServico
 * @property-read \App\Models\Parceiro|null $parceiro
 * @property-read \App\Models\PlanoPreventivo|null $planoPreventivo
 * @property-read \App\Models\Servico $servico
 * @property-read \App\Models\User|null $updater
 * @property-read \App\Models\Veiculo $veiculo
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agendamento newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agendamento newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agendamento query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agendamento whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agendamento whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agendamento whereDataAgendamento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agendamento whereDataLimite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agendamento whereDataRealizado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agendamento whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agendamento whereObservacao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agendamento whereOrdemServicoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agendamento whereParceiroId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agendamento wherePlanoPreventivoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agendamento wherePosicao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agendamento whereServicoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agendamento whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agendamento whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agendamento whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Agendamento whereVeiculoId($value)
 */
	class Agendamento extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $descricao
 * @property array<array-key, mixed> $attachments
 * @property string $anexavel_type
 * @property int $anexavel_id
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Anexo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Anexo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Anexo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Anexo whereAnexavelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Anexo whereAnexavelType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Anexo whereAttachments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Anexo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Anexo whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Anexo whereDescricao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Anexo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Anexo whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Anexo whereUpdatedBy($value)
 */
	class Anexo extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $veiculo_id
 * @property string|null $quilometragem
 * @property string $data_referencia
 * @property string|null $descricao
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $complemento
 * @property string $anotavel_type
 * @property int $anotavel_id
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnotacaoVeiculo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnotacaoVeiculo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnotacaoVeiculo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnotacaoVeiculo whereAnotavelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnotacaoVeiculo whereAnotavelType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnotacaoVeiculo whereComplemento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnotacaoVeiculo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnotacaoVeiculo whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnotacaoVeiculo whereDataReferencia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnotacaoVeiculo whereDescricao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnotacaoVeiculo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnotacaoVeiculo whereQuilometragem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnotacaoVeiculo whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnotacaoVeiculo whereVeiculoId($value)
 */
	class AnotacaoVeiculo extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $viagem_id
 * @property string $descricao
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Viagem $viagem
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnotacaoViagem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnotacaoViagem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnotacaoViagem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnotacaoViagem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnotacaoViagem whereDescricao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnotacaoViagem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnotacaoViagem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnotacaoViagem whereViagemId($value)
 */
	class AnotacaoViagem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $viagem_id
 * @property int|null $integrado_id
 * @property string|null $documento_transporte
 * @property int|null $documento_frete_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property string $km_dispersao
 * @property int $km_dispersao_rateio
 * @property-read \App\Models\ViagemComplemento|null $complementos
 * @property-read \App\Models\Integrado|null $integrado
 * @property-read \App\Models\Viagem $viagem
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CargaViagem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CargaViagem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CargaViagem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CargaViagem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CargaViagem whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CargaViagem whereDocumentoFreteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CargaViagem whereDocumentoTransporte($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CargaViagem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CargaViagem whereIntegradoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CargaViagem whereKmDispersao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CargaViagem whereKmDispersaoRateio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CargaViagem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CargaViagem whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CargaViagem whereViagemId($value)
 */
	class CargaViagem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $veiculo_id
 * @property array<array-key, mixed> $itens_verificados
 * @property array<array-key, mixed>|null $itens_corrigidos
 * @property array<array-key, mixed>|null $pendencias
 * @property string $data_referencia
 * @property string|null $periodo
 * @property string $quilometragem
 * @property string $status
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property array<array-key, mixed>|null $anexos
 * @property-read \App\Models\User $creator
 * @property-read mixed $itens_corrigidos_count
 * @property-read mixed $itens_verificados_count
 * @property-read mixed $pendencias_count
 * @property-read \App\Models\Veiculo $veiculo
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Checklist newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Checklist newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Checklist query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Checklist whereAnexos($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Checklist whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Checklist whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Checklist whereDataReferencia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Checklist whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Checklist whereItensCorrigidos($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Checklist whereItensVerificados($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Checklist wherePendencias($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Checklist wherePeriodo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Checklist whereQuilometragem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Checklist whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Checklist whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Checklist whereVeiculoId($value)
 */
	class Checklist extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $conteudo
 * @property int|null $veiculo_id
 * @property string $comentavel_type
 * @property int $comentavel_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $created_by
 * @property-read \App\Models\User|null $creator
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comentario newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comentario newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comentario query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comentario whereComentavelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comentario whereComentavelType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comentario whereConteudo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comentario whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comentario whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comentario whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comentario whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comentario whereVeiculoId($value)
 */
	class Comentario extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $pneu_id
 * @property string $data_conserto
 * @property string $tipo_conserto
 * @property string|null $ciclo_vida
 * @property int|null $parceiro_id
 * @property string $valor
 * @property bool $garantia
 * @property int|null $veiculo_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Parceiro|null $parceiro
 * @property-read \App\Models\Pneu $pneu
 * @property-read \App\Models\Veiculo|null $veiculo
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conserto newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conserto newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conserto query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conserto whereCicloVida($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conserto whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conserto whereDataConserto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conserto whereGarantia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conserto whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conserto whereParceiroId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conserto wherePneuId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conserto whereTipoConserto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conserto whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conserto whereValor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conserto whereVeiculoId($value)
 */
	class Conserto extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property \Illuminate\Support\Carbon $data_inicio
 * @property \Illuminate\Support\Carbon $data_fim
 * @property array<array-key, mixed> $descricao
 * @property numeric $custo_total
 * @property int $quantidade_veiculos
 * @property string|null $custo_medio_por_veiculo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustoDiverso newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustoDiverso newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustoDiverso query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustoDiverso whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustoDiverso whereCustoMedioPorVeiculo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustoDiverso whereCustoTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustoDiverso whereDataFim($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustoDiverso whereDataInicio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustoDiverso whereDescricao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustoDiverso whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustoDiverso whereQuantidadeVeiculos($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustoDiverso whereUpdatedAt($value)
 */
	class CustoDiverso extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $descricao
 * @property string|null $image_path
 * @property string|null $medida
 * @property string|null $modelo
 * @property \App\Enum\Pneu\EstadoPneuEnum $estado_pneu
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Pneu> $pneus
 * @property-read int|null $pneus_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DesenhoPneu newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DesenhoPneu newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DesenhoPneu query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DesenhoPneu whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DesenhoPneu whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DesenhoPneu whereDescricao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DesenhoPneu whereEstadoPneu($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DesenhoPneu whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DesenhoPneu whereImagePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DesenhoPneu whereMedida($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DesenhoPneu whereModelo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DesenhoPneu whereUpdatedAt($value)
 */
	class DesenhoPneu extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $viagem_id
 * @property string $tipo_divergencia
 * @property string|null $descricao
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Viagem $viagem
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DivergenciaViagem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DivergenciaViagem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DivergenciaViagem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DivergenciaViagem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DivergenciaViagem whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DivergenciaViagem whereDescricao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DivergenciaViagem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DivergenciaViagem whereTipoDivergencia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DivergenciaViagem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DivergenciaViagem whereViagemId($value)
 */
	class DivergenciaViagem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nome
 * @property string $unidade
 * @property string $setor
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Indicador> $indicadores
 * @property-read int|null $indicadores_count
 * @property-read mixed $pontuacao_coletiva
 * @property-read mixed $pontuacao_individual
 * @property-read mixed $pontuacao_maxima
 * @property-read mixed $pontuacao_obtida
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Resultado> $resultados
 * @property-read int|null $resultados_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gestor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gestor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gestor query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gestor whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gestor whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gestor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gestor whereNome($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gestor whereSetor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gestor whereUnidade($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gestor whereUpdatedAt($value)
 */
	class Gestor extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $gestor_id
 * @property int $indicador_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GestorIndicador newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GestorIndicador newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GestorIndicador query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GestorIndicador whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GestorIndicador whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GestorIndicador whereGestorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GestorIndicador whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GestorIndicador whereIndicadorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GestorIndicador whereUpdatedAt($value)
 */
	class GestorIndicador extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $pneu_id
 * @property int $veiculo_id
 * @property string $data_inicial
 * @property string $data_final
 * @property string $km_inicial
 * @property string|null $km_final
 * @property string|null $km_percorrido
 * @property string $eixo
 * @property string $posicao
 * @property string $sulco_movimento
 * @property string $ciclo_vida
 * @property string|null $motivo
 * @property string|null $observacao
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\Anexo> $anexos
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property-read int|null $anexos_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Comentario> $comentarios
 * @property-read int|null $comentarios_count
 * @property-read \App\Models\Pneu $pneu
 * @property-read \App\Models\Veiculo $veiculo
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoricoMovimentoPneu newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoricoMovimentoPneu newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoricoMovimentoPneu query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoricoMovimentoPneu whereAnexos($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoricoMovimentoPneu whereCicloVida($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoricoMovimentoPneu whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoricoMovimentoPneu whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoricoMovimentoPneu whereDataFinal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoricoMovimentoPneu whereDataInicial($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoricoMovimentoPneu whereEixo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoricoMovimentoPneu whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoricoMovimentoPneu whereKmFinal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoricoMovimentoPneu whereKmInicial($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoricoMovimentoPneu whereKmPercorrido($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoricoMovimentoPneu whereMotivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoricoMovimentoPneu whereObservacao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoricoMovimentoPneu wherePneuId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoricoMovimentoPneu wherePosicao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoricoMovimentoPneu whereSulcoMovimento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoricoMovimentoPneu whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoricoMovimentoPneu whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoricoMovimentoPneu whereVeiculoId($value)
 */
	class HistoricoMovimentoPneu extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $veiculo_id
 * @property \Illuminate\Support\Carbon $data_referencia
 * @property int $quilometragem
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Veiculo|null $veiculo
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoricoQuilometragem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoricoQuilometragem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoricoQuilometragem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoricoQuilometragem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoricoQuilometragem whereDataReferencia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoricoQuilometragem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoricoQuilometragem whereQuilometragem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoricoQuilometragem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoricoQuilometragem whereVeiculoId($value)
 */
	class HistoricoQuilometragem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $file_name
 * @property string $file_path
 * @property string|null $file_size
 * @property string|null $file_hash
 * @property string $import_type
 * @property string|null $import_description
 * @property int $user_id
 * @property \App\Enum\Import\StatusImportacaoEnum $status
 * @property int $total_rows
 * @property int|null $processed_rows
 * @property int $success_rows
 * @property int $error_rows
 * @property int $warning_rows
 * @property int $skipped_rows
 * @property int $total_batches
 * @property int $processed_batches
 * @property string|null $progress_percentage
 * @property array<array-key, mixed>|null $errors
 * @property array<array-key, mixed>|null $warnings
 * @property array<array-key, mixed>|null $skipped_reasons
 * @property array<array-key, mixed>|null $options
 * @property array<array-key, mixed>|null $mapping
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $finished_at
 * @property int|null $duration_seconds
 * @property string|null $notes
 * @property array<array-key, mixed>|null $summary
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string|null $duration_formatted
 * @property-read string|null $file_size_formatted
 * @property-read string $progress_percentage_formatted
 * @property-read string $status_badge_color
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog byStatus(string $status)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog byType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog byUser(int $userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog completed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog inProgress()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog whereDurationSeconds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog whereErrorRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog whereErrors($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog whereFileHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog whereFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog whereFileSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog whereFinishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog whereImportDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog whereImportType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog whereMapping($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog whereOptions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog whereProcessedBatches($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog whereProcessedRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog whereProgressPercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog whereSkippedReasons($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog whereSkippedRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog whereSuccessRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog whereSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog whereTotalBatches($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog whereTotalRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog whereWarningRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportLog whereWarnings($value)
 */
	class ImportLog extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $descricao
 * @property string $tipo_avaliacao
 * @property string|null $objetivo
 * @property string|null $tipo_meta
 * @property string $peso
 * @property string $periodicidade
 * @property string $tipo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Gestor> $gestores
 * @property-read int|null $gestores_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Resultado> $resultados
 * @property-read int|null $resultados_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Indicador newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Indicador newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Indicador query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Indicador whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Indicador whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Indicador whereDescricao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Indicador whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Indicador whereObjetivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Indicador wherePeriodicidade($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Indicador wherePeso($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Indicador whereTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Indicador whereTipoAvaliacao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Indicador whereTipoMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Indicador whereUpdatedAt($value)
 */
	class Indicador extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $veiculo_id
 * @property string $data_inspecao
 * @property int $quilometragem
 * @property string|null $observacoes
 * @property string $status
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItemInspecao> $itens
 * @property-read int|null $itens_count
 * @property-read \App\Models\Veiculo $veiculo
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inspecao newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inspecao newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inspecao query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inspecao whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inspecao whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inspecao whereDataInspecao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inspecao whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inspecao whereObservacoes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inspecao whereQuilometragem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inspecao whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inspecao whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inspecao whereVeiculoId($value)
 */
	class Inspecao extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $codigo
 * @property string $nome
 * @property string $km_rota
 * @property string|null $municipio
 * @property string|null $estado
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property string|null $latitude
 * @property string|null $longitude
 * @property \App\Enum\ClienteEnum $cliente
 * @property bool $alerta_viagem
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CargaViagem> $cargas
 * @property-read int|null $cargas_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Comentario> $comentarios
 * @property-read int|null $comentarios_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Integrado newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Integrado newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Integrado query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Integrado whereAlertaViagem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Integrado whereCliente($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Integrado whereCodigo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Integrado whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Integrado whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Integrado whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Integrado whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Integrado whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Integrado whereKmRota($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Integrado whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Integrado whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Integrado whereMunicipio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Integrado whereNome($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Integrado whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Integrado whereUpdatedBy($value)
 */
	class Integrado extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $inspecao_id
 * @property string $inspecionavel_type
 * @property int $inspecionavel_id
 * @property string|null $observacao
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Inspecao $inspecao
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $item_inspecionado
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemInspecao newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemInspecao newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemInspecao query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemInspecao whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemInspecao whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemInspecao whereInspecaoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemInspecao whereInspecionavelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemInspecao whereInspecionavelType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemInspecao whereObservacao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemInspecao whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemInspecao whereUpdatedAt($value)
 */
	class ItemInspecao extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $ordem_servico_id
 * @property int $servico_id
 * @property int|null $plano_preventivo_id
 * @property string|null $posicao
 * @property string|null $observacao
 * @property \App\Enum\OrdemServico\StatusOrdemServicoEnum $status
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read \App\Models\Agendamento|null $agendamento
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Comentario> $comentarios
 * @property-read int|null $comentarios_count
 * @property-read \App\Models\User $creator
 * @property-read \App\Models\OrdemServico $ordemServico
 * @property-read \App\Models\PlanoPreventivo|null $planoPreventivo
 * @property-read \App\Models\Servico $servico
 * @property-read \App\Models\Veiculo|null $veiculo
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemOrdemServico newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemOrdemServico newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemOrdemServico query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemOrdemServico whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemOrdemServico whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemOrdemServico whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemOrdemServico whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemOrdemServico whereObservacao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemOrdemServico whereOrdemServicoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemOrdemServico wherePlanoPreventivoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemOrdemServico wherePosicao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemOrdemServico whereServicoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemOrdemServico whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemOrdemServico whereUpdatedAt($value)
 */
	class ItemOrdemServico extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $veiculo_id
 * @property string $data_inicio
 * @property string $data_fim
 * @property string $custo_total
 * @property int $resultado_periodo_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ResultadoPeriodo $resultadoPeriodo
 * @property-read \App\Models\Veiculo $veiculo
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManutencaoCusto newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManutencaoCusto newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManutencaoCusto query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManutencaoCusto whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManutencaoCusto whereCustoTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManutencaoCusto whereDataFim($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManutencaoCusto whereDataInicio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManutencaoCusto whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManutencaoCusto whereResultadoPeriodoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManutencaoCusto whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManutencaoCusto whereVeiculoId($value)
 */
	class ManutencaoCusto extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdemExterna newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdemExterna newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdemExterna query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdemExterna whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdemExterna whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdemExterna whereUpdatedAt($value)
 */
	class OrdemExterna extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $ordem_servico_id
 * @property string $ordem_sankhya_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OrdemServico $ordemServico
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdemSankhya newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdemSankhya newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdemSankhya query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdemSankhya whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdemSankhya whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdemSankhya whereOrdemSankhyaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdemSankhya whereOrdemServicoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdemSankhya whereUpdatedAt($value)
 */
	class OrdemSankhya extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $veiculo_id
 * @property string|null $quilometragem
 * @property \App\Enum\OrdemServico\TipoManutencaoEnum|null $tipo_manutencao
 * @property string|null $data_inicio
 * @property string|null $data_fim
 * @property \App\Enum\OrdemServico\StatusOrdemServicoEnum $status
 * @property string $status_sankhya
 * @property int|null $parceiro_id
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Agendamento> $agendamentos
 * @property-read int|null $agendamentos_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Agendamento> $agendamentosPendentes
 * @property-read int|null $agendamentos_pendentes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Comentario> $comentarios
 * @property-read int|null $comentarios_count
 * @property-read \App\Models\User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItemOrdemServico> $itens
 * @property-read int|null $itens_count
 * @property-read \App\Models\Parceiro|null $parceiro
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Agendamento> $pendentes
 * @property-read int|null $pendentes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PlanoManutencaoOrdemServico> $planoPreventivoVinculado
 * @property-read int|null $plano_preventivo_vinculado_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OrdemSankhya> $sankhyaId
 * @property-read int|null $sankhya_id_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Servico> $servicos
 * @property-read int|null $servicos_count
 * @property-read \App\Models\Veiculo $veiculo
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdemServico newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdemServico newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdemServico query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdemServico whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdemServico whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdemServico whereDataFim($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdemServico whereDataInicio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdemServico whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdemServico whereParceiroId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdemServico whereQuilometragem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdemServico whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdemServico whereStatusSankhya($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdemServico whereTipoManutencao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdemServico whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrdemServico whereVeiculoId($value)
 */
	class OrdemServico extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $nome
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parceiro newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parceiro newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parceiro query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parceiro whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parceiro whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parceiro whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parceiro whereNome($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parceiro whereUpdatedAt($value)
 */
	class Parceiro extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $plano_preventivo_id
 * @property int|null $ordem_servico_id
 * @property int $veiculo_id
 * @property string $km_execucao
 * @property string|null $data_execucao
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OrdemServico|null $ordemServico
 * @property-read \App\Models\PlanoPreventivo|null $planoPreventivo
 * @property-read \App\Models\PlanoManutencaoVeiculo|null $planoPreventivoVinculado
 * @property-read \App\Models\Veiculo $veiculo
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanoManutencaoOrdemServico newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanoManutencaoOrdemServico newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanoManutencaoOrdemServico query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanoManutencaoOrdemServico whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanoManutencaoOrdemServico whereDataExecucao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanoManutencaoOrdemServico whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanoManutencaoOrdemServico whereKmExecucao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanoManutencaoOrdemServico whereOrdemServicoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanoManutencaoOrdemServico wherePlanoPreventivoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanoManutencaoOrdemServico whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanoManutencaoOrdemServico whereVeiculoId($value)
 */
	class PlanoManutencaoOrdemServico extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $plano_preventivo_id
 * @property int $veiculo_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\PlanoPreventivo|null $planoPreventivo
 * @property-read float $proxima_execucao
 * @property-read float $quilometragem_restante
 * @property-read \App\Models\PlanoManutencaoOrdemServico|null $ultima_execucao
 * @property-read \App\Models\Veiculo $veiculo
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanoManutencaoVeiculo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanoManutencaoVeiculo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanoManutencaoVeiculo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanoManutencaoVeiculo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanoManutencaoVeiculo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanoManutencaoVeiculo wherePlanoPreventivoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanoManutencaoVeiculo whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanoManutencaoVeiculo whereVeiculoId($value)
 */
	class PlanoManutencaoVeiculo extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $descricao
 * @property string|null $periodicidade
 * @property int $intervalo
 * @property int $is_active
 * @property array<array-key, mixed>|null $itens
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PlanoManutencaoOrdemServico> $ordensServico
 * @property-read int|null $ordens_servico_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PlanoManutencaoVeiculo> $veiculos
 * @property-read int|null $veiculos_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanoPreventivo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanoPreventivo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanoPreventivo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanoPreventivo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanoPreventivo whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanoPreventivo whereDescricao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanoPreventivo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanoPreventivo whereIntervalo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanoPreventivo whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanoPreventivo whereItens($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanoPreventivo wherePeriodicidade($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanoPreventivo whereUpdatedAt($value)
 */
	class PlanoPreventivo extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $numero_fogo
 * @property string|null $marca
 * @property string|null $modelo
 * @property string|null $medida
 * @property string|null $desenho_pneu_id
 * @property \App\Enum\Pneu\StatusPneuEnum $status
 * @property string $ciclo_vida
 * @property \App\Enum\Pneu\LocalPneuEnum|null $local
 * @property string|null $data_aquisicao
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $valor
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Conserto> $consertos
 * @property-read int|null $consertos_count
 * @property-read \App\Models\DesenhoPneu|null $desenhoPneu
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\HistoricoMovimentoPneu> $historicoMovimentacao
 * @property-read int|null $historico_movimentacao_count
 * @property-read int $km_percorrido
 * @property-read int $km_percorrido_ciclo
 * @property-read \App\Models\PneuPosicaoVeiculo $posicaoVeiculo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Recapagem> $recapagens
 * @property-read int|null $recapagens_count
 * @property-read \App\Models\Recapagem|null $ultimoRecap
 * @property-read \App\Models\Veiculo $veiculo
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pneu newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pneu newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pneu query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pneu whereCicloVida($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pneu whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pneu whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pneu whereDataAquisicao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pneu whereDesenhoPneuId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pneu whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pneu whereLocal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pneu whereMarca($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pneu whereMedida($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pneu whereModelo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pneu whereNumeroFogo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pneu whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pneu whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pneu whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pneu whereValor($value)
 */
	class Pneu extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $pneu_id
 * @property int $veiculo_id
 * @property string|null $data_inicial
 * @property string|null $km_inicial
 * @property string $eixo
 * @property string $posicao
 * @property int $sequencia
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property-read mixed $km_percorrido
 * @property-read mixed $km_rodado
 * @property-read \App\Models\Pneu|null $pneu
 * @property-read \App\Models\Veiculo $veiculo
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PneuPosicaoVeiculo aplicados()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PneuPosicaoVeiculo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PneuPosicaoVeiculo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PneuPosicaoVeiculo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PneuPosicaoVeiculo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PneuPosicaoVeiculo whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PneuPosicaoVeiculo whereDataInicial($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PneuPosicaoVeiculo whereEixo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PneuPosicaoVeiculo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PneuPosicaoVeiculo whereKmInicial($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PneuPosicaoVeiculo wherePneuId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PneuPosicaoVeiculo wherePosicao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PneuPosicaoVeiculo whereSequencia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PneuPosicaoVeiculo whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PneuPosicaoVeiculo whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PneuPosicaoVeiculo whereVeiculoId($value)
 */
	class PneuPosicaoVeiculo extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $pneu_id
 * @property string $data_recapagem
 * @property int $desenho_pneu_id
 * @property string|null $ciclo_vida
 * @property int|null $parceiro_id
 * @property string|null $valor
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property-read \App\Models\DesenhoPneu $desenhoPneu
 * @property-read \App\Models\Parceiro|null $parceiro
 * @property-read \App\Models\Pneu $pneu
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recapagem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recapagem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recapagem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recapagem whereCicloVida($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recapagem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recapagem whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recapagem whereDataRecapagem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recapagem whereDesenhoPneuId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recapagem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recapagem whereParceiroId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recapagem wherePneuId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recapagem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recapagem whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recapagem whereValor($value)
 */
	class Recapagem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $gestor_id
 * @property int $indicador_id
 * @property string|null $objetivo
 * @property string|null $resultado
 * @property string $pontuacao_obtida
 * @property string $periodo
 * @property string|null $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read \App\Models\Gestor $gestor
 * @property-read \App\Models\Indicador $indicador
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resultado newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resultado newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resultado query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resultado whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resultado whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resultado whereGestorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resultado whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resultado whereIndicadorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resultado whereObjetivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resultado wherePeriodo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resultado wherePontuacaoObtida($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resultado whereResultado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resultado whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resultado whereUpdatedAt($value)
 */
	class Resultado extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $veiculo_id
 * @property int|null $tipo_veiculo_id
 * @property string $data_inicio
 * @property string $data_fim
 * @property int|null $km_inicial
 * @property int|null $km_final
 * @property int|null $km_percorrido
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $status
 * @property-read \App\Models\Abastecimento|null $abastecimentoFinal
 * @property-read \App\Models\Abastecimento|null $abastecimentoInicial
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Abastecimento> $abastecimentos
 * @property-read int|null $abastecimentos_count
 * @property-read float $consumo_medio_combustivel
 * @property-read string|null $diferenca_meta_consumo
 * @property-read mixed $dispersao_km
 * @property-read int $dispersao_km_abastecimento_km_viagem
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DocumentoFrete> $documentos
 * @property-read int|null $documentos_count
 * @property-read float $faturamento_por_km_pago
 * @property-read float $faturamento_por_km_rodado
 * @property-read float $km_pago
 * @property-read int $km_rodado_abastecimento
 * @property-read int $km_rodado_viagens
 * @property-read \App\Models\ManutencaoCusto|null $manutencao
 * @property-read string $media_km_pago_viagem
 * @property-read float $percentual_manutencao_faturamento
 * @property-read string $periodo
 * @property-read float $preco_medio_combustivel
 * @property-read float $quantidade_litros_combustivel
 * @property-read int $quantidade_viagens
 * @property-read float $resultado_liquido
 * @property-read \App\Models\TipoVeiculo|null $tipoVeiculo
 * @property-read string $title
 * @property-read string|null $variacao_faturamento_mes_anterior
 * @property-read \App\Models\Veiculo $veiculo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Viagem> $viagens
 * @property-read int|null $viagens_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResultadoPeriodo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResultadoPeriodo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResultadoPeriodo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResultadoPeriodo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResultadoPeriodo whereDataFim($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResultadoPeriodo whereDataInicio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResultadoPeriodo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResultadoPeriodo whereKmFinal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResultadoPeriodo whereKmInicial($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResultadoPeriodo whereKmPercorrido($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResultadoPeriodo whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResultadoPeriodo whereTipoVeiculoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResultadoPeriodo whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResultadoPeriodo whereVeiculoId($value)
 */
	class ResultadoPeriodo extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $codigo
 * @property string $descricao
 * @property string|null $complemento
 * @property string|null $tipo
 * @property int $controla_posicao
 * @property int $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servico newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servico newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servico query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servico whereCodigo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servico whereComplemento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servico whereControlaPosicao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servico whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servico whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servico whereDescricao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servico whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servico whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servico whereTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Servico whereUpdatedAt($value)
 */
	class Servico extends \Eloquent {}
}

namespace App\Models{
/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TecnicoManutencao newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TecnicoManutencao newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TecnicoManutencao query()
 */
	class TecnicoManutencao extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $descricao
 * @property string $meta_media Meta de média de consumo em km/l
 * @property int $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Veiculo> $veiculos
 * @property-read int|null $veiculos_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoVeiculo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoVeiculo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoVeiculo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoVeiculo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoVeiculo whereDescricao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoVeiculo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoVeiculo whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoVeiculo whereMetaMedia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoVeiculo whereUpdatedAt($value)
 */
	class TipoVeiculo extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string $user_type
 * @property int $is_admin
 * @property int $is_active
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUserType($value)
 */
	class User extends \Eloquent implements \Filament\Models\Contracts\FilamentUser {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $filial
 * @property string $placa
 * @property string|null $modelo
 * @property string|null $marca
 * @property string|null $ano_fabricacao
 * @property string|null $ano_modelo
 * @property string|null $chassis
 * @property bool $is_active
 * @property array<array-key, mixed>|null $informacoes_complementares
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string $km_medio
 * @property string|null $data_km_medio
 * @property int|null $tipo_veiculo_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItemOrdemServico> $itens
 * @property-read int|null $itens_count
 * @property-read \App\Models\HistoricoQuilometragem|null $kmAtual
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OrdemServico> $manutencoes
 * @property-read int|null $manutencoes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PlanoPreventivo> $planoPreventivo
 * @property-read int|null $plano_preventivo_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PneuPosicaoVeiculo> $pneus
 * @property-read int|null $pneus_count
 * @property-read mixed $quilometragem_atual
 * @property-read \App\Models\TipoVeiculo|null $tipoVeiculo
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Veiculo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Veiculo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Veiculo onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Veiculo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Veiculo whereAnoFabricacao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Veiculo whereAnoModelo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Veiculo whereChassis($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Veiculo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Veiculo whereDataKmMedio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Veiculo whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Veiculo whereFilial($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Veiculo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Veiculo whereInformacoesComplementares($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Veiculo whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Veiculo whereKmMedio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Veiculo whereMarca($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Veiculo whereModelo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Veiculo wherePlaca($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Veiculo whereTipoVeiculoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Veiculo whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Veiculo withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Veiculo withoutTrashed()
 */
	class Veiculo extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $veiculo_id
 * @property string $numero_viagem
 * @property string|null $documento_transporte
 * @property string $km_rodado
 * @property string $km_pago
 * @property string $km_cadastro
 * @property string $km_cobrar
 * @property \App\Enum\MotivoDivergenciaViagem|null $motivo_divergencia
 * @property string $data_competencia
 * @property string $data_inicio
 * @property string $data_fim
 * @property bool $conferido
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\DivergenciaViagem> $divergencias
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $checked_by
 * @property string|null $km_dispersao
 * @property string|null $dispersao_percentual
 * @property string|null $condutor
 * @property int $considerar_relatorio
 * @property string|null $unidade_negocio
 * @property string|null $cliente
 * @property int|null $resultado_periodo_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AnotacaoViagem> $anotacoes
 * @property-read int|null $anotacoes_count
 * @property-read \App\Models\CargaViagem|null $carga
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CargaViagem> $cargas
 * @property-read int|null $cargas_count
 * @property-read \App\Models\User|null $checker
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Comentario> $comentarios
 * @property-read int|null $comentarios_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ViagemComplemento> $complementos
 * @property-read int|null $complementos_count
 * @property-read \App\Models\User|null $creator
 * @property-read int|null $divergencias_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DocumentoFrete> $documentos
 * @property-read int|null $documentos_count
 * @property-read string $integrados_nomes
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Integrado> $integrados
 * @property-read int|null $integrados_count
 * @property-read array|null $maps_integrados
 * @property-read \App\Models\ResultadoPeriodo|null $resultadoPeriodo
 * @property-read \App\Models\User|null $updater
 * @property-read \App\Models\Veiculo $veiculo
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Viagem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Viagem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Viagem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Viagem whereCheckedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Viagem whereCliente($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Viagem whereCondutor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Viagem whereConferido($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Viagem whereConsiderarRelatorio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Viagem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Viagem whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Viagem whereDataCompetencia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Viagem whereDataFim($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Viagem whereDataInicio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Viagem whereDispersaoPercentual($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Viagem whereDivergencias($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Viagem whereDocumentoTransporte($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Viagem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Viagem whereKmCadastro($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Viagem whereKmCobrar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Viagem whereKmDispersao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Viagem whereKmPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Viagem whereKmRodado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Viagem whereMotivoDivergencia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Viagem whereNumeroViagem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Viagem whereResultadoPeriodoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Viagem whereUnidadeNegocio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Viagem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Viagem whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Viagem whereVeiculoId($value)
 */
	class Viagem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $veiculo_id
 * @property array<array-key, mixed>|null $destinos
 * @property string|null $km_rodado
 * @property string|null $km_pago
 * @property string|null $km_dispersao
 * @property string|null $dispersao_percentual
 * @property string|null $data_competencia
 * @property string|null $frete
 * @property string|null $condutor
 * @property int $checked
 * @property int $created_by
 * @property int|null $updated_by
 * @property int|null $checked_by
 * @property string|null $observacao
 * @property string|null $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $documento_frete_id
 * @property-read \App\Models\User $creator
 * @property-read \App\Models\DocumentoFrete|null $documentos
 * @property-read \App\Models\Veiculo $veiculo
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemBugio newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemBugio newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemBugio query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemBugio whereChecked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemBugio whereCheckedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemBugio whereCondutor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemBugio whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemBugio whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemBugio whereDataCompetencia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemBugio whereDestinos($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemBugio whereDispersaoPercentual($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemBugio whereDocumentoFreteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemBugio whereFrete($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemBugio whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemBugio whereKmDispersao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemBugio whereKmPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemBugio whereKmRodado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemBugio whereObservacao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemBugio whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemBugio whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemBugio whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemBugio whereVeiculoId($value)
 */
	class ViagemBugio extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $viagem_id
 * @property int|null $veiculo_id
 * @property string $numero_viagem
 * @property string|null $documento_transporte
 * @property int|null $integrado_id
 * @property string $km_rodado
 * @property string $km_pago
 * @property string $km_divergencia
 * @property string $km_cobrar
 * @property string|null $motivo_divergencia
 * @property string $data_competencia
 * @property bool $conferido
 * @property \App\Enum\Viagem\StatusViagemEnum $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Integrado|null $integrado
 * @property-read \App\Models\Veiculo|null $veiculo
 * @property-read \App\Models\Viagem $viagem
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemComplemento newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemComplemento newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemComplemento query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemComplemento whereConferido($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemComplemento whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemComplemento whereDataCompetencia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemComplemento whereDocumentoTransporte($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemComplemento whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemComplemento whereIntegradoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemComplemento whereKmCobrar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemComplemento whereKmDivergencia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemComplemento whereKmPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemComplemento whereKmRodado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemComplemento whereMotivoDivergencia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemComplemento whereNumeroViagem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemComplemento whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemComplemento whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemComplemento whereVeiculoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ViagemComplemento whereViagemId($value)
 */
	class ViagemComplemento extends \Eloquent {}
}

