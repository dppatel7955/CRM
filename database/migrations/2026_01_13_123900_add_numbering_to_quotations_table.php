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
        Schema::table('quotations', function (Blueprint $table) {
            $table->integer('quotation_no')->nullable()->after('id');
            $table->integer('revision_no')->default(0)->after('quotation_no');
            $table->string('custom_quotation_id')->nullable()->after('revision_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn(['quotation_no', 'revision_no', 'custom_quotation_id']);
        });
    }
};
