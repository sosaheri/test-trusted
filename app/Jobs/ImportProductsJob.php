<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $path) {}

    public function handle(): void
    {
        $rows = array_map('str_getcsv', file(storage_path("app/{$this->path}")));

        foreach ($rows as $row) {
            Product::create([
                'company_id' => auth()->user()->company_id,
                'name'       => $row[0],
                'sku'        => $row[1],
                'price'      => (float) $row[2],
                'stock'      => (int) $row[3],
            ]);
        }
    }
}
