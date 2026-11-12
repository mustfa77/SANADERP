<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Document types now apply to workers (default, existing types) or clients.
     */
    public function up(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->string('applies_to', 10)->default('worker')->after('id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->dropIndex(['applies_to']);
            $table->dropColumn('applies_to');
        });
    }
};
