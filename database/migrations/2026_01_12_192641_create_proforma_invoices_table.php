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
        Schema::create('proforma_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->json('organization_snapshot')->nullable();
            $table->decimal('invoice_percentage', 5, 2)->default(100);
            $table->date('po_date')->nullable();
            $table->string('po_number')->nullable();
            $table->text('shipping_address')->nullable();
            $table->json('charges')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proforma_invoices');
    }
};
