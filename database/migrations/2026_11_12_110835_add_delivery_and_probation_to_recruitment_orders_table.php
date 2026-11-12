<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Arrival housing, delivery to the client (method, receiver, fees) and
     * the probation period after delivery.
     */
    public function up(): void
    {
        Schema::table('recruitment_orders', function (Blueprint $table) {
            $table->foreignId('arrival_camp_id')->nullable()->after('arrived_at')->index()->constrained('camps')->nullOnDelete();

            $table->string('delivery_method', 10)->nullable()->after('delivered_at');
            $table->string('receiver_name')->nullable()->after('delivery_method');
            $table->string('receiver_phone', 20)->nullable()->after('receiver_name');
            $table->unsignedSmallInteger('camp_days')->nullable()->after('receiver_phone');
            $table->decimal('camp_fee', 10, 2)->nullable()->after('camp_days');
            $table->decimal('delivery_fee', 10, 2)->nullable()->after('camp_fee');
            $table->string('delivery_notes', 1000)->nullable()->after('delivery_fee');
            $table->foreignId('delivered_by')->nullable()->after('delivery_notes')->index()->constrained('users')->nullOnDelete();

            $table->date('probation_ends_at')->nullable()->after('delivered_by')->index();
            $table->date('completed_at')->nullable()->after('probation_ends_at');
            $table->date('returned_at')->nullable()->after('completed_at');
            $table->foreignId('return_reason_id')->nullable()->after('returned_at')->index()->constrained('lookups')->restrictOnDelete();
            $table->string('return_note', 1000)->nullable()->after('return_reason_id');
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_orders', function (Blueprint $table) {
            $table->dropForeign(['arrival_camp_id']);
            $table->dropForeign(['delivered_by']);
            $table->dropForeign(['return_reason_id']);
            $table->dropIndex(['arrival_camp_id']);
            $table->dropIndex(['delivered_by']);
            $table->dropIndex(['return_reason_id']);
            $table->dropIndex(['probation_ends_at']);
        });

        Schema::table('recruitment_orders', function (Blueprint $table) {
            $table->dropColumn([
                'arrival_camp_id', 'delivery_method', 'receiver_name', 'receiver_phone', 'camp_days', 'camp_fee',
                'delivery_fee', 'delivery_notes', 'delivered_by', 'probation_ends_at', 'completed_at', 'returned_at',
                'return_reason_id', 'return_note',
            ]);
        });
    }
};
