<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Travel stages of an order, copied from the country's stages when the
     * travel process starts (later edits of the settings do not change an
     * order in progress). due_at = started_at + max_days.
     */
    public function up(): void
    {
        Schema::create('recruitment_order_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruitment_order_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('country_travel_stage_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->string('name_ar');
            $table->string('name_en');
            $table->unsignedInteger('sort_order');
            $table->unsignedSmallInteger('max_days')->nullable();
            $table->date('started_at')->nullable();
            $table->date('due_at')->nullable()->index();
            $table->date('completed_at')->nullable()->index();
            $table->string('notes', 500)->nullable();
            $table->foreignId('completed_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_order_stages');
    }
};
