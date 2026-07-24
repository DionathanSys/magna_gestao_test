<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Spatie\PdfToText\Pdf as PdfToTextPdf;

trait PdfExtractorTrait
{
    /**
     * Extrai dados estruturados de um arquivo PDF
     */
    public function extractPdfData(UploadedFile $file): string
    {
        // Detectar ambiente e definir caminho do pdftotext
        $pdfToTextPath = $this->getPdfToTextPath();

        // Extrair texto do PDF
        $text = PdfToTextPdf::getText($file->getRealPath(), $pdfToTextPath);

        return $text;
    }

    /**
     * Determina o caminho do executável pdftotext baseado no ambiente
     */
    protected function getPdfToTextPath(): string
    {
        // Detectar sistema operacional
        if (PHP_OS_FAMILY === 'Windows') {
            $windowsPaths = 'C:\\xpdf-tool\\xpdf-tools-win-4.05\\bin64\\pdftotext.exe';
            // $windowsPaths = "C:\\Program Files\\xpdf-tool\\xpdf-tools-win-4.05\\bin64\\pdftotext.exe";
            if (file_exists($windowsPaths)) {
                return $windowsPaths;
            }
        }

        return '/usr/bin/pdftotext';
    }
}
