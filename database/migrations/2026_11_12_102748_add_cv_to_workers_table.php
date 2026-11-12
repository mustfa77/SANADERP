<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Candidate CV (filled by the external agency or staff). Skills and the
     * template's extra fields (e.g. driving licence) are stored as JSON; the
     * template comes from the worker's profession (professions.cv_template).
     */
    public function up(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->string('cv_reference_no', 30)->nullable()->after('musaned_cv_no')->index();
            $table->string('cv_category', 20)->nullable()->after('cv_reference_no');
            $table->string('home_city', 100)->nullable()->after('cv_category');
            $table->string('education', 100)->nullable()->after('home_city');
            $table->unsignedTinyInteger('children_count')->nullable()->after('education');
            $table->unsignedSmallInteger('height_cm')->nullable()->after('children_count');
            $table->unsignedSmallInteger('weight_kg')->nullable()->after('height_cm');
            $table->string('arabic_level', 10)->nullable()->after('weight_kg');
            $table->string('english_level', 10)->nullable()->after('arabic_level');
            $table->string('full_photo_path')->nullable()->after('photo_path');
            $table->json('cv_skills')->nullable()->after('skills');
            $table->json('cv_details')->nullable()->after('cv_skills');
            $table->timestamp('cv_updated_at')->nullable();
            $table->foreignId('cv_updated_by')->nullable()->index()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            // Indexes first: SQLite cannot drop an indexed column.
            $table->dropForeign(['cv_updated_by']);
            $table->dropIndex(['cv_updated_by']);
            $table->dropIndex(['cv_reference_no']);
        });

        Schema::table('workers', function (Blueprint $table) {
            $table->dropColumn([
                'cv_reference_no', 'cv_category', 'home_city', 'education', 'children_count', 'height_cm', 'weight_kg',
                'arabic_level', 'english_level', 'full_photo_path', 'cv_skills', 'cv_details', 'cv_updated_at', 'cv_updated_by',
            ]);
        });
    }
};
