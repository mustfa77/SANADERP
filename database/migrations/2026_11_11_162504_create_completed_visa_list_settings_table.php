<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * How many of the latest completed visas to attach to the printed
     * authority file, per authority and country.
     */
    public function up(): void
    {
        Schema::create('completed_visa_list_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('authority_id')->constrained()->cascadeOnDelete();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('visas_count')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['authority_id', 'country_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('completed_visa_list_settings');
    }
};
