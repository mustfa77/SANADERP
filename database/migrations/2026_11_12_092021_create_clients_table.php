<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Clients (sponsors / renters). national_id is encrypted;
     * national_id_lookup holds its HMAC for uniqueness and exact search.
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->text('national_id');
            $table->string('national_id_lookup', 64)->unique();
            $table->string('phone', 20)->index();
            $table->string('phone2', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('city')->nullable()->index();
            $table->string('district')->nullable();
            $table->string('national_address', 500)->nullable();
            $table->foreignId('branch_id')->index()->constrained()->restrictOnDelete();
            $table->unsignedInteger('visa_balance')->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('user_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
