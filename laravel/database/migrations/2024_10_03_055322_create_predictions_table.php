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
        Schema::create('predictions', function (Blueprint $table) {
            $table->foreignUuid('product_id')->index()->constrained()->onDelete('cascade');
            $table->integer('value');
            $table->date('date');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['product_id', 'date']);

            $table->index('date');
            $table->index(['product_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('predictions');
    }
};
