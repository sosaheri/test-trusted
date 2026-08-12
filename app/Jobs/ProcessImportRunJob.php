<?php

namespace App\Jobs;

use App\Models\ImportRun;
use App\Services\CatalogCsvImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessImportRunJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ImportRun $importRun
    ) {
    }

    public function handle(CatalogCsvImportService $service): void
    {
        $service->process($this->importRun);
    }
}
