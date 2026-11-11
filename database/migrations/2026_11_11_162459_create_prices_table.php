<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rental prices per country and rental type. Only one active price per
     * (country, rental_type) is allowed; enforced in validation so old
     * inactive prices are kept as history.
     */
    public function up(): void
    {
        Schema::create('prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->string('rental_type', 20);
            $table->decimal('price', 10, 2);
            $table->decimal('vat_percent', 5, 2)->default(15);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['country_id', 'rental_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prices');
    }
};
