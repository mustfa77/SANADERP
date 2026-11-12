<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_salary_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->index()->constrained()->cascadeOnDelete();
            $table->decimal('old_salary', 10, 2);
            $table->decimal('new_salary', 10, 2);
            $table->string('reason', 500);
            $table->foreignId('changed_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_salary_changes');
    }
};
