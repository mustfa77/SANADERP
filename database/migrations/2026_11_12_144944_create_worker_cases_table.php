<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A worker waiting for a decision (returned by a client, absconding
     * report...). Opened automatically when the worker becomes
     * "awaiting_decision"; the decision is a sponsorship transfer, a
     * deportation or back to work. Managed by WorkerCaseService.
     */
    public function up(): void
    {
        Schema::create('worker_cases', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->foreignId('branch_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('worker_id')->index()->constrained()->restrictOnDelete();
            $table->nullableMorphs('related');
            $table->string('status', 20)->default('open')->index();
            $table->string('reason', 1000)->nullable();
            $table->timestamp('opened_at');
            $table->foreignId('opened_by')->nullable()->index()->constrained('users')->nullOnDelete();

            // Decision (cases.decide)
            $table->string('decision', 20)->nullable()->index();
            $table->string('decision_note', 1000)->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->foreignId('decided_by')->nullable()->index()->constrained('users')->nullOnDelete();

            // Transfer: the new sponsor (an existing client, or name + ID).
            $table->foreignId('new_sponsor_client_id')->nullable()->index()->constrained('clients')->nullOnDelete();
            $table->string('new_sponsor_name')->nullable();
            $table->string('new_sponsor_id_number', 20)->nullable();
            $table->decimal('transfer_fee', 10, 2)->nullable();

            // Deportation
            $table->date('flight_date')->nullable();
            $table->decimal('ticket_cost', 10, 2)->nullable();

            $table->date('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('worker_case_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_case_id')->index()->constrained()->cascadeOnDelete();
            $table->string('note', 2000);
            $table->foreignId('created_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_case_notes');
        Schema::dropIfExists('worker_cases');
    }
};
