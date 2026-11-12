<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per status change (from_status is null for the initial status).
     * related_* points at the record that caused it (contract, case...).
     */
    public function up(): void
    {
        Schema::create('worker_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->index()->constrained()->cascadeOnDelete();
            $table->string('from_status', 30)->nullable()->index();
            $table->string('to_status', 30)->index();
            $table->string('reason', 1000)->nullable();
            $table->nullableMorphs('related');
            $table->foreignId('changed_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_status_history');
    }
};
