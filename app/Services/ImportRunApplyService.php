<?php

namespace App\Services;

use App\Models\ImportRun;
use App\Models\ImportRunItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ImportRunApplyService
{
    public function apply(ImportRun $importRun, int $companyId): void
    {
        if ($importRun->company_id !== $companyId) {
            abort(403, 'La corrida no pertenece a la empresa autenticada.');
        }

        if ($importRun->status === 'applied') {
            return;
        }

        $validItems = ImportRunItem::query()
            ->where('import_run_id', $importRun->id)
            ->where('status', 'valid')
            ->get();

        DB::transaction(function () use ($importRun, $validItems, $companyId) {
            foreach ($validItems as $item) {
                $data = $item->data ?? [];
                $sku = strtoupper((string) ($data['sku'] ?? ''));
                $name = (string) ($data['name'] ?? '');
                $price = (string) ($data['price'] ?? '0');
                $stock = (string) ($data['stock'] ?? '0');

                Product::withTrashed()
                    ->updateOrCreate(
                        [
                            'company_id' => $companyId,
                            'sku' => $sku,
                        ],
                        [
                            'name' => $name,
                            'price' => $price,
                            'stock' => $stock,
                            'deleted_at' => null,
                        ]
                    );
            }

            $importRun->update(['status' => 'applied']);
        });
    }
}
