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
        
        // Separar em linhas e limpar
        $lines = array_map('trim', explode("\n", $text));
        
        // Remover caracteres de controle como \f (form feed)
        $lines = array_map(function($line) {
            return preg_replace('/[\x00-\x1F\x7F]/', '', $line);
        }, $lines);

        for ($i = 0; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            
            // Pular linhas vazias
            if (empty($line)) {
                continue;
            }
            
            // Buscar por NFE no início da linha
            if (preg_match('/^NFE:\s*(\d+)$/', $line, $matches)) {
                // Se já temos dados acumulados, salvar antes de iniciar novo
                if (!empty($current) && isset($current['doc_transporte'])) {
                    $this->addToDataArray($data, $current);
                }
                
                $current = ['nfe' => $matches[1]];
                continue;
            }
            
            // Buscar por Chave de acesso
            if (preg_match('/^Chave de acesso:\s*(\d+)$/', $line, $matches)) {
                $current['chave_acesso'] = $matches[1];
                continue;
            }
            
            // Buscar por Destino
            if (preg_match('/^Destino:\s*(.+)$/', $line, $matches)) {
                $current['destino'] = trim($matches[1]);
                continue;
            }
            
            // Buscar por Doc.Transporte e Placa na mesma linha
            if (preg_match('/^Doc\.Transporte:\s*(\d+)\s*-\s*Placa:\s*(\w+)\s*-\s*R\$$/', $line, $matches)) {
                $current['doc_transporte'] = $matches[1];
                $current['placa'] = $matches[2];
                
                // O valor está na próxima linha não vazia
                $valorEncontrado = false;
                $j = $i + 1;
                
                while ($j < count($lines) && !$valorEncontrado) {
                    $valorLine = trim($lines[$j]);
                    
                    // Pular linhas vazias
                    if (empty($valorLine)) {
                        $j++;
                        continue;
                    }
                    
                    // Verificar se é um valor monetário (formato: 123,45 ou 1.234,56)
                    if (preg_match('/^(\d{1,3}(?:\.\d{3})*),(\d{2})$/', $valorLine, $valorMatches)) {
                        // Remover pontos (separadores de milhares) e trocar vírgula por ponto
                        $valorStr = str_replace('.', '', $valorMatches[1]) . '.' . $valorMatches[2];
                        $current['valor'] = (float) $valorStr;
                        $valorEncontrado = true;
                    }
                    // Formato alternativo sem pontos: 123,45
                    elseif (preg_match('/^(\d+),(\d{2})$/', $valorLine, $valorMatches)) {
                        $valorStr = $valorMatches[1] . '.' . $valorMatches[2];
                        $current['valor'] = (float) $valorStr;
                        $valorEncontrado = true;
                    }
                    
                    $j++;
                }
                
                // Se encontrou o valor, adicionar aos dados
                if ($valorEncontrado && isset($current['nfe'])) {
                    $this->addToDataArray($data, $current);
                    $current = []; // Reset para próximo registro
                }
                
                continue;
            }
        }
        
        // Adicionar último registro se existir
        if (!empty($current) && isset($current['doc_transporte']) && isset($current['valor'])) {
            $this->addToDataArray($data, $current);
        }
        
        return $data;
    }
    
    /**
     * Adiciona registro ao array de dados, tratando duplicatas
     *
     * @param array &$data
     * @param array $current
     */
    protected function addToDataArray(array &$data, array $current): void
    {
        // Validar se tem todos os campos obrigatórios
        $requiredFields = ['nfe', 'chave_acesso', 'destino', 'doc_transporte', 'placa', 'valor'];
        
        foreach ($requiredFields as $field) {
            if (!isset($current[$field])) {
                \Illuminate\Support\Facades\Log::warning("Campo obrigatório '{$field}' não encontrado", $current);
                return;
            }
        }
        
        $docTransporte = $current['doc_transporte'];
        $key1 = $docTransporte . '-1';
        $key2 = $docTransporte . '-2';
        
        if (array_key_exists($key1, $data)) {
            // Se já existe o primeiro registro e os valores são iguais, zerar o segundo
            if (abs($data[$key1]['valor'] - $current['valor']) < 0.01) { // Comparação com tolerância
                $current['valor'] = 0;
            }
            $data[$key2] = $current;
        } else {
            $data[$key1] = $current;
        }
    }

}