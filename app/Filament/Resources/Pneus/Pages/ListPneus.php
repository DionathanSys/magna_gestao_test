<?php

namespace App\Filament\Resources\Pneus\Pages;

use App\Enum;
use App\Filament\Resources\Pneus\PneuResource;
use App\Jobs\RegistrarHistoricoMovimentacao;
use App\Models;
use App\Services;
use App\Services\NotificacaoService as notify;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ListPneus extends ListRecords
{
    protected static string $resource = PneuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Pneu')
                ->icon('heroicon-o-plus-circle')
                ->using(function (array $data, array $arguments): ?Models\Pneu {

                    $dataRecap = $data['recap'];
                    $dataHistoricoMov = $data['historicoMovimentacao'] ?? [];

                    if ($arguments['apenasRecapar'] ?? false) {

                        $data = self::mutateDataRecap($dataRecap ?? []);

                        $service = new Services\Pneus\PneuService;
                        $service->recapar($data);

                        if ($service->hasError()) {
                            notify::error(titulo: 'Falha no processo de recapagem', mensagem: $service->getMessage());
                            $this->halt();
                        }

                        notify::success('Recapagem realizada com sucesso.');
                        $this->fill(Arr::only($data, ['valor', 'pneu_medida_id', 'pneu_marca_id', 'pneu_modelo_id', 'desenho_pneu_id', 'pneu_local_id', 'status', 'data_aquisicao', 'fornecedor_compra_id', 'sulco_inicial', 'recapavel', 'limite_recapagens']));
                        $this->halt();

                        return null;
                    }

                    unset($data['recap']);
                    unset($data['historicoMovimentacao']);

                    $service = new Services\Pneus\PneuService;
                    $pneu = $service->create($data);

                    if ($service->hasError()) {
                        notify::error(titulo: 'Erro ao cadastrar pneu', mensagem: $service->getMessage());
                        $this->halt();
                    }

                    notify::success('Pneu cadastrado com sucesso.');

                    if ($arguments['recapar'] ?? false) {

                        $dataRecap = $this->mutateDataRecap(
                            array_merge($dataRecap, ['pneu_id' => $pneu->id])
                        );

                        Log::debug(__METHOD__.' - Iniciando recapagem após criação do pneu', ['data_recap' => $dataRecap]);

                        $service->recapar($dataRecap);

                        if ($service->hasError()) {
                            notify::error(titulo: 'Erro ao recapar pneu', mensagem: $service->getMessage());
                            $this->halt();
                        }

                        notify::success('Recapagem realizada com sucesso.');
                        $this->fill(Arr::only($data, ['ciclo_vida', 'valor', 'pneu_medida_id', 'pneu_marca_id', 'pneu_modelo_id', 'desenho_pneu_id', 'pneu_local_id', 'status', 'data_aquisicao', 'fornecedor_compra_id', 'sulco_inicial', 'recapavel', 'limite_recapagens']));
                        $this->halt();

                        return $pneu;
                    }

                    if ($dataHistoricoMov) {
                        Log::info(__METHOD__.' - Registrando movimentações de histórico após criação do pneu', ['pneu_id' => $pneu->id, 'data_historico_mov' => $dataHistoricoMov]);
                        foreach ($dataHistoricoMov as $movimentacao) {
                            $movimentacao['historico']['pneu_id'] = $pneu->id;
                            $dataMovimentacao = $movimentacao['historico'];
                            RegistrarHistoricoMovimentacao::dispatch($dataMovimentacao);
                        }
                    } else {
                        Log::warning(__METHOD__.' - Nenhuma movimentação de histórico registrada após criação do pneu', ['pneu_id' => $pneu->id]);
                    }

                    if ($arguments['another'] ?? false) {
                        $this->fill(Arr::only($data, ['ciclo_vida', 'valor', 'pneu_medida_id', 'pneu_marca_id', 'pneu_modelo_id', 'desenho_pneu_id', 'pneu_local_id', 'status', 'data_aquisicao', 'fornecedor_compra_id', 'sulco_inicial', 'recapavel', 'limite_recapagens']));

                        return $pneu;
                    }

                    return $pneu;
                })
                ->successNotification(null)
                ->extraModalFooterActions(function (CreateAction $action, array $data): array {
                    return [
                        $action->makeModalSubmitAction('criarERecapar', arguments: ['recapar' => true]),
                        $action->makeModalSubmitAction('salvarECriarOutro', arguments: ['another' => true]),
                        $action->makeModalSubmitAction('apenasRecapar', arguments: ['apenasRecapar' => true]),
                    ];
                })
                ->preserveFormDataWhenCreatingAnother(['ciclo_vida', 'valor', 'pneu_medida_id', 'pneu_marca_id', 'pneu_modelo_id', 'desenho_pneu_id', 'pneu_local_id', 'status', 'data_aquisicao', 'fornecedor_compra_id', 'sulco_inicial', 'recapavel', 'limite_recapagens']),

        ];
    }

    private function mutateDataRecap(array $data): array
    {
        // Normalizar os indices do array, devido conflito de nomes no form
        // entre os campos do pneu e da recapagem
        return [
            'pneu_id' => $data['pneu_id'],
            'valor' => $data['valor_recapagem'],
            'desenho_pneu_id' => $data['desenho_pneu_id_recapagem'],
            'data_recapagem' => $data['data_recapagem'],
        ];
    }

    public function getTabs(): array
    {
        return [
            'Todos' => Tab::make(),
            'Estoque' => Tab::make()
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('local', Enum\Pneu\LocalPneuEnum::ESTOQUE_CCO)),
            'Frota' => Tab::make()
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('local', Enum\Pneu\LocalPneuEnum::FROTA)),
            'Outros' => Tab::make()
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->whereNotIn('local', [Enum\Pneu\LocalPneuEnum::ESTOQUE_CCO, Enum\Pneu\LocalPneuEnum::FROTA])),
            'Est./Frota' => Tab::make()
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->whereIn('local', [Enum\Pneu\LocalPneuEnum::ESTOQUE_CCO, Enum\Pneu\LocalPneuEnum::FROTA])),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'Estoque';
    }
}
