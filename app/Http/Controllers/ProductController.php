<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q');

        return DB::select("SELECT * FROM products WHERE name ILIKE '%{$q}%'");
    }

    public function store(Request $request)
    {
        return Product::create($request->all());
    }

    /**
     * Búsqueda usada por el autocomplete del header. A diferencia de
     * index(), este método sí parametriza el bind vía el placeholder `?`.
     */
    public function suggest(Request $request)
    {
        $q = $request->query('q', '');

        return DB::select('SELECT id, name, sku FROM products WHERE name ILIKE ?', ["%{$q}%"]);
    }
}
