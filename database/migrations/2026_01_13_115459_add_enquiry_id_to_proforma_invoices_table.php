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
        Schema::table('proforma_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('enquiry_id')->nullable()->after('id');
            $table->foreign('enquiry_id')->references('id')->on('enquiries')->onDelete('cascade');
            $table->unsignedBigInteger('quotation_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proforma_invoices', function (Blueprint $table) {
            $table->dropForeign(['enquiry_id']);
            $table->dropColumn('enquiry_id');
            $table->unsignedBigInteger('quotation_id')->nullable(false)->change();
        });
    }
};
