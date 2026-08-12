<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportRunItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_run_id',
        'row_number',
        'status',
        'data',
        'errors',
    ];

    protected $casts = [
        'data' => 'array',
        'errors' => 'array',
    ];

    public function importRun(): BelongsTo
    {
        return $this->belongsTo(ImportRun::class);
    }
}
