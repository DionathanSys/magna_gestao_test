<?php

namespace App\Console\Commands;

use App\Models\Veiculo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportarMapaPneusExcel extends Command
{
    protected $signature = 'pneus:exportar-mapa-excel {--all : Inclui veiculos inativos e excluidos} {--path= : Caminho completo do arquivo .xlsx}';

    protected $description = 'Exporta o mapa de pneus de todos os caminhões para um arquivo Excel';

    public function handle(): int
    {
        $outputPath = $this->resolveOutputPath();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Mapa de Pneus');

        $headers = [
            'A' => 'Veículo ID',
            'B' => 'Placa',
            'C' => 'Ativo',
            'D' => 'Tipo Veículo',
            'E' => 'Mapa Código',
            'F' => 'Mapa Nome',
            'G' => 'Posição ID',
            'H' => 'Sequência',
            'I' => 'Eixo',
            'J' => 'Código Posição',
            'K' => 'Nome Posição',
            'L' => 'Lado',
            'M' => 'Conjunto',
            'N' => 'Tipo Posição',
            'O' => 'Pneu ID',
            'P' => 'Nº Fogo',
            'Q' => 'Marca',
            'R' => 'Modelo',
            'S' => 'Medida',
            'T' => 'Status Pneu',
            'U' => 'Local Pneu',
            'V' => 'Ciclo Vida',
            'W' => 'Data Aplicação',
            'X' => 'KM Inicial',
            'Y' => 'KM Rodado Posição',
        ];

        foreach ($headers as $column => $label) {
            $sheet->setCellValue($column.'1', $label);
        }

        $sheet->getStyle('A1:Y1')->applyFromArray([
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

        $query = Veiculo::query()
            ->with([
                'tipoVeiculo:id,descricao',
                'mapaPneu:id,codigo,nome',
                'pneus.mapaPosicao:id,codigo,nome,eixo_numero,lado,conjunto,tipo_posicao',
                'pneus.pneu.marcaCatalogo:id,nome',
                'pneus.pneu.modeloCatalogo:id,nome',
                'pneus.pneu.medidaCatalogo:id,codigo',
            ])
            ->orderBy('id');

        if ($this->option('all')) {
            $query->withTrashed();
        } else {
            $query->where('is_active', true);
        }

        $row = 2;
        $vehicles = 0;
        $positions = 0;

        $query->chunkById(100, function ($veiculos) use ($sheet, &$row, &$vehicles, &$positions): void {
            foreach ($veiculos as $veiculo) {
                $vehicles++;

                foreach ($veiculo->pneus->sortBy('sequencia') as $posicao) {
                    $positions++;
                    $sheet->setCellValue('A'.$row, $veiculo->id);
                    $sheet->setCellValue('B'.$row, $veiculo->placa);
                    $sheet->setCellValue('C'.$row, $veiculo->is_active ? 'Sim' : 'Não');
                    $sheet->setCellValue('D'.$row, $veiculo->tipoVeiculo?->descricao ?? 'N/A');
                    $sheet->setCellValue('E'.$row, $veiculo->mapaPneu?->codigo ?? 'N/A');
                    $sheet->setCellValue('F'.$row, $veiculo->mapaPneu?->nome ?? 'N/A');
                    $sheet->setCellValue('G'.$row, $posicao->id);
                    $sheet->setCellValue('H'.$row, $posicao->sequencia);
                    $sheet->setCellValue('I'.$row, $posicao->mapaPosicao?->eixo_numero ?? $posicao->eixo);
                    $sheet->setCellValue('J'.$row, $posicao->mapaPosicao?->codigo ?? $posicao->posicao);
                    $sheet->setCellValue('K'.$row, $posicao->mapaPosicao?->nome ?? $posicao->posicao);
                    $sheet->setCellValue('L'.$row, $posicao->mapaPosicao?->lado ?? 'N/A');
                    $sheet->setCellValue('M'.$row, $posicao->mapaPosicao?->conjunto ?? 'N/A');
                    $sheet->setCellValue('N'.$row, $posicao->mapaPosicao?->tipo_posicao ?? 'N/A');
                    $sheet->setCellValue('O'.$row, $posicao->pneu?->id ?? '');
                    $sheet->setCellValue('P'.$row, $posicao->pneu?->numero_fogo ?? '');
                    $sheet->setCellValue('Q'.$row, $posicao->pneu?->marcaCatalogo?->nome ?? '');
                    $sheet->setCellValue('R'.$row, $posicao->pneu?->modeloCatalogo?->nome ?? '');
                    $sheet->setCellValue('S'.$row, $posicao->pneu?->medidaCatalogo?->codigo ?? '');
                    $sheet->setCellValue('T'.$row, $posicao->pneu?->status?->value ?? '');
                    $sheet->setCellValue('U'.$row, $posicao->pneu?->local?->value ?? '');
                    $sheet->setCellValue('V'.$row, $posicao->pneu?->ciclo_vida ?? '');
                    $sheet->setCellValue('W'.$row, $posicao->data_inicial ? date('d/m/Y', strtotime((string) $posicao->data_inicial)) : '');
                    $sheet->setCellValue('X'.$row, $posicao->km_inicial ?? '');
                    $sheet->setCellValue('Y'.$row, $posicao->km_rodado ?? 0);
                    $row++;
                }
            }
        });

        if ($row > 2) {
            $sheet->getStyle('A2:Y'.($row - 1))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E5E7EB'],
                    ],
                ],
            ]);
        }

        foreach (range('A', 'Y') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->freezePane('A2');

        $writer = new Xlsx($spreadsheet);
        $writer->save($outputPath);

        $this->info('Exportação concluída.');
        $this->line('Veículos exportados: '.$vehicles);
        $this->line('Posições exportadas: '.$positions);
        $this->line('Arquivo: '.$outputPath);

        return self::SUCCESS;
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

        return $directory.DIRECTORY_SEPARATOR.'mapa_pneus_caminhoes_'.now()->format('Y-m-d_His').'.xlsx';
    }
}
