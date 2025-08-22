<?php

namespace App\Livewire;

use App\Filament\Resources\OrdemServicos\Schemas\OrdemServicoForm;
use App\Models\OrdemServico;
use Filament\Actions\Concerns\InteractsWithRecord;
use Livewire\Component;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Contracts\View\View;
use Filament\Schemas\Schema;


class FormTeste extends Component implements HasSchemas
{
    use InteractsWithSchemas;
    use InteractsWithRecord;

    public ?array $data = [];
    public OrdemServico $ordemServico;

    public function mount(OrdemServico $ordemServico): void
    {
        $this->ordemServico = $ordemServico;
        $this->form->fill($ordemServico->attributesToArray());
    }


    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns([
                'default' => 2,
                'xl' => 4,
                '2xl' => 6,
            ])
            ->components(
                [
                    OrdemServicoForm::getVeiculoIdFormField()
                        ->columnSpan([
                            'default' => 2,
                            'xl' => 2
                        ]),
                    OrdemServicoForm::getQuilometragemFormField()
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


                ]
            )
            ->statePath('data')
            ->model($this->ordemServico);
    }

    public function create(): void
    {
        dd($this->form->getState());
    }

    public function render()
    {
        return view('livewire.form-teste');
    }
}
