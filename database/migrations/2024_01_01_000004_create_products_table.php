<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('sku');
            $table->string('name');
            $table->decimal('price', 18, 4);
            $table->decimal('stock', 14, 6);
            $table->softDeletes();
            $table->timestamps();
        });

        DB::statement('CREATE UNIQUE INDEX products_company_sku_active_unique ON products (company_id, sku) WHERE deleted_at IS NULL');

    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
