<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Configurable document checklist. A null country / profession means the
     * type applies to all countries / professions.
     */
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('profession_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('authority_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('name_ar');
            $table->string('name_en');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_required')->default(false);
            $table->boolean('include_in_authority_file')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['country_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
