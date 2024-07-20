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
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('provider_id')->constrained('providers');
            $table->foreignUuid('category_id')->constrained('categories');
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('unit');
            $table->string('amount_per_unit');
            $table->integer('min_stock_level');
            $table->integer('sale');
            $table->integer('cost');
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
        Schema::dropIfExists('products');
    }
};
