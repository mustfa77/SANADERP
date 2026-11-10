<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('city_ar')->nullable()->after('name_en');
            $table->string('city_en')->nullable()->after('city_ar');
            $table->string('address_ar', 500)->nullable()->after('city_en');
            $table->string('address_en', 500)->nullable()->after('address_ar');
            $table->string('phone', 20)->nullable()->after('address_en');
            $table->string('email')->nullable()->after('phone');
            $table->foreignId('manager_id')->nullable()->after('email')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manager_id');
            $table->dropColumn(['city_ar', 'city_en', 'address_ar', 'address_en', 'phone', 'email']);
        });
    }
};
