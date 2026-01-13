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
            $table->integer('proforma_no')->nullable()->after('id');
            $table->integer('revision_no')->default(0)->after('proforma_no');
            $table->string('custom_proforma_id')->nullable()->after('revision_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proforma_invoices', function (Blueprint $table) {
            $table->dropColumn(['proforma_no', 'revision_no', 'custom_proforma_id']);
        });
    }
};
