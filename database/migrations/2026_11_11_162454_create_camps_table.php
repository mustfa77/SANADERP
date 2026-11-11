<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Worker housing. Branch-owned (BranchScope).
     */
    public function up(): void
    {
        Schema::create('camps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('district_ar')->nullable();
            $table->string('district_en')->nullable();
            $table->string('address_ar', 500)->nullable();
            $table->string('address_en', 500)->nullable();
            $table->unsignedInteger('capacity')->default(0);
            $table->string('type', 20)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camps');
    }
};
