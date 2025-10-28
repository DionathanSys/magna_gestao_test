<?php

namespace App\Livewire;

use App\Filament\Resources\OrdemServicos\Schemas\{OrdemServicoForm, Components};
use App\Models\OrdemServico;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Concerns\InteractsWithRecord;
use Livewire\Component;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Contracts\View\View;
use Filament\Schemas\Schema;
use App\Services\NotificacaoService as notify;


class FormTeste extends Component implements HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithRecord;

    public ?array $data = [];
    public OrdemServico $ordemServico;

    public function mount(OrdemServico $ordemServico): void
    {
        $this->ordemServico = $ordemServico->load(['agendamentosPendentes', 'planoPreventivoVinculado', 'sankhyaId']);
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
                                Tabs\Tab::make('Inicio')
                                    ->columns([
                                        'default' => 2,
                                        'xl' => 4,
                                        '2xl' => 6,
                                    ])
                                    ->schema([
                                        OrdemServicoForm::getVeiculoIdFormField()
                                            ->columnSpan([
                                                'default' => 2,
                                                'xl' => 2
                                            ]),
                                        OrdemServicoForm::getQuilometragemFormField()
                                            ->label('Quilometragem')
                                            ->columnSpan([
                                                'default' => 2,
                                                'xl' => 2
                                            ]),
                                        OrdemServicoForm::getTipoManutencaoFormField()
                                            ->columnSpan([
                                                'default' => 2,
                                                'xl' => 2
                                            ]),
                                        OrdemServicoForm::getStatusFormField()
                                            ->columnSpan([
                                                'default' => 2,
                                                'xl' => 2
                                            ]),
                                        OrdemServicoForm::getStatusSankhyaFormField()
                                            ->columnSpan([
                                                'default' => 2,
                                                'xl' => 2
                                            ]),
                                        OrdemServicoForm::getParceiroIdFormField()
                                            ->label('Parceiro Externo')
                                            ->columnSpan([
                                                'default' => 2,
                                                'xl' => 2
                                            ]),
                                        OrdemServicoForm::getDataInicioFormField()
                                            ->columnSpan([
                                                'default' => 2,
                                                'xl' => 2
                                            ]),
                                        OrdemServicoForm::getDataFimFormField()
                                            ->columnSpan([
                                                'default' => 2,
                                                'xl' => 2
                                            ]),

                                    ]),
                                Tabs\Tab::make('Agendamentos')
                                    ->badge(fn(): string => (string) $this->ordemServico->agendamentosPendentes()->count())
                                    ->badgeColor('danger')
                                    ->columns(4)
                                    ->schema([
                                        Components\AgendamentoRepeater::make()
                                            ->columnSpanFull(),
                                    ]),
                                Tabs\Tab::make('Preventivas')
                                    ->badge(fn(): string => (string) $this->ordemServico->planoPreventivoVinculado()->count())
                                    ->badgeColor('info')
                                    ->columns(4)
                                    ->schema([
                                        Components\PlanosPreventivosVinculadoRepeater::make()
                                            ->columnSpanFull(),
                                    ]),
                                Tabs\Tab::make('Sankhya')
                                    ->columns(4)
                                    ->schema([
                                        Components\OrdemServicoSankhyaRepeater::make()
                                    ]),

                            ]
                        )
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
