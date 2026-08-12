<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CompanyContextService;
use Illuminate\Http\Request;

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
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('sku', 'ilike', "%{$search}%");
            });
        }

        return response()->json($query->paginate($request->get('per_page', 15)));
    }
}
