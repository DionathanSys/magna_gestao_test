<?php

namespace App\Services\Import\Importers;

use App\{Models, Enum, Services};
use App\Traits\PdfExtractorTrait;

class ViagemEspelhoFreteImporter
{

    use PdfExtractorTrait;

    public function handle(string $filePath): array
    {
        // Extrair texto do PDF
        $text = $this->extractPdfData(new \Illuminate\Http\UploadedFile($filePath, basename($filePath)));

        // Processar o texto e extrair dados estruturados
        $data = $this->processPdfText($text);
 
        return $data;
    }

    /**
     * Processa o texto extraído e retorna array estruturado
     *
     * @param string $text
     * @return array
     */
    public function processPdfText(string $text): array
    {
        $data = [];
        $current = [];
        
        // Separar em linhas
        $lines = array_map('trim', explode("\n", $text));

        // dump($lines);

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            
            // Buscar por NFE
            if (preg_match('/NFE:\s*(\d+)/', $line, $matches)) {
                $current['nfe'] = $matches[1];
            }
            
            // Buscar por Chave de acesso
            if (preg_match('/Chave de acesso:\s*(\d+)/', $line, $matches)) {
                $current['chave_acesso'] = $matches[1];
            }
            
            // Buscar por Destino
            if (preg_match('/Destino:\s*(.+)/', $line, $matches)) {
                $current['destino'] = trim($matches[1]);
            }
            
            // Buscar por Doc.Transporte e Placa na mesma linha
            if (preg_match('/Doc\.Transporte:\s*(\d+)\s*-\s*Placa:\s*(\w+)\s*-\s*R\$/', $line, $matches)) {
                $current['doc_transporte'] = $matches[1];
                $current['placa'] = $matches[2];
                
                // O valor está na próxima linha
                if (isset($lines[$i + 2])) {
                    $valorLine = trim($lines[$i + 2]);
                    // Remover vírgulas e converter para float
                    $valor = (float) str_replace(',', '.', $valorLine);
                    $current['valor'] = $valor;
                }
                
                // Processar registro duplicado se necessário
                $this->processDuplicateRecord($data, $current);
                
                // Reset para próximo registro
                $current = [];
                // dd($current);
            }
        }
        
        return $data;
    }
    
    /**
     * Processa registros duplicados
     *
     * @param array &$data
     * @param array $current
     */
    protected function processDuplicateRecord(array &$data, array $current): void
    {
        $docTransporte = $current['doc_transporte'];
        $key1 = $docTransporte . '-1';
        $key2 = $docTransporte . '-2';
        
        if (array_key_exists($key1, $data)) {
            // Se já existe o primeiro registro e os valores são iguais, zerar o segundo
            if ($data[$key1]['valor'] == $current['valor']) {
                $current['valor'] = 0;
            }
            $data[$key2] = $current;
        } else {
            $data[$key1] = $current;
        }
    }

}