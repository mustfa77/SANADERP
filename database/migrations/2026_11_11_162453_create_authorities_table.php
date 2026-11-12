<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * External authorities, e.g. the Philippine labor office in Riyadh.
     */
    public function up(): void
    {
        Schema::create('authorities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('city_ar')->nullable();
            $table->string('city_en')->nullable();
            $table->string('address_ar', 500)->nullable();
            $table->string('address_en', 500)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authorities');
    }
};
