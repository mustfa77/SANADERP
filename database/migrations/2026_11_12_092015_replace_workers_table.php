<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replaces the placeholder workers table (plain passport number, sample
     * data only) with the full worker file. Passport and iqama numbers are
     * encrypted; *_lookup holds their HMAC for uniqueness and exact search.
     */
    public function up(): void
    {
        Schema::dropIfExists('workers');

        Schema::create('workers', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('name_ar')->nullable();
            $table->string('name_en');
            $table->string('gender', 10)->index();
            $table->date('birth_date')->nullable();
            $table->string('religion', 20)->nullable()->index();
            $table->string('marital_status', 20)->nullable();
            $table->string('photo_path')->nullable();

            // Origin
            $table->foreignId('country_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('profession_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('agency_id')->nullable()->index()->constrained()->restrictOnDelete();

            // Location
            $table->foreignId('branch_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('camp_id')->nullable()->index()->constrained()->nullOnDelete();

            // Passport
            $table->text('passport_no');
            $table->string('passport_lookup', 64)->unique();
            $table->string('passport_type', 20)->default('ordinary');
            $table->date('passport_expiry')->nullable()->index();

            // Residency and medical
            $table->text('iqama_no')->nullable();
            $table->string('iqama_lookup', 64)->nullable()->unique();
            $table->date('iqama_expiry')->nullable()->index();
            $table->date('medical_expiry')->nullable()->index();

            // Work
            $table->json('languages')->nullable();
            $table->unsignedTinyInteger('experience_years')->default(0);
            $table->json('skills')->nullable();
            $table->decimal('salary', 10, 2)->default(0);

            // Type and reference
            $table->string('sponsor_type', 10)->index();
            $table->string('rental_type', 10)->default('none')->index();
            $table->string('musaned_cv_no', 50)->nullable()->index();

            // Status (changed only by WorkerStatusService)
            $table->string('status', 30)->default('candidate_new')->index();
            $table->timestamp('status_changed_at')->nullable();

            $table->foreignId('created_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workers');

        Schema::create('workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('agency_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('nationality', 2)->nullable();
            $table->string('passport_number', 30)->nullable()->unique();
            $table->string('iqama_number', 20)->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
