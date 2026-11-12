<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every change of a worker's housing (workers.camp_id): check-in, check-out
     * or transfer, recorded automatically by the Worker model.
     */
    public function up(): void
    {
        Schema::create('camp_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('worker_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('from_camp_id')->nullable()->index()->constrained('camps')->nullOnDelete();
            $table->foreignId('to_camp_id')->nullable()->index()->constrained('camps')->nullOnDelete();
            $table->string('type', 10)->index();
            $table->dateTime('moved_at')->index();
            $table->string('note', 500)->nullable();
            $table->foreignId('created_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camp_movements');
    }
};
