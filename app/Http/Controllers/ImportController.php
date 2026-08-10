<?php

namespace App\Http\Controllers;

use App\Jobs\ImportProductsJob;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function store(Request $request)
    {
        $path = $request->file('file')->store('imports', 'public');
        ImportProductsJob::dispatch($path);

        return response()->json(['ok' => true]);
    }
}
