<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_name');
            $table->string('model_name')->nullable();
            $table->string('hsn_code')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('dealer_price', 10, 2)->nullable();
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->decimal('max_discount', 5, 2)->nullable();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->json('attributes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
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
