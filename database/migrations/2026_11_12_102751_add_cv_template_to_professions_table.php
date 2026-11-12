<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CV template of the profession (config/cv_templates.php): which skills
     * and extra fields its workers' CV has.
     */
    public function up(): void
    {
        Schema::table('professions', function (Blueprint $table) {
            $table->string('cv_template', 30)->default('housemaid')->after('name_en');
        });
    }

    public function down(): void
    {
        Schema::table('professions', function (Blueprint $table) {
            $table->dropColumn('cv_template');
        });
    }
};
