<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessImportRunJob;
use App\Models\ImportRun;
use App\Services\CompanyContextService;
use App\Services\ImportRunApplyService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class ImportRunController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContextService,
        protected ImportRunApplyService $importRunApplyService,
    ) {
    }

    public function store(Request $request)
    {
        $companyId = $this->companyContextService->resolve();

        $request->validate([
            'file' => ['required', 'file', 'mimetypes:text/csv,text/plain,application/csv'],
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $path = $file->storeAs('imports', $file->getClientOriginalName(), 'local');

        $importRun = ImportRun::create([
            'company_id' => $companyId,
            'file_path' => $path,
            'status' => 'pending',
            'total_rows' => 0,
            'valid_rows' => 0,
            'rejected_rows' => 0,
        ]);

        ProcessImportRunJob::dispatch($importRun);

        return response()->json([
            'message' => 'Importación encolada correctamente.',
            'import_run' => [
                'id' => $importRun->id,
                'status' => $importRun->status,
                'company_id' => $companyId,
            ],
        ], 202);
    }

    public function show(ImportRun $importRun)
    {
        $companyId = $this->companyContextService->resolve();

        if ((int) $importRun->company_id !== $companyId) {
            abort(403, 'La corrida no pertenece a tu empresa.');
        }

        return response()->json([
            'id' => $importRun->id,
            'status' => $importRun->status,
            'total_rows' => $importRun->total_rows,
            'valid_rows' => $importRun->valid_rows,
            'rejected_rows' => $importRun->rejected_rows,
        ]);
    }

    public function apply(ImportRun $importRun)
    {
        $companyId = $this->companyContextService->resolve();
        $this->importRunApplyService->apply($importRun, $companyId);

        return response()->json([
            'message' => 'Importación aplicada correctamente.',
            'import_run' => [
                'id' => $importRun->id,
                'status' => $importRun->status,
                'company_id' => $companyId,
            ],
        ]);
    }
}
