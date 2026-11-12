<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Daily roll-call of a housing: one row per worker and day.
     */
    public function up(): void
    {
        Schema::create('camp_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('camp_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('worker_id')->index()->constrained()->cascadeOnDelete();
            $table->date('date')->index();
            $table->string('status', 10);
            $table->string('note', 500)->nullable();
            $table->foreignId('recorded_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['worker_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camp_attendances');
    }
};
