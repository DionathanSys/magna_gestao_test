<?php

namespace App\Filament\Pages;

use App\Services\Relatorios\TratadorRelatorioBrfService;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class TratadorRelatorioBrf extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected string $view = 'filament.pages.tratador-relatorio-brf';

    protected static ?string $navigationLabel = 'Tratador Relatório BRF';

    protected static ?string $title = 'Tratador de Relatório BRF';

    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    public ?array $data = [];

    public ?array $stats = null;

    public ?string $outputPath = null;

    public bool $processed = false;

    public function mount(): void
    {
        $this->form->fill([
            'arquivo' => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                FileUpload::make('arquivo')
                    ->label('Relatório BRF (Excel/CSV)')
                    ->disk('public')
                    ->required()
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                        'text/csv',
                        'text/plain',
                    ])
                    ->maxSize(50 * 1024),
            ])
            ->statePath('data');
    }

    public function processar(): void
    {
        $data = $this->form->getState();
        $arquivo = $data['arquivo'] ?? null;

        if (! $arquivo) {
            Notification::make()
                ->title('Selecione um arquivo primeiro')
                ->warning()
                ->send();

            return;
        }

        if (! Storage::disk('public')->exists($arquivo)) {
            Notification::make()
                ->title('Arquivo não encontrado no servidor')
                ->danger()
                ->send();

            return;
        }

        try {
            $service = app(TratadorRelatorioBrfService::class);
            $result = $service->process($arquivo);

            $this->stats = $result['stats'];
            $this->outputPath = $result['output_path'];
            $this->processed = true;

            Notification::make()
                ->title('Relatório processado com sucesso!')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro ao processar relatório')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function baixar(): StreamedResponse
    {
        if (! $this->outputPath || ! Storage::disk('public')->exists($this->outputPath)) {
            Notification::make()
                ->title('Arquivo não encontrado. Processe novamente.')
                ->danger()
                ->send();

            return response()->streamDownload(function () {}, 'erro.txt');
        }

        $fullPath = Storage::disk('public')->path($this->outputPath);

        return response()->streamDownload(function () use ($fullPath): void {
            readfile($fullPath);
        }, 'relatorio_brf_tratado.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
