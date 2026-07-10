<?php

namespace App\Filament\Resources\OrdemServicos\Pages;

use App\Enum;
use App\Filament\Resources\OrdemServicos\OrdemServicoResource;
use App\Filament\Resources\OrdemServicos\Schemas\Components\OrdemServicoDataAberturaInput;
use App\Filament\Resources\OrdemServicos\Schemas\Components\OrdemServicoTipoManutencaoInput;
use App\Filament\Resources\OrdemServicos\Schemas\Components\OrdemServicoVeiculoInput;
use App\Filament\Resources\OrdemServicos\Schemas\OrdemServicoForm;
use App\Models\OrdemServico;
use App\Models\Veiculo;
use App\Services\NotificacaoService as notify;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class MobileCreateOrdemServico extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string $resource = OrdemServicoResource::class;

    protected static ?string $title = 'Nova OS';

    protected string $view = 'filament.resources.ordem-servicos.pages.mobile-create';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'tipo_manutencao' => Enum\OrdemServico\TipoManutencaoEnum::CORRETIVA->value,
            'data_inicio' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Grid::make(1)
                    ->schema([
                        OrdemServicoVeiculoInput::make()
                            ->columnSpanFull(),
                        OrdemServicoForm::getQuilometragemFormField()
                            ->label('Quilometragem')
                            ->columnSpanFull(),
                        OrdemServicoTipoManutencaoInput::make()
                            ->columnSpanFull(),
                        OrdemServicoDataAberturaInput::make()
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data')
            ->model(OrdemServico::class);
    }

    public function salvar(): void
    {
        $data = $this->form->getState();

        $veiculo = Veiculo::with('kmAtual')->find($data['veiculo_id']);

        if (($veiculo->kmAtual->quilometragem ?? 0) > $data['quilometragem']) {
            notify::error(mensagem: 'A quilometragem informada deve ser maior ou igual à quilometragem atual do veículo.');

            return;
        }

        $data['created_by'] = Auth::id();
        $data['status'] = Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE;
        $data['status_sankhya'] = Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE;

        $ordemServico = OrdemServico::create($data);
        notify::success(mensagem: 'Ordem de Serviço criada com sucesso!');

        $this->redirect(OrdemServicoResource::getUrl('mobile-detail', ['record' => $ordemServico->id]));
    }

    public function getListUrl(): string
    {
        return OrdemServicoResource::getUrl('mobile-list');
    }
}
