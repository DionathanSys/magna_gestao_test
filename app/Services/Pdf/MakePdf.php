<?php

namespace App\Services\Pdf;

use Barryvdh\DomPDF\Facade\Pdf;

class MakePdf
{
    private $pdf;

    public function __construct(private string $view)
    {
        // You can inject dependencies here if needed
    }

    public function createPdf(array $data)
    {
        $this->pdf = Pdf::loadView($this->view, $data);
        return $this;
    }

    public function download(string $filename = 'document.pdf')
    {
        if (!$this->pdf) {
            throw new \Exception('PDF não foi criado. Chame createPdf() primeiro.');
        }

        return $this->pdf->download($filename);
    }

}
