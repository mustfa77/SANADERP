<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Workers of a monthly contract over time (a replacement adds a row):
     * when each one left the office to the client and came back.
     */
    public function up(): void
    {
        Schema::create('rental_contract_workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_contract_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('worker_id')->index()->constrained()->restrictOnDelete();
            $table->dateTime('departed_at');
            $table->dateTime('returned_at')->nullable();
            $table->foreignId('return_reason_id')->nullable()->index()->constrained('lookups')->restrictOnDelete();
            $table->string('return_note', 1000)->nullable();
            $table->foreignId('return_camp_id')->nullable()->index()->constrained('camps')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Existing contracts: one row for their worker (left at 08:00 on the start date).
        foreach (DB::table('rental_contracts')->orderBy('id')->get() as $contract) {
            DB::table('rental_contract_workers')->insert([
                'rental_contract_id' => $contract->id,
                'worker_id' => $contract->worker_id,
                'departed_at' => substr((string) $contract->starts_at, 0, 10).' 08:00:00',
                'returned_at' => $contract->closed_at ? substr((string) $contract->closed_at, 0, 10).' 12:00:00' : null,
                'return_reason_id' => $contract->close_reason_id,
                'return_note' => $contract->close_note,
                'return_camp_id' => $contract->return_camp_id,
                'created_by' => $contract->created_by,
                'created_at' => $contract->created_at,
                'updated_at' => $contract->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_contract_workers');
    }
};
