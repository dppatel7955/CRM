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
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('organization_name');
            $table->string('contact_person_name')->nullable();
            $table->string('phone')->unique()->nullable();
            $table->string('email')->unique()->nullable();
            $table->text('address')->nullable();
            $table->string('gst_number')->nullable();
            $table->boolean('is_dealer')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
