<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class PdfService
{
    public function renderViewToString(string $view, array $data = []): string
    {
        $html = View::make($view, $data)->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    public function saveViewAsPdf(string $view, array $data, string $path): string
    {
        $pdf = $this->renderViewToString($view, $data);
        Storage::put($path, $pdf);
        return $path;
    }
}
