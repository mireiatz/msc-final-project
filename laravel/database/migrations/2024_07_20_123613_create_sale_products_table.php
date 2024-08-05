<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sale_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sale_id')->index()->constrained('sales')->onDelete('cascade');
            $table->foreignUuid('product_id')->index()->constrained('products');
            $table->integer('quantity');
            $table->integer('unit_sale');
            $table->integer('total_sale');
            $table->integer('unit_cost');
            $table->integer('total_cost');
            $table->string('currency');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_products');
    }
};
