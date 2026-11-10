<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extend the default users table with the ERP fields. The single "name"
     * column is replaced by the bilingual name_ar / name_en pair.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('id');
            $table->string('name_en')->nullable()->after('name_ar');
            $table->string('phone', 20)->nullable()->unique()->after('email');
            $table->string('status', 20)->default('active')->index()->after('password');
            $table->string('user_type', 20)->default('internal')->index()->after('status');
            $table->foreignId('agency_id')->nullable()->after('user_type')->constrained('agencies')->nullOnDelete();
            $table->string('preferred_language', 2)->default('ar')->after('agency_id');
            $table->string('avatar')->nullable()->after('preferred_language');
            $table->timestamp('last_login_at')->nullable()->after('avatar');
            $table->boolean('must_change_password')->default(false)->after('last_login_at');
            $table->boolean('two_factor_enabled')->default(false)->after('must_change_password');
            $table->string('two_factor_code')->nullable()->after('two_factor_enabled');
            $table->timestamp('two_factor_expires_at')->nullable()->after('two_factor_code');
            $table->foreignId('created_by')->nullable()->after('remember_token')->constrained('users')->nullOnDelete();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->default('')->after('id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('agency_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropUnique(['phone']);
            $table->dropIndex(['status']);
            $table->dropIndex(['user_type']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'name_ar', 'name_en', 'phone', 'status', 'user_type', 'preferred_language', 'avatar',
                'last_login_at', 'must_change_password', 'two_factor_enabled', 'two_factor_code',
                'two_factor_expires_at',
            ]);
        });
    }
};
