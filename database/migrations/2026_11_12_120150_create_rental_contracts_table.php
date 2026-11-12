<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Monthly rental contract: one month, renewed by a new contract for the
     * next month (renewed_from_id). Changed only by RentalContractService,
     * which also moves the worker's status (available <-> rented_monthly).
     */
    public function up(): void
    {
        Schema::create('rental_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->foreignId('branch_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('client_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('worker_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('renewed_from_id')->nullable()->unique()->constrained('rental_contracts')->restrictOnDelete();
            $table->string('status', 20)->default('active')->index();

            $table->date('starts_at')->index();
            $table->date('ends_at')->index();

            // Amounts in SAR: price before VAT, discount, then VAT on the rest.
            $table->decimal('price', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('vat_percent', 5, 2)->default(15);
            $table->decimal('vat_amount', 10, 2);
            $table->decimal('total', 10, 2);

            $table->date('closed_at')->nullable();
            $table->foreignId('close_reason_id')->nullable()->index()->constrained('lookups')->restrictOnDelete();
            $table->string('close_note', 1000)->nullable();
            $table->foreignId('return_camp_id')->nullable()->index()->constrained('camps')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_contracts');
    }
};
