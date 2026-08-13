<?php

namespace App\Services;

use App\Models\ImportRun;
use App\Models\ImportRunItem;
use App\Models\Product;

class CatalogCsvImportService
{
    public function process(ImportRun $importRun): void
    {
        $importRun->update(['status' => 'processing']);

        $filePath = storage_path('app/' . $importRun->file_path);

        if (! is_file($filePath)) {
            $importRun->update(['status' => 'failed']);

            return;
        }

        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            $importRun->update(['status' => 'failed']);

            return;
        }

        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);
            $importRun->update(['status' => 'failed']);

            return;
        }

        $normalizedHeader = array_map(fn ($column) => $this->normalizeHeaderName((string) $column), $header);
        $requiredColumns = ['name', 'sku', 'price', 'stock'];
        $missingColumns = array_diff($requiredColumns, $normalizedHeader);

        if ($missingColumns !== []) {
            fclose($handle);
            $importRun->update(['status' => 'failed']);

            return;
        }

        $totalRows = 0;
        $validRows = 0;
        $rejectedRows = 0;
        $seenInRun = [];
        $batch = [];

        while (($row = fgetcsv($handle)) !== false) {
            if ($this->isBlankRow($row)) {
                continue;
            }

            $totalRows++;

            $rowValues = array_pad($row, count($normalizedHeader), '');

            if (count($rowValues) > count($normalizedHeader)) {
                $rowValues = array_slice($rowValues, 0, count($normalizedHeader));
            }

            $record = array_combine($normalizedHeader, $rowValues);

            if (! is_array($record)) {
                $record = [];
            }

            $normalizedRecord = $this->normalizeRow($record);
            $validation = $this->validateRow($normalizedRecord, $importRun->company_id, $seenInRun);

            if ($validation['valid']) {
                $batch[] = [
                    'import_run_id' => $importRun->id,
                    'row_number' => $totalRows,
                    'status' => 'valid',
                    'data' => json_encode($validation['data']),
                    'errors' => json_encode([]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $validRows++;
            } else {
                $batch[] = [
                    'import_run_id' => $importRun->id,
                    'row_number' => $totalRows,
                    'status' => 'rejected',
                    'data' => json_encode($normalizedRecord),
                    'errors' => json_encode($validation['errors']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $rejectedRows++;
            }

            if (count($batch) >= 1000) {
                ImportRunItem::insert($batch);
                $batch = [];
            }
        }

        fclose($handle);

        if ($batch !== []) {
            ImportRunItem::insert($batch);
        }

        $importRun->update([
            'status' => 'validated',
            'total_rows' => $totalRows,
            'valid_rows' => $validRows,
            'rejected_rows' => $rejectedRows,
        ]);
    }

    protected function normalizeHeaderName(string $column): string
    {
        $normalized = trim((string) $column);
        $normalized = preg_replace('/^\xEF\xBB\xBF/', '', $normalized);
        $normalized = strtolower(trim($normalized));

        return $normalized;
    }

    protected function isBlankRow(array $row): bool
    {
        if ($row === []) {
            return true;
        }

        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    protected function normalizeRow(array $record): array
    {
        $name = trim((string) ($record['name'] ?? ''));
        $sku = strtoupper(trim((string) ($record['sku'] ?? '')));

        return [
            'name' => $name,
            'sku' => $sku,
            'price' => $this->normalizeDecimal((string) ($record['price'] ?? ''), 'price'),
            'stock' => $this->normalizeDecimal((string) ($record['stock'] ?? ''), 'stock'),
        ];
    }

    protected function validateRow(array $record, int $companyId, array &$seenInRun): array
    {
        $errors = [];
        $data = [
            'name' => trim((string) ($record['name'] ?? '')),
            'sku' => strtoupper(trim((string) ($record['sku'] ?? ''))),
        ];

        if ($data['name'] === '') {
            $errors[] = 'El nombre es obligatorio.';
        }

        if ($data['sku'] === '') {
            $errors[] = 'El SKU es obligatorio.';
        }

        if (isset($record['price']) && $record['price'] === null) {
            $errors[] = 'El precio es obligatorio y debe ser numérico.';
        }

        if (isset($record['stock']) && $record['stock'] === null) {
            $errors[] = 'El stock es obligatorio y debe ser numérico.';
        }

        if ($data['sku'] !== '') {
            $skuKey = $companyId . ':' . $data['sku'];

            if (isset($seenInRun[$skuKey])) {
                $errors[] = 'El SKU ya aparece más de una vez dentro del mismo archivo para esta empresa.';
            }

            $seenInRun[$skuKey] = true;

            $existingProduct = Product::withTrashed()
                ->where('company_id', $companyId)
                ->where('sku', $data['sku'])
                ->exists();

            if ($existingProduct) {
                $errors[] = 'El SKU ya existe para esta empresa y no puede duplicarse.';
            }
        }

        $data['price'] = $record['price'] ?? null;
        $data['stock'] = $record['stock'] ?? null;

        if ($data['price'] !== null && $data['price'] !== '' && ! is_numeric($data['price'])) {
            $errors[] = 'El precio debe ser numérico.';
        }

        if ($data['stock'] !== null && $data['stock'] !== '' && (! is_numeric($data['stock']) || (float) $data['stock'] < 0)) {
            $errors[] = 'El stock debe ser un número mayor o igual a cero.';
        }

        return [
            'valid' => $errors === [],
            'data' => $data,
            'errors' => $errors,
        ];
    }

    protected function normalizeDecimal(string $value, string $field): ?string
    {
        $cleanValue = trim($value);

        if ($cleanValue === '') {
            return null;
        }

        $cleanValue = preg_replace('/[^0-9,\.\-+E]/i', '', $cleanValue);

        if ($cleanValue === '' || $cleanValue === '-' || $cleanValue === '+' || $cleanValue === '.') {
            return null;
        }

        if (str_contains($cleanValue, ',') && str_contains($cleanValue, '.')) {
            $lastComma = strrpos($cleanValue, ',');
            $lastDot = strrpos($cleanValue, '.');

            if ($lastComma > $lastDot) {
                $cleanValue = str_replace('.', '', $cleanValue);
                $cleanValue = str_replace(',', '.', $cleanValue);
            } else {
                $cleanValue = str_replace(',', '', $cleanValue);
            }
        } elseif (str_contains($cleanValue, ',')) {
            $cleanValue = str_replace('.', '', $cleanValue);
            $cleanValue = str_replace(',', '.', $cleanValue);
        }

        if (! is_numeric($cleanValue)) {
            return null;
        }

        $numericValue = (float) $cleanValue;

        if ($field === 'price') {
            return number_format($numericValue, 4, '.', '');
        }

        return number_format($numericValue, 6, '.', '');
    }
}
