<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * In-system alerts, recalculated by "alerts:refresh" (hourly) from the
     * data and the alert periods in Settings. An alert closes by itself when
     * its condition is gone, or when a user marks it as handled.
     */
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->index()->constrained()->cascadeOnDelete();
            $table->string('type', 30)->index();
            $table->morphs('subject');
            $table->string('key', 150)->unique();
            $table->string('level', 10)->default('warning');
            $table->date('due_at')->nullable()->index();
            $table->json('params')->nullable();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->foreignId('resolved_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
