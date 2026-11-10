<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Minimal candidates table: enough for the agency portal scoping.
     * The full recruitment module will extend it.
     */
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->restrictOnDelete();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('nationality', 2)->nullable();
            $table->string('passport_number', 30)->nullable();
            $table->string('status', 20)->default('new')->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
