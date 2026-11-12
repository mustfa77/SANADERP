<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Transport: trips get their purpose, worker, client, places and the
     * driver's progress; drivers (users of type "driver") get a profile with
     * the vehicle.
     */
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->string('number', 20)->nullable()->unique()->after('id');
            $table->string('type', 20)->default('manual')->index()->after('number');
            $table->foreignId('worker_id')->nullable()->index()->after('driver_id')->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->index()->after('worker_id')->constrained()->nullOnDelete();
            $table->nullableMorphs('related');
            $table->string('from_place', 500)->nullable();
            $table->string('to_place', 500)->nullable();
            $table->string('notes', 1000)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_note', 500)->nullable();
            $table->foreignId('created_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->index(['branch_id', 'scheduled_at']);
        });

        Schema::create('driver_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->index()->constrained()->restrictOnDelete();
            $table->string('license_number', 30)->nullable();
            $table->date('license_expiry')->nullable();
            $table->string('vehicle_model', 100)->nullable();
            $table->string('vehicle_plate', 20)->nullable();
            $table->unsignedTinyInteger('seats')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_profiles');

        Schema::table('trips', function (Blueprint $table) {
            $table->dropForeign(['worker_id']);
            $table->dropForeign(['client_id']);
            $table->dropForeign(['created_by']);
            $table->dropIndex(['branch_id', 'scheduled_at']);
            $table->dropIndex(['type']);
            $table->dropIndex(['worker_id']);
            $table->dropIndex(['client_id']);
            $table->dropIndex(['created_by']);
            $table->dropUnique(['number']);
            $table->dropMorphs('related');
        });

        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn(['number', 'type', 'worker_id', 'client_id', 'from_place', 'to_place', 'notes', 'started_at', 'completed_at', 'cancelled_at', 'cancel_note', 'created_by']);
        });
    }
};
