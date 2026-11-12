<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * final_total: the amount finally due after an early return or a
     * cancellation (null = the full total). Hourly bookings also get the
     * actual departure / return times.
     */
    public function up(): void
    {
        Schema::table('rental_contracts', function (Blueprint $table) {
            $table->decimal('final_total', 10, 2)->nullable()->after('total');
        });

        Schema::table('hourly_bookings', function (Blueprint $table) {
            $table->decimal('final_total', 10, 2)->nullable()->after('total');
            $table->dateTime('departed_at')->nullable()->after('ends_at');
            $table->dateTime('returned_at')->nullable()->after('departed_at');
        });
    }

    public function down(): void
    {
        Schema::table('rental_contracts', function (Blueprint $table) {
            $table->dropColumn('final_total');
        });

        Schema::table('hourly_bookings', function (Blueprint $table) {
            $table->dropColumn(['final_total', 'departed_at', 'returned_at']);
        });
    }
};
