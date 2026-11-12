<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A client's recruitment request, from candidate selection to delivery.
     * Status changes go through App\Services\Recruitment\RecruitmentService,
     * which also moves the worker's status.
     */
    public function up(): void
    {
        Schema::create('recruitment_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->foreignId('branch_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('client_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('country_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('profession_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('worker_id')->nullable()->index()->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('new')->index();

            // Recorded at contract time; a client may have any number of orders / visas.
            $table->string('visa_number', 30)->nullable()->unique();
            $table->date('visa_issued_at')->nullable();
            $table->string('musaned_contract_no', 50)->nullable()->index();
            $table->decimal('contract_amount', 10, 2)->nullable();
            $table->date('contracted_at')->nullable()->index();
            $table->date('travel_started_at')->nullable();
            $table->date('arrived_at')->nullable()->index();
            $table->date('delivered_at')->nullable();

            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancel_reason_id')->nullable()->index()->constrained('lookups')->restrictOnDelete();
            $table->string('cancel_note', 500)->nullable();

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_orders');
    }
};
