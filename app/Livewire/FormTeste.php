<?php

namespace App\Livewire;

use App\Filament\Resources\OrdemServicos\Schemas\Components;
use App\Filament\Resources\OrdemServicos\Schemas\Components\OrdemServicoDataAberturaInput;
use App\Filament\Resources\OrdemServicos\Schemas\Components\OrdemServicoTipoManutencaoInput;
use App\Filament\Resources\OrdemServicos\Schemas\Components\OrdemServicoVeiculoInput;
use App\Filament\Resources\OrdemServicos\Schemas\OrdemServicoForm;
use App\Models\OrdemServico;
use App\Services\NotificacaoService as notify;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Concerns\InteractsWithRecord;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Component;

class FormTeste extends Component implements HasSchemas
{
    use InteractsWithActions;
    use InteractsWithRecord;
    use InteractsWithSchemas;

    public ?array $data = [];

    public OrdemServico $ordemServico;

    public function mount(OrdemServico $ordemServico): void
    {
        $this->ordemServico = $ordemServico->load(['sankhyaId']);
        $this->form->fill($ordemServico->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(
                [
                    Tabs::make('Tabs')
                        ->contained(false)
                        ->columnSpanFull()
                        ->tabs(
                            [
                                Tabs\Tab::make('Informações Gerais')
                                    ->columns([
                                        'default' => 2,
                                        'xl' => 4,
                                        '2xl' => 6,
                                    ])
                                    ->schema([
                                        OrdemServicoVeiculoInput::make()
                                            ->columnSpan([
                                                'default' => 2,
                                                'xl' => 2,
                                            ]),
                                        OrdemServicoForm::getQuilometragemFormField()
                                            ->label('Quilometragem')
                                            ->columnSpan([
                                                'default' => 2,
                                                'xl' => 2,
                                            ]),
                                        OrdemServicoTipoManutencaoInput::make()
                                            ->columnSpan([
                                                'default' => 2,
                                                'xl' => 2,
                                            ]),
                                        OrdemServicoForm::getStatusFormField()
                                            ->columnSpan([
                                                'default' => 2,
                                                'xl' => 2,
                                            ]),
                                        OrdemServicoForm::getStatusSankhyaFormField()
                                            ->columnSpan([
                                                'default' => 2,
                                                'xl' => 2,
                                            ]),
                                        OrdemServicoForm::getParceiroIdFormField()
                                            ->label('Parceiro Externo')
                                            ->columnSpan([
                                                'default' => 2,
                                                'xl' => 2,
                                            ]),
                                        OrdemServicoDataAberturaInput::make()
                                            ->columnSpan([
                                                'default' => 2,
                                                'xl' => 2,
                                            ]),
                                        OrdemServicoForm::getDataFimFormField()
                                            ->columnSpan([
                                                'default' => 2,
                                                'xl' => 2,
                                            ]),

                                    ]),
                                Tabs\Tab::make('Sankhya')
                                    ->columns(4)
                                    ->schema([
                                        Components\OrdemServicoSankhyaRepeater::make(),
                                    ]),

                            ]
                        ),
                ]
            )
            ->statePath('data')
            ->model($this->ordemServico);
    }

    public function edit(): void
    {
        $this->ordemServico->update($this->form->getState());
        notify::success('Ordem de Serviço atualizada com sucesso!');
    }

    public function render()
    {
        return view('livewire.form-teste');
    }
}
