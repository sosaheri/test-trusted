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

        $allowedSortFields = ['id', 'sku', 'name', 'price', 'stock', 'created_at'];
        $sortBy = in_array($request->get('sort_by'), $allowedSortFields, true)
            ? $request->get('sort_by')
            : 'id';
        $sortDir = strtolower((string) $request->get('sort_dir', 'desc')) === 'asc'
            ? 'asc'
            : 'desc';

        $query = Product::query()
            ->where('company_id', $companyId)
            ->orderBy($sortBy, $sortDir);

        if ($search = $request->get('search')) {
            $term = trim((string) $search);

            if ($term !== '') {
                $likeTerm = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term) . '%';

                $query->where(function ($q) use ($likeTerm) {
                    $q->whereRaw('LOWER(name) LIKE LOWER(?)', [$likeTerm])
                        ->orWhereRaw('LOWER(sku) LIKE LOWER(?)', [$likeTerm]);
                });
            }
        }

        return response()->json($query->paginate((int) $request->get('per_page', 15)));
    }
}
