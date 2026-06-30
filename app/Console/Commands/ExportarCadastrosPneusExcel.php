<?php

namespace App\Console\Commands;

use App\Models\HistoricoMovimentoPneu;
use App\Models\Pneu;
use App\Models\Recapagem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportarCadastrosPneusExcel extends Command
{
    protected $signature = 'pneus:exportar-cadastros-excel {--path= : Caminho completo do arquivo .xlsx}';

    protected $description = 'Exporta pneus, recapagens e histórico de movimentações para um arquivo Excel com abas separadas';

    public function handle(): int
    {
        $outputPath = $this->resolveOutputPath();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $pneusCount = $this->buildPneusSheet($spreadsheet);
        $recapagensCount = $this->buildRecapagensSheet($spreadsheet);
        $historicoCount = $this->buildHistoricoSheet($spreadsheet);

        $writer = new Xlsx($spreadsheet);
        $writer->save($outputPath);

        $this->info('Exportação concluída.');
        $this->line('Pneus exportados: '.$pneusCount);
        $this->line('Recapagens exportadas: '.$recapagensCount);
        $this->line('Movimentações exportadas: '.$historicoCount);
        $this->line('Arquivo: '.$outputPath);

        return self::SUCCESS;
    }

    protected function buildPneusSheet(Spreadsheet $spreadsheet): int
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Pneus');

        $headers = [
            'A' => 'ID',
            'B' => 'Nº Fogo',
            'C' => 'Marca',
            'D' => 'Modelo',
            'E' => 'Medida',
            'F' => 'Desenho',
            'G' => 'Status',
            'H' => 'Local',
            'I' => 'Ciclo Vida',
            'J' => 'Data Aquisição',
            'K' => 'Valor',
            'L' => 'Sulco Inicial',
            'M' => 'Recapável',
            'N' => 'Limite Recapagens',
            'O' => 'Número Série',
            'P' => 'DOT',
            'Q' => 'Veículo Atual',
            'R' => 'Posição Atual',
            'S' => 'KM Ciclo',
            'T' => 'KM Total',
            'U' => 'Ativo',
        ];

        $this->writeHeaders($sheet, $headers);

        $row = 2;

        Pneu::query()
            ->with([
                'marcaCatalogo:id,nome',
                'modeloCatalogo:id,nome',
                'medidaCatalogo:id,codigo',
                'desenhoPneu:id,descricao',
                'localCatalogo:id,nome',
                'posicaoVeiculo:id,pneu_id,veiculo_id,posicao',
                'posicaoVeiculo.veiculo:id,placa',
            ])
            ->orderBy('id')
            ->chunkById(200, function ($pneus) use ($sheet, &$row): void {
                foreach ($pneus as $pneu) {
                    $sheet->setCellValue('A'.$row, $pneu->id);
                    $sheet->setCellValue('B'.$row, $pneu->numero_fogo);
                    $sheet->setCellValue('C'.$row, $pneu->marcaCatalogo?->nome ?? $pneu->marca ?? '');
                    $sheet->setCellValue('D'.$row, $pneu->modeloCatalogo?->nome ?? $pneu->modelo ?? '');
                    $sheet->setCellValue('E'.$row, $pneu->medidaCatalogo?->codigo ?? $pneu->medida ?? '');
                    $sheet->setCellValue('F'.$row, $pneu->desenhoPneu?->descricao ?? '');
                    $sheet->setCellValue('G'.$row, $pneu->status?->value ?? '');
                    $sheet->setCellValue('H'.$row, $pneu->localCatalogo?->nome ?? $pneu->local?->value ?? '');
                    $sheet->setCellValue('I'.$row, $pneu->ciclo_vida);
                    $sheet->setCellValue('J'.$row, $pneu->data_aquisicao?->format('d/m/Y') ?? '');
                    $sheet->setCellValue('K'.$row, (float) ($pneu->valor ?? 0));
                    $sheet->setCellValue('L'.$row, (float) ($pneu->sulco_inicial ?? 0));
                    $sheet->setCellValue('M'.$row, $pneu->recapavel ? 'Sim' : 'Não');
                    $sheet->setCellValue('N'.$row, $pneu->limite_recapagens ?? '');
                    $sheet->setCellValue('O'.$row, $pneu->numero_serie ?? '');
                    $sheet->setCellValue('P'.$row, $pneu->dot ?? '');
                    $sheet->setCellValue('Q'.$row, $pneu->posicaoVeiculo?->veiculo?->placa ?? '');
                    $sheet->setCellValue('R'.$row, $pneu->posicaoVeiculo?->posicao && $pneu->posicaoVeiculo?->id !== 0 ? $pneu->posicaoVeiculo->posicao : '');
                    $sheet->setCellValue('S'.$row, $pneu->km_percorrido_ciclo ?? 0);
                    $sheet->setCellValue('T'.$row, $pneu->km_percorrido ?? 0);
                    $sheet->setCellValue('U'.$row, array_key_exists('ativo', $pneu->getAttributes()) ? ((bool) $pneu->getAttribute('ativo') ? 'Sim' : 'Não') : '');
                    $row++;
                }
            });

        $this->finalizeSheet($sheet, 'A', 'U', $row - 1);

        return $row - 2;
    }

    protected function buildRecapagensSheet(Spreadsheet $spreadsheet): int
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Recapagens');

        $headers = [
            'A' => 'ID',
            'B' => 'Pneu ID',
            'C' => 'Nº Fogo',
            'D' => 'Marca',
            'E' => 'Modelo',
            'F' => 'Ciclo Vida',
            'G' => 'Data Recapagem',
            'H' => 'Desenho',
            'I' => 'Modelo Desenho',
            'J' => 'Valor',
            'K' => 'Ciclo ID',
            'L' => 'Criado em',
        ];

        $this->writeHeaders($sheet, $headers);

        $row = 2;

        Recapagem::query()
            ->with([
                'pneu:id,numero_fogo,pneu_marca_id,pneu_modelo_id',
                'pneu.marcaCatalogo:id,nome',
                'pneu.modeloCatalogo:id,nome',
                'desenhoPneu:id,descricao,modelo',
            ])
            ->orderBy('id')
            ->chunkById(200, function ($recapagens) use ($sheet, &$row): void {
                foreach ($recapagens as $recapagem) {
                    $sheet->setCellValue('A'.$row, $recapagem->id);
                    $sheet->setCellValue('B'.$row, $recapagem->pneu_id);
                    $sheet->setCellValue('C'.$row, $recapagem->pneu?->numero_fogo ?? '');
                    $sheet->setCellValue('D'.$row, $recapagem->pneu?->marcaCatalogo?->nome ?? '');
                    $sheet->setCellValue('E'.$row, $recapagem->pneu?->modeloCatalogo?->nome ?? '');
                    $sheet->setCellValue('F'.$row, $recapagem->ciclo_vida ?? '');
                    $sheet->setCellValue('G'.$row, $recapagem->data_recapagem ? date('d/m/Y', strtotime((string) $recapagem->data_recapagem)) : '');
                    $sheet->setCellValue('H'.$row, $recapagem->desenhoPneu?->descricao ?? '');
                    $sheet->setCellValue('I'.$row, $recapagem->desenhoPneu?->modelo ?? '');
                    $sheet->setCellValue('J'.$row, (float) ($recapagem->valor ?? 0));
                    $sheet->setCellValue('K'.$row, $recapagem->pneu_ciclo_id ?? '');
                    $sheet->setCellValue('L'.$row, $recapagem->created_at?->format('d/m/Y H:i') ?? '');
                    $row++;
                }
            });

        $this->finalizeSheet($sheet, 'A', 'L', $row - 1);

        return $row - 2;
    }

    protected function buildHistoricoSheet(Spreadsheet $spreadsheet): int
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Historico Mov');

        $headers = [
            'A' => 'ID',
            'B' => 'Pneu ID',
            'C' => 'Nº Fogo',
            'D' => 'Veículo',
            'E' => 'Ciclo Vida',
            'F' => 'Ciclo ID',
            'G' => 'Posição Veículo ID',
            'H' => 'Eixo',
            'I' => 'Posição',
            'J' => 'Motivo',
            'K' => 'Tipo Evento',
            'L' => 'Sulco',
            'M' => 'KM Inicial',
            'N' => 'KM Final',
            'O' => 'KM Percorrido',
            'P' => 'Data Inicial',
            'Q' => 'Data Final',
            'R' => 'Observação',
            'S' => 'Qtd. Anexos JSON',
            'T' => 'Criado em',
        ];

        $this->writeHeaders($sheet, $headers);

        $row = 2;

        HistoricoMovimentoPneu::query()
            ->with([
                'pneu:id,numero_fogo',
                'veiculo:id,placa',
            ])
            ->orderBy('id')
            ->chunkById(200, function ($movimentacoes) use ($sheet, &$row): void {
                foreach ($movimentacoes as $movimento) {
                    $sheet->setCellValue('A'.$row, $movimento->id);
                    $sheet->setCellValue('B'.$row, $movimento->pneu_id);
                    $sheet->setCellValue('C'.$row, $movimento->pneu?->numero_fogo ?? '');
                    $sheet->setCellValue('D'.$row, $movimento->veiculo?->placa ?? '');
                    $sheet->setCellValue('E'.$row, $movimento->ciclo_vida ?? '');
                    $sheet->setCellValue('F'.$row, $movimento->pneu_ciclo_id ?? '');
                    $sheet->setCellValue('G'.$row, $movimento->pneu_posicao_veiculo_id ?? '');
                    $sheet->setCellValue('H'.$row, $movimento->eixo ?? '');
                    $sheet->setCellValue('I'.$row, $movimento->posicao ?? '');
                    $sheet->setCellValue('J'.$row, $movimento->motivo ?? '');
                    $sheet->setCellValue('K'.$row, $movimento->tipo_evento ?? '');
                    $sheet->setCellValue('L'.$row, (float) ($movimento->sulco_movimento ?? 0));
                    $sheet->setCellValue('M'.$row, $movimento->km_inicial ?? '');
                    $sheet->setCellValue('N'.$row, $movimento->km_final ?? '');
                    $sheet->setCellValue('O'.$row, $movimento->km_percorrido ?? '');
                    $sheet->setCellValue('P'.$row, $movimento->data_inicial ? date('d/m/Y', strtotime((string) $movimento->data_inicial)) : '');
                    $sheet->setCellValue('Q'.$row, $movimento->data_final ? date('d/m/Y', strtotime((string) $movimento->data_final)) : '');
                    $sheet->setCellValue('R'.$row, $movimento->observacao ?? '');
                    $sheet->setCellValue('S'.$row, is_array($movimento->anexos) ? count($movimento->anexos) : 0);
                    $sheet->setCellValue('T'.$row, $movimento->created_at?->format('d/m/Y H:i') ?? '');
                    $row++;
                }
            });

        $this->finalizeSheet($sheet, 'A', 'T', $row - 1);

        return $row - 2;
    }

    protected function writeHeaders(Worksheet $sheet, array $headers): void
    {
        foreach ($headers as $column => $label) {
            $sheet->setCellValue($column.'1', $label);
        }

        $lastColumn = array_key_last($headers);

        $sheet->getStyle('A1:'.$lastColumn.'1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F4E78'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D9D9D9'],
                ],
            ],
        ]);
    }

    protected function finalizeSheet(Worksheet $sheet, string $startColumn, string $endColumn, int $lastRow): void
    {
        if ($lastRow >= 2) {
            $sheet->getStyle($startColumn.'2:'.$endColumn.$lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E5E7EB'],
                    ],
                ],
            ]);
        }

        foreach (range($startColumn, $endColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->freezePane('A2');
    }

    protected function resolveOutputPath(): string
    {
        $customPath = $this->option('path');

        if (is_string($customPath) && trim($customPath) !== '') {
            $directory = dirname($customPath);

            if (! File::isDirectory($directory)) {
                File::makeDirectory($directory, 0777, true);
            }

            return $customPath;
        }

        $directory = storage_path('app/exports');

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0777, true);
        }

        return $directory.DIRECTORY_SEPARATOR.'cadastros_pneus_'.now()->format('Y-m-d_His').'.xlsx';
    }
}
