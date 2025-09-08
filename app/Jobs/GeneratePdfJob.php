<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\PdfService;
use Illuminate\Support\Facades\Storage;

class GeneratePdfJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $view, public array $data, public string $path)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $service = app(PdfService::class);
        $pdf = $service->renderViewToString($this->view, $this->data);
        Storage::put($this->path, $pdf);
    }
}
