<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Refund requested for a rental contract / hourly booking (early return,
     * cancellation), approved or rejected by finance (refunds.approve).
     */
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->index()->constrained()->restrictOnDelete();
            $table->morphs('payable');
            $table->decimal('amount', 10, 2);
            $table->string('status', 20)->default('pending')->index();
            $table->string('reason', 1000)->nullable();
            $table->foreignId('requested_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->foreignId('decided_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->string('decision_note', 1000)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
