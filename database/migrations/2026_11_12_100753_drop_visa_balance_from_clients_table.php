<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * There is no visa balance: the visa number is recorded on each
     * recruitment order, and a client may request any number of visas.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('visa_balance');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->unsignedInteger('visa_balance')->default(0)->after('branch_id');
        });
    }
};
