<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'file_path',
        'status',
        'total_rows',
        'valid_rows',
        'rejected_rows',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ImportRunItem::class);
    }
}
