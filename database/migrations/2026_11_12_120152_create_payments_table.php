<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Payment received for a rental contract or an hourly booking, with the
     * transfer receipt (private disk). Verified by accounts (payments.verify).
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->index()->constrained()->restrictOnDelete();
            $table->morphs('payable');
            $table->decimal('amount', 10, 2);
            $table->date('paid_at')->index();
            $table->foreignId('payment_method_id')->index()->constrained('lookups')->restrictOnDelete();
            $table->string('reference', 100)->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('receipt_name')->nullable();
            $table->string('notes', 1000)->nullable();

            $table->foreignId('received_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->index();
            $table->foreignId('verified_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
