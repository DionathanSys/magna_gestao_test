<?php

namespace App\Filament\Pages;

use App\Enum\ClienteEnum;
use App\Models\Integrado;
use App\Models\Viagem;
use App\Models\ViagemAttachment;
use BackedEnum;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use UnitEnum;

class RelatorioViagensIntegradosDocumentos extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.pages.relatorio-viagens-integrados-documentos';

    protected static ?string $navigationLabel = 'Relatório Viagens/Integrados/Documentos';

    protected static ?string $title = 'Relatório de Viagens com Integrados e Documentos';

    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    public ?array $data = [];

    public ?array $dadosRelatorio = [];

    public string $ordenarPor = 'data_competencia';

    public string $direcaoOrdenacao = 'desc';

    public function mount(): void
    {
        $this->data = $this->getDefaultData();
    }

    public function getDefaultData(): array
    {
        return [
            'data_competencia_inicio' => null,
            'data_competencia_fim' => null,
            'integrado_id' => null,
            'cliente' => null,
            'conferido' => null,
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Filtros do Relatório')
                    ->columns(5)
                    ->columnSpan(12)
                    ->description('Selecione os filtros para carregar o relatório de viagens com integrados e documentos vinculados')
                    ->components([
                        DatePicker::make('data_competencia_inicio')
                            ->label('Data Competência Início')
                            ->placeholder('Início')
                            ->native(false),

                        DatePicker::make('data_competencia_fim')
                            ->label('Data Competência Fim')
                            ->placeholder('Fim')
                            ->native(false),

                        Select::make('integrado_id')
                            ->label('Integrado')
                            ->placeholder('Todos')
                            ->options(
                                Integrado::query()
                                    ->orderBy('nome')
                                    ->pluck('nome', 'id')
                            )
                            ->searchable()
                            ->preload(),

                        Select::make('cliente')
                            ->label('Cliente')
                            ->placeholder('Todos')
                            ->options(ClienteEnum::toSelectArray()),

                        Select::make('conferido')
                            ->label('Conferido')
                            ->placeholder('Todos')
                            ->options([
                                1 => 'Sim',
                                0 => 'Não',
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function carregarDados(): void
    {
        try {
            $query = Viagem::query()
                ->with([
                    'cargas.integrado',
                    'documentos',
                    'attachments.receivedFiscalDocument',
                    'veiculo',
                ])
                ->orderBy($this->ordenarPor, $this->direcaoOrdenacao);

            $dataInicio = $this->data['data_competencia_inicio'] ?? null;
            $dataFim = $this->data['data_competencia_fim'] ?? null;
            $integradoId = $this->data['integrado_id'] ?? null;
            $cliente = $this->data['cliente'] ?? null;
            $conferido = $this->data['conferido'] ?? null;

            if ($dataInicio) {
                $query->where('data_competencia', '>=', $dataInicio);
            }

            if ($dataFim) {
                $query->where('data_competencia', '<=', $dataFim);
            }

            if ($integradoId) {
                $query->whereHas('cargas', function ($q) use ($integradoId): void {
                    $q->where('integrado_id', $integradoId);
                });
            }

            if ($cliente !== null && $cliente !== '') {
                $query->where('cliente', $cliente);
            }

            if ($conferido !== null && $conferido !== '') {
                $query->where('conferido', (bool) $conferido);
            }

            $viagens = $query->get();

            $mapped = $viagens->map(function (Viagem $viagem): array {
                $integrados = $viagem->cargas
                    ->pluck('integrado')
                    ->filter()
                    ->unique('id')
                    ->values();

                $integradosNomes = $integrados->pluck('nome')->filter()->implode(', ');
                $integradosMunicipios = $integrados->pluck('municipio')->filter()->implode(', ');

                $documentos = $viagem->documentos ?? collect();
                $numerosDocumentos = $documentos->pluck('numero_documento')->filter()->implode(', ');
                $valorLiquidoTotal = $documentos->sum('valor_liquido');

                $notas = $viagem->attachments
                    ->pluck('receivedFiscalDocument')
                    ->filter()
                    ->unique('id')
                    ->values();
                $numerosNotas = $notas->pluck('numero_nota')->filter()->implode(', ');

                return [
                    'id' => $viagem->id,
                    'numero_viagem' => $viagem->numero_viagem ?? '',
                    'data_competencia' => $viagem->data_competencia ? Carbon::parse($viagem->data_competencia)->format('d/m/Y') : '',
                    'data_competencia_sort' => $viagem->data_competencia ?? '',
                    'placa' => $viagem->veiculo?->placa ?? '',
                    'integrados' => $integradosNomes ?: 'Sem integrado',
                    'municipios' => $integradosMunicipios ?: '-',
                    'km_rodado' => $viagem->km_rodado ?? 0,
                    'km_pago' => $viagem->km_pago ?? 0,
                    'numeros_documentos' => $numerosDocumentos ?: 'Sem frete',
                    'valor_liquido' => $valorLiquidoTotal,
                    'numeros_notas' => $numerosNotas ?: 'Sem nota',
                    'conferido' => $viagem->conferido,
                ];
            });

            $this->dadosRelatorio = $mapped->toArray();

            Notification::make()
                ->title('Dados carregados com sucesso')
                ->success()
                ->body(count($this->dadosRelatorio).' registro(s) encontrado(s)')
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro ao carregar dados')
                ->danger()
                ->body($e->getMessage())
                ->send();

            $this->dadosRelatorio = [];
        }
    }

    public function exportarExcel(): void
    {
        if (empty($this->dadosRelatorio)) {
            Notification::make()
                ->warning()
                ->title('Sem dados para exportar')
                ->body('Carregue os dados primeiro antes de exportar.')
                ->send();

            return;
        }

        $originalMemoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', '512M');

        try {
            $spreadsheet = new Spreadsheet;
            $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');
            $spreadsheet->getDefaultStyle()->getFont()->setSize(10);

            // ====== PLANILHA 1: VIAGENS ======
            $viagensSheet = $spreadsheet->getActiveSheet();
            $viagensSheet->setTitle('Viagens');

            $headers = [
                'A' => 'ID',
                'B' => 'Nº Viagem',
                'C' => 'Data Competência',
                'D' => 'Placa',
                'E' => 'Integrado(s)',
                'F' => 'Município(s)',
                'G' => 'Km Rodado',
                'H' => 'Km Pago',
                'I' => 'Nº Doc. Frete',
                'J' => 'Valor Líquido',
                'K' => 'Nº Notas (NF-e)',
                'L' => 'Conferido',
            ];

            $row = 1;
            foreach ($headers as $col => $header) {
                $viagensSheet->setCellValue($col.$row, $header);
            }

            static::styleHeader($viagensSheet, 'A1:L1');

            $viagensSheet->getColumnDimension('A')->setWidth(8);
            $viagensSheet->getColumnDimension('B')->setWidth(20);
            $viagensSheet->getColumnDimension('C')->setWidth(16);
            $viagensSheet->getColumnDimension('D')->setWidth(12);
            $viagensSheet->getColumnDimension('E')->setWidth(35);
            $viagensSheet->getColumnDimension('F')->setWidth(30);
            $viagensSheet->getColumnDimension('G')->setWidth(13);
            $viagensSheet->getColumnDimension('H')->setWidth(13);
            $viagensSheet->getColumnDimension('I')->setWidth(25);
            $viagensSheet->getColumnDimension('J')->setWidth(16);
            $viagensSheet->getColumnDimension('K')->setWidth(30);
            $viagensSheet->getColumnDimension('L')->setWidth(12);

            $row = 2;
            foreach ($this->dadosRelatorio as $item) {
                $viagensSheet->setCellValue('A'.$row, $item['id']);
                $viagensSheet->setCellValue('B'.$row, $item['numero_viagem']);
                $viagensSheet->setCellValue('C'.$row, $item['data_competencia']);
                $viagensSheet->setCellValue('D'.$row, $item['placa']);
                $viagensSheet->setCellValue('E'.$row, $item['integrados']);
                $viagensSheet->setCellValue('F'.$row, $item['municipios']);
                $viagensSheet->setCellValue('G'.$row, $item['km_rodado']);
                $viagensSheet->setCellValue('H'.$row, $item['km_pago']);
                $viagensSheet->setCellValue('I'.$row, $item['numeros_documentos']);
                $viagensSheet->setCellValue('J'.$row, $item['valor_liquido']);
                $viagensSheet->getStyle('J'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
                $viagensSheet->setCellValue('K'.$row, $item['numeros_notas']);
                $viagensSheet->setCellValue('L'.$row, $item['conferido'] ? 'Sim' : 'Não');

                $row++;
            }

            if ($row > 2) {
                static::applyBorders($viagensSheet, 'A1:L'.($row - 1));
            }

            // ====== PLANILHA 2: NOTAS FISCAIS ======
            $notasSheet = $spreadsheet->createSheet();
            $notasSheet->setTitle('Notas Fiscais');

            $notasHeaders = [
                'A' => 'Viagem ID',
                'B' => 'Nº Viagem',
                'C' => 'Nº Nota',
                'D' => 'Chave NF-e',
                'E' => 'Tipo Documento',
                'F' => 'Emitente',
                'G' => 'Destinatário',
                'H' => 'Data Emissão',
            ];

            $row = 1;
            foreach ($notasHeaders as $col => $header) {
                $notasSheet->setCellValue($col.$row, $header);
            }

            static::styleHeader($notasSheet, 'A1:H1');

            $notasSheet->getColumnDimension('A')->setWidth(10);
            $notasSheet->getColumnDimension('B')->setWidth(20);
            $notasSheet->getColumnDimension('C')->setWidth(15);
            $notasSheet->getColumnDimension('D')->setWidth(50);
            $notasSheet->getColumnDimension('E')->setWidth(18);
            $notasSheet->getColumnDimension('F')->setWidth(35);
            $notasSheet->getColumnDimension('G')->setWidth(35);
            $notasSheet->getColumnDimension('H')->setWidth(18);

            $viagensIds = collect($this->dadosRelatorio)->pluck('id')->values()->all();

            $attachments = ViagemAttachment::query()
                ->whereIn('viagem_id', $viagensIds)
                ->with('receivedFiscalDocument')
                ->get();

            $row = 2;
            foreach ($attachments as $attachment) {
                $doc = $attachment->receivedFiscalDocument;

                if (! $doc) {
                    continue;
                }

                $viagem = Viagem::find($attachment->viagem_id);

                $notasSheet->setCellValue('A'.$row, $attachment->viagem_id);
                $notasSheet->setCellValue('B'.$row, $viagem?->numero_viagem ?? '');
                $notasSheet->setCellValue('C'.$row, $doc->numero_nota ?? '');
                $notasSheet->setCellValue('D'.$row, $doc->chave_nfe ?? '');
                $notasSheet->setCellValue('E'.$row, $doc->tipo_documento ?? '');
                $notasSheet->setCellValue('F'.$row, $doc->emitente_nome ?? '');
                $notasSheet->setCellValue('G'.$row, $doc->destinatario_nome ?? '');
                $notasSheet->setCellValue('H'.$row, $doc->emitido_em ? Carbon::parse($doc->emitido_em)->format('d/m/Y') : '');

                $row++;
            }

            if ($row > 2) {
                static::applyBorders($notasSheet, 'A1:H'.($row - 1));
            }

            $spreadsheet->setActiveSheetIndex(0);

            ini_set('memory_limit', $originalMemoryLimit);

            $fileName = 'relatorio_viagens_integrados_documentos_'.date('Y-m-d_His').'.xlsx';

            response()->streamDownload(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->send();

            exit;
        } catch (\Exception $e) {
            ini_set('memory_limit', $originalMemoryLimit);

            Notification::make()
                ->danger()
                ->title('Erro ao exportar')
                ->body('Ocorreu um erro ao gerar o arquivo Excel: '.$e->getMessage())
                ->send();
        }
    }

    public function ordenarPorColuna(string $coluna): void
    {
        if ($this->ordenarPor === $coluna) {
            $this->direcaoOrdenacao = $this->direcaoOrdenacao === 'asc' ? 'desc' : 'asc';
        } else {
            $this->ordenarPor = $coluna;
            $this->direcaoOrdenacao = 'asc';
        }

        if (! empty($this->dadosRelatorio)) {
            usort($this->dadosRelatorio, function ($a, $b) {
                $valorA = $a[$this->ordenarPor] ?? '';
                $valorB = $b[$this->ordenarPor] ?? '';

                if ($this->direcaoOrdenacao === 'asc') {
                    return $valorA <=> $valorB;
                }

                return $valorB <=> $valorA;
            });
        }
    }

    protected static function styleHeader($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    protected static function applyBorders($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
    }
}
