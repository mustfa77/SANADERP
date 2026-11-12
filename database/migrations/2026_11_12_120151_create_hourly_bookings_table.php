<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hourly booking: one worker, one date, one shift. starts_at / ends_at
     * are copied from the shift so a worker is never booked twice at
     * overlapping times (checked by HourlyBookingService).
     */
    public function up(): void
    {
        Schema::create('hourly_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->foreignId('branch_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('client_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('worker_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('shift_id')->index()->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('booked')->index();

            $table->date('date')->index();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->decimal('hours', 5, 2);

            $table->decimal('hourly_price', 10, 2);
            $table->decimal('price', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('vat_percent', 5, 2)->default(15);
            $table->decimal('vat_amount', 10, 2);
            $table->decimal('total', 10, 2);

            $table->string('address', 500)->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancel_reason_id')->nullable()->index()->constrained('lookups')->restrictOnDelete();
            $table->string('cancel_note', 500)->nullable();

            $table->foreignId('created_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['worker_id', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hourly_bookings');
    }
};
