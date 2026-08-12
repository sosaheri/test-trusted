<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CompanyContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContextService,
    ) {
    }

    public function index(Request $request)
    {
        $companyId = $this->companyContextService->resolve();

        $query = Product::query()
            ->where('company_id', $companyId)
            ->orderBy($request->get('sort_by', 'id'), $request->get('sort_dir', 'desc'));

        if ($search = $request->get('search')) {
            $term = trim((string) $search);

            if ($term !== '') {
                $likeTerm = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';

                if (DB::getDriverName() === 'pgsql') {
                    $query->where(function ($q) use ($likeTerm) {
                        $q->whereRaw('name ILIKE ?', [$likeTerm])
                            ->orWhereRaw('sku ILIKE ?', [$likeTerm]);
                    });
                } else {
                    $query->where(function ($q) use ($likeTerm) {
                        $q->whereRaw('LOWER(name) LIKE LOWER(?)', [$likeTerm])
                            ->orWhereRaw('LOWER(sku) LIKE LOWER(?)', [$likeTerm]);
                    });
                }
            }
        }

        return response()->json($query->paginate($request->get('per_page', 15)));
    }
}
